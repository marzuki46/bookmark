<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('version_code');
            $table->string('version_name');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->timestamps();
            $table->unique('version_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};