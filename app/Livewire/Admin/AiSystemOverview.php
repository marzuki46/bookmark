<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AiSummary;
use App\Models\FamilyAiUsage;
use Livewire\Component;

final class AiSystemOverview extends Component
{
    public function render()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $providers = [
            [
                'name' => 'AI Hub (OpenAI-compatible)',
                'endpoint' => (string) config('services.ai.api_url'),
                'model' => (string) config('services.ai.model'),
                'configured' => filled(config('services.ai.api_key')),
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
