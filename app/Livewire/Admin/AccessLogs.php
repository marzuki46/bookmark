<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\FailedLoginLog;
use App\Models\RequestLog;
use Livewire\Component;
use Livewire\WithPagination;

final class AccessLogs extends Component
{
    use WithPagination;

    public string $userSearch = '';

    public string $method = '';

    public string $status = '';

    public string $date = '';

    public function updatedUserSearch(): void
    {
        $this->resetPage();
    }

    public function getRequestsProperty()
    {
        return RequestLog::query()
            ->with('user')
            ->when($this->method !== '', fn ($q) => $q->where('method', $this->method))
            ->when($this->status !== '', fn ($q) => $q->where('status_code', (int) $this->status))
            ->when($this->date !== '', fn ($q) => $q->whereDate('created_at', $this->date))
            ->when($this->userSearch !== '', function ($q) {
                $q->whereHas('user', fn ($q) => $q
                    ->where('name', 'like', "%{$this->userSearch}%")
                    ->orWhere('email', 'like', "%{$this->userSearch}%"));
            })
            ->latest('created_at')
            ->paginate(30);
    }

    public function getFailedLoginsProperty()
    {
        return FailedLoginLog::query()
            ->latest()
            ->limit(20)
            ->get();
    }

    public function clearFilters(): void
    {
        $this->userSearch = '';
        $this->method = '';
        $this->status = '';
        $this->date = '';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.access-logs', [
            'requests' => $this->requests,
            'failedLogins' => $this->failedLogins,
        ]);
    }
}
