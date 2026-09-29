<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

final class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Starter plans. Prices are placeholders the admin edits from the web UI
     * before the app goes live; slugs stay stable because subscriptions link
     * to plan rows, not names.
     */
    public function run(): void
    {
        $plans = [
            ['slug' => 'free-trial', 'name' => 'Cuan Trial 7 Hari', 'duration_type' => 'monthly', 'duration_days' => 7, 'ai_analysis_limit' => 3, 'price' => 0],
            ['slug' => 'lifetime', 'name' => 'Cuan Seumur Hidup', 'duration_type' => 'lifetime', 'duration_days' => null, 'ai_analysis_limit' => null, 'price' => 299_000],
            ['slug' => 'yearly', 'name' => 'Cuan Keluarga Tahunan', 'duration_type' => 'yearly', 'duration_days' => null, 'ai_analysis_limit' => 50, 'price' => 99_000],
            ['slug' => 'monthly', 'name' => 'Cuan Hemat Bulanan', 'duration_type' => 'monthly', 'duration_days' => null, 'ai_analysis_limit' => 10, 'price' => 9_900],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['description' => 'Paket berbayar aplikasi Keuangan Keluarga. Harga disesuaikan admin.']),
            );
        }
    }
}
