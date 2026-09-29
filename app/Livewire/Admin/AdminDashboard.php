<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\FailedLoginLog;
use App\Models\Family;
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

    public function getFamiliesCountProperty(): int
    {
        return Family::query()->count();
    }

    public function getAdminsCountProperty(): int
    {
        return User::query()->where('is_admin', true)->count();
    }

    public function getActiveFamiliesProperty(): int
    {
        return Subscription::query()
            ->whereNotNull('family_id')
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->distinct('family_id')
            ->count('family_id');
    }

    public function getTrialFamiliesProperty(): int
    {
        return Subscription::query()
            ->join('subscription_plans', 'subscription_plans.id', '=', 'subscriptions.plan_id')
            ->whereNotNull('subscriptions.family_id')
            ->where('subscriptions.status', 'active')
            ->where('subscription_plans.slug', 'free-trial')
            ->where(fn ($q) => $q->whereNull('subscriptions.expires_at')->orWhere('subscriptions.expires_at', '>', now()))
            ->distinct('subscriptions.family_id')
            ->count('subscriptions.family_id');
    }

    public function getPaidFamiliesProperty(): int
    {
        return SubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereNotNull('family_id')
            ->distinct('family_id')
            ->count('family_id');
    }

    public function getSalesCountProperty(): int
    {
        return SubscriptionPayment::query()->where('status', 'paid')->count();
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

    public function getSalesTrendProperty(): array
    {
        $rows = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['paid_at', 'gross_amount'])
            ->groupBy(fn (SubscriptionPayment $payment) => $payment->paid_at?->format('Y-m'));

        return collect(range(11, 0))->map(function (int $offset) use ($rows): array {
            $month = now()->subMonths($offset);
            $items = $rows->get($month->format('Y-m'), collect());

            return [
                'key' => $month->format('Y-m'),
                'label' => $month->format('M Y'),
                'count' => $items->count(),
                'revenue' => (int) $items->sum('gross_amount'),
            ];
        })->values()->all();
    }

    public function getDurationAnalysisProperty(): array
    {
        $buckets = [
            '1' => ['label' => '1 bulan', 'min' => 0, 'max' => 1],
            '2-3' => ['label' => '2-3 bulan', 'min' => 2, 'max' => 3],
            '4-6' => ['label' => '4-6 bulan', 'min' => 4, 'max' => 6],
            '7-12' => ['label' => '7-12 bulan', 'min' => 7, 'max' => 12],
            '13+' => ['label' => '13+ bulan', 'min' => 13, 'max' => null],
        ];
        $details = collect();

        Subscription::query()
            ->with(['family', 'plan'])
            ->whereNotNull('family_id')
            ->whereHas('plan', fn ($query) => $query->where('slug', '!=', 'free-trial'))
            ->get()
            ->each(function (Subscription $subscription) use (&$details, $buckets): void {
                if (! $subscription->starts_at) {
                    return;
                }

                $end = $subscription->expires_at ?? now();
                $months = max(1, (int) ceil($subscription->starts_at->diffInDays($end) / 30));
                $bucketKey = collect($buckets)->search(fn (array $bucket) => $months >= $bucket['min'] && ($bucket['max'] === null || $months <= $bucket['max']));
                $key = (string) $bucketKey;

                $details->push([
                    'bucket' => $key,
                    'family' => $subscription->family?->name ?? '-',
                    'plan' => $subscription->plan?->name ?? '-',
                    'months' => $months,
                ]);
            });

        $counts = collect($buckets)->mapWithKeys(fn (array $bucket, string $key) => [$key => [
            'label' => $bucket['label'],
            'count' => $details->where('bucket', $key)->count(),
        ]])->all();

        return [
            'average' => $details->isEmpty() ? 0 : round($details->avg('months'), 1),
            'buckets' => $counts,
            'details' => $details->all(),
        ];
    }

    public string $selectedDurationBucket = '';

    public function selectDurationBucket(string $bucket): void
    {
        $this->selectedDurationBucket = $this->selectedDurationBucket === $bucket ? '' : $bucket;
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
