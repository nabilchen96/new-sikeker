<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cascadings', function (Blueprint $table) {

            $table->id();

            $table->foreignId('id_kontrak')
                ->constrained('kontraks')
                ->cascadeOnDelete();

            $table->foreignId('id_user')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'id_kontrak',
                'id_user'
            ]);

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('cascadings');
    }
};