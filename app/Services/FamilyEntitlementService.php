<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyAiUsage;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

final class FamilyEntitlementService
{
    public function consumeAiAnalysis(Family $family): bool
    {
        return DB::transaction(function () use ($family): bool {
            $subscription = Subscription::query()
                ->with('plan')
                ->where('family_id', $family->id)
                ->where('status', 'active')
                ->latest('id')
                ->lockForUpdate()
                ->get()
                ->first(fn (Subscription $item) => $item->isUsable());

            if (! $subscription) {
                return false;
            }

            $limit = $subscription->plan?->ai_analysis_limit;
            if ($limit === null) {
                return true;
            }

            $periodStart = now()->startOfMonth()->toDateString();
            $usage = FamilyAiUsage::query()
                ->where('family_id', $family->id)
                ->whereDate('period_start', $periodStart)
                ->lockForUpdate()
                ->first();

            if (! $usage) {
                $usage = FamilyAiUsage::query()->create([
                    'family_id' => $family->id,
                    'period_start' => $periodStart,
                ]);
            }

            if ($usage->analysis_count >= $limit) {
                return false;
            }

            $usage->increment('analysis_count');

            return true;
        });
    }

    public function aiUsage(Family $family): array
    {
        $subscription = Subscription::query()
            ->with('plan')
            ->where('family_id', $family->id)
            ->where('status', 'active')
            ->latest('id')
            ->get()
            ->first(fn (Subscription $item) => $item->isUsable());

        $limit = $subscription?->plan?->ai_analysis_limit;
        $used = (int) FamilyAiUsage::query()
            ->where('family_id', $family->id)
            ->whereDate('period_start', now()->startOfMonth()->toDateString())
            ->value('analysis_count');

        return ['used' => $used, 'limit' => $limit];
    }
}
