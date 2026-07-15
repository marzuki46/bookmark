<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class FinancialAIService
{
    private AIService $ai;

    public function __construct(?AIService $ai = null)
    {
        $this->ai = $ai ?? new AIService;
    }

    /**
     * Initialize with an existing, pre-configured AIService instance.
     * Used by the webhook controller where auth context is manually set.
     */
    public function initializeWithAI(AIService $ai): void
    {
        $this->ai = $ai;
    }

    /**
     * Parse natural language text into a financial transaction.
     * Examples:
     * - "makan 30000" → expense, Makan, 30000
     * - "gaji bulan ini 5jt" → income, Gaji, 5000000
     * - "beli bensin 100 ribu" → expense, Bensin, 100000
     */
    public function parseTransaction(string $text): ?array
    {
        if (! $this->ai->isConfigured()) {
            return $this->ruleBasedParse($text);
        }

        $prompt = 'Kamu adalah asisten keuangan. Analisis teks berikut dan ekstrak informasi transaksi keuangan.

Contoh:
- "makan 30000" → {"type":"expense","amount":30000,"description":"Makan","category":"Makanan"}
- "gaji bulan ini 5jt" → {"type":"income","amount":5000000,"description":"Gaji bulan ini","category":"Gaji"}
- "beli bensin 100 ribu" → {"type":"expense","amount":100000,"description":"Beli bensin","category":"Transportasi"}
- "beli domain 200000" → {"type":"expense","amount":200000,"description":"Beli domain","category":"Technology"}
- "freelance project 2.5jt" → {"type":"income","amount":2500000,"description":"Freelance project","category":"Freelance"}
- "jajan 15.000" → {"type":"expense","amount":15000,"description":"Jajan","category":"Makanan"}
- "bayar listrik 350 ribu" → {"type":"expense","amount":350000,"description":"Bayar listrik","category":"Utilities"}
- "dana masuk 500rb" → {"type":"income","amount":500000,"description":"Dana masuk","category":"Lainnya"}
- "ongkir 20k" → {"type":"expense","amount":20000,"description":"Ongkir","category":"Transportasi"}
- "gaji 5jt diterima" → {"type":"income","amount":5000000,"description":"Gaji","category":"Gaji"}

Aturan:
- amount harus dalam angka (tanpa titik, tanpa Rp). Konversi: jt → 000000, rb/rbu → 000, k → 000
- category harus salah satu dari: Makanan, Transportasi, Belanja, Hiburan, Kesehatan, Tagihan, Technology, Pendidikan, Gaji, Freelance, Bisnis, Investasi, Lainnya
- Jika mention "pengeluaran" atau "keluar" → expense. Jika "pemasukan" atau "masuk" atau "gaji" atau "dana" → income
- Jika tidak jelas, tebak dari konteks
- Balas HANYA dengan JSON, tanpa markdown, tanpa teks lain

Teks: "' . $text . '"';

        try {
            $result = $this->ai->askRaw($prompt, $text, 200);
            if ($result === null) {
                return $this->ruleBasedParse($text);
            }

            // Clean the result
            $result = trim($result);
            $result = preg_replace('/```json\s*/', '', $result);
            $result = preg_replace('/```\s*$/', '', $result);

            $decoded = json_decode($result, true);
            if (! is_array($decoded) || ! isset($decoded['type'], $decoded['amount'], $decoded['description'])) {
                return $this->ruleBasedParse($text);
            }

            return [
                'type' => $decoded['type'] === 'income' ? 'income' : 'expense',
                'amount' => (float) $decoded['amount'],
                'description' => $decoded['description'],
                'category' => $decoded['category'] ?? 'Lainnya',
            ];
        } catch (\Exception $e) {
            logger()->error('FinancialAI parseTransaction failed', ['error' => $e->getMessage()]);
            return $this->ruleBasedParse($text);
        }
    }

    /**
     * Rule-based fallback parsing when AI is not available.
     */
    public function ruleBasedParse(string $text): array
    {
        $text = trim($text);
        $lower = strtolower($text);

        // Determine type
        $type = 'expense';
        $incomeKeywords = ['pemasukan', 'masuk', 'gaji', 'dana masuk', 'pendapatan', 'income', 'fee', 'honor', 'profit'];
        $expenseKeywords = ['pengeluaran', 'keluar', 'bayar', 'beli', 'biaya'];

        foreach ($incomeKeywords as $kw) {
            if (str_contains($lower, $kw)) {
                $type = 'income';
                break;
            }
        }

        // Only override to expense if expense keywords found (and no income keywords detected)
        if ($type === 'income') {
            foreach ($expenseKeywords as $kw) {
                if (str_contains($lower, $kw)) {
                    $type = 'expense';
                    break;
                }
            }
        }

        // Extract amount - look for patterns like "30000", "5jt", "100 ribu", "500rb", "20k"
        $amount = 0.0;
        $amountPatterns = [
            '/(\d+[.,]?\d*)\s*(jt|juta|juta)\b/i' => function ($m) {
                return (float) str_replace(',', '.', $m[1]) * 1000000;
            },
            '/(\d+[.,]?\d*)\s*(rb|ribu|rbu)\b/i' => function ($m) {
                return (float) str_replace(',', '.', $m[1]) * 1000;
            },
            '/(\d+[.,]?\d*)\s*k\b/i' => function ($m) {
                return (float) str_replace(',', '.', $m[1]) * 1000;
            },
            '/(\d+[.,]?\d*)\s*(jt|juta|juta)/i' => function ($m) {
                return (float) str_replace(',', '.', $m[1]) * 1000000;
            },
            '/(\d+[.,]?\d*)/' => function ($m) {
                return (float) str_replace(',', '.', $m[0]);
            },
        ];

        foreach ($amountPatterns as $pattern => $callback) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = $callback($matches);
                break;
            }
        }

        // Clean description - remove the amount part
        $description = preg_replace('/\b\d+[.,]?\d*\s*(jt|juta|rb|ribu|rbu|k|ribuan)?\b/i', '', $text);
        $description = preg_replace('/\b(pengeluaran|pemasukan|keluar|masuk)\s*/i', '', $description);
        $description = trim($description);
        $description = ucfirst($description ?: 'Transaksi');

        // Determine category from description
        $category = $this->guessCategory($description, $type);

        return [
            'type' => $type,
            'amount' => $amount > 0 ? $amount : 0,
            'description' => $description ?: 'Transaksi',
            'category' => $category,
        ];
    }

    /**
     * Answer financial questions based on transaction data.
     */
    public function answerQuery(string $question, array $transactions): string
    {
        // If AI is not configured, provide basic summary
        if (! $this->ai->isConfigured()) {
            return $this->basicSummary($question, $transactions);
        }

        // Format transactions for context
        $totalIncome = 0;
        $totalExpense = 0;
        $txList = '';
        foreach ($transactions as $i => $tx) {
            $catName = is_array($tx['category'] ?? null) ? ($tx['category']['name'] ?? 'Lainnya') : ($tx['category'] ?? 'Lainnya');
            $txList .= ($i + 1) . ". [{$tx['date']}] {$tx['description']} - Rp " . number_format((float) $tx['amount'], 0, ',', '.') . " ({$tx['type']}, {$catName})\n";
            if ($tx['type'] === 'income') {
                $totalIncome += (float) $tx['amount'];
            } else {
                $totalExpense += (float) $tx['amount'];
            }
        }

        $summary = "Total Pemasukan: Rp " . number_format($totalIncome, 0, ',', '.') . "\n";
        $summary .= "Total Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "\n";
        $summary .= "Saldo: Rp " . number_format($totalIncome - $totalExpense, 0, ',', '.') . "\n";

        $prompt = "Kamu adalah asisten keuangan pribadi. Berikut adalah data transaksi pengguna:\n\n";
        $prompt .= $summary . "\n";
        $prompt .= "Daftar Transaksi:\n" . ($txList ?: "(Belum ada transaksi)\n");
        $prompt .= "\nPertanyaan pengguna: \"{$question}\"\n\n";
        $prompt .= "Jawab dengan ramah, informatif, dan dalam Bahasa Indonesia. Berikan analisis yang membantu.";

        try {
            $result = $this->ai->askRaw($prompt, $question, 500);
            return $result ?? $this->basicSummary($question, $transactions);
        } catch (\Exception $e) {
            return $this->basicSummary($question, $transactions);
        }
    }

    private function basicSummary(string $question, array $transactions): string
    {
        $totalIncome = 0;
        $totalExpense = 0;
        foreach ($transactions as $tx) {
            if ($tx['type'] === 'income') {
                $totalIncome += (float) $tx['amount'];
            } else {
                $totalExpense += (float) $tx['amount'];
            }
        }

        $balance = $totalIncome - $totalExpense;
        $count = count($transactions);

        return "📊 *Ringkasan Keuangan*\n\n" .
            "Total {$count} transaksi tercatat.\n" .
            "💵 Pemasukan: Rp " . number_format($totalIncome, 0, ',', '.') . "\n" .
            "💸 Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "\n" .
            "💰 Saldo: Rp " . number_format($balance, 0, ',', '.') . "\n\n" .
            "Untuk analisis lebih detail, silakan atur AI API Key di Settings.";
    }

    private function guessCategory(string $description, string $type): string
    {
        $lower = strtolower($description);

        if ($type === 'income') {
            if (str_contains($lower, 'gaji')) return 'Gaji';
            if (str_contains($lower, 'freelance') || str_contains($lower, 'project') || str_contains($lower, 'proyek')) return 'Freelance';
            if (str_contains($lower, 'bisnis') || str_contains($lower, 'jual') || str_contains($lower, 'dagang')) return 'Bisnis';
            if (str_contains($lower, 'invest') || str_contains($lower, 'saham') || str_contains($lower, 'crypto')) return 'Investasi';
            return 'Lainnya';
        }

        if (str_contains($lower, 'makan') || str_contains($lower, 'jajan') || str_contains($lower, 'kopi') || str_contains($lower, 'minum')) return 'Makanan';
        if (str_contains($lower, 'bensin') || str_contains($lower, 'ongkir') || str_contains($lower, 'transport') || str_contains($lower, 'grab') || str_contains($lower, 'gojek')) return 'Transportasi';
        if (str_contains($lower, 'beli') || str_contains($lower, 'shopping') || str_contains($lower, 'belanja')) return 'Belanja';
        if (str_contains($lower, 'listrik') || str_contains($lower, 'air') || str_contains($lower, 'pdam') || str_contains($lower, 'telpon') || str_contains($lower, 'internet')) return 'Tagihan';
        if (str_contains($lower, 'obat') || str_contains($lower, 'dokter') || str_contains($lower, 'rumah sakit') || str_contains($lower, 'kesehatan')) return 'Kesehatan';
        if (str_contains($lower, 'domain') || str_contains($lower, 'hosting') || str_contains($lower, 'server') || str_contains($lower, 'vps') || str_contains($lower, 'saas') || str_contains($lower, 'tool')) return 'Technology';
        if (str_contains($lower, 'kursus') || str_contains($lower, 'les') || str_contains($lower, 'buku') || str_contains($lower, 'belajar') || str_contains($lower, 'course')) return 'Pendidikan';
        if (str_contains($lower, 'nonton') || str_contains($lower, 'film') || str_contains($lower, 'game') || str_contains($lower, 'liburan')) return 'Hiburan';
        return 'Lainnya';
    }
}
