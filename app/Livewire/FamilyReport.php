<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyTransaction;
use App\Services\FamilyAIService;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FamilyReport extends Component
{
    public string $period = 'this_year';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showAiModal = false;

    public string $aiQuery = '';

    public string $aiAnswer = '';

    public bool $aiLoading = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function updatedPeriod(): void
    {
        match ($this->period) {
            'today' => $this->dateFrom = $this->dateTo = now()->format('Y-m-d'),
            'this_week' => $this->dateFrom = now()->startOfWeek()->format('Y-m-d'),
            'this_month' => $this->dateFrom = now()->startOfMonth()->format('Y-m-d'),
            'last_month' => $this->dateFrom = now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'this_year' => $this->dateFrom = now()->startOfYear()->format('Y-m-d'),
            default => null,
        };

        if ($this->period !== 'custom') {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function getStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['income' => 0, 'expense' => 0, 'balance' => 0, 'count' => 0];
        }

        $query = FamilyTransaction::forFamily($family->id)->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        $income = (float) (clone $query)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('type', 'expense')->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'count' => (clone $query)->count(),
        ];
    }

    public function getMonthlyStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return [];
        }

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $income = (float) FamilyTransaction::forFamily($family->id)->where('type', 'income')
                ->whereMonth('date', $date->month)->whereYear('date', $date->year)->sum('amount');
            $expense = (float) FamilyTransaction::forFamily($family->id)->where('type', 'expense')
                ->whereMonth('date', $date->month)->whereYear('date', $date->year)->sum('amount');

            $months[] = [
                'month' => $date->format('M'),
                'income' => $income,
                'expense' => $expense,
            ];
        }

        return $months;
    }

    public function getCategoryStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['expense' => collect(), 'income' => collect()];
        }

        $query = FamilyTransaction::with('category')->forFamily($family->id)->whereBetween('date', [$this->dateFrom, $this->dateTo]);

        return [
            'expense' => (clone $query)->where('type', 'expense')
                ->selectRaw('category_id, SUM(amount) as total')->groupBy('category_id')->orderByDesc('total')->with('category')->get(),
            'income' => (clone $query)->where('type', 'income')
                ->selectRaw('category_id, SUM(amount) as total')->groupBy('category_id')->orderByDesc('total')->with('category')->get(),
        ];
    }

    public function getPayerStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return [];
        }

        $query = FamilyTransaction::forFamily($family->id)->whereBetween('date', [$this->dateFrom, $this->dateTo]);

        return [
            'husband' => (float) (clone $query)->where('type', 'expense')->where('payer', 'husband')->sum('amount'),
            'wife' => (float) (clone $query)->where('type', 'expense')->where('payer', 'wife')->sum('amount'),
            'shared' => (float) (clone $query)->where('type', 'expense')->where('payer', 'shared')->sum('amount'),
        ];
    }

    public function getHealthScoreProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['score' => 0, 'grade' => '-', 'recommendations' => []];
        }

        return (new FamilyAIService)->healthScore($family);
    }

    public function askAi(): void
    {
        $family = $this->family;
        if (! $family || strlen($this->aiQuery) < 3) {
            return;
        }

        $this->aiLoading = true;
        $this->aiAnswer = '';

        try {
            $service = new FamilyAIService;
            $metrics = $service->healthScore($family);
            $this->aiAnswer = $service->analyze($family, $metrics) ?: 'Analisis tidak tersedia saat ini.';
        } catch (\Exception $e) {
            $this->aiAnswer = '❌ Error: '.$e->getMessage();
        }

        $this->aiLoading = false;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
        $this->aiQuery = '';
        $this->aiAnswer = '';
    }

    public function exportCsv(): StreamedResponse
    {
        $family = $this->family;
        $query = FamilyTransaction::with('category')->forFamily($family->id)->whereBetween('date', [$this->dateFrom, $this->dateTo])->orderBy('date');

        $filename = 'laporan-keluarga-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Tipe', 'Deskripsi', 'Kategori', 'Payer', 'Metode', 'Jumlah', 'Catatan']);

            foreach ($query->cursor() as $tx) {
                fputcsv($handle, [
                    $tx->date->format('Y-m-d'),
                    $tx->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $tx->description,
                    $tx->category?->name ?? '-',
                    $tx->payer,
                    $tx->payment_method ?? '-',
                    $tx->amount,
                    $tx->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-report', [
            'family' => $this->family,
            'stats' => $this->stats,
            'monthlyStats' => $this->monthlyStats,
            'categoryStats' => $this->categoryStats,
            'payerStats' => $this->payerStats,
            'healthScore' => $this->healthScore,
        ]);
    }
}
