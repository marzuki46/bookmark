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
     *
     * The affordability checks are anchored on *essential* monthly needs (real
     * spending history, otherwise the essentials share of income) rather than
     * raw total expense, and debt installments are only scored on the share
     * still unpaid this month — payments already recorded as expenses are not
     * counted twice.
     */
    public function healthScore(Family $family): array
    {
        $income = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'income')->where('date', '>=', now()->startOfMonth())->sum('amount');
        $expense = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')->where('date', '>=', now()->startOfMonth())->sum('amount');

        $allocator = new FamilyAllocationService;
        $advisor = new FamilyAdvisorService;
        $avgExpense = $allocator->averageMonthlyExpense($family);
        $essentialMonthly = $advisor->essentialMonthlyExpense($family);
        $emergencyTarget = $allocator->emergencyFundTarget($family);

        $emergencyCurrent = (float) FamilyGoal::forFamily($family->id)
            ->where('type', 'emergency_fund')->sum('current_amount');

        $debt = FamilyDebt::forFamily($family->id)->where('type', 'payable')->where('status', '!=', 'settled')->get();
        $totalDebt = $debt->sum(fn ($d) => $d->remaining);
        $plannedInstallment = (float) $debt->sum('installment');
        $realizedThisMonth = $advisor->realizedDebtThisMonth($family);
        $uncoveredObligation = max(0.0, $plannedInstallment - $realizedThisMonth);

        $insufficient = $income <= 0 && $expense <= 0 && $avgExpense <= 0 && $emergencyCurrent <= 0;

        if ($insufficient) {
            return [
                'score' => 0,
                'grade' => 'Belum cukup data',
                'insufficient_data' => true,
                'income' => $income,
                'expense' => $expense,
                'savings' => 0.0,
                'essential_monthly' => 0.0,
                'emergency_current' => 0.0,
                'emergency_target' => 0.0,
                'total_debt' => 0.0,
                'planned_debt' => 0.0,
                'realized_debt_this_month' => 0.0,
                'uncovered_debt' => 0.0,
                'recommendations' => [
                    'Catat pemasukan dan pengeluaran rutin selama minimal 1 bulan agar Kang Cuan bisa menilai kesehatan keuangan.',
                ],
            ];
        }

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
        } elseif ($expense > 0) {
            // Spending without any recorded income this month.
            $score -= 15;
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

        if ($income > 0 && $totalDebt > 0) {
            $debtRatio = $totalDebt / ($income * 12) * 100;
            if ($debtRatio <= 20) {
                $score += 5;
            } elseif ($debtRatio > 50) {
                $score -= 10;
            }
        }

        // Affordability of the month's *unpaid* installments only; the paid part
        // is already reflected inside $expense and must not be charged again.
        if ($income > 0 && $uncoveredObligation > 0 && ($uncoveredObligation / $income > 0.5)) {
            $score -= 10;
        }

        // Survival check: can the family cover its essential needs from income?
        if ($income > 0 && $essentialMonthly > 0) {
            $essentialRatio = $essentialMonthly / $income * 100;
            if ($essentialRatio > 85) {
                $score -= 10;
            } elseif ($essentialRatio > 70) {
                $score -= 5;
            } elseif ($essentialRatio <= 50) {
                $score += 5;
            }
        }

        $score = (int) round(max(0, min(100, $score)));

        $recommendations = $this->ruleRecommendations(
            $family,
            $income,
            $expense,
            $essentialMonthly,
            $emergencyCurrent,
            $emergencyTarget,
            $totalDebt,
            $uncoveredObligation,
            $realizedThisMonth,
        );

        return [
            'score' => $score,
            'grade' => $score >= 80 ? 'Sangat Sehat' : ($score >= 60 ? 'Cukup Sehat' : ($score >= 40 ? 'Perlu Perhatian' : 'Kritis')),
            'insufficient_data' => false,
            'income' => $income,
            'expense' => $expense,
            'savings' => $income - $expense,
            'essential_monthly' => round($essentialMonthly, 2),
            'emergency_current' => $emergencyCurrent,
            'emergency_target' => $emergencyTarget,
            'total_debt' => $totalDebt,
            'planned_debt' => $plannedInstallment,
            'realized_debt_this_month' => $realizedThisMonth,
            'uncovered_debt' => $uncoveredObligation,
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

    private function ruleRecommendations(
        Family $family,
        float $income,
        float $expense,
        float $essentialMonthly,
        float $emergencyCurrent,
        float $emergencyTarget,
        float $totalDebt,
        float $uncoveredObligation,
        float $realizedThisMonth,
    ): array {
        $recs = [];

        if ($emergencyTarget > 0 && $emergencyCurrent < $emergencyTarget) {
            $recs[] = 'Prioritaskan dana darurat: sisihkan '.round(max(0, $emergencyTarget - $emergencyCurrent), 0).' lagi (target 3x pengeluaran bulanan).';
        }

        if ($income > 0) {
            $savingsRate = ($income - $expense) / $income * 100;
            if ($savingsRate < 10) {
                $recs[] = 'Tingkatkan tabungan ke minimal 10-20% dari pemasukan; evaluasi pengeluaran non-esensial.';
            }
        } elseif ($expense > 0) {
            $recs[] = 'Bulan ini tercatat pengeluaran tanpa pemasukan — pastikan pencatatan pemasukan lengkap.';
        }

        if ($totalDebt > 0) {
            $allocator = new FamilyAllocationService;
            $order = $allocator->debtPayoffOrder($family, 'avalanche');
            if (! empty($order)) {
                $top = $order[0]['debt']->name;
                $recs[] = 'Lunasi hutang prioritas: "'.$top.'" (strategi avalanche: bunga tertinggi lebih dulu).';
            }

            if ($uncoveredObligation <= 0 && $realizedThisMonth > 0) {
                $recs[] = 'Cicilan bulan ini sudah terbayar penuh — alihkan dana bekas cicilan ke dana darurat atau target tabungan.';
            } elseif ($income > 0 && ($uncoveredObligation / $income > 0.5)) {
                $recs[] = 'Sisa cicilan wajib bulan ini mencapai lebih dari separuh pemasukan — bicarakan restrukturisasi dengan kreditur.';
            }
        }

        if ($income > 0 && $essentialMonthly > 0) {
            $essentialRatio = $essentialMonthly / $income * 100;
            if ($essentialRatio > 85) {
                $recs[] = 'Kebutuhan pokok menghabiskan hampir semua pemasukan — cari tambahan pemasukan atau biaya tetap yang bisa ditekan.';
            } elseif ($essentialRatio > 70) {
                $recs[] = 'Kebutuhan pokok >70% pemasukan. Pertimbangkan prinsip 50/30/20 agar ada ruang untuk goals.';
            } elseif ($essentialRatio <= 50) {
                $recs[] = 'Kebutuhan pokok masih di bawah 50% pemasukan — ruang menabung untuk dana darurat dan tujuan masih terbuka lebar.';
            }
        }

        if (empty($recs)) {
            $recs[] = 'Keuangan keluarga dalam kondisi sehat. Pertahankan pola disiplin menabung ini.';
        }

        return $recs;
    }
}
