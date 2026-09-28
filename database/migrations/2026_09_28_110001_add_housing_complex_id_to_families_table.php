<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('housing_complex_id')
                ->nullable()
                ->after('owner_user_id')
                ->constrained('housing_complexes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('housing_complex_id');
        });
    }
};
