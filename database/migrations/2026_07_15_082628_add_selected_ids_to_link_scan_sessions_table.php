<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_scan_sessions', function (Blueprint $table) {
            $table->json('selected_ids')->nullable()->after('results');
        });
    }

    public function down(): void
    {
        Schema::table('link_scan_sessions', function (Blueprint $table) {
            $table->dropColumn('selected_ids');
        });
    }
};
