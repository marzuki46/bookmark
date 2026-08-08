<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Services\FamilyAllocationService;
use Livewire\Component;

final class FamilyAppAllocation extends Component
{
    public array $draft = [];

    public string $surplus = '0';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function mount(): void
    {
        $this->loadSuggestions();
    }

    public function loadSuggestions(): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $service = new FamilyAllocationService;
        $this->surplus = (string) $service->monthlySurplus($family, now()->month, now()->year);

        $this->draft = collect($service->suggestAllocation($family, now()->month, now()->year))
            ->map(fn ($s) => [
                'goal_id' => $s['goal']->id,
                'name' => $s['goal']->name,
                'amount' => (string) $s['amount'],
                'icon' => $s['goal']->icon ?? '🎯',
                'color' => $s['goal']->color ?? '#6366f1',
                'current' => (string) $s['goal']->current_amount,
                'target' => (string) $s['goal']->target_amount,
            ])->values()->all();
    }

    public function confirm(): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $items = collect($this->draft)
            ->map(fn ($d) => [
                'goal_id' => (int) $d['goal_id'],
                'amount' => (float) $d['amount'],
            ])
            ->filter(fn ($d) => $d['amount'] > 0)
            ->values()
            ->all();

        if (empty($items)) {
            $this->statusMessage = 'Belum ada alokasi untuk dikonfirmasi.';
            $this->statusType = 'error';

            return;
        }

        (new FamilyAllocationService)->confirmAllocation($family, now()->month, now()->year, $items);

        $this->statusMessage = 'Jatah bulan ini berhasil dialokasikan! 🎉';
        $this->statusType = 'success';
        $this->loadSuggestions();
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-app-allocation', [
            'family' => $this->family,
        ]);
    }
}
