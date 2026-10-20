<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\PromoCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Livewire\Component;

final class PromoCodeManager extends Component
{
    public string $name = '';
    public string $durationDays = '30';
    public string $maxRedemptions = '';
    public string $startsAt = '';
    public string $expiresAt = '';
    public bool $isActive = true;
    public string $createdCode = '';
    public string $statusMessage = '';

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'durationDays' => ['required', 'integer', 'in:30,60'],
            'maxRedemptions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'isActive' => ['boolean'],
        ]);

        $plainCode = 'CUAN-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        PromoCode::query()->create([
            'name' => $data['name'],
            'code_hash' => hash('sha256', $plainCode),
            'code_encrypted' => Crypt::encryptString($plainCode),
            'duration_days' => (int) $data['durationDays'],
            'max_redemptions' => $data['maxRedemptions'] === '' ? null : (int) $data['maxRedemptions'],
            'starts_at' => $data['startsAt'] ?: null,
            'expires_at' => $data['expiresAt'] ?: null,
            'is_active' => (bool) $data['isActive'],
            'created_by' => auth()->id(),
        ]);

        $this->createdCode = $plainCode;
        $this->statusMessage = 'Promo dibuat. Simpan kode ini dan bagikan melalui chat.';
        $this->reset(['name', 'durationDays', 'maxRedemptions', 'startsAt', 'expiresAt']);
        $this->durationDays = '30';
    }

    public function toggle(int $id): void
    {
        $promo = PromoCode::query()->findOrFail($id);
        $promo->update(['is_active' => ! $promo->is_active]);
        $this->statusMessage = 'Status promo diperbarui.';
    }

    public function render()
    {
        return view('livewire.admin.promo-code-manager', [
            'promos' => PromoCode::query()->latest()->limit(50)->get(),
        ]);
    }
}
