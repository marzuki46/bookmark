<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\PasswordChangeRequest;
use App\Services\ResendMailService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

final class SettingsPage extends Component
{
    public string $aiProvider = 'custom';

    public string $aiApiUrl = '';

    public string $aiApiKey = '';

    public string $aiModel = '';

    public bool $aiAutoSummary = false;

    public bool $aiAutoCategory = false;

    public bool $aiAutoTagging = false;

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $confirmPassword = '';

    public string $newPin = '';

    public string $confirmPin = '';

    public string $newEmail = '';

    public string $confirmEmail = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public string $cacheStatus = '';

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function saveAiSettings(): void
    {
        $settings = $this->getSettings();
        $settings['ai'] = [
            'provider' => $this->aiProvider,
            'api_url' => $this->aiApiUrl,
            'api_key' => $this->aiApiKey,
            'model' => $this->aiModel,
            'auto_summary' => $this->aiAutoSummary,
            'auto_category' => $this->aiAutoCategory,
            'auto_tagging' => $this->aiAutoTagging,
        ];
        $this->saveSettings($settings);

        $this->statusMessage = 'AI settings saved successfully.';
        $this->statusType = 'success';
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->statusMessage = 'Current password is incorrect.';
            $this->statusType = 'error';

            return;
        }

        if ($this->newPassword !== $this->confirmPassword) {
            $this->statusMessage = 'New passwords do not match.';
            $this->statusType = 'error';

            return;
        }

        if (strlen($this->newPassword) < 8) {
            $this->statusMessage = 'Password must be at least 8 characters.';
            $this->statusType = 'error';

            return;
        }

        $this->createChangeRequest('password', Hash::make($this->newPassword));

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->confirmPassword = '';
    }

    public function updatePin(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->statusMessage = 'Current password is incorrect.';
            $this->statusType = 'error';

            return;
        }

        if ($this->newPin !== $this->confirmPin) {
            $this->statusMessage = 'PINs do not match.';
            $this->statusType = 'error';

            return;
        }

        if (! preg_match('/^\d{4,6}$/', $this->newPin)) {
            $this->statusMessage = 'PIN must be 4-6 digits.';
            $this->statusType = 'error';

            return;
        }

        $this->createChangeRequest('pin', Hash::make($this->newPin));

        $this->currentPassword = '';
        $this->newPin = '';
        $this->confirmPin = '';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function clearCache(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('event:clear');

            $this->cacheStatus = 'Cache cleared successfully at '.now()->format('H:i:s');
            $this->statusMessage = 'All caches cleared (config, views, routes, events, cache).';
            $this->statusType = 'success';
        } catch (\Exception $e) {
            $this->cacheStatus = 'Failed: '.$e->getMessage();
            $this->statusMessage = 'Failed to clear cache: '.$e->getMessage();
            $this->statusType = 'error';
        }
    }

    public function updateEmail(): void
    {
        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->statusMessage = 'Current password is incorrect.';
            $this->statusType = 'error';

            return;
        }

        if ($this->newEmail !== $this->confirmEmail) {
            $this->statusMessage = 'Emails do not match.';
            $this->statusType = 'error';

            return;
        }

        if (! filter_var($this->newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->statusMessage = 'Invalid email address.';
            $this->statusType = 'error';

            return;
        }

        if ($this->newEmail === $user->email) {
            $this->statusMessage = 'New email is the same as current email.';
            $this->statusType = 'error';

            return;
        }

        $this->createEmailChangeRequest($this->newEmail);

        $this->currentPassword = '';
        $this->newEmail = '';
        $this->confirmEmail = '';

        $this->statusMessage = 'Confirmation email sent. Check your inbox to approve the email change.';
        $this->statusType = 'success';
    }

    public function render()
    {
        return view('livewire.settings-page');
    }

    private function createChangeRequest(string $type, string $hashedValue): void
    {
        $user = auth()->user();

        // Coba kirim email konfirmasi
        try {
            $token = Str::random(64);

            PasswordChangeRequest::create([
                'user_id' => $user->id,
                'type' => $type,
                'new_value_hash' => $hashedValue,
                'token' => $token,
                'expires_at' => now()->addHours(24),
            ]);

            $approveUrl = route('password-change.approve', $token);
            $rejectUrl = route('password-change.reject', $token);

            $mailService = new ResendMailService;
            $sent = $mailService->sendPasswordChangeRequest($user, $type, $approveUrl, $rejectUrl);

            if ($sent) {
                $this->statusMessage = 'Email konfirmasi telah dikirim. Cek email Anda untuk menyetujui perubahan.';
                $this->statusType = 'success';
                return;
            }
        } catch (\Exception $e) {
            logger()->warning('Failed to send confirmation email, applying change directly', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }

        // Jika email tidak terkirim, apply perubahan langsung
        $column = match ($type) {
            'password' => 'password',
            'pin' => 'pin_hash',
            default => null,
        };

        if ($column) {
            $user->update([$column => $hashedValue]);

            $label = match ($type) {
                'password' => 'Password',
                'pin' => 'PIN',
                default => ucfirst($type),
            };

            $this->statusMessage = "{$label} berhasil diubah (tanpa email konfirmasi).";
            $this->statusType = 'success';
        }
    }

    private function createEmailChangeRequest(string $newEmail): void
    {
        $user = auth()->user();
        $token = Str::random(64);

        PasswordChangeRequest::create([
            'user_id' => $user->id,
            'type' => 'email',
            'new_value_hash' => null,
            'new_value_plain' => $newEmail,
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ]);

        $approveUrl = route('password-change.approve', $token);
        $rejectUrl = route('password-change.reject', $token);

        try {
            $mailService = new ResendMailService;
            $mailService->sendPasswordChangeRequest($user, 'email', $approveUrl, $rejectUrl, $newEmail);
        } catch (\Exception $e) {
            logger()->error('Failed to send email change email', ['error' => $e->getMessage()]);
        }
    }

    private function loadSettings(): void
    {
        $settings = $this->getSettings();
        $ai = $settings['ai'] ?? [];

        $this->aiProvider = $ai['provider'] ?? 'custom';
        $this->aiApiUrl = $ai['api_url'] ?? '';
        $this->aiApiKey = $ai['api_key'] ?? '';
        $this->aiModel = $ai['model'] ?? '';
        $this->aiAutoSummary = $ai['auto_summary'] ?? false;
        $this->aiAutoCategory = $ai['auto_category'] ?? false;
        $this->aiAutoTagging = $ai['auto_tagging'] ?? false;
    }

    private function getSettings(): array
    {
        $path = storage_path('app/user-settings-'.auth()->id().'.json');

        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? [];
        }

        return [];
    }

    private function saveSettings(array $settings): void
    {
        $path = storage_path('app/user-settings-'.auth()->id().'.json');
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
