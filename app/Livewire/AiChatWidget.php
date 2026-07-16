<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AiChatService;
use Livewire\Component;

final class AiChatWidget extends Component
{
    public bool $open = false;

    public string $message = '';

    public array $messages = [];

    public bool $loading = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function send(): void
    {
        $text = trim($this->message);

        if ($text === '' || $this->loading) {
            return;
        }

        $this->messages[] = [
            'role' => 'user',
            'content' => $text,
        ];

        $this->message = '';
        $this->loading = true;

        $chatService = new AiChatService(auth()->id());

        $history = array_map(fn ($m) => [
            'role' => $m['role'],
            'content' => $m['content'],
        ], array_slice($this->messages, -10));

        $reply = $chatService->chat($text, $history);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $reply,
        ];

        $this->loading = false;
    }

    public function clearChat(): void
    {
        $this->messages = [];
    }

    public function render()
    {
        return view('livewire.ai-chat-widget');
    }
}
