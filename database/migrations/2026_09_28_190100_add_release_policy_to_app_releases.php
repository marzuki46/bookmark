<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_releases', function (Blueprint $table): void {
            $table->boolean('is_mandatory')->default(false)->after('notes');
            $table->timestamp('released_at')->nullable()->after('sha256');
            $table->index(['version_code', 'is_mandatory']);
        });
    }

    public function down(): void
    {
        Schema::table('app_releases', function (Blueprint $table): void {
            $table->dropIndex(['version_code', 'is_mandatory']);
            $table->dropColumn(['is_mandatory', 'released_at']);
        });
    }
};
