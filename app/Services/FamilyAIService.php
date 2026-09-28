<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * AI analysis for the Family finance module using NVIDIA NIM
 * (OpenAI-compatible endpoint, see config/services.php -> nvidia).
 * Always falls back to a rule-based score so the feature works offline.
 */
final class FamilyAIService
{
    private string $apiUrl;

    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiUrl = config('services.nvidia.api_url') ?: 'https://integrate.api.nvidia.com/v1';
        // Cast rather than rely on the default: a present-but-null config value
        // would otherwise blow up on the string-typed property instead of
        // degrading to the rule-based fallback.
        $this->apiKey = (string) (config('services.nvidia.api_key') ?: '');
        $this->model = config('services.nvidia.model') ?: 'deepseek-ai/deepseek-r1';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Rule-based financial health score (0-100) + structured recommendations.
     */
    public function healthScore(Family $family): array
    {
        $income = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'income')->where('date', '>=', now()->startOfMonth())->sum('amount');
        $expense = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')->where('date', '>=', now()->startOfMonth())->sum('amount');

        $allocator = new FamilyAllocationService;
        $avgExpense = $allocator->averageMonthlyExpense($family);
        $emergencyTarget = $allocator->emergencyFundTarget($family);

        $emergencyCurrent = (float) FamilyGoal::forFamily($family->id)
            ->where('type', 'emergency_fund')->sum('current_amount');

        $debt = FamilyDebt::forFamily($family->id)->where('type', 'payable')->where('status', '!=', 'settled')->get();
        $totalDebt = $debt->sum(fn ($d) => $d->remaining);
        $debtInstallment = (float) $debt->sum('installment');

        $score = 60.0;

        if ($income > 0) {
            $savingsRate = ($income - $expense) / $income * 100;
            if ($savingsRate >= 20) {
                $score += 20;
            } elseif ($savingsRate >= 10) {
                $score += 10;
            } elseif ($savingsRate >= 0) {
                $score += 5;
            } else {
                $score -= 15;
            }
        }

        if ($emergencyTarget > 0) {
            $coverage = $emergencyCurrent / $emergencyTarget * 100;
            if ($coverage >= 100) {
                $score += 15;
            } elseif ($coverage >= 50) {
                $score += 8;
            } elseif ($coverage > 0) {
                $score += 3;
            } else {
                $score -= 10;
            }
        }

        if ($totalDebt > 0) {
            if ($income > 0) {
                $debtRatio = $totalDebt / ($income * 12) * 100;
                if ($debtRatio <= 20) {
                    $score += 5;
                } elseif ($debtRatio > 50) {
                    $score -= 10;
                }
            }

            if ($debtInstallment > 0 && $income > 0 && ($debtInstallment / max($income, 1) > 0.5)) {
                $score -= 10;
            }
        }

        if ($income > 0 && $expense > 0) {
            $needs = $expense / max($income, 1) * 100;
            if ($needs > 70) {
                $score -= 5;
            }
        }

        $score = (int) round(max(0, min(100, $score)));

        $recommendations = $this->ruleRecommendations($family, $income, $expense, $emergencyCurrent, $emergencyTarget, $totalDebt);

