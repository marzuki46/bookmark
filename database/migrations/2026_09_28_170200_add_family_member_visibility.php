<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->string('relationship', 20)->default('adult')->after('payer_role');
            $table->json('visibility')->nullable()->after('relationship');
            $table->index(['family_id', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropIndex(['family_id', 'relationship']);
            $table->dropColumn(['relationship', 'visibility']);
        });
    }
};
