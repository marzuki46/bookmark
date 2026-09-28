<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            // NULL for the shared family-health message; set for a personal one.
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('scope', ['family', 'user'])->default('family');
            // ISO year-week, e.g. "2026-W39". Keeps the weekly cadence idempotent.
            $table->string('week_key', 8);
            $table->text('message');
            $table->enum('tone', ['positive', 'neutral', 'warning', 'critical'])->default('neutral');
            $table->json('metrics')->nullable();
            // True when the message came from the rule engine because the AI
            // was unavailable — useful for spotting a silently degraded feed.
            $table->boolean('is_fallback')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'user_id', 'week_key', 'scope'], 'family_insights_unique_week');
            $table->index(['family_id', 'scope', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_insights');
    }
};
