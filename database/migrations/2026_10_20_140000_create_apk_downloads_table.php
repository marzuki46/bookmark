<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apk_downloads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('app_release_id')->constrained('app_releases')->cascadeOnDelete();
            $table->timestamp('downloaded_at');
            $table->index(['app_release_id', 'downloaded_at']);
            $table->index('downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apk_downloads');
    }
};
