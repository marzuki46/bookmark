<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\FailedLoginLog;
use App\Models\IpBlock;
use Livewire\Component;

class IpBlocker extends Component
{
    public string $newIp = '';

    public ?string $blockReason = null;

    public int $selectedId = 0;

    protected function rules(): array
    {
        return [
            'newIp' => 'required|ip',
            'blockReason' => 'nullable|string|max:500',
        ];
    }

    public function blockIp(): void
    {
        $this->validate();

        if (IpBlock::isIpBlocked($this->newIp)) {
            session()->flash('error', 'IP '.$this->newIp.' sudah diblokir.');

            return;
        }

        IpBlock::create([
            'ip_address' => $this->newIp,
            'reason' => $this->blockReason,
            'blocked_at' => now(),
            'is_active' => true,
        ]);

        $this->newIp = '';
        $this->blockReason = null;

        session()->flash('success', 'IP berhasil diblokir.');
    }

    public function unblockIp(int $id): void
    {
        $ipBlock = IpBlock::findOrFail($id);
        $ipBlock->update(['is_active' => false]);

        session()->flash('success', 'IP '.$ipBlock->ip_address.' berhasil dibuka blokirnya.');
    }

    public function deleteLog(int $id): void
    {
        FailedLoginLog::findOrFail($id)->delete();
    }

    public function clearOldLogs(): void
    {
        FailedLoginLog::where('created_at', '<', now()->subDays(30))->delete();

        session()->flash('success', 'Log lebih dari 30 hari berhasil dihapus.');
    }

    public function render()
    {
        $activeBlocks = IpBlock::where('is_active', true)
            ->orderByDesc('blocked_at')
            ->get();

        $inactiveBlocks = IpBlock::where('is_active', false)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $recentLogs = FailedLoginLog::orderByDesc('created_at')
            ->limit(50)
            ->get();

        $stats = [
            'total_blocked' => IpBlock::where('is_active', true)->count(),
            'total_attempts_24h' => FailedLoginLog::where('created_at', '>=', now()->subDay())->count(),
            'unique_ips_24h' => FailedLoginLog::where('created_at', '>=', now()->subDay())->distinct('ip_address')->count('ip_address'),
            'auto_blocks_24h' => IpBlock::where('is_active', true)->where('blocked_at', '>=', now()->subDay())->count(),
        ];

        return view('livewire.ip-blocker', [
            'activeBlocks' => $activeBlocks,
            'inactiveBlocks' => $inactiveBlocks,
            'recentLogs' => $recentLogs,
            'stats' => $stats,
        ]);
    }
}
