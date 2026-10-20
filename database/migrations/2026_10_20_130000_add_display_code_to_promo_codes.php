<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->text('code_encrypted')->nullable()->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table): void {
            $table->dropColumn('code_encrypted');
        });
    }
};
