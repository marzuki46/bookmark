<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Midtrans transaction audit trail for paid subscriptions. Named
     * "subscription_payments" because "payments" already belongs to invoices.
     */
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable();
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->string('payment_type')->nullable();
            $table->string('status')->default('pending');           // pending|paid|failed|expired
            $table->json('raw')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
