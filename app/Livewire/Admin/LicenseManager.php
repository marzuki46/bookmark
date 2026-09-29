<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Livewire\Component;

/**
 * Admin panel to manage a family's paid license. A license belongs to the
 * family (not the user) and covers every member: grant a fresh license,
 * extend by a plan (window carried forward), set a custom expiry, or revoke.
 */
final class LicenseManager extends Component
{
    public ?int $editFamilyId = null;

    public ?int $grantPlanId = null;

    public ?string $editExpiresAt = null;

    public string $mode = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamiliesProperty()
    {
        return Family::query()
            ->with(['owner:id,name,email'])
            ->withCount('members')
            ->orderBy('name')
            ->paginate(15);
    }

    public function getPlansProperty()
    {
        return SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->get();
    }

    public function openGrant(int $familyId): void
    {
        $this->resetEdit();
        $this->editFamilyId = $familyId;
        $this->mode = 'grant';
    }

    public function openExtend(int $familyId): void
    {
        $this->resetEdit();
        $this->editFamilyId = $familyId;
        $this->mode = 'extend';

        $family = Family::with('owner')->findOrFail($familyId);
        $current = $this->latest($family);
        $this->editExpiresAt = $current?->expires_at?->format('Y-m-d');
    }

    public function cancelEdit(): void
    {
        $this->resetEdit();
    }

    /**
     * Grant or replace the family's license with the chosen plan.
     */
    public function grant(): void
    {
        $data = $this->validate([
            'editFamilyId' => ['required', 'integer', 'exists:families,id'],
            'grantPlanId' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $family = Family::query()->with('owner')->findOrFail($data['editFamilyId']);
        $plan = SubscriptionPlan::query()->findOrFail($data['grantPlanId']);

        $subscription = app(SubscriptionService::class)->activate($family->owner, $plan);

        $until = $subscription->expires_at ? ' sampai '.$subscription->expires_at->format('d M Y') : ' (seumur hidup)';

        activity('admin-license')->causedBy(auth()->user())
            ->log("Memberi lisensi {$plan->name} ke keluarga {$family->name}");

        $this->flash('Lisensi '.$plan->name.' aktif untuk '.$family->name.$until.'.');
        $this->resetEdit();
    }

    /**
     * Extend the active license by a plan's duration. Uses the same
     * SubscriptionService invariant as a renewal: continues the current
     * window if it is still valid, otherwise starts from today.
     */
    public function extend(): void
    {
        $data = $this->validate([
            'editFamilyId' => ['required', 'integer', 'exists:families,id'],
            'grantPlanId' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $family = Family::query()->with('owner')->findOrFail($data['editFamilyId']);
        $plan = SubscriptionPlan::query()->findOrFail($data['grantPlanId']);

        $subscription = app(SubscriptionService::class)->activate($family->owner, $plan);

        $until = $subscription->expires_at ? ' sampai '.$subscription->expires_at->format('d M Y') : ' (seumur hidup)';

        activity('admin-license')->causedBy(auth()->user())
            ->log("Perpanjang lisensi {$plan->name} keluarga {$family->name}");

        $this->flash('Lisensi diperpanjang: '.$plan->name.$until.'.');
        $this->resetEdit();
    }

    /**
     * Set a custom expiry date on the family's active license. Leaving the
     * field empty converts the license to lifetime.
     */
    public function saveExpiry(): void
    {
        $data = $this->validate([
            'editFamilyId' => ['required', 'integer', 'exists:families,id'],
            'editExpiresAt' => ['nullable', 'date'],
        ]);

        if ($data['editExpiresAt'] !== null && $data['editExpiresAt'] < now()->format('Y-m-d')) {
            $this->addError('editExpiresAt', 'Tanggal berakhir tidak boleh sebelum hari ini.');

            return;
        }

        $family = Family::query()->with('owner')->findOrFail($data['editFamilyId']);
        $current = $this->latest($family);

        if (! $current) {
            $this->statusType = 'error';
            $this->statusMessage = 'Keluarga belum memiliki lisensi. Pilih "Beri Lisensi" dahulu.';

            return;
        }

        $current->update(['expires_at' => $data['editExpiresAt'] ?: null]);

        activity('admin-license')->causedBy(auth()->user())
            ->log('Ubah masa aktif lisensi keluarga '.$family->name);

        $until = $data['editExpiresAt'] ? ' berakhir '.$data['editExpiresAt'] : ' menjadi seumur hidup';
        $this->flash('Masa aktif diubah'.$until.'.');
        $this->resetEdit();
    }

    public function revoke(int $familyId): void
    {
        $family = Family::query()->with('owner')->findOrFail($familyId);

        app(SubscriptionService::class)->revoke($family->owner);

        activity('admin-license')->causedBy(auth()->user())
            ->log("Mencabut lisensi keluarga {$family->name}");

        $this->flash('Lisensi '.$family->name.' dicabut.');
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    private function latest(Family $family): ?Subscription
    {
        return Subscription::query()->with('plan')
            ->where('family_id', $family->id)
            ->latest('id')
            ->first();
    }

    private function resetEdit(): void
    {
        $this->editFamilyId = null;
        $this->grantPlanId = null;
        $this->editExpiresAt = null;
        $this->mode = '';
        $this->clearValidation();
    }

    private function flash(string $message): void
    {
        $this->statusMessage = $message;
        $this->statusType = 'success';
    }

    public function render()
    {
        return view('livewire.admin.license-manager', [
            'families' => $this->families,
            'plans' => $this->plans,
        ]);
    }
}
