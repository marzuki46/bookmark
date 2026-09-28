<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyInsight;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Notifications\FamilyInsightNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Weekly insight generator.
 *
 * Runs on a schedule (Monday 04:00) rather than per app-open, because each
 * family costs one AI call plus one per member. The output is persisted in
 * family_insights so the app can render it instantly on open, and so a failed
 * week can be retried without re-billing the provider.
 *
 * Husbands and wives get different messages: the family-health note is shared,
 * but each member's personal note is built from the transactions they actually
 * paid for.
 */
final class FamilyInsightService
{
    public function __construct(
        private readonly FamilyAIService $ai,
        private readonly NudgeService $nudges,
    ) {}

    public function weekKey(?\DateTimeInterface $for = null): string
    {
        return ($for ? Carbon::instance($for) : now())->format('o-\WW');
    }

    /**
     * @return Collection<int, FamilyInsight>
     */
    public function generateForFamily(Family $family, bool $force = false): Collection
    {
        $week = $this->weekKey();
        $metrics = $this->ai->healthScore($family);

        $saved = collect();

        // Shared family health, delivered to every member.
        [$insight, $familyChanged] = $this->persist(
            $family,
            $week,
            $this->familyMessage($family, $metrics),
            $this->familyTone($metrics),
            $metrics,
            $force
        );

        $saved->push($insight);

        // One personal note per member, from their own spending.
        foreach ($family->members()->with('user:id,name')->get() as $member) {
            if ($member->user === null) {
                continue;
            }

            $slice = $this->personalSlice($family, $member->user_id);

            [$personal] = $this->persist(
                $family,
                $week,
                $this->personalMessage($member->user->name, $slice),
                $slice['tone'],
                $metrics + ['personal' => $slice],
                $force,
                $member->user_id
            );

            $saved->push($personal);
        }

        // Only notify when this run actually changed the shared note. Without
        // this guard a retrying scheduler would re-push the same message to
        // the whole household every time the command ran.
        if (! $force && $familyChanged) {
            $this->notifyMembers($family, $insight);
        }

        return $saved;
    }

    /**
     * Upsert keyed on (family, user, week, scope).
     *
     * The unique index cannot dedupe the family-scope rows because their
     * user_id is NULL and MySQL treats NULLs as distinct, so the family scope
     * is matched with an explicit whereNull.
     *
     * @return array{0: FamilyInsight, 1: bool} the insight and whether it changed
     */
    private function persist(
        Family $family,
        string $week,
        array $payload,
        string $tone,
        array $metrics,
        bool $force,
        ?int $userId = null
    ): array {
        $message = FamilyInsight::clamp($payload['message']);
        $isFallback = $payload['is_fallback'] ?? false;

        $query = FamilyInsight::query()
            ->where('family_id', $family->id)
            ->where('week_key', $week)
            ->where('scope', $userId === null ? 'family' : 'user');

        $userId === null
            ? $query->whereNull('user_id')
            : $query->where('user_id', $userId);

        $existing = $query->first();

        // Without --force an already-generated week is left untouched, so a
        // second cron run cannot re-bill the AI or spam duplicate pushes.
        if ($existing && ! $force) {
            return [$existing, false];
        }

        $attributes = [
            'message' => $message,
            'tone' => $tone,
            'metrics' => $metrics,
            'is_fallback' => $isFallback,
            'read_at' => null,
        ];

        if ($existing) {
            $changed = $existing->only(array_keys($attributes)) != $attributes;

            if ($changed) {
                $existing->update($attributes);
            }

            return [$existing->refresh(), $changed];
        }

        return [FamilyInsight::create($attributes + [
            'family_id' => $family->id,
            'user_id' => $userId,
            'scope' => $userId === null ? 'family' : 'user',
            'week_key' => $week,
        ]), true];
    }

