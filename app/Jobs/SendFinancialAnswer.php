<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendFinancialAnswer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 10;

    public function __construct(
        private readonly int $userId,
        private readonly string $sender,
        private readonly string $answer,
    ) {}

    public function handle(): void
    {
        $waService = new WhatsAppService($this->userId);
        if (! $waService->isConfigured()) {
            logger()->warning('SendFinancialAnswer: WA not configured', ['user_id' => $this->userId]);
            return;
        }

        $waService->sendMessage($this->sender, $this->answer);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('SendFinancialAnswer failed', [
            'user_id' => $this->userId,
            'sender' => $this->sender,
            'error' => $e->getMessage(),
        ]);
    }
}
