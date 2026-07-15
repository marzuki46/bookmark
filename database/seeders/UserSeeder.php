<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'ohmjuki@gmail.com'],
            [
                'name' => 'Marzuki',
                'password' => bcrypt('Sugihwara5'),
                'setup_completed' => true,
                'pin_hash' => bcrypt('123456'),
            ]
        );
    }
}
