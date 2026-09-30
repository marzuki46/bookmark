<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kang Cuan message templates. The server only stores templates; the phone
     * decides when to fire a notification (like an alarm) and picks a random
     * template per slot so users never see the same message twice in a row.
     *
     * slot: one of small_number: pagi | malam | bulanan
     * variant: only meaningful for the "malam" slot, where the recap depends on
     *          how the day went: pengeluaran-luas | pemasukan-luas | kosong.
     * content: message body, may contain the {nama} placeholder which the app
     *          replaces with the user's first name at send time.
     */
    public function up(): void
    {
        Schema::create('affirmations', function (Blueprint $table) {
            $table->id();
            $table->string('slot', 20);
            $table->string('variant', 30)->nullable();
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['slot', 'variant', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affirmations');
    }
};