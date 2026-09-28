<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Encrypted copy of the plaintext login code so it can be shown
            // again (e.g. "family login code" screen) without a full rotation.
            // The digest in app_login_code stays the only thing used to log in.
            $table->text('app_login_code_plain')->nullable()->after('app_login_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('app_login_code_plain');
        });
    }
};