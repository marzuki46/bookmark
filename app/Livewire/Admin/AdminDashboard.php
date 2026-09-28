<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\FailedLoginLog;
use App\Models\RequestLog;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Livewire\Component;

final class AdminDashboard extends Component
{
    public function getUsersCountProperty(): int
    {
        return User::query()->count();
    }

    public function getAdminsCountProperty(): int
    {
        return User::query()->where('is_admin', true)->count();
    }

    public function getActiveSubscriptionsProperty(): int
    {
        return Subscription::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();
    }

    public function getRevenueProperty(): int
    {
        return (int) SubscriptionPayment::query()
            ->where('status', 'paid')
            ->sum('gross_amount');
    }

    public function getRequestsTodayProperty(): int
    {
        return RequestLog::query()->whereDate('created_at', today())->count();
    }

    public function getFailedLoginsTodayProperty(): int
    {
        return FailedLoginLog::query()->whereDate('created_at', today())->count();
    }

    public function getRecentPaymentsProperty()
    {
        return SubscriptionPayment::query()
            ->with(['user', 'plan'])
            ->latest()
            ->limit(8)
            ->get();
    }

    public function getUsersProperty()
    {
        return User::query()
            ->withSum('subscriptions as active_sub_count', 'id')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
