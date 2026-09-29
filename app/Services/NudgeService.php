<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyTransaction;
use App\Models\IncomeSource;
use Illuminate\Support\Carbon;

/**
 * Deterministic, zero-cost nudges fired the moment a transaction is saved.
 *
 * This is the "no button" half of the insight feature: the AI runs on a weekly
 * schedule (expensive), while these rules react instantly to what the user just
 * entered (free). Returns null when there is genuinely nothing worth saying, so
 * the app can stay quiet instead of nagging.
 */
final class NudgeService
{
    private const TONE_RANK = [
        'critical' => 4,
        'warning' => 3,
        'neutral' => 2,
        'positive' => 1,
    ];

    /**
     * @return array{tone: string, message: string, code: string}|null
     */
    public function evaluate(Family $family, ?FamilyTransaction $justSaved = null): ?array
    {
        $candidates = array_filter([
            $this->categoryBudgetRule($family, $justSaved),
            $this->projectionRule($family),
            $this->noIncomeRule($family),
            $this->overdueDebtRule($family),
            $this->savingsRateRule($family),
        ]);

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b): int => self::TONE_RANK[$b['tone']] <=> self::TONE_RANK[$a['tone']]);

        return $candidates[0];
    }

    /**
     * The motivating case: give a calm, practical suggestion without judging
     * the household's choices.
     * Checks the category that was just touched first, since that is the one the
     * user is looking at on screen.
     */
    private function categoryBudgetRule(Family $family, ?FamilyTransaction $justSaved): ?array
    {
        $now = now();
        $expenseCategories = $justSaved?->type === 'expense'
            ? collect([$justSaved->category_id])->filter()
            : $family->transactions()
                ->where('type', 'expense')
                ->whereNotNull('category_id')
                ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
                ->distinct()
                ->pluck('category_id');

        $budgets = $family->budgets()
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->whereIn('category_id', $expenseCategories->all())
            ->with('category')
            ->get();

        $worst = null;

        foreach ($budgets as $budget) {
            $limit = (float) $budget->amount;
            if ($limit <= 0) {
                continue;
            }

            $spent = (float) $family->transactions()
                ->where('type', 'expense')
                ->where('category_id', $budget->category_id)
                ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
                ->sum('amount');

            $ratio = $spent / $limit;
            if ($ratio < 0.8) {
                continue;
            }

            $daysLeft = max(1, $now->daysInMonth - $now->day);
            $headroom = max(0, $limit - $spent);
            $perDay = $headroom / $daysLeft;
            $name = $budget->category?->name ?? 'kategori ini';

            $candidate = $ratio >= 1.0
                ? [
                    'tone' => 'critical',
                    'code' => 'budget_exceeded',
                    'message' => sprintf(
                        '%s sudah melewati batas anggaran bulan ini (%s dari %s). Masih ada %d hari; mungkin ini waktu yang baik untuk meninjau pilihan berikutnya.',
                        $name,
                        $this->rp($spent),
                        $this->rp($limit),
                        $daysLeft
                    ),
                ]
                : [
                    'tone' => 'warning',
                    'code' => 'budget_near_limit',
                    'message' => sprintf(
                        '%s sudah memakai sekitar %d%% anggaran (%s dari %s). Masih ada %d hari; rata-rata %s/hari bisa membantu menjaga ruang sampai akhir bulan.',
                        $name,
                        (int) round($ratio * 100),
                        $this->rp($spent),
                        $this->rp($limit),
                        $daysLeft,
                        $this->rp($perDay)
                    ),
                ];

            if ($worst === null || self::TONE_RANK[$candidate['tone']] > self::TONE_RANK[$worst['tone']]) {
                $worst = $candidate;
            }
        }

        return $worst;
    }

    /**
     * "Pikirkan saldoku yang masih imut": projects month-end spend from the
     * run rate so far and compares it against income.
     */
    private function projectionRule(Family $family): ?array
    {
        $now = now();
        $daysInMonth = $now->daysInMonth;
        $dayOfMonth = $now->day;

        $income = (float) $family->transactions()
            ->where('type', 'income')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
            ->sum('amount');

        if ($income <= 0 || $dayOfMonth < 3) {
            return null;
        }

        $expense = (float) $family->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
            ->sum('amount');

        $projected = $expense / $dayOfMonth * $daysInMonth;
        $balance = $income - $projected;

        if ($balance > 0) {
            return null;
        }

        $daysLeft = max(1, $daysInMonth - $dayOfMonth);

        return [
            'tone' => 'warning',
            'code' => 'projected_deficit',
            'message' => sprintf(
                'Perkiraan akhir bulan: pengeluaran %s dan pemasukan %s. Masih ada %d hari; yuk pertimbangkan prioritas pengeluaran berikutnya bersama.',
                $this->rp($projected),
                $this->rp($income),
                $daysLeft
            ),
        ];
    }

    private function noIncomeRule(Family $family): ?array
    {
        $now = now();

        // Salary usually lands early; nagging on day 1-4 is just noise.
        if ($now->day < 5) {
            return null;
        }

        $hasIncome = $family->transactions()
            ->where('type', 'income')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
            ->exists();

        if ($hasIncome) {
            return null;
        }

        return [
            'tone' => 'neutral',
            'code' => 'no_income_logged',
            'message' => 'Belum ada pemasukan tercatat bulan ini. Kalau sudah masuk, mencatatnya akan membantu ringkasan keluarga lebih akurat.',
        ];
    }

    private function overdueDebtRule(Family $family): ?array
    {
        $overdue = FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')
            ->where('status', '!=', 'settled')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->first();

        if ($overdue === null) {
            return null;
        }

        $days = (int) now()->startOfDay()->diffInDays($overdue->due_date->startOfDay());

        return [
            'tone' => 'critical',
            'code' => 'debt_overdue',
            'message' => sprintf(
                'Catatan menunjukkan "%s" melewati jatuh tempo %d hari. Sisa tagihan %s; mungkin bisa dibicarakan jadwal berikutnya bersama.',
                $overdue->name,
                $days,
                $this->rp($overdue->remaining)
            ),
        ];
    }

    private function savingsRateRule(Family $family): ?array
    {
        $now = now();

        $income = (float) $family->transactions()->where('type', 'income')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])->sum('amount');
        $expense = (float) $family->transactions()->where('type', 'expense')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])->sum('amount');

        if ($income <= 0) {
            return null;
        }

        $rate = ($income - $expense) / $income * 100;

        if ($rate >= 10) {
            return null;
        }

        if ($rate >= 0) {
            return [
                'tone' => 'neutral',
                'code' => 'thin_savings',
                'message' => sprintf(
                    'Tabungan bulan ini sekitar %d%% dari pemasukan. Jika sesuai kondisi keluarga, menyisihkan %s sebelum pengeluaran berikutnya bisa menjadi pilihan.',
                    (int) round($rate),
                    $this->rp($income * 0.1)
                ),
            ];
        }

        return [
            'tone' => 'warning',
            'code' => 'negative_savings',
            'message' => sprintf(
                'Ringkasan bulan ini menunjukkan pengeluaran %s lebih besar dari pemasukan. Yuk lihat bersama pengeluaran mana yang paling mudah disesuaikan.',
                $this->rp(abs($income - $expense))
            ),
        ];
    }

    /**
     * Month-to-date income grouped by stream, for the "gaji vs usaha" view.
     */
    public function incomeBySource(Family $family): array
    {
        $now = now();

        $bySource = $family->transactions()
            ->where('type', 'income')
            ->whereNotNull('income_source_id')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
            ->get(['income_source_id', 'amount'])
            ->groupBy('income_source_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $unlabelled = (float) $family->transactions()
            ->where('type', 'income')
            ->whereNull('income_source_id')
            ->whereBetween('date', [$now->copy()->startOfMonth(), $now])
            ->sum('amount');

        $names = IncomeSource::whereIn('id', $bySource->keys())->pluck('name', 'id');

        $rows = $bySource->map(function (float $total, int|string $id) use ($names): array {
            $sourceId = (int) $id;

            return [
                'income_source_id' => $sourceId,
                'name' => $names[$sourceId] ?? 'Sumber #'.$sourceId,
                'total' => $total,
            ];
        })->values()->all();

        if ($unlabelled > 0) {
            $rows[] = [
                'income_source_id' => null,
                'name' => 'Belum dikategorikan',
                'total' => $unlabelled,
            ];
        }

        return $rows;
    }

    private function rp(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    public function daysLeftThisMonth(): int
    {
        $now = Carbon::now();

        return max(0, $now->daysInMonth - $now->day);
    }
}
