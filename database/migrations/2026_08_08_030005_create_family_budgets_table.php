<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('family_categories')->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['family_id', 'category_id', 'month', 'year'], 'family_budgets_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_budgets');
    }
};
