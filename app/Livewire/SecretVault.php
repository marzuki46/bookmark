<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Item;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

final class SecretVault extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'all';

    public bool $showModal = false;

    public bool $editMode = false;

    public ?int $editingId = null;

    public bool $unlocked = false;

    public string $masterPassword = '';

    public string $formTitle = '';

    public string $formContent = '';

    public string $formCategory = '';

    public string $formUsername = '';

    public string $formPassword = '';

    public string $formUrl = '';

    public string $formNotes = '';

    public string $unlockError = '';

    public int $unlockAttempts = 0;

    public string $statusMessage = '';

    public string $statusType = 'success';

    protected string $paginationTheme = 'tailwind';

    private const MAX_UNLOCK_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

    public function rules(): array
    {
        return [
            'formTitle' => 'required|string|max:255',
            'formContent' => 'nullable|string|max:5000',
            'formCategory' => 'nullable|string|max:100',
            'formUsername' => 'nullable|string|max:255',
            'formPassword' => 'nullable|string|max:255',
            'formUrl' => 'nullable|string|max:2048',
            'formNotes' => 'nullable|string|max:2000',
        ];
    }

    public function unlock(): void
    {
        $this->unlockError = '';

        // Rate limit check
        $lockKey = 'vault_lockout:'.auth()->id();
        $lockedUntil = cache()->get($lockKey);
        if ($lockedUntil && now()->timestamp < $lockedUntil) {
            $seconds = $lockedUntil - now()->timestamp;
            $this->unlockError = "Too many attempts. Try again in {$seconds} seconds.";

            return;
        }

        if (empty($this->masterPassword)) {
            $this->unlockError = 'Please enter your PIN.';

            return;
        }

        $user = auth()->user();

        if (! $user || ! $user->pin_hash) {
            $this->unlockError = 'PIN not set. Please complete setup.';

            return;
        }

        if (! Hash::check($this->masterPassword, $user->pin_hash)) {
            $this->unlockAttempts++;
            $attempts = $this->unlockAttempts;

            if ($attempts >= self::MAX_UNLOCK_ATTEMPTS) {
                cache()->put($lockKey, now()->timestamp + self::LOCKOUT_SECONDS, self::LOCKOUT_SECONDS);
                $this->unlockError = 'Too many failed attempts. Locked for 5 minutes.';
                $this->unlockAttempts = 0;
            } else {
                $remaining = self::MAX_UNLOCK_ATTEMPTS - $attempts;
                $this->unlockError = "Wrong PIN. {$remaining} attempts remaining.";
            }

            return;
        }

        $this->unlocked = true;
        $this->masterPassword = '';
        $this->unlockAttempts = 0;
        cache()->forget($lockKey);
    }

    public function lock(): void
    {
        $this->unlocked = false;
        $this->masterPassword = '';
        $this->unlockError = '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'secret')->findOrFail($id);
        $this->editingId = $id;
        $this->editMode = true;
        $this->formTitle = $item->title ?? '';
        $this->formContent = $this->decryptField($item->metadata['content'] ?? '');
        $this->formCategory = $item->metadata['category'] ?? '';
        $this->formUsername = $this->decryptField($item->metadata['username'] ?? '');
        $this->formPassword = $this->decryptField($item->metadata['password'] ?? '');
        $this->formUrl = $item->metadata['url'] ?? '';
        $this->formNotes = $this->decryptField($item->metadata['notes'] ?? '');
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'type' => 'secret',
            'title' => Str::limit($this->formTitle, 255, ''),
            'content' => null,
            'metadata' => array_filter([
                'category' => $this->formCategory ?: null,
                'username' => $this->encryptField($this->formUsername),
                'password' => $this->encryptField($this->formPassword),
                'url' => $this->formUrl ?: null,
                'notes' => $this->encryptField($this->formNotes),
            ]),
        ];

        if ($this->editMode && $this->editingId) {
            $item = Item::where('user_id', auth()->id())->where('type', 'secret')->findOrFail($this->editingId);
            $item->update($data);
        } else {
            Item::create($data);
        }

        $this->closeModal();
        $this->statusMessage = 'Secret saved securely.';
        $this->statusType = 'success';
    }

    public function trash(int $id): void
    {
        $item = Item::where('user_id', auth()->id())->where('type', 'secret')->findOrFail($id);
        $item->delete();
        $this->statusMessage = 'Secret deleted.';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        $query = Item::where('user_id', auth()->id())->where('type', 'secret');

        if ($this->filter !== 'all' && $this->filter !== '') {
            $query->where('metadata->category', $this->filter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%");
            });
        }

        $secrets = $query->latest()->paginate(12);

        $categories = Item::where('user_id', auth()->id())
            ->where('type', 'secret')
            ->whereNotNull('metadata')
            ->get()
            ->pluck('metadata.category')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $stats = [
            'total' => Item::where('user_id', auth()->id())->where('type', 'secret')->count(),
        ];

        return view('livewire.secret-vault', compact('secrets', 'categories', 'stats'));
    }

    private function resetForm(): void
    {
        $this->formTitle = '';
        $this->formContent = '';
        $this->formCategory = '';
        $this->formUsername = '';
        $this->formPassword = '';
        $this->formUrl = '';
        $this->formNotes = '';
        $this->editingId = null;
        $this->editMode = false;
        $this->clearValidation();
    }

    private function encryptField(string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Crypt::encryptString($value);
    }

    private function decryptField(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return '*** decryption failed ***';
        }
    }
}
