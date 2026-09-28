<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Livewire\Component;
use Livewire\WithPagination;

final class SubscriptionManager extends Component
{
    use WithPagination;

    public ?int $grantUserId = null;

    public ?int $grantPlanId = null;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getUsersProperty()
    {
        return User::query()
            ->withCount('subscriptions')
            ->with('subscriptions')
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
            ->with(['user', 'plan'])
            ->latest()
            ->limit(15)
            ->get();
    }

    public function openGrant(int $userId): void
    {
        $this->grantUserId = $userId;
        $this->grantPlanId = null;
    }

    public function grant(): void
    {
        $data = $this->validate([
            'grantUserId' => ['required', 'integer', 'exists:users,id'],
            'grantPlanId' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $user = User::query()->findOrFail($data['grantUserId']);
        $plan = SubscriptionPlan::query()->findOrFail($data['grantPlanId']);

        app(SubscriptionService::class)->activate($user, $plan);

        activity('admin-subscription')->causedBy(auth()->user())
            ->log("Memberi akses {$plan->name} ke {$user->email}");

        $this->grantUserId = null;
        $this->grantPlanId = null;
        $this->flash('Akses berlangganan diberikan.');
    }

    public function revoke(int $userId): void
    {
        $user = User::query()->findOrFail($userId);
        app(SubscriptionService::class)->revoke($user);

        activity('admin-subscription')->causedBy(auth()->user())->log("Mencabut akses {$user->email}");
        $this->flash('Akses dicabut.');
    }

    public function render()
    {
        return view('livewire.admin.subscription-manager', [
            'users' => $this->users,
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
