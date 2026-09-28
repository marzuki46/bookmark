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
            ['slug' => 'lifetime', 'name' => 'Akses Seumur Hidup', 'duration_type' => 'lifetime', 'price' => 499_000],
            ['slug' => 'yearly', 'name' => 'Tahunan', 'duration_type' => 'yearly', 'price' => 99_000],
            ['slug' => 'monthly', 'name' => 'Bulanan', 'duration_type' => 'monthly', 'price' => 9_900],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                array_merge($plan, ['description' => 'Paket berbayar aplikasi Keuangan Keluarga. Harga disesuaikan admin.']),
            );
        }
    }
}
