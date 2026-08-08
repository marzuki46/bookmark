<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('family_categories')->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->date('date');
            $table->string('payment_method')->nullable();
            $table->enum('payer', ['husband', 'wife', 'shared'])->default('shared');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['family_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_transactions');
    }
};
