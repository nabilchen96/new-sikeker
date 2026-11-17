<?php

namespace Tests\Support;

use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait CascadingDatabase
{
    protected function createCascadingDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->string('role_kontrak')->nullable();
            $table->integer('id_unit')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('unit');
        });
        (require database_path('migrations/2025_12_08_130639_create_tahuns_table.php'))->up();
        (require database_path('migrations/2026_09_17_130811_create_kontraks_table.php'))->up();
        (require database_path('migrations/2026_11_16_050000_add_calculation_settings_to_kontraks.php'))->up();
        (require database_path('migrations/2026_09_19_223728_create_realisasis_table.php'))->up();
        Schema::create('rencana_prokers', function (Blueprint $table) {
            $table->id();
            $table->string('id_pk')->nullable();
        });
        Schema::create('aksi_prokers', function (Blueprint $table) {
            $table->id();
            $table->string('id_rencana_proker')->nullable();
            $table->date('tgl_pengerjaan')->nullable();
        });
        Schema::create('realisasi_aksi_proker', function (Blueprint $table) {
            $table->unsignedBigInteger('realisasi_id');
            $table->unsignedBigInteger('aksi_proker_id');
            $table->timestamps();
        });
        DB::table('tahuns')->insert(['id' => 1, 'tahun' => '2026', 'status' => 'Aktif']);
        Kontrak::forceCreate(['id' => 1000, 'kode' => 'SK PK', 'nama' => 'Sasaran PK', 'jenis' => 'PK', 'id_tahun' => 1]);
        Kontrak::forceCreate(['id' => 2000, 'kode' => 'SK KPI', 'nama' => 'Sasaran KPI', 'jenis' => 'KPI', 'id_tahun' => 1]);
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/cascading_2026.json')), true);
        $insert = function ($data, $parent) use (&$insert) {
            $attrs = array_intersect_key($data, array_flip(['id', 'kode', 'nama', 'jenis', 'target', 'jenis_target']));
            Kontrak::forceCreate($attrs + ['id_parent' => $parent, 'id_tahun' => 1]);
            foreach ($data['children'] as $child) {
                $insert($child, $data['id']);
            }
        };
        foreach ($fixture['nodes'] as $data) {
            $insert($data, $data['jenis'] === 'PK' ? 1000 : 2000);
        }
        User::forceCreate(['id' => 1, 'name' => 'Local test', 'email' => 'local@example.test', 'password' => 'test-password-only', 'role' => 'Admin']);
    }
}
