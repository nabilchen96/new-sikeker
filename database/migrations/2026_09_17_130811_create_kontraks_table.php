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
        Schema::create('kontraks', function (Blueprint $table) {
            $table->id();
            $table->string('id_parent')->nullable();
            $table->string('kode')->nullable();
            $table->text('nama');
            $table->string('jenis'); //PK atau KPI
            $table->integer('target')->nullable();
            $table->string('jenis_target')->nullable(); //Dokumen, Orang, Rupiah
            $table->string('id_tahun');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontraks');
    }
};
