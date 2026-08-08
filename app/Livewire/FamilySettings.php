<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class FamilySettings extends Component
{
    public string $familyName = '';

    public string $inviteCode = '';

    public bool $showInviteModal = false;

    public string $joinCode = '';

    public bool $showCreateMemberModal = false;

    public string $memberName = '';

    public string $memberEmail = '';

    public string $memberPassword = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function mount(): void
    {
        $family = auth()->user()->family();
        if ($family) {
            $this->familyName = $family->name;
            $this->inviteCode = $family->invite_code ?? '';
        }
    }

    public function getMembersProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyMember::with('user')->where('family_id', $family->id)->get();
    }

    public function saveFamilyName(): void
    {
        $this->validate([
            'familyName' => 'required|string|max:100',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $family->update(['name' => $this->familyName]);
        $this->statusMessage = 'Nama keluarga berhasil disimpan.';
        $this->statusType = 'success';
    }

    public function regenerateInviteCode(): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $family->update(['invite_code' => Family::generateInviteCode()]);
        $this->inviteCode = $family->invite_code;
        $this->statusMessage = 'Kode undangan baru: '.$family->invite_code;
        $this->statusType = 'success';
    }

    public function openCreateMember(): void
    {
        $this->memberName = '';
        $this->memberEmail = '';
        $this->memberPassword = '';
        $this->showCreateMemberModal = true;
    }

    public function closeCreateMember(): void
    {
        $this->showCreateMemberModal = false;
        $this->clearValidation();
    }

    /**
     * Create a family-only account for the spouse (e.g. wife).
     * The husband provides the credentials; the wife logs in on her own.
     */
    public function createMember(): void
    {
        $this->validate([
            'memberName' => 'required|string|max:255',
            'memberEmail' => 'required|email|max:255|unique:users,email',
            'memberPassword' => ['required', Password::min(8)],
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $user = User::create([
            'name' => $this->memberName,
            'email' => $this->memberEmail,
            'password' => $this->memberPassword,
            'setup_completed' => true,
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_family_only' => true,
        ]);

        $this->statusMessage = 'Akun "'.$user->name.'" berhasil dibuat. Sampaikan email & password kepada istri.';
        $this->statusType = 'success';
        $this->closeCreateMember();
    }

    public function openInvite(): void
    {
        $this->joinCode = '';
        $this->showInviteModal = true;
    }

    public function closeInvite(): void
    {
        $this->showInviteModal = false;
        $this->joinCode = '';
        $this->clearValidation();
    }

    /**
     * Join another family via invite code (moves the current user into it).
     */
    public function joinFamily(): void
    {
        $this->validate([
            'joinCode' => 'required|string|max:20',
        ]);

        $family = Family::where('invite_code', Str::upper(trim($this->joinCode)))->first();

        if (! $family) {
            $this->statusMessage = 'Kode undangan tidak ditemukan.';
            $this->statusType = 'error';

            return;
        }

        if ($family->isMember(auth()->user())) {
            $this->statusMessage = 'Anda sudah menjadi anggota keluarga ini.';
            $this->statusType = 'error';

            return;
        }

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => auth()->id(),
            'role' => 'member',
            'is_family_only' => false,
        ]);

        $this->statusMessage = 'Berhasil bergabung ke keluarga "'.$family->name.'".';
        $this->statusType = 'success';
        $this->closeInvite();
    }

    public function removeMember(int $id): void
    {
        $family = $this->family;
        if (! $family) {
            return;
        }

        $member = FamilyMember::where('family_id', $family->id)->findOrFail($id);

        if ($member->role === 'owner' || $member->user_id === auth()->id()) {
            $this->statusMessage = 'Tidak bisa menghapus pemilik keluarga.';
            $this->statusType = 'error';

            return;
        }

        $member->delete();
        $this->statusMessage = 'Anggota berhasil dikeluarkan.';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-settings', [
            'family' => $this->family,
            'members' => $this->members,
        ]);
    }
}
