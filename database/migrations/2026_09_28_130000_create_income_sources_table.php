<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->enum('type', ['salary', 'side', 'business', 'other'])->default('other');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['family_id', 'is_active']);
        });

        Schema::table('family_transactions', function (Blueprint $table) {
            // Nullable: existing rows predate income tracking, and a salary can
            // be recorded without ever naming a stream.
            $table->foreignId('income_source_id')
                ->nullable()
                ->after('category_id')
                ->constrained('income_sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('family_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('income_source_id');
        });

        Schema::dropIfExists('income_sources');
    }
};
