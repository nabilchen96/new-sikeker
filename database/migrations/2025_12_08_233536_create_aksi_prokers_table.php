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
        Schema::create('aksi_prokers', function (Blueprint $table) {
            $table->id();
            $table->string('id_rencana_proker');
            $table->text('kegiatan_proker');
            $table->string('bukti_kegiatan')->nullable();
            $table->integer('progress');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aksi_prokers');
    }
};
