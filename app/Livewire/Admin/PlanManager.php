<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class PlanManager extends Component
{
    public ?int $editingId = null;

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $durationType = 'monthly';

    public string $durationDays = '';

    public string $aiAnalysisLimit = '';

    public string $price = '';

    public bool $isActive = true;

    public bool $showForm = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->clearValidation();
    }

    public function edit(int $id): void
    {
        $plan = SubscriptionPlan::query()->findOrFail($id);

        $this->editingId = $plan->id;
        $this->slug = $plan->slug;
        $this->name = $plan->name;
        $this->description = (string) $plan->description;
        $this->durationType = $plan->duration_type;
        $this->durationDays = $plan->duration_days ? (string) $plan->duration_days : '';
        if ($this->durationDays !== '') {
            $this->durationType = 'custom';
        }
        $this->aiAnalysisLimit = $plan->ai_analysis_limit === null ? '' : (string) $plan->ai_analysis_limit;
        $this->price = (string) $plan->price;
        $this->isActive = (bool) $plan->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'durationType' => ['required', Rule::in(['custom', 'lifetime', 'monthly', 'yearly'])],
            'durationDays' => ['nullable', 'integer', 'min:1', 'max:3650', Rule::requiredIf($this->durationType === 'custom')],
            'aiAnalysisLimit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'price' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'slug' => $this->uniqueSlug($data['name']),
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'duration_type' => $data['durationType'] === 'custom' ? 'monthly' : $data['durationType'],
            'duration_days' => $data['durationType'] === 'custom' ? (int) $data['durationDays'] : null,
            'ai_analysis_limit' => $data['aiAnalysisLimit'] === '' || $data['aiAnalysisLimit'] === null ? null : (int) $data['aiAnalysisLimit'],
            'price' => (int) $data['price'],
            'is_active' => $data['isActive'],
        ];

        if ($this->editingId) {
            SubscriptionPlan::query()->whereKey($this->editingId)->update($payload);
            activity('admin-plan')->causedBy(auth()->user())->log("Mengubah paket {$payload['slug']}");
            $this->flash('Paket diperbarui.');
        } else {
            SubscriptionPlan::query()->create($payload);
            activity('admin-plan')->causedBy(auth()->user())->log("Membuat paket {$payload['slug']}");
            $this->flash('Paket dibuat.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $plan = SubscriptionPlan::query()->findOrFail($id);
        $plan->forceFill(['is_active' => ! $plan->is_active])->save();
        $this->flash('Status paket berubah.');
    }

    public function getPlansProperty()
    {
        return SubscriptionPlan::query()->withCount('subscriptions')->orderBy('price')->get();
    }

    public function render()
    {
        return view('livewire.admin.plan-manager', ['plans' => $this->plans]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->slug = '';
        $this->name = '';
        $this->description = '';
        $this->durationType = 'monthly';
        $this->durationDays = '';
        $this->aiAnalysisLimit = '';
        $this->price = '';
        $this->isActive = true;
        $this->showForm = false;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'paket';
        $slug = $base;
        $suffix = 2;

        while (SubscriptionPlan::query()
            ->where('slug', $slug)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function flash(string $message): void
    {
        $this->statusMessage = $message;
        $this->statusType = 'success';
    }
}
