<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['payable', 'receivable'])->default('payable');
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('interest_rate', 8, 2)->nullable();
            $table->decimal('installment', 15, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'partial', 'settled'])->default('open');
            $table->unsignedInteger('priority')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_debts');
    }
};
