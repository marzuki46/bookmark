<?php

/**
 * One-off recovery tool: resync the "migrations" ledger with the real schema.
 *
 * Symptom: `php artisan migrate --force` fails with SQLSTATE[42S21]
 * "Column already exists" even though the schema is actually ahead of the
 * migrations table (e.g. the schema was applied out-of-band and a full
 * `migrate` was never able to run).
 *
 * Strategy (idempotent):
 *   1. Compute the same pending set the migrator would run.
 *   2. For each pending migration, verify against information_schema whether
 *      every object it creates already exists (tables, columns, indexes,
 *      foreign keys). If yes -> record it in the `migrations` table and move on.
 *   3. Everything genuinely missing is handed back to the real migrator
 *      ($migrator->runPending), which runs + logs it exactly like `migrate`.
 *   4. For the family-scope backfill (170000) the data update is replayed too,
 *      because it is idempotent and must not be skipped when that migration is
 *      recorded as already applied.
 *
 * Usage:   php recover_migrations.php
 * Verify:  php artisan migrate:status      (all pending should be "Ran")
 * Note:    safe to run again; deletes nothing; never drops anything.
 */

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function hasColumnIf(string $table, string $column): bool
{
    return Schema::hasTable($table) && Schema::hasColumn($table, $column);
}

function hasIndexIf(string $table, string $index): bool
{
    if (! Schema::hasTable($table)) {
        return false;
    }
    $row = DB::select(
        'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
        [$table, $index],
    );

    return (int) $row[0]->c > 0;
}

function hasForeignOnIf(string $table, string $column): bool
{
    if (! Schema::hasTable($table)) {
        return false;
    }
    $row = DB::select(
        'SELECT COUNT(*) AS c FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
        [$table, $column],
    );

    return (int) $row[0]->c > 0;
}

function recordMigration(string $name): void
{
    $batch = (int) DB::table('migrations')->max('batch') + 1;
    DB::table('migrations')->insert([
        'migration' => $name,
        'batch' => $batch,
    ]);
}

function replayFamilyScopeBackfill(): void
{
    $count = 0;
    DB::table('family_members')->orderBy('id')->eachById(
        function (object $membership) use (&$count): void {
            $count += DB::table('subscriptions')
                ->where('user_id', $membership->user_id)
                ->whereNull('family_id')
                ->update(['family_id' => $membership->family_id]);
            $count += DB::table('subscription_payments')
                ->where('user_id', $membership->user_id)
                ->whereNull('family_id')
                ->update(['family_id' => $membership->family_id]);
        },
    );

    if ($count > 0) {
        echo "    + replayed idempotent family-scope backfill ($count row(s))\n";
    }
}

/**
 * Per-migration preconditions. A migration is treated as "already applied"
 * only when EVERY object it creates already exists.
 */
$prereqs = [
    '2026_09_28_170000_add_family_scope_to_subscriptions.php' => function (): bool {
        return
            hasColumnIf('subscriptions', 'family_id')
            && hasColumnIf('subscription_payments', 'family_id')
            && hasIndexIf('subscriptions', 'subscriptions_family_id_status_index')
            && hasIndexIf('subscription_payments', 'subscription_payments_family_id_status_index')
            && hasForeignOnIf('subscriptions', 'family_id')
            && hasForeignOnIf('subscription_payments', 'family_id');
    },
    '2026_09_28_170200_add_family_member_visibility.php' => function (): bool {
        return
            hasColumnIf('family_members', 'relationship')
            && hasColumnIf('family_members', 'visibility')
            && hasIndexIf('family_members', 'family_members_family_id_relationship_index');
    },
    '2026_09_28_170300_add_family_query_indexes.php' => function (): bool {
        return
            hasIndexIf('family_transactions', 'family_transactions_family_type_date_idx')
            && hasIndexIf('family_transactions', 'family_transactions_family_user_date_idx')
            && hasIndexIf('family_debts', 'family_debts_family_status_due_idx')
            && hasIndexIf('family_goals', 'family_goals_family_status_deadline_idx');
    },
    '2026_09_28_180001_add_app_login_code_plain_to_users_table.php' => function (): bool {
        return hasColumnIf('users', 'app_login_code_plain');
    },
    '2026_09_28_180002_create_app_error_logs_table.php' => function (): bool {
        return Schema::hasTable('app_error_logs');
    },
    '2026_09_28_190001_create_app_releases_table.php' => function (): bool {
        return Schema::hasTable('app_releases');
    },
    '2026_09_28_190100_add_release_policy_to_app_releases.php' => function (): bool {
        return
            hasColumnIf('app_releases', 'is_mandatory')
            && hasColumnIf('app_releases', 'released_at')
            && hasIndexIf('app_releases', 'app_releases_version_code_is_mandatory_index');
    },
    '2026_09_29_090000_add_advisor_columns_to_families_table.php' => function (): bool {
        return
            hasColumnIf('families', 'advisor_enabled')
            && hasColumnIf('families', 'advisor_profile');
    },
];

/** @var Migrator $migrator */
$migrator = app('migrator');

$pending = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));
$ran = DB::table('migrations')->pluck('migration')->flip();

$toRun = [];
$recorded = [];

echo "Recovering migrations ledger for database: ".DB::connection()->getDatabaseName()."\n";

foreach ($pending as $path) {
    $name = $migrator->getMigrationName($path);

    if (isset($ran[$name])) {
        continue;
    }

    $base = basename($path);
    $already = isset($prereqs[$base]) && $prereqs[$base]();

    if ($already) {
        recordMigration($name);
        $recorded[] = $name;
        echo "  [RECORDED] already applied:          $name\n";
    } else {
        $toRun[] = $path;
        echo "  [MISSING]  queued to run:             $name\n";
    }
}

if ($toRun !== []) {
    echo "Running genuinely missing migrations...\n";
    $migrator->runPending($toRun);
}

$backfillPrereq = $prereqs['2026_09_28_170000_add_family_scope_to_subscriptions.php'] ?? static fn (): bool => false;
if ($backfillPrereq()
    && ! in_array(basename('2026_09_28_170000_add_family_scope_to_subscriptions.php'), array_map('basename', $toRun), true)) {
    replayFamilyScopeBackfill();
}

$left = 0;
$recordedMap = array_flip($recorded);
foreach ($pending as $path) {
    $name = $migrator->getMigrationName($path);
    if (isset($recordedMap[$name]) || in_array($path, $toRun, true)) {
        continue;
    }
    if (! DB::table('migrations')->where('migration', $name)->exists()) {
        $left++;
        echo "  [LEFTOVER] still not applied: $name\n";
    }
}

if ($recorded === [] && $toRun === []) {
    echo "Nothing pending.\n";
} else {
    echo 'Done. Recorded '.count($recorded).' migration(s), ran '.count($toRun).' migration(s).'.PHP_EOL;
}

echo 'Next: verify with `php artisan migrate:status` (all should be "Ran").'.PHP_EOL;