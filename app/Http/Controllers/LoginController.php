<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FailedLoginLog;
use App\Models\IpBlock;
use App\Models\User;
use App\Services\ResendMailService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function showLoginForm()
    {
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        if (! User::where('setup_completed', true)->exists()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:1',
        ]);

        $ip = $request->ip();

        if (IpBlock::isIpBlocked($ip)) {
            throw ValidationException::withMessages([
                'email' => ['This IP address has been blocked. Please contact the administrator.'],
            ])->status(403);
        }

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again in '.$seconds.' seconds.'],
            ])->status(429);
        }

        $credentials = $request->only('email', 'password');

        if (! auth()->attempt($credentials, false)) {
            RateLimiter::hit($throttleKey, 900);

            $this->logFailedLogin($request);

            return back()->withErrors([
                'email' => ['The provided credentials do not match our records.'],
            ])->withInput($request->only('email'));
        }

        RateLimiter::clear($throttleKey);
        $this->clearLoginAttempts($request);

        $request->session()->regenerate();
        $request->session()->migrate(true);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function throttleKey(Request $request): string
    {
        return 'login:'.strtolower($request->input('email', '')).'|'.$request->ip();
    }

    private function clearLoginAttempts(Request $request): void
    {
        $key = 'login_attempts:'.$request->ip();
        $lockKey = 'login_lockout:'.$request->ip();
        Cache::forget($key);
        Cache::forget($lockKey);
    }

    private function logFailedLogin(Request $request): void
    {
        $ip = $request->ip();
        $email = $request->input('email');
        $userAgent = $request->userAgent();

        FailedLoginLog::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $recentCount = FailedLoginLog::where('ip_address', $ip)
            ->where('email', $email)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $owner = User::first();

        if ($owner && $recentCount >= 3) {
            $mailService = new ResendMailService;
            $mailService->sendFailedLoginAlert($owner->email, $ip, $userAgent ?? 'unknown', $recentCount);

            if ($recentCount >= 5 && ! IpBlock::isIpBlocked($ip)) {
                IpBlock::create([
                    'ip_address' => $ip,
                    'reason' => "Auto-blocked: {$recentCount} failed login attempts for {$email}",
                    'blocked_at' => now(),
                    'is_active' => true,
                ]);

                $mailService->sendIpBlockedAlert($owner->email, $ip, "Auto-blocked after {$recentCount} failed login attempts");
            }
        }
    }
}
