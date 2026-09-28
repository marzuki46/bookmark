<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\FamilyInsight;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Weekly family insight push.
 *
 * The insight itself lives in family_insights, so the app can always show it by
 * polling even when no push channel is available. This notification is purely
 * the wake-up tap; it deliberately carries no financial figures in the payload
 * beyond the message, since a lock-screen preview is not a private place.
 */
class FamilyInsightNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly FamilyInsight $insight) {}

    /**
     * Always resolves to a real channel.
     *
     * Laravel discards a notification outright when via() returns an empty
     * array, so "FCM not configured yet" must never mean "never evaluated".
     * FcmChannel itself stays inert until credentials exist, and the app reads
     * the persisted family_insights row on open as the polling fallback.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['fcm'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'family_insight',
            'insight_id' => $this->insight->id,
            'family_id' => $this->insight->family_id,
            'scope' => $this->insight->scope,
            'tone' => $this->insight->tone,
            'message' => $this->insight->message,
            'week_key' => $this->insight->week_key,
        ];
    }

    public function toArrayForFcm(object $notifiable): array
    {
        return [
            'title' => $this->insight->scope === 'family' ? 'Kesehatan Keuangan Keluarga' : 'Catatan untukmu',
            'body' => $this->insight->message,
            'tone' => $this->insight->tone,
            'data' => $this->toArray($notifiable),
        ];
    }
}
