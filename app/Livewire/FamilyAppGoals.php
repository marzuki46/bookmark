<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyGoal;
use App\Services\FamilyAllocationService;
use Livewire\Component;

final class FamilyAppGoals extends Component
{
    public bool $showModal = false;

    public string $formName = '';

    public string $formType = 'custom';

    public string $formTargetAmount = '';

    public string $formMonthlyAllocation = '';

    public string $formIcon = '🎯';

    public string $formColor = '#6366f1';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getGoalsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyGoal::forFamily($family->id)->where('status', 'active')->orderBy('priority')->orderBy('id')->get();
    }

    public function getEmergencyFundProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return ['current' => 0, 'target' => 0, 'percent' => 0];
        }

        $service = new FamilyAllocationService;
        $current = (float) FamilyGoal::forFamily($family->id)->where('type', 'emergency_fund')->sum('current_amount');
        $target = $service->emergencyFundTarget($family);

        return [
            'current' => $current,
            'target' => $target,
            'percent' => $target > 0 ? min(100, round($current / $target * 100)) : 0,
        ];
    }

    public function openCreateEmergency(): void
    {
        $family = $this->family;
        $target = $family ? (new FamilyAllocationService)->emergencyFundTarget($family) : 0;

        $this->formName = 'Dana Darurat';
        $this->formType = 'emergency_fund';
        $this->formTargetAmount = (string) max(1, $target);
        $this->formMonthlyAllocation = '';
        $this->formIcon = '🛡️';
        $this->formColor = '#10b981';
        $this->showModal = true;
    }

    public function openCreateCustom(): void
    {
        $this->formName = '';
        $this->formType = 'custom';
        $this->formTargetAmount = '';
        $this->formMonthlyAllocation = '';
        $this->formIcon = '🎯';
        $this->formColor = '#6366f1';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->clearValidation();
    }

    public function save(): void
    {
        $this->validate([
            'formName' => 'required|string|max:150',
            'formType' => 'required|in:emergency_fund,custom',
            'formTargetAmount' => 'required|numeric|min:0.01',
            'formMonthlyAllocation' => 'nullable|numeric|min:0',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        FamilyGoal::create([
            'family_id' => $family->id,
            'name' => $this->formName,
            'type' => $this->formType,
            'target_amount' => $this->formTargetAmount,
            'current_amount' => 0,
            'monthly_allocation' => $this->formMonthlyAllocation ?: 0,
            'priority' => $this->formType === 'emergency_fund' ? 1 : 10,
            'icon' => $this->formIcon,
            'color' => $this->formColor,
        ]);

        $this->statusMessage = 'Tabungan baru berhasil dibuat!';
        $this->statusType = 'success';
        $this->closeModal();
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-app-goals', [
            'family' => $this->family,
            'goals' => $this->goals,
            'emergencyFund' => $this->emergencyFund,
        ]);
    }
}
