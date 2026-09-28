<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per HTTP request, so the admin can see which URLs were hit, by
     * whom, from where, and whether they 4xx/5xx'd. Pruning is a console
     * command because the table grows with traffic on purpose.
     */
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
