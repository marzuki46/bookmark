<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_transactions', function (Blueprint $table): void {
            $table->index(['family_id', 'type', 'date'], 'family_transactions_family_type_date_idx');
            $table->index(['family_id', 'user_id', 'date'], 'family_transactions_family_user_date_idx');
        });

        Schema::table('family_debts', function (Blueprint $table): void {
            $table->index(['family_id', 'status', 'due_date'], 'family_debts_family_status_due_idx');
        });

        Schema::table('family_goals', function (Blueprint $table): void {
            $table->index(['family_id', 'status', 'deadline'], 'family_goals_family_status_deadline_idx');
        });
    }

    public function down(): void
    {
        Schema::table('family_goals', function (Blueprint $table): void {
            $table->dropIndex('family_goals_family_status_deadline_idx');
        });
        Schema::table('family_debts', function (Blueprint $table): void {
            $table->dropIndex('family_debts_family_status_due_idx');
        });
        Schema::table('family_transactions', function (Blueprint $table): void {
            $table->dropIndex('family_transactions_family_type_date_idx');
            $table->dropIndex('family_transactions_family_user_date_idx');
        });
    }
};
