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
        Schema::create('rencana_prokers', function (Blueprint $table) {
            $table->id();
            $table->string('id_proker');
            $table->text('rencana_proker');
            $table->string('bulan_mulai');
            $table->string('minggu_mulai');
            $table->string('bulan_akhir');
            $table->string('minggu_akhir');
            $table->string('status_rencana')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rencana_prokers');
    }
};
