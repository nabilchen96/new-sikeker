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
        Schema::create('realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kontrak')
                ->constrained('kontraks')
                ->cascadeOnDelete();

            // Simpan tanggal sebagai tanggal awal bulan
            // contoh: 2026-09-01
            $table->date('periode');

            // Nilai realisasi pada periode tersebut
            $table->decimal('nilai', 15, 2);

            $table->timestamps();

            /*
             * Satu kontrak hanya boleh memiliki
             * satu realisasi pada satu periode.
             */
            $table->unique([
                'id_kontrak',
                'periode'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('realisasis');
    }
};
