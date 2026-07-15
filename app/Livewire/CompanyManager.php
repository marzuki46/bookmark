<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;

final class CompanyManager extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formPicName = '';

    public string $formAddress = '';

    public string $formEmail = '';

    public string $formPhone = '';

    public string $formBank = '';

    public string $formAccNum = '';

    public string $formAccName = '';

    public $logo = null;

    public $signature = null;

    public function mount(): void
    {
        //
    }

    public function getCompaniesProperty()
    {
        return Company::where('user_id', auth()->id())->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $company = Company::where('user_id', auth()->id())->findOrFail($id);

        $this->editingId = $company->id;
        $this->formName = $company->name;
        $this->formPicName = $company->pic_name;
        $this->formAddress = $company->address;
        $this->formEmail = $company->email;
        $this->formPhone = $company->phone;
        $this->formBank = $company->bank_name;
        $this->formAccNum = $company->acc_number;
        $this->formAccName = $company->acc_name;
        $this->logo = null;
        $this->signature = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'formName' => 'required|string|max:255',
            'formPicName' => 'nullable|string|max:255',
            'formAddress' => 'nullable|string|max:500',
            'formEmail' => 'nullable|email|max:255',
            'formPhone' => 'nullable|string|max:50',
            'formBank' => 'nullable|string|max:100',
            'formAccNum' => 'nullable|string|max:50',
            'formAccName' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'signature' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->formName,
            'pic_name' => $this->formPicName,
            'address' => $this->formAddress,
            'email' => $this->formEmail,
            'phone' => $this->formPhone,
            'bank_name' => $this->formBank,
            'acc_number' => $this->formAccNum,
            'acc_name' => $this->formAccName,
        ];

        if ($this->editingId) {
            $company = Company::where('user_id', auth()->id())->findOrFail($this->editingId);

            if ($this->logo) {
                if ($company->logo_path && \Storage::disk('public')->exists($company->logo_path)) {
                    \Storage::disk('public')->delete($company->logo_path);
                }
                $data['logo_path'] = $this->logo->store('companies', 'public');
            }

            if ($this->signature) {
                if ($company->signature_path && \Storage::disk('public')->exists($company->signature_path)) {
                    \Storage::disk('public')->delete($company->signature_path);
                }
                $data['signature_path'] = $this->signature->store('companies', 'public');
            }

            $company->update($data);
        } else {
            if ($this->logo) {
                $data['logo_path'] = $this->logo->store('companies', 'public');
            }

            if ($this->signature) {
                $data['signature_path'] = $this->signature->store('companies', 'public');
            }

            Company::create(array_merge($data, ['user_id' => auth()->id()]));
        }

        $this->closeForm();
    }

    public function deleteCompany(int $id): void
    {
        $company = Company::where('user_id', auth()->id())->findOrFail($id);

        if ($company->logo_path && \Storage::disk('public')->exists($company->logo_path)) {
            \Storage::disk('public')->delete($company->logo_path);
        }

        if ($company->signature_path && \Storage::disk('public')->exists($company->signature_path)) {
            \Storage::disk('public')->delete($company->signature_path);
        }

        $company->delete();
    }

    public function render()
    {
        return view('livewire.company-manager');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formPicName = '';
        $this->formAddress = '';
        $this->formEmail = '';
        $this->formPhone = '';
        $this->formBank = '';
        $this->formAccNum = '';
        $this->formAccName = '';
        $this->logo = null;
        $this->signature = null;
    }
}
