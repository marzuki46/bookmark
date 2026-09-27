<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reclassify any rows previously auto-created from WhatsApp before the
        // enum value disappears, otherwise MySQL would clamp them to ''.
        DB::table('financial_transactions')
            ->where('source', 'wa_auto')
            ->update(['source' => 'manual']);

        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->enum('source', ['manual', 'ai'])->default('manual')->change();
        });

        Schema::table('financial_transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_transactions', 'wa_sender')) {
                $table->dropColumn('wa_sender');
            }
        });

        // Every dashboard aggregate filters by user then by date range.
        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type', 'date'], 'fin_tx_user_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->dropIndex('fin_tx_user_type_date_idx');
            $table->dropIndex(['user_id', 'date']);
        });

        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->string('wa_sender')->nullable()->after('source');
        });

        Schema::table('financial_transactions', function (Blueprint $table): void {
            $table->enum('source', ['manual', 'wa_auto'])->default('manual')->change();
        });
    }
};
