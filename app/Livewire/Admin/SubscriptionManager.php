<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Livewire\Component;
use Livewire\WithPagination;

final class SubscriptionManager extends Component
{
    use WithPagination;

    public ?int $grantFamilyId = null;

    public ?int $grantPlanId = null;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamiliesProperty()
    {
        return Family::query()
            ->with(['owner:id,name,email', 'members.user:id,name,email'])
            ->withCount('members')
            ->orderBy('name')
            ->paginate(20);
    }

    public function getPlansProperty()
    {
        return SubscriptionPlan::query()->orderBy('price')->get();
    }

    public function getRecentPaymentsProperty()
    {
        return SubscriptionPayment::query()
            ->with(['user', 'family', 'plan'])
            ->latest()
            ->limit(15)
            ->get();
    }

    public function openGrant(int $familyId): void
    {
        $this->grantFamilyId = $familyId;
        $this->grantPlanId = null;
    }

    public function grant(): void
    {
        $data = $this->validate([
            'grantFamilyId' => ['required', 'integer', 'exists:families,id'],
            'grantPlanId' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $family = Family::query()->with('owner')->findOrFail($data['grantFamilyId']);
        $plan = SubscriptionPlan::query()->findOrFail($data['grantPlanId']);

        app(SubscriptionService::class)->activate($family->owner, $plan);

        activity('admin-subscription')->causedBy(auth()->user())
            ->log("Memberi akses {$plan->name} ke keluarga {$family->name}");

        $this->grantFamilyId = null;
        $this->grantPlanId = null;
        $this->flash('Akses berlangganan diberikan.');
    }

    public function revoke(int $familyId): void
    {
        $family = Family::query()->with('owner')->findOrFail($familyId);
        app(SubscriptionService::class)->revoke($family->owner);

        activity('admin-subscription')->causedBy(auth()->user())->log("Mencabut akses keluarga {$family->name}");
        $this->flash('Akses dicabut.');
    }

    public function render()
    {
        return view('livewire.admin.subscription-manager', [
            'families' => $this->families,
            'plans' => $this->plans,
            'recentPayments' => $this->recentPayments,
        ]);
    }

    private function flash(string $message): void
    {
        $this->statusMessage = $message;
        $this->statusType = 'success';
    }
}
