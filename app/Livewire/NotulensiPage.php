<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use App\Services\AiChatService;
use Livewire\Component;

final class NotulensiPage extends Component
{
    public string $meetingText = '';

    public string $result = '';

    public bool $generating = false;

    public bool $saved = false;

    public function generate(): void
    {
        $text = trim($this->meetingText);

        if ($text === '') {
            return;
        }

        $this->generating = true;
        $this->saved = false;

        $chatService = new AiChatService(auth()->id());
        $this->result = $chatService->chat("notulensi {$text}");

        $this->generating = false;
    }

    public function saveAsNote(): void
    {
        if ($this->result === '') {
            return;
        }

        Item::create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'title' => 'Notulensi - '.now()->format('d M Y H:i'),
            'content' => $this->result,
            'favorite' => true,
        ]);

        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.notulensi-page');
    }
}
