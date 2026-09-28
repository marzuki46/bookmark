<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SHA-256 digest of the app login code, prefixed for future algorithm
            // changes. Nullable so existing accounts keep working without a code.
            $table->string('app_login_code', 80)->nullable()->unique()->after('password');
            $table->timestamp('app_login_code_rotated_at')->nullable()->after('app_login_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['app_login_code']);
            $table->dropColumn(['app_login_code', 'app_login_code_rotated_at']);
        });
    }
};
