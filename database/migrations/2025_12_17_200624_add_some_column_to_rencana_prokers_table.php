<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rencana_prokers', function (Blueprint $table) {
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('jenis_proker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rencana_prokers', function (Blueprint $table) {
            //
        });
    }
};
