<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Family;
use App\Services\FamilyInsightService;
use Illuminate\Console\Command;

final class GenerateFamilyInsights extends Command
{
    protected $signature = 'finance:insights
        {--family= : Only this family ID}
        {--force : Regenerate and re-notify even if this week already has insights}';

    protected $description = 'Generate the weekly AI insight for each family (husband, wife, and family health)';

    public function handle(FamilyInsightService $insights): int
    {
        // Only families that actually have members: an empty family would
        // generate insight that nobody can ever read.
        $query = Family::query()
            ->whereHas('members')
            ->with('members');

        if ($this->option('family')) {
            $query->whereKey($this->option('family'));
        }

        $families = $query->get();
        $force = (bool) $this->option('force');

        $this->components->info("Generating insights for {$families->count()} famil(ies)…");

        $created = 0;
        $failed = 0;

        foreach ($families as $family) {
            try {
                $result = $insights->generateForFamily($family, $force);
                $created += $result->count();
                $fallbacks = $result->where('is_fallback', true)->count();

                $this->components->twoColumnDetail(
                    $family->name,
                    sprintf('%d insight%s', $result->count(), $fallbacks > 0 ? " ({$fallbacks} fallback)" : ''),
                );
            } catch (\Throwable $e) {
                $failed++;
                $this->components->error("{$family->name}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->newLine();
        $this->components->info("{$created} insight(s) written.");

        if ($failed > 0) {
            $this->components->error("{$failed} famil(ies) failed.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
