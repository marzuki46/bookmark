<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyGoal;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Services\FamilyAIService;
use Livewire\Component;

final class FamilyDashboard extends Component
{
    public string $familyName = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public ?int $selectedMonth = null;

    public ?int $selectedYear = null;

    public bool $showAiModal = false;

    public string $aiQuery = '';

    public string $aiAnswer = '';

    public bool $aiLoading = false;

    public function mount(): void
    {
        $this->selectedMonth = now()->month;
        $this->selectedYear = now()->year;
    }

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getStatsProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['income' => 0, 'expense' => 0, 'balance' => 0, 'count' => 0];
        }

        $start = sprintf('%04d-%02d-01', $this->selectedYear, $this->selectedMonth);
        $end = date('Y-m-t', strtotime($start));

        $income = (float) FamilyTransaction::forFamily($family->id)->where('type', 'income')->whereBetween('date', [$start, $end])->sum('amount');
        $expense = (float) FamilyTransaction::forFamily($family->id)->where('type', 'expense')->whereBetween('date', [$start, $end])->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'count' => FamilyTransaction::forFamily($family->id)->whereBetween('date', [$start, $end])->count(),
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

    public function getRecentTransactionsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyTransaction::with('category', 'user')->forFamily($family->id)->latest('date')->latest('id')->take(8)->get();
    }

    public function getHealthScoreProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['score' => 0, 'grade' => '-', 'recommendations' => []];
        }

        return (new FamilyAIService)->healthScore($family);
    }

    public function createFamily(): void
    {
        $this->validate([
            'familyName' => 'required|string|max:100',
        ]);

        $user = auth()->user();
        $family = Family::create([
            'name' => $this->familyName,
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_family_only' => false,
        ]);

        $this->seedDefaultCategories($family);

        $this->familyName = '';
        $this->statusMessage = 'Keluarga berhasil dibuat!';
        $this->statusType = 'success';
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

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function seedDefaultCategories(Family $family): void
    {
        $defaults = [
            ['name' => 'Makanan', 'type' => 'expense', 'icon' => '🍜', 'color' => '#ef4444'],
            ['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316'],
            ['name' => 'Belanja', 'type' => 'expense', 'icon' => '🛒', 'color' => '#eab308'],
            ['name' => 'Tagihan', 'type' => 'expense', 'icon' => '📄', 'color' => '#06b6d4'],
            ['name' => 'Kesehatan', 'type' => 'expense', 'icon' => '🏥', 'color' => '#ec4899'],
            ['name' => 'Pendidikan', 'type' => 'expense', 'icon' => '📚', 'color' => '#14b8a6'],
            ['name' => 'Hiburan', 'type' => 'expense', 'icon' => '🎮', 'color' => '#a855f7'],
            ['name' => 'Gaji', 'type' => 'income', 'icon' => '💰', 'color' => '#10b981'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => '💼', 'color' => '#3b82f6'],
            ['name' => 'Bisnis', 'type' => 'income', 'icon' => '🏪', 'color' => '#8b5cf6'],
            ['name' => 'Lainnya', 'type' => 'income', 'icon' => '✨', 'color' => '#0ea5e9'],
        ];

        foreach ($defaults as $cat) {
            FamilyCategory::create(array_merge($cat, [
                'family_id' => $family->id,
                'is_system' => true,
            ]));
        }
    }

    public function render()
    {
        return view('livewire.family-dashboard', [
            'family' => $this->family,
            'stats' => $this->stats,
            'goals' => $this->goals,
            'recentTransactions' => $this->recentTransactions,
            'healthScore' => $this->healthScore,
        ]);
    }
}
