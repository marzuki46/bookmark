<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\LoginCodeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

final class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $isAdmin = false;

    public ?string $issuedCode = null;

    public ?string $issuedForEmail = null;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $user = User::query()->findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->isAdmin = (bool) $user->is_admin;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'isAdmin' => ['boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'is_admin' => $data['isAdmin'],
        ];

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->forceFill($payload)->save();
            activity('admin-user')->causedBy(auth()->user())->log("Mengubah akun {$user->email}");
            $this->flash('Akun diperbarui.');
        } else {
            $password = $data['password'] ?: Str::random(16);
            $user = User::query()->create([
                ...$payload,
                'email_verified_at' => now(),
                'setup_completed' => true,
                'password' => Hash::make($password),
            ]);
            activity('admin-user')->causedBy(auth()->user())->log("Membuat akun {$user->email}");
            $this->flash('Akun dibuat. Kode login bisa langsung diterbitkan.');
        }

        $this->resetForm();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function issueCode(int $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->issuedCode = app(LoginCodeService::class)->issueFor($user);
        $this->issuedForEmail = $user->email;

        activity('admin-user')->causedBy(auth()->user())->log("Menerbitkan kode login baru untuk {$user->email}");
    }

    public function clearIssuedCode(): void
    {
        $this->issuedCode = null;
        $this->issuedForEmail = null;
    }

    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.admin.user-manager', ['users' => $this->users]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->isAdmin = false;
    }

    private function flash(string $message): void
    {
        $this->statusMessage = $message;
        $this->statusType = 'success';
    }
}
