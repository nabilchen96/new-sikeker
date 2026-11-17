<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontraks', function (Blueprint $table) {
            $table->string('calculation_type')->nullable();
            $table->json('calculation_config')->nullable();
            // Supports fractional scores and the Rp15.45 billion KPI target.
            $table->decimal('target', 22, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('kontraks', function (Blueprint $table) {
            $table->dropColumn(['calculation_type', 'calculation_config']);
        });
        // Keep decimal precision: reverting to integer would lose user data.
    }
};
