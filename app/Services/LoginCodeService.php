<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Issues and verifies the permanent, high-entropy login codes used by the
 * Android app in place of an email + password pair.
 *
 * The code carries ~80 bits of entropy (32 symbols x 16 positions), so a fast
 * digest is used rather than bcrypt: that keeps login a single indexed lookup
 * instead of a full table scan. Brute-forcing the digest is not the attack
 * surface here — guessing the code itself is, and 2^80 is infeasible.
 *
 * NOTE: rotating the pepper in `config('app.login_code_pepper')` invalidates
 * every issued code. Keep it stable once codes are handed out.
 */
final class LoginCodeService
{
    /** Unambiguous alphabet: omits I, O, 0 and 1 so codes can be read aloud. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const GROUPS = 4;

    private const GROUP_LENGTH = 4;

    private const LENGTH = self::GROUPS * self::GROUP_LENGTH;

    private const HASH_PREFIX = 'sha256:';

    /**
     * Human-readable code, e.g. "K7QM-3ZPD-8RWT-5XNB".
     */
    public function generate(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $raw = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $raw .= self::ALPHABET[random_int(0, $max)];
        }

        return implode('-', str_split($raw, self::GROUP_LENGTH));
    }

    /**
     * Strips formatting so "k7qm 3zpd-8rwt5xnb" and "K7QM-3ZPD-8RWT-5XNB"
     * are treated as the same code.
     */
    public function normalize(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    public function hash(string $code): string
    {
        return self::HASH_PREFIX.hash('sha256', $this->normalize($code).$this->pepper());
    }

    public function findUserByCode(string $code): ?User
    {
        $normalized = $this->normalize($code);

        // Reject malformed input before touching the database.
        if (strlen($normalized) !== self::LENGTH) {
            return null;
        }

        return User::where('app_login_code', $this->hash($normalized))->first();
    }

    /**
     * Generates a code, stores only its digest, and returns the plaintext for
     * one-time display. Retries on the (astronomically unlikely) collision.
     */
    public function issueFor(User $user): string
    {
        do {
            $plain = $this->generate();
            $digest = $this->hash($plain);
        } while (User::where('app_login_code', $digest)->whereKeyNot($user->id)->exists());

        $user->forceFill([
            'app_login_code' => $digest,
            'app_login_code_plain' => encrypt($plain),
            'app_login_code_rotated_at' => now(),
        ])->save();

        return $plain;
    }

    /**
     * Returns the current plaintext code if it is still stored (encrypted),
     * so the "family login code" screen can re-show it without rotating.
     */
    public function displayCodeFor(User $user): ?string
    {
        if (blank($user->app_login_code_plain)) {
            return null;
        }

        try {
            return decrypt((string) $user->app_login_code_plain);
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasCode(User $user): bool
    {
        return filled($user->app_login_code);
    }

    private function pepper(): string
    {
        return (string) config('app.login_code_pepper', '');
    }
}