        return [
            'score' => $score,
            'grade' => $score >= 80 ? 'Sangat Sehat' : ($score >= 60 ? 'Cukup Sehat' : ($score >= 40 ? 'Perlu Perhatian' : 'Kritis')),
            'income' => $income,
            'expense' => $expense,
            'savings' => max(0, $income - $expense),
            'emergency_current' => $emergencyCurrent,
            'emergency_target' => $emergencyTarget,
            'total_debt' => $totalDebt,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate narrative analysis via NVIDIA. Falls back to rule summary.
     */
    public function analyze(Family $family, array $metrics = []): string
    {
        if (! $this->isConfigured()) {
            return $this->ruleSummary($metrics);
        }

        $metrics = $metrics ?: $this->healthScore($family);

        $prompt = "Kamu adalah penasihat keuangan keluarga yang ringkas dan praktis.
Data keuangan keluarga bulan ini:
- Skor kesehatan: {$metrics['score']}/100 ({$metrics['grade']})
- Pemasukan: Rp ".number_format((float) $metrics['income'], 0, ',', '.').'
- Pengeluaran: Rp '.number_format((float) $metrics['expense'], 0, ',', '.').'
- Dana darurat: Rp '.number_format((float) $metrics['emergency_current'], 0, ',', '.').' dari target Rp '.number_format((float) $metrics['emergency_target'], 0, ',', '.').'
- Total hutang tersisa: Rp '.number_format((float) $metrics['total_debt'], 0, ',', '.')."

Beri 3-4 saran prioritas dalam Bahasa Indonesia. Format: setiap saran satu baris, dimulai dengan '•'. Fokus pada hal paling mendesak, bukan saran umum.";

        try {
            $result = $this->ask($prompt, 400);

            return $result ?: $this->ruleSummary($metrics);
        } catch (\Exception $e) {
            logger()->error('FamilyAI analyze failed', ['error' => $e->getMessage()]);

            return $this->ruleSummary($metrics);
        }
    }

    /**
     * Runs a single prompt through the configured provider.
     *
     * Exposed so other services (e.g. the weekly insight generator) reuse the
     * same credentials, caching and error handling instead of reimplementing
     * an HTTP client. Returns null when unconfigured or on failure — callers
     * are expected to have a rule-based fallback.
     */
    public function chat(string $prompt, int $maxTokens = 200): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            return $this->ask($prompt, $maxTokens);
        } catch (\Exception $e) {
            logger()->error('FamilyAI chat failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function ask(string $prompt, int $maxTokens = 400): ?string
    {
        $cacheKey = 'family_ai_'.md5($prompt);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $url = rtrim($this->apiUrl, '/').'/chat/completions';

        $response = Http::timeout(45)
            ->connectTimeout(10)
            ->withToken($this->apiKey)
            ->post($url, [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => 'Kamu adalah penasihat keuangan keluarga yang membantu, ringkas, dan berbicara dalam Bahasa Indonesia.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.4,
                'stream' => false,
            ]);

        if (! $response->successful()) {
            logger()->warning('NVIDIA AI error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        }

        $text = $response->json('choices.0.message.content');

        if ($text) {
            Cache::put($cacheKey, trim($text), 600);
        }

        return $text ? trim($text) : null;
    }

    private function ruleSummary(array $metrics): string
    {
        $lines = [];
        $lines[] = "Skor kesehatan keuangan: {$metrics['score']}/100 ({$metrics['grade']}).";
        $lines[] = 'Pemasukan Rp '.number_format((float) $metrics['income'], 0, ',', '.').' vs pengeluaran Rp '.number_format((float) $metrics['expense'], 0, ',', '.').'.';

        foreach ($metrics['recommendations'] as $rec) {
            $lines[] = '• '.$rec;
        }

        return implode("\n", $lines);
    }

    private function ruleRecommendations(Family $family, float $income, float $expense, float $emergencyCurrent, float $emergencyTarget, float $totalDebt): array
    {
        $recs = [];

        if ($emergencyTarget > 0 && $emergencyCurrent < $emergencyTarget) {
            $recs[] = 'Prioritaskan dana darurat: sisihkan '.round(max(0, $emergencyTarget - $emergencyCurrent), 0).' lagi (target 3x pengeluaran bulanan).';
        }

        if ($income > 0) {
            $savingsRate = ($income - $expense) / $income * 100;
            if ($savingsRate < 10) {
                $recs[] = 'Tingkatkan tabungan ke minimal 10-20% dari pemasukan; evaluasi pengeluaran non-esensial.';
            }
        } elseif ($income > 0 && $expense > $income) {
            $recs[] = 'Pengeluaran melebihi pemasukan bulan ini — cek budget dan kurangi biaya yang bisa dipangkas.';
        }

        if ($totalDebt > 0) {
            $allocator = new FamilyAllocationService;
            $order = $allocator->debtPayoffOrder($family, 'avalanche');
            if (! empty($order)) {
                $top = $order[0]['debt']->name;
                $recs[] = 'Lunasi hutang prioritas: "'.$top.'" (strategi avalanche: bunga tertinggi lebih dulu).';
            }
        }

        if ($expense > 0 && $income > 0) {
            $needs = $expense / max($income, 1) * 100;
            if ($needs > 70) {
                $recs[] = 'Beban pengeluaran >70% pemasukan. Pertimbangkan prinsip 50/30/20 agar ada ruang untuk goals.';
            }
        }

        if (empty($recs)) {
            $recs[] = 'Keuangan keluarga dalam kondisi sehat. Pertahankan pola disiplin menabung ini.';
        }

        return $recs;
    }
}
