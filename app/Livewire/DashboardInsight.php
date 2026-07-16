<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Services\AiChatService;
use Livewire\Component;

final class DashboardInsight extends Component
{
    public string $insight = '';

    public bool $loading = false;

    public bool $generated = false;

    public function generateInsight(): void
    {
        $this->loading = true;

        $chatService = new AiChatService(auth()->id());
        $this->insight = $chatService->chat('Buat ringkasan insight dari semua data knowledge hub saya. Sertakan: topik utama, pola data, suggestion untuk organizir lebih baik, dan hal menarik yang ditemukan. Jawab dalam Bahasa Indonesia yang singkat dan actionable.');

        $this->generated = true;
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.dashboard-insight');
    }
}
