<?php

declare(strict_types=1);

namespace App\Providers;

use App\Notifications\FcmChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Laravel's ChannelManager ships with only mail, database and
        // broadcast drivers, so the family push needs an explicit channel.
        Notification::resolved(function (ChannelManager $manager): void {
            $manager->extend('fcm', fn (): FcmChannel => new FcmChannel);
        });
    }
}
