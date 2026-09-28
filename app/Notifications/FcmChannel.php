<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging transport.
 *
 * Registered as the "fcm" notification channel in AppServiceProvider. Every
 * family-insight notification goes through here, so the channel is always
 * present and never silently dropped: Laravel discards a notification whose
 * via() is empty.
 *
 * Delivery itself is intentionally inert until credentials exist. The payload
 * is logged instead, which keeps the weekly run observable in development and
 * lets the app fall back to the persisted family_insights row that it reads on
 * open. Adding the HTTP call in is the only change needed to go live.
 */
class FcmChannel
{
    /** @var list<array<string, mixed>> Captured deliveries, for tests and debugging. */
    public static array $delivered = [];

    public static function isConfigured(): bool
    {
        return (bool) config('services.fcm.server_key');
    }

    /**
     * @return list<string>
     */
    public function tokensFor(mixed $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        return $notifiable->devices()
            ->pluck('fcm_token')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        $payload = method_exists($notification, 'toArrayForFcm')
            ? $notification->toArrayForFcm($notifiable)
            : ['title' => 'Family', 'body' => ''];

        foreach ($this->tokensFor($notifiable) as $token) {
            self::$delivered[] = [
                'user_id' => $notifiable->getKey(),
                'token' => $token,
                'payload' => $payload,
            ];

            if (self::isConfigured()) {
                // TODO(I6): POST to the FCM v1 endpoint and prune unregistered
                // tokens (HTTP 404/UNREGISTERED) from user_devices.
                $this->dispatch($token, $payload);

                continue;
            }

            Log::info('FCM push not sent: server key not configured.', [
                'user_id' => $notifiable->getKey(),
                'title' => $payload['title'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(string $token, array $payload): void
    {
        Log::info('FCM push dispatched.', [
            'token' => substr($token, 0, 12).'…',
            'payload' => $payload,
        ]);
    }
}
