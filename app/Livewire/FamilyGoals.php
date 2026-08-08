<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyGoal;
use App\Services\FamilyAllocationService;
use Livewire\Component;

final class FamilyGoals extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formType = 'custom';

    public string $formTargetAmount = '';

    public string $formMonthlyAllocation = '';

    public string $formDeadline = '';

    public string $formIcon = '🎯';

    public string $formColor = '#6366f1';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public bool $showAllocationModal = false;

    public array $allocationDraft = [];

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

    public function getCompletedGoalsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyGoal::forFamily($family->id)->where('status', 'completed')->latest()->get();
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

    public function getSurplusProperty(): float
    {
        $family = $this->family;
        if (! $family) {
            return 0;
        }

        return (new FamilyAllocationService)->monthlySurplus($family, now()->month, now()->year);
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formType = 'custom';
        $this->formTargetAmount = '';
        $this->formMonthlyAllocation = '';
        $this->formDeadline = '';
        $this->formIcon = '🎯';
        $this->formColor = '#6366f1';
        $this->showModal = true;
    }

    public function openCreateEmergency(): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $target = (new FamilyAllocationService)->emergencyFundTarget($family);

        $this->editingId = null;
        $this->formName = 'Dana Darurat';
        $this->formType = 'emergency_fund';
        $this->formTargetAmount = (string) max(1, $target);
        $this->formMonthlyAllocation = '';
        $this->formDeadline = '';
        $this->formIcon = '🛡️';
        $this->formColor = '#10b981';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $family = $this->family;
        $goal = FamilyGoal::forFamily($family?->id ?? 0)->findOrFail($id);
        $this->editingId = $id;
        $this->formName = $goal->name;
        $this->formType = $goal->type;
        $this->formTargetAmount = (string) $goal->target_amount;
        $this->formMonthlyAllocation = (string) $goal->monthly_allocation;
        $this->formDeadline = $goal->deadline?->format('Y-m-d') ?? '';
        $this->formIcon = $goal->icon ?? '🎯';
        $this->formColor = $goal->color ?? '#6366f1';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->clearValidation();
    }

    public function save(): void
    {
        $this->validate([
            'formName' => 'required|string|max:150',
            'formType' => 'required|in:emergency_fund,custom',
            'formTargetAmount' => 'required|numeric|min:0.01',
            'formMonthlyAllocation' => 'nullable|numeric|min:0',
            'formDeadline' => 'nullable|date',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $data = [
            'family_id' => $family->id,
            'name' => $this->formName,
            'type' => $this->formType,
            'target_amount' => $this->formTargetAmount,
            'monthly_allocation' => $this->formMonthlyAllocation ?: 0,
            'deadline' => $this->formDeadline ?: null,
            'icon' => $this->formIcon,
            'color' => $this->formColor,
        ];

        if ($this->editingId) {
            FamilyGoal::forFamily($family->id)->findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Goal berhasil diperbarui.';
        } else {
            FamilyGoal::create($data);
            $this->statusMessage = 'Goal berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $family = $this->family;
        FamilyGoal::forFamily($family?->id ?? 0)->findOrFail($id)->delete();
        $this->statusMessage = 'Goal berhasil dihapus.';
        $this->statusType = 'success';
    }

    public function archive(int $id): void
    {
        $family = $this->family;
        FamilyGoal::forFamily($family?->id ?? 0)->findOrFail($id)->update(['status' => 'archived']);
        $this->statusMessage = 'Goal diarsipkan.';
        $this->statusType = 'success';
    }

    public function openAllocation(): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $suggestions = (new FamilyAllocationService)->suggestAllocation($family, now()->month, now()->year);

        $this->allocationDraft = collect($suggestions)->map(fn ($s) => [
            'goal_id' => $s['goal']->id,
            'name' => $s['goal']->name,
            'amount' => (string) $s['amount'],
            'icon' => $s['goal']->icon ?? '🎯',
            'color' => $s['goal']->color ?? '#6366f1',
        ])->values()->all();

        $this->showAllocationModal = true;
    }

    public function closeAllocation(): void
    {
        $this->showAllocationModal = false;
        $this->allocationDraft = [];
    }

    public function confirmAllocation(): void
    {
        $family = $this->family;
        if (! $family || empty($this->allocationDraft)) {
            return;
        }

        $items = collect($this->allocationDraft)->map(fn ($d) => [
            'goal_id' => (int) $d['goal_id'],
            'amount' => (float) $d['amount'],
        ])->filter(fn ($d) => $d['amount'] > 0)->all();

        (new FamilyAllocationService)->confirmAllocation($family, now()->month, now()->year, $items);

        $this->closeAllocation();
        $this->statusMessage = 'Alokasi jatah berhasil dikonfirmasi!';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-goals', [
            'family' => $this->family,
            'goals' => $this->goals,
            'completedGoals' => $this->completedGoals,
            'emergencyFund' => $this->emergencyFund,
            'surplus' => $this->surplus,
        ]);
    }
}