    private function notifyMembers(Family $family, ?FamilyInsight $insight): void
    {
        if ($insight === null) {
            return;
        }

        $users = $family->members()->with('user')->get()->pluck('user')->filter();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new FamilyInsightNotification($insight));
    }

    /**
     * @return array{message: string, is_fallback: bool}
     */
    private function familyMessage(Family $family, array $metrics): array
    {
        $prompt = sprintf(
            "Data keuangan keluarga (bulan ini):\n"
            ."Skor kesehatan %d/100 (%s)\n"
            ."Pemasukan %s, pengeluaran %s\n"
            ."Dana darurat %s dari target %s\n"
            ."Total hutang tersisa %s\n\n"
            .'Tulis SATU pesan singkat (maksimal 180 karakter) untuk pasangan rumah tangga. '
            .'Gaya: ramah, tegas, seperti pasangan yang peduli. Sebut angka yang paling penting. '
            .'Jangan pakai bullet, jangan basa-basi, jangan sapa. Langsung ke inti.',
            $metrics['score'],
            $metrics['grade'],
            $this->rp($metrics['income']),
            $this->rp($metrics['expense']),
            $this->rp($metrics['emergency_current']),
            $this->rp($metrics['emergency_target']),
            $this->rp($metrics['total_debt'])
        );

        $ai = $this->ai->chat($prompt, 160);

        if ($ai !== null) {
            return ['message' => $this->firstLine($ai), 'is_fallback' => false];
        }

        return ['message' => $this->ruleFamilyMessage($metrics), 'is_fallback' => true];
    }

    /**
     * @return array{tone: string, message: string, is_fallback: bool, ...}
     */
    private function personalMessage(string $name, array $slice): array
    {
        $first = explode(' ', trim($name))[0];

        // The payer role changes the voice: the husband is usually the one
        // being carried by the family budget, and the wife usually the one
        // holding the small daily spending, so identical numbers deserve
        // different framing rather than the same sentence twice.
        $audience = match ($slice['payer_role'] ?? null) {
            'husband' => 'suami, penanggung utama keluarga',
            'wife' => 'istri, pengeluar belanja harian',
            default => 'anggota keluarga yang perannya belum ditentukan',
        };

        $prompt = sprintf(
            "Data keuangan %s bulan ini:\n"
            ."Peran: %s\n"
            ."Pengeluaran pribadi %s (terbesar: %s)\n"
            ."Pemasukan tercatat %s\n"
            ."Hutang aktif keluarga %s\n\n"
            .'Tulis SATU pesan singkat (maks 160 karakter) untuk %s. '
            .'Personal tapi tidak menyalahkan. Kalau kondisinya bagus, katakan juga. '
            .'Jangan bullet, jangan basa-basi.',
            $name,
            $audience,
            $this->rp($slice['expense']),
            $slice['top_category'] ?? 'belum ada',
            $this->rp($slice['income']),
            $this->rp($slice['family_debt']),
            $first
        );

        $ai = $this->ai->chat($prompt, 140);

        if ($ai !== null) {
            return $slice + ['message' => $this->firstLine($ai), 'is_fallback' => false];
        }

        return $slice + ['message' => $this->rulePersonalMessage($first, $slice), 'is_fallback' => true];
    }

    /**
     * Month-to-date figures for a single member, using the payer column so the
     * husband and wife see their own behaviour rather than a shared total.
     *
     * @return array{expense: float, income: float, family_debt: float, top_category: ?string, payer_role: ?string, tone: string, has_entries: bool}
     */
    private function personalSlice(Family $family, int $userId): array
    {
        $now = now();
        $period = [$now->copy()->startOfMonth(), $now];

        // Read through the membership rather than a second query so the slice
        // can tell whether this member is the husband or the wife.
        $payerRole = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('user_id', $userId)
            ->value('payer_role');

        $own = FamilyTransaction::forFamily($family->id)
            ->where('user_id', $userId)
            ->whereBetween('date', $period);

        $expense = (float) (clone $own)->where('type', 'expense')->sum('amount');
        $income = (float) (clone $own)->where('type', 'income')->sum('amount');

        $topCategory = FamilyTransaction::forFamily($family->id)
            ->where('user_id', $userId)
            ->where('type', 'expense')
            ->whereBetween('date', $period)
            ->with('category:id,name')
            ->get()
            ->sortByDesc('amount')
            ->first()
            ?->category
            ?->name;

        $familyDebt = (float) FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')
            ->where('status', '!=', 'settled')
            ->sum('amount');

        $tone = match (true) {
            $expense > 0 && $income > 0 && $expense > $income => 'warning',
            $expense > 0 => 'neutral',
            default => 'positive',
        };

        return [
            'expense' => $expense,
            'income' => $income,
            'family_debt' => $familyDebt,
            'top_category' => $topCategory,
            'payer_role' => $payerRole,
            'tone' => $tone,
            'has_entries' => $expense > 0 || $income > 0,
        ];
    }

    private function ruleFamilyMessage(array $metrics): string
    {
        $margin = (float) $metrics['income'] - (float) $metrics['expense'];

        if ($margin < 0) {
            return sprintf(
                'Bulan ini pengeluaran %s lebih besar dari pemasukan. Hentikan dulu yang bisa ditunda.',
                $this->rp(abs($margin))
            );
        }

        if ($metrics['total_debt'] > 0) {
            return sprintf(
                'Sisa hutang keluarga %s. Alokasikan bayar yang bunga paling tinggi dulu biar ringan.',
                $this->rp($metrics['total_debt'])
            );
        }

        if ($metrics['emergency_target'] > 0 && $metrics['emergency_current'] < $metrics['emergency_target']) {
            return sprintf(
                'Dana darurat baru %s dari target %s. Sisihkan tiap payslip biar tidak kepotong dadakan.',
                $this->rp($metrics['emergency_current']),
                $this->rp($metrics['emergency_target'])
            );
        }

        if ($margin > 0) {
            return sprintf(
                'Bagus, bulan ini surplus %s. Sisihkan dulu sebelum pengeluaran lain memakai uang itu.',
                $this->rp($margin)
            );
        }

        return 'Belum ada angka yang berarti bulan ini. Mulai catat pengeluaran harian biar polanya kelihatan.';
    }

    private function rulePersonalMessage(string $first, array $slice): string
    {
        if (! $slice['has_entries']) {
            return $first.', belum ada catatan darimu bulan ini. Sekali dua kali gapapa, tapi rutin biar enak ditata.';
        }

        if ($slice['expense'] > $slice['income'] && $slice['income'] > 0) {
            return sprintf(
                '%s, pengeluaranmu %s lebih besar dari pemasukanmu. Coba pangkas yang non-esensial dulu ya.',
                $first,
                $this->rp($slice['expense'] - $slice['income'])
            );
        }

        if ($slice['expense'] > 0) {
            return sprintf(
                '%s, pengeluaranmu %s, paling banyak di %s. Sisa bulan ini masih aman, yuk dijaga.',
                $first,
                $this->rp($slice['expense']),
                $slice['top_category'] ?? 'beberapa kategori'
            );
        }

        return $first.', nice catat pemasukanmu bulan ini. Lanjut konsisten ya.';
    }

    private function familyTone(array $metrics): string
    {
        $score = (int) $metrics['score'];

        return match (true) {
            $score >= 80 => 'positive',
            $score >= 60 => 'neutral',
            $score >= 40 => 'warning',
            default => 'critical',
        };
    }

    /**
     * The AI is asked for a single line, but models occasionally still wrap or
     * bullet it. Taking the first non-empty line keeps the notification clean.
     */
    private function firstLine(string $text): string
    {
        foreach (preg_split('/\r\n|\r|\n/', trim($text)) ?: [] as $line) {
            $line = ltrim(trim($line), "-•*— \t");

            if ($line !== '') {
                return $line;
            }
        }

        return trim($text);
    }

    private function rp(mixed $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
