<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['emergency_fund', 'custom'])->default('custom');
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->decimal('monthly_allocation', 15, 2)->default(0);
            $table->unsignedInteger('priority')->default(10);
            $table->date('deadline')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->enum('status', ['active', 'completed', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_goals');
    }
};
