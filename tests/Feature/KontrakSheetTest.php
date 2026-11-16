<?php

namespace Tests\Feature;

use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Models\User;
use App\Services\KontrakCalculator;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class KontrakSheetTest extends TestCase
{
    use CascadingDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        DB::purge('sqlite');
        $this->createCascadingDatabase();
        $this->actingAs(User::find(1));
    }

    public function test_ordinary_modal_is_restored_and_sheet_is_a_separate_page(): void
    {
        $this->get('/kontrak')->assertOk()->assertSee('Edit seperti Excel')->assertSee('calculation_settings')->assertDontSee('quick-entry.js', false);
        $response = $this->get('/kontrak-sheet?indikator=82')->assertOk()->assertSee('Target / rumus')->assertSee('sheet_grid');
        $rows = $response->viewData('sheetRows');
        $this->assertSame('=E2*40%+E3*40%+E4*20%', $rows[0]['input']);
        $this->assertSame('80.95', $rows[3]['input']);
    }

    public function test_all_34_existing_indicators_round_trip_through_excel_formulas(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/cascading_2026.json')), true);
        foreach ($fixture['nodes'] as $data) {
            $rows = $this->get('/kontrak-sheet?jenis='.$data['jenis'].'&indikator='.$data['id'])->assertOk()->viewData('sheetRows');
            $response = $this->postJson('/save-kontrak-sheet', ['id_parent' => $data['jenis'] === 'PK' ? 1000 : 2000, 'jenis' => $data['jenis'], 'rows' => $rows])->assertOk();
            $preview = $this->postJson('/preview-kontrak-sheet', ['jenis' => $data['jenis'], 'rows' => $response->json('rows')])->assertOk();
            $this->assertEqualsWithDelta((float) $data['expected'], $preview->json('rows.0.value'), 0.000001, $data['kode']);
        }
    }

    public function test_changes_use_stable_ids_and_ordinary_metadata_edit_preserves_formula(): void
    {
        $rows = $this->get('/kontrak-sheet?indikator=82')->viewData('sheetRows');
        $rows[1]['input'] = '70';
        $rows[1]['jenis_target'] = 'nilai';
        Realisasi::create(['id_kontrak' => 83, 'periode' => '2026-11-01', 'nilai' => 55]);
        $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertOk();
        $root = Kontrak::with('childrenRecursive')->find(82);
        $this->assertSame('formula', $root->calculation_type);
        $this->assertEqualsWithDelta(84.19, (new KontrakCalculator)->calculateTree($root)[82]['target'], 0.000001);
        $this->assertSame('nilai', Kontrak::find(83)->jenis_target);
        $this->assertDatabaseHas('realisasis', ['id_kontrak' => 83, 'nilai' => 55]);
        $before = $root->calculation_config;
        $this->postJson('/update-bagian', ['id' => 82, 'id_parent' => 1000, 'jenis' => 'PK', 'kode' => 'IKK 14', 'sub_indikator' => 'Dosen diperbarui', 'jenis_target' => 'persen', 'calculation_type' => 'formula', 'calculation_config' => []])->assertOk();
        $this->assertSame($before, Kontrak::find(82)->calculation_config);
    }

    public function test_cycles_and_invalid_formulas_do_not_write_anything(): void
    {
        $rows = $this->get('/kontrak-sheet?indikator=69')->viewData('sheetRows');
        $name = Kontrak::find(69)->nama;
        $rows[0]['nama'] = 'Do not save';
        foreach (['=E1', '=E999', '=system("id")'] as $formula) {
            $rows[0]['input'] = $formula;
            $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertUnprocessable();
            $this->assertSame($name, Kontrak::find(69)->nama);
        }
        $rows[0]['input'] = '=E2';
        $rows[1]['input'] = '=E1';
        $this->postJson('/preview-kontrak-sheet', ['jenis' => 'PK', 'rows' => $rows])->assertUnprocessable();
        $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertUnprocessable();
    }

    public function test_new_rows_sum_and_zero_values_can_be_saved_together(): void
    {
        $rows = [
            ['key' => 'root', 'id' => null, 'parent_key' => null, 'kode' => 'IKK baru', 'nama' => 'Jumlah', 'jenis_target' => 'orang', 'input' => '=SUM(E2:E3)'],
            ['key' => 'a', 'id' => null, 'parent_key' => 'root', 'kode' => '1', 'nama' => 'Pertama', 'jenis_target' => 'orang', 'input' => '0'],
            ['key' => 'b', 'id' => null, 'parent_key' => 'root', 'kode' => '2', 'nama' => 'Kedua', 'jenis_target' => 'orang', 'input' => '12,5'],
        ];
        $response = $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertOk();
        $id = $response->json('id');
        $root = Kontrak::with('childrenRecursive')->find($id);
        $this->assertEqualsWithDelta(12.5, (new KontrakCalculator)->calculateTree($root)[$id]['target'], 0.000001);
        $preview = $this->postJson('/preview-kontrak-sheet', ['jenis' => 'PK', 'rows' => $response->json('rows')])->assertOk();
        $this->assertEqualsWithDelta(12.5, $preview->json('rows.0.value'), 0.000001);
    }

    public function test_realization_follows_formula_dependencies_without_double_counting(): void
    {
        $rows = $this->get('/kontrak-sheet?indikator=10')->viewData('sheetRows');
        $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertOk();
        $root = Kontrak::with('childrenRecursive')->find(10);
        $calculator = new KontrakCalculator;
        $results = $calculator->calculateTree($root);
        $inputs = $calculator->inputNodes($root, $results);
        $this->assertCount(8, $inputs);
        $actuals = [];
        foreach ($inputs as $input) {
            $actuals[(string) $input->id] = (float) $input->target;
        }
        $this->assertEqualsWithDelta(95.5710955711, $calculator->calculateTree($root, $actuals)[10]['actual'], 0.000001);
    }
}
