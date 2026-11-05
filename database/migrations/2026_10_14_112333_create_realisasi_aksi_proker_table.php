
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_aksi_proker', function (Blueprint $table) {
            $table->id();

            $table->foreignId('realisasi_id')
                ->constrained('realisasis')
                ->cascadeOnDelete();

            $table->foreignId('aksi_proker_id')
                ->constrained('aksi_prokers')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'realisasi_id',
                'aksi_proker_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_aksi_proker');
    }
};
