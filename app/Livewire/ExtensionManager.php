<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

final class ExtensionManager extends Component
{
    public bool $showCreateForm = false;

    public string $newTokenName = 'chrome-extension';

    public ?string $createdTokenValue = null;

    public function getTokensProperty()
    {
        return auth()->user()->tokens()->latest()->get();
    }

    public function createToken(): void
    {
        $this->validate([
            'newTokenName' => 'required|string|max:255',
        ]);

        $existing = auth()->user()->tokens()->where('name', $this->newTokenName)->first();
        if ($existing) {
            $this->dispatch('show-toast', ['message' => 'A token with this name already exists.', 'type' => 'error']);
            $this->showCreateForm = false;

            return;
        }

        $token = auth()->user()->createToken($this->newTokenName);
        $this->createdTokenValue = $token->plainTextToken;
        $this->showCreateForm = false;
    }

    public function revokeToken(int $id): void
    {
        auth()->user()->tokens()->where('id', $id)->delete();
        $this->createdTokenValue = null;
    }

    public function dismissToken(): void
    {
        $this->createdTokenValue = null;
    }

    public function render()
    {
        return view('livewire.extension-manager');
    }
}
