<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('family_id')
                ->nullable()
                ->after('user_id')
                ->constrained('families')
                ->nullOnDelete();
            $table->index(['family_id', 'status']);
        });

        Schema::table('subscription_payments', function (Blueprint $table): void {
            $table->foreignId('family_id')
                ->nullable()
                ->after('user_id')
                ->constrained('families')
                ->nullOnDelete();
            $table->index(['family_id', 'status']);
        });

        // Backfill is intentionally idempotent and uses the existing membership
        // table so old user-owned purchases become family-owned entitlements.
        DB::table('family_members')->select(['user_id', 'family_id'])->orderBy('id')->eachById(
            function (object $membership): void {
                DB::table('subscriptions')
                    ->where('user_id', $membership->user_id)
                    ->whereNull('family_id')
                    ->update(['family_id' => $membership->family_id]);
                DB::table('subscription_payments')
                    ->where('user_id', $membership->user_id)
                    ->whereNull('family_id')
                    ->update(['family_id' => $membership->family_id]);
            },
        );
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table): void {
            $table->dropForeign(['family_id']);
            $table->dropIndex(['family_id', 'status']);
            $table->dropColumn('family_id');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['family_id']);
            $table->dropIndex(['family_id', 'status']);
            $table->dropColumn('family_id');
        });
    }
};
