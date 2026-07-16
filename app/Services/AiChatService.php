<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Item;

final class AiChatService
{
    private AIService $ai;

    private WebSearchService $search;

    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->ai = new AIService($userId);
        $this->search = new WebSearchService($userId);
    }

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    public function chat(string $message, array $history = []): string
    {
        if (! $this->ai->isConfigured()) {
            return 'AI belum dikonfigurasi. Silakan atur API key di Settings.';
        }

        $lower = strtolower(trim($message));

        if (str_starts_with($lower, 'search ') || str_starts_with($lower, 'cari ') || str_starts_with($lower, 'google ') || str_starts_with($lower, '?')) {
            $query = preg_replace('/^(search |cari |google |\?)/i', '', $message);

            return $this->searchAndAnswer($query);
        }

        if (in_array($lower, ['ringkasan', 'summary', 'overview', 'semua data', 'all data'])) {
            return $this->generateOverview();
        }

        if (str_starts_with($lower, 'notulensi') || str_starts_with($lower, 'meeting') || str_starts_with($lower, 'rapat')) {
            $text = preg_replace('/^(notulensi |meeting |rapat )/i', '', $message);

            return $this->generateNotulensi($text);
        }

        $context = $this->buildContext();

        $systemPrompt = "You are Knowledge Hub AI assistant. You help the user manage their knowledge base.
You have access to ALL their data: bookmarks, notes, snippets, worksheets, and tags.
Answer in the same language the user uses (Indonesian or English).
Be concise, helpful, and reference specific items from their data when relevant.
If the user asks about something not in their data, you can use web search by prefixing your internal note with [SEARCH].";

        $fullMessage = "User's knowledge base context:\n{$context}\n\nUser question: {$message}";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $fullMessage];

        try {
            $url = rtrim($this->ai->getSettings()['api_url'] ?? '', '/').'/chat/completions';
            $apiKey = $this->ai->getSettings()['api_key'] ?? '';
            $model = $this->ai->getSettings()['model'] ?? 'gpt-4o-mini';

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withToken($apiKey)
                ->post($url, [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                ]);

            if (! $response->successful()) {
                return 'AI error: '.$response->status();
            }

            return $response->json('choices.0.message.content') ?? 'Tidak ada response dari AI.';
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    private function searchAndAnswer(string $query): string
    {
        $results = $this->search->search($query, 5);

        if (empty($results)) {
            return "Tidak ditemukan hasil untuk: {$query}";
        }

        $context = "Web search results for: {$query}\n\n";
        foreach ($results as $i => $r) {
            $context .= ($i + 1).". {$r['title']}\n   {$r['url']}\n   {$r['snippet']}\n\n";
        }

        $kbContext = $this->buildContext();

        $prompt = "Based on these web search results AND the user's knowledge base, answer the question: {$query}

Web search results:
{$context}

User's knowledge base:
{$kbContext}

Provide a comprehensive answer combining web results and personal data. Be concise.";

        return $this->ai->askRaw($prompt, $query, 800) ?? 'Gagal generate jawaban.';
    }

    private function generateOverview(): string
    {
        $context = $this->buildContext();

        $prompt = "You are a knowledge management assistant. Analyze the user's knowledge base and provide:
1. A brief overview of what they have (counts by type)
2. Key themes and topics
3. Suggestions for organization
4. Any notable patterns

Be concise and actionable.";

        return $this->ai->askRaw($prompt, $context, 600) ?? 'Gagal generate overview.';
    }

    private function generateNotulensi(string $meetingText): string
    {
        if (empty($meetingText)) {
            return 'Mohon masukkan teks notulensi/rapat. Contoh: notulensi [paste teks rapat]';
        }

        $prompt = "You are a meeting notes assistant. Convert this raw meeting text into structured notulensi in Indonesian:

Format:
📋 **Notulensi Rapat**

**Peserta:** (extract names if mentioned)
**Tanggal:** (extract date if mentioned, or use today)
**Topik:** (main topic)

**Poin Pembahasan:**
1. (topic 1)
   - Detail: ...
2. (topic 2)
   - Detail: ...

**Keputusan:**
- (decision 1)
- (decision 2)

**Action Items:**
- [ ] (task 1) — Responsible: (name)
- [ ] (task 2) — Responsible: (name)

**Catatan Tambahan:** (any other notes)

Be thorough but concise.";

        return $this->ai->askRaw($prompt, $meetingText, 1500) ?? 'Gagal generate notulensi.';
    }

    private function buildContext(): string
    {
        $userId = $this->userId;

        $bookmarks = Item::where('user_id', $userId)->where('type', 'bookmark')
            ->select('id', 'title', 'url', 'content')->latest()->take(30)->get();
        $notes = Item::where('user_id', $userId)->where('type', 'note')
            ->select('id', 'title', 'content')->latest()->take(20)->get();
        $snippets = Item::where('user_id', $userId)->where('type', 'snippet')
            ->select('id', 'title', 'content')->latest()->take(10)->get();
        $worksheets = Item::where('user_id', $userId)->where('type', 'worksheet')
            ->select('id', 'title', 'metadata')->latest()->take(10)->get();
        $prompts = Item::where('user_id', $userId)->where('type', 'prompt')
            ->select('id', 'title', 'content')->latest()->take(10)->get();
        $todos = Item::where('user_id', $userId)->where('type', 'todo')
            ->select('id', 'title', 'content', 'metadata')->latest()->take(20)->get();

        $ctx = "=== KNOWLEDGE BASE SUMMARY ===\n";
        $ctx .= "Bookmarks: {$bookmarks->count()} items\n";
        $ctx .= "Notes: {$notes->count()} items\n";
        $ctx .= "Snippets: {$snippets->count()} items\n";
        $ctx .= "Worksheets: {$worksheets->count()} items\n";
        $ctx .= "Prompts: {$prompts->count()} items\n";
        $ctx .= "Todos: {$todos->count()} items\n\n";

        if ($bookmarks->isNotEmpty()) {
            $ctx .= "--- BOOKMARKS ---\n";
            foreach ($bookmarks as $b) {
                $ctx .= "[{$b->id}] {$b->title}";
                if ($b->url) {
                    $ctx .= " ({$b->url})";
                }
                if ($b->content) {
                    $ctx .= ' — '.mb_substr($b->content, 0, 100);
                }
                $ctx .= "\n";
            }
            $ctx .= "\n";
        }

        if ($notes->isNotEmpty()) {
            $ctx .= "--- NOTES ---\n";
            foreach ($notes as $n) {
                $ctx .= "[{$n->id}] {$n->title}";
                if ($n->content) {
                    $ctx .= ' — '.mb_substr($n->content, 0, 150);
                }
                $ctx .= "\n";
            }
            $ctx .= "\n";
        }

        if ($snippets->isNotEmpty()) {
            $ctx .= "--- CODE SNIPPETS ---\n";
            foreach ($snippets as $s) {
                $ctx .= "[{$s->id}] {$s->title}";
                if ($s->content) {
                    $ctx .= ' — '.mb_substr($s->content, 0, 100);
                }
                $ctx .= "\n";
            }
            $ctx .= "\n";
        }

        if ($worksheets->isNotEmpty()) {
            $ctx .= "--- WORKSHEETS ---\n";
            foreach ($worksheets as $w) {
                $meta = $w->metadata ?? [];
                $rows = count($meta['rows'] ?? []);
                $checklist = count($meta['checklist'] ?? []);
                $ctx .= "[{$w->id}] {$w->title} ({$rows} rows, {$checklist} checklist items)\n";
            }
            $ctx .= "\n";
        }

        if ($prompts->isNotEmpty()) {
            $ctx .= "--- AI PROMPTS ---\n";
            foreach ($prompts as $p) {
                $ctx .= "[{$p->id}] {$p->title}";
                if ($p->content) {
                    $ctx .= ' — '.mb_substr($p->content, 0, 100);
                }
                $ctx .= "\n";
            }
        }

        if ($todos->isNotEmpty()) {
            $ctx .= "\n--- TODOS ---\n";
            foreach ($todos as $t) {
                $meta = $t->metadata ?? [];
                $status = ($meta['completed'] ?? false) ? 'DONE' : 'PENDING';
                $priority = $meta['priority'] ?? 'medium';
                $due = $meta['due_date'] ?? 'no due date';
                $ctx .= "[{$t->id}] [{$status}] [{$priority}] {$t->title} (due: {$due})";
                if ($t->content) {
                    $ctx .= ' — '.mb_substr($t->content, 0, 80);
                }
                $ctx .= "\n";
            }
        }

        return $ctx;
    }
}
