<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    /**
     * Create default financial categories for existing users.
     */
    public function run(): void
    {
        $users = User::all();

        $defaultExpenseCategories = [
            ['name' => 'Makanan', 'icon' => '🍜', 'color' => '#ef4444', 'type' => 'expense'],
            ['name' => 'Transportasi', 'icon' => '🚗', 'color' => '#f97316', 'type' => 'expense'],
            ['name' => 'Belanja', 'icon' => '🛒', 'color' => '#eab308', 'type' => 'expense'],
            ['name' => 'Hiburan', 'icon' => '🎮', 'color' => '#a855f7', 'type' => 'expense'],
            ['name' => 'Kesehatan', 'icon' => '🏥', 'color' => '#ec4899', 'type' => 'expense'],
            ['name' => 'Tagihan', 'icon' => '📄', 'color' => '#06b6d4', 'type' => 'expense'],
            ['name' => 'Technology', 'icon' => '💻', 'color' => '#6366f1', 'type' => 'expense'],
            ['name' => 'Pendidikan', 'icon' => '📚', 'color' => '#14b8a6', 'type' => 'expense'],
        ];

        $defaultIncomeCategories = [
            ['name' => 'Gaji', 'icon' => '💰', 'color' => '#10b981', 'type' => 'income'],
            ['name' => 'Freelance', 'icon' => '💼', 'color' => '#3b82f6', 'type' => 'income'],
            ['name' => 'Bisnis', 'icon' => '🏪', 'color' => '#8b5cf6', 'type' => 'income'],
            ['name' => 'Investasi', 'icon' => '📈', 'color' => '#0ea5e9', 'type' => 'income'],
        ];

        $allDefaults = array_merge($defaultExpenseCategories, $defaultIncomeCategories);

        foreach ($users as $user) {
            foreach ($allDefaults as $category) {
                FinancialCategory::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $category['name'],
                    ],
                    [
                        'type' => $category['type'],
                        'icon' => $category['icon'],
                        'color' => $category['color'],
                        'is_system' => true,
                    ]
                );
            }
        }

        $this->command->info('Default financial categories seeded for ' . $users->count() . ' user(s).');
    }
}
