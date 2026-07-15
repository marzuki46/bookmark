<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_scanning')->default(false);
            $table->boolean('is_complete')->default(false);
            $table->integer('total')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('progress')->default(0);
            $table->integer('alive_count')->default(0);
            $table->integer('dead_count')->default(0);
            $table->integer('timeout_count')->default(0);
            $table->integer('current_offset')->default(0);
            $table->integer('batch_size')->default(5);
            $table->json('results')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_scan_sessions');
    }
};
