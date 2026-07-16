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

    public string $noteTitle = '';

    public bool $generating = false;

    public bool $saved = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public int $timeout = 120;

    public function generate(): void
    {
        $text = trim($this->meetingText);

        if ($text === '') {
            $this->statusMessage = 'Masukkan teks rapat terlebih dahulu.';
            $this->statusType = 'error';

            return;
        }

        $this->generating = true;
        $this->saved = false;
        $this->statusMessage = '';

        try {
            $chatService = new AiChatService(auth()->id());
            $this->result = $chatService->chat("notulensi {$text}");

            if (str_starts_with($this->result, 'AI belum dikonfigurasi') || str_starts_with($this->result, 'AI error')) {
                $this->statusMessage = $this->result;
                $this->statusType = 'error';
                $this->result = '';
            }
        } catch (\Exception $e) {
            $this->statusMessage = 'Error: '.$e->getMessage();
            $this->statusType = 'error';
        }

        $this->generating = false;
    }

    public function saveAsNote(): void
    {
        $content = trim($this->result);

        if ($content === '') {
            $this->statusMessage = 'Tidak ada hasil untuk disimpan.';
            $this->statusType = 'error';

            return;
        }

        $title = trim($this->noteTitle) ?: 'Notulensi - '.now()->format('d M Y H:i');

        Item::create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'title' => $title,
            'content' => $content,
            'favorite' => true,
        ]);

        $this->saved = true;
        $this->statusMessage = 'Notulensi berhasil disimpan sebagai Note.';
        $this->statusType = 'success';
    }

    public function clearAll(): void
    {
        $this->meetingText = '';
        $this->result = '';
        $this->noteTitle = '';
        $this->saved = false;
        $this->statusMessage = '';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.notulensi-page');
    }
}
