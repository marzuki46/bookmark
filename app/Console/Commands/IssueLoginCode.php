<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LoginCodeService;
use Illuminate\Console\Command;

final class IssueLoginCode extends Command
{
    protected $signature = 'finance:login-code
        {user : User ID or email address}
        {--rotate : Replace an existing code (revokes nothing, just re-issues)}';

    protected $description = 'Issue a permanent login code for the Android app';

    public function handle(LoginCodeService $codes): int
    {
        $identifier = (string) $this->argument('user');

        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('id', ctype_digit($identifier) ? (int) $identifier : 0)
            ->first();

        if (! $user) {
            $this->components->error("User not found: {$identifier}");

            return self::FAILURE;
        }

        if ($codes->hasCode($user) && ! $this->option('rotate')) {
            $this->components->warn("User #{$user->id} already has a code.");
            $this->components->warn('Re-run with --rotate to replace it. The old code will stop working.');

            return self::FAILURE;
        }

        $plain = $codes->issueFor($user);

        $this->newLine();
        $this->components->info("Login code for {$user->name} <{$user->email}>");
        $this->newLine();
        $this->line("    {$plain}");
        $this->newLine();
        $this->components->warn('Shown once. It is stored hashed, so it cannot be recovered.');
        $this->components->warn('Delivering it securely is on you — hand it over in person, not over chat.');

        if (config('app.login_code_pepper') === '') {
            $this->newLine();
            $this->components->warn('LOGIN_CODE_PEPPER is not set. The code is still safe (80 bits of');
            $this->components->warn('entropy), but set a pepper in .env before going live.');
        }

        return self::SUCCESS;
    }
}
