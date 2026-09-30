<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Affirmation;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class AffirmationManager extends Component
{
    public ?int $editingId = null;

    public string $slot = 'pagi';

    public string $slotFilter = '';

    public bool $showInactive = false;

    public string $variant = '';

    public string $content = '';

    public bool $isActive = true;

    public bool $showForm = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->clearValidation();
    }

    public function edit(int $id): void
    {
        $message = Affirmation::query()->findOrFail($id);

        $this->editingId = $message->id;
        $this->slot = $message->slot;
        $this->variant = (string) $message->variant;
        $this->content = $message->content;
        $this->isActive = (bool) $message->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'slot' => ['required', Rule::in(['pagi', 'malam', 'bulanan'])],
            'variant' => ['nullable', 'string', 'max:30', Rule::requiredIf($this->slot === 'malam')],
            'content' => ['required', 'string', 'max:2000'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'slot' => $data['slot'],
            'variant' => $data['variant'] ?: null,
            'content' => $data['content'],
            'is_active' => $data['isActive'],
        ];

        if ($this->editingId) {
            Affirmation::query()->whereKey($this->editingId)->update($payload);
            activity('admin-affirmation')->causedBy(auth()->user())->log("Mengubah penyemangat #{$this->editingId}");
            $this->flash('Penyemangat diperbarui.');
        } else {
            Affirmation::query()->create($payload);
            activity('admin-affirmation')->causedBy(auth()->user())->log('Membuat penyemangat baru');
            $this->flash('Penyemangat dibuat.');
        }

        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $message = Affirmation::query()->findOrFail($id);
        $message->forceFill(['is_active' => ! $message->is_active])->save();
        $this->flash('Status penyemangat berubah.');
    }

    public function destroy(int $id): void
    {
        Affirmation::query()->whereKey($id)->delete();
        activity('admin-affirmation')->causedBy(auth()->user())->log("Menghapus penyemangat #{$id}");
        $this->flash('Penyemangat dihapus.');
    }

    public function getMessagesProperty()
    {
        return Affirmation::query()
            ->when($this->slotFilter !== '', fn ($query) => $query->where('slot', $this->slotFilter))
            ->when(! $this->showInactive, fn ($query) => $query->where('is_active', true))
            ->orderBy('slot')
            ->orderBy('variant')
            ->orderByDesc('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.affirmation-manager', ['messages' => $this->messages]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->slot = 'pagi';
        $this->variant = '';
        $this->content = '';
        $this->isActive = true;
        $this->showForm = false;
    }

    private function flash(string $message): void
    {
        $this->statusMessage = $message;
        $this->statusType = 'success';
    }
}