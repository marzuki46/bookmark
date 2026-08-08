<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyTransaction;
use App\Services\FamilyAIService;
use Livewire\Component;

final class FamilyAppDashboard extends Component
{
    public bool $showAiModal = false;

    public string $aiQuery = '';

    public string $aiAnswer = '';

    public bool $aiLoading = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['income' => 0, 'expense' => 0, 'balance' => 0];
        }

        $income = (float) FamilyTransaction::forFamily($family->id)->where('type', 'income')
            ->where('date', '>=', now()->startOfMonth())->sum('amount');
        $expense = (float) FamilyTransaction::forFamily($family->id)->where('type', 'expense')
            ->where('date', '>=', now()->startOfMonth())->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    public function getEmergencyFundProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['current' => 0, 'target' => 0, 'percent' => 0];
        }

        $service = new \App\Services\FamilyAllocationService;
        $current = (float) FamilyGoal::forFamily($family->id)->where('type', 'emergency_fund')->sum('current_amount');
        $target = $service->emergencyFundTarget($family);

        return [
            'current' => $current,
            'target' => $target,
            'percent' => $target > 0 ? min(100, round($current / $target * 100)) : 0,
        ];
    }

    public function getGoalsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyGoal::forFamily($family->id)->where('status', 'active')->orderBy('priority')->orderBy('id')->get();
    }

    public function getUpcomingDebtProperty(): ?array
    {
        $family = $this->family;
        if (! $family) {
            return null;
        }

        $debt = FamilyDebt::forFamily($family->id)->where('type', 'payable')->where('status', '!=', 'settled')
            ->whereNotNull('due_date')->orderBy('due_date')->first();

        if (! $debt) {
            return null;
        }

        return [
            'name' => $debt->name,
            'amount' => $debt->installment ?? $debt->remaining,
            'due_date' => $debt->due_date,
            'days_left' => (int) now()->startOfDay()->diffInDays($debt->due_date->startOfDay(), false),
        ];
    }

    public function getHealthScoreProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['score' => 0, 'grade' => '-'];
        }

        return (new FamilyAIService)->healthScore($family);
    }

    public function getRecentTransactionsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyTransaction::with('category')->forFamily($family->id)->latest('date')->latest('id')->take(5)->get();
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
            $this->aiAnswer = (new FamilyAIService)->analyze($family) ?: 'Analisis tidak tersedia saat ini.';
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

    public function render()
    {
        return view('livewire.family-app-dashboard', [
            'family' => $this->family,
            'stats' => $this->stats,
            'emergencyFund' => $this->emergencyFund,
            'goals' => $this->goals,
            'upcomingDebt' => $this->upcomingDebt,
            'healthScore' => $this->healthScore,
            'recentTransactions' => $this->recentTransactions,
        ]);
    }
}
