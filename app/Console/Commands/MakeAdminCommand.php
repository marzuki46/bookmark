<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

final class MakeAdminCommand extends Command
{
    protected $signature = 'users:make-admin {email?}';

    protected $description = 'Lift an existing account to admin (set is_admin = true)';

    public function handle(): int
    {
        $email = $this->argument('email')
            ?? $this->ask('Email akun yang mau dijadikan admin?')
            ?? config('app.admin_email');

        if (! $email) {
            $this->error('Tidak ada email. Berikan argumen atau isi APP_ADMIN_EMAIL di .env.');

            return self::FAILURE;
        }

        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        $user = User::firstWhere('email', $email);

        if (! $user) {
            $this->error("Akun dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("{$email} sekarang admin.");

        return self::SUCCESS;
    }
}
