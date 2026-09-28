<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per owned entitlement. Each user has at most one active row at a
     * time; a new purchase extends the current one rather than stacking rows.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('status')->default('active');          // active|expired|cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();          // null = lifetime
            $table->string('provider', 20)->default('midtrans');  // midtrans|manual
            $table->string('order_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
