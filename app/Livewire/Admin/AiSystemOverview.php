<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AiSummary;
use App\Models\FamilyAiUsage;
use App\Models\SystemSetting;
use Livewire\Component;

final class AiSystemOverview extends Component
{
    public string $apiUrl = '';
    public string $apiKey = '';
    public string $model = '';
    public bool $apiKeyConfigured = false;
    public string $statusMessage = '';

    public function mount(): void
    {
        $this->apiUrl = (string) (SystemSetting::query()->where('key', 'ai.api_url')->value('value') ?: config('services.ai.api_url'));
        $this->model = (string) (SystemSetting::query()->where('key', 'ai.model')->value('value') ?: config('services.ai.model'));
        $this->apiKeyConfigured = filled(SystemSetting::query()->where('key', 'ai.api_key')->value('value') ?: config('services.ai.api_key'));
    }

    public function save(): void
    {
        $data = $this->validate([
            'apiUrl' => ['required', 'url', 'max:500'],
            'apiKey' => ['nullable', 'string', 'max:500'],
            'model' => ['required', 'string', 'max:150'],
        ]);

        SystemSetting::query()->updateOrCreate(['key' => 'ai.api_url'], ['value' => trim($data['apiUrl'])]);
        SystemSetting::query()->updateOrCreate(['key' => 'ai.model'], ['value' => trim($data['model'])]);
        if (trim($data['apiKey']) !== '') {
            SystemSetting::query()->updateOrCreate(['key' => 'ai.api_key'], ['value' => trim($data['apiKey'])]);
            $this->apiKey = '';
            $this->apiKeyConfigured = true;
        }

        $this->statusMessage = 'Konfigurasi AI 9Router disimpan.';
    }

    public function render()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $providers = [
            [
                'name' => 'AI Hub (OpenAI-compatible)',
                'endpoint' => $this->apiUrl,
                'model' => $this->model,
                'configured' => $this->apiKeyConfigured,
                'features' => 'Bookmark summary, category, tags, chat',
            ],
            [
                'name' => 'NVIDIA NIM',
                'endpoint' => (string) config('services.nvidia.api_url'),
                'model' => (string) config('services.nvidia.model'),
                'configured' => filled(config('services.nvidia.api_key')),
                'features' => 'Analisis keuangan keluarga dan insight',
            ],
        ];

        return view('livewire.admin.ai-system-overview', [
            'providers' => $providers,
            'summaryCount' => AiSummary::query()->count(),
            'familyUsageCount' => (int) FamilyAiUsage::query()->whereDate('period_start', '>=', $monthStart)->sum('analysis_count'),
            'familyUsageFamilies' => FamilyAiUsage::query()->whereDate('period_start', '>=', $monthStart)->distinct('family_id')->count('family_id'),
        ]);
    }
}
