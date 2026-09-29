<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_ai_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->unsignedInteger('analysis_count')->default(0);
            $table->timestamps();
            $table->unique(['family_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_ai_usages');
    }
};
