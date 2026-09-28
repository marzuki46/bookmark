<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            // family_transactions.payer already labels each row husband/wife, but
            // nothing recorded which member those labels referred to, so the app
            // could not tell whose spending it was looking at. Nullable because an
            // existing family has to pick before the label means anything, and a
            // member who is not the husband or the wife simply leaves it null.
            $table->enum('payer_role', ['husband', 'wife'])->nullable()->after('is_family_only');
        });
    }

    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table): void {
            $table->dropColumn('payer_role');
        });
    }
};
