<?php

namespace Tests\Feature;

use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Models\User;
use App\Services\KontrakCalculator;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class KontrakCalculationTest extends TestCase
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

    public function test_both_contract_tabs_render_the_workbook_targets_and_calculation_form(): void
    {
        $this->get('/kontrak?jenis=PK')->assertOk()
            ->assertSee('calculation_type')->assertSee('window.kontrakCalculationForms', false)
            ->assertSee('86,67')->assertSee('93,92')->assertSee('80,34')->assertSee('78,19')->assertSee('90,83')->assertSee('3,62');
        $this->get('/kontrak?jenis=KPI')->assertOk()->assertSee('15.450.000.000')->assertSee('1,79')->assertSee('2,33');
    }

    public function test_new_indicator_accepts_manual_values_and_cannot_borrow_another_parents_sources(): void
    {
        $payload = [
            'id_parent' => 1000, 'kode' => 'IKK 99', 'sub_indikator' => 'Indikator baru', 'jenis' => 'PK',
            'target' => '95,57', 'jenis_target' => 'dokumen', 'calculation_type' => 'manual',
        ];
        $this->postJson('/store-bagian', $payload)->assertOk()->assertJsonPath('responCode', 1);
        $this->assertDatabaseHas('kontraks', ['kode' => 'IKK 99', 'target' => 95.57, 'jenis_target' => 'dokumen', 'calculation_type' => 'manual']);
        $payload['id'] = 69;
        $this->postJson('/store-bagian', $payload)->assertUnprocessable()->assertJsonValidationErrors('id');
        unset($payload['id']);
        $payload['calculation_type'] = 'ratio';
        $payload['calculation_config'] = ['numerator_id' => 70, 'denominator_id' => 71, 'factor' => 100];
        $this->postJson('/store-bagian', $payload)->assertUnprocessable()->assertJsonValidationErrors('calculation_config.numerator_id');
    }

    private function editPayload(array $replace = []): array
    {
        return array_replace_recursive([
            'id' => 69, 'id_parent' => 1000, 'kode' => 'IKK 10', 'sub_indikator' => 'Sertifikasi', 'jenis' => 'PK',
            'jenis_target' => 'persen', 'target' => null, 'calculation_type' => 'ratio',
            'calculation_config' => ['numerator_id' => 70, 'denominator_id' => 71, 'factor' => 100],
        ], $replace);
    }

    public function test_formula_settings_are_saved_and_invalid_source_selections_are_rejected(): void
    {
        $this->postJson('/update-bagian', $this->editPayload())->assertOk()->assertJsonPath('responCode', 1);
        $this->assertSame('ratio', Kontrak::find(69)->calculation_type);
        $this->postJson('/update-bagian', $this->editPayload(['calculation_config' => ['denominator_id' => 8]]))
            ->assertUnprocessable()->assertJsonValidationErrors('calculation_config.denominator_id');
        $this->postJson('/update-bagian', $this->editPayload(['calculation_config' => ['denominator_id' => 70]]))
            ->assertUnprocessable()->assertJsonValidationErrors('calculation_config.denominator_id');
        $this->postJson('/update-bagian', $this->editPayload(['calculation_type' => 'weighted_sum', 'calculation_config' => ['weights' => ['8' => 100]]]))
            ->assertUnprocessable()->assertJsonValidationErrors('calculation_config.weights');
    }

    public function test_manual_ikk_target_accepts_comma_decimals_and_retains_units_with_children(): void
    {
        $payload = $this->editPayload(['id' => 86, 'kode' => 'IKK 15', 'calculation_type' => 'manual', 'target' => '3,62', 'jenis_target' => 'nilai', 'calculation_config' => []]);
        $payload['calculation_config'] = [];
        $this->postJson('/update-bagian', $payload)->assertOk();
        $parent = Kontrak::find(86);
        Kontrak::create(['id_parent' => 86, 'kode' => '1', 'nama' => 'Keterangan', 'target' => 999, 'jenis_target' => 'dokumen', 'jenis' => 'PK', 'id_tahun' => 1]);
        $result = (new KontrakCalculator)->calculateTree($parent->fresh('childrenRecursive'))[86];
        $this->assertSame(3.62, $result['target']);
        $this->assertSame('nilai', $result['unit']);
        $this->assertSame('15450000000.000000', Kontrak::find(114)->target);
    }

    public function test_monthly_realization_uses_formula_sources_and_aggregates_consistently(): void
    {
        $calculator = new KontrakCalculator;
        $root = Kontrak::with('childrenRecursive')->find(1000);
        $results = $calculator->calculateTree($root);
        foreach ($root->childrenRecursive as $ikk) {
            foreach ($calculator->inputNodes($ikk, $results) as $input) {
                if ($input->target !== null) {
                    Realisasi::create(['id_kontrak' => $input->id, 'periode' => '2026-11-01', 'nilai' => $input->target]);
                }
            }
        }
        $response = $this->get('/realisasi?jenis=PK&bulan=11&tahun=2026')->assertOk();
        $items = collect($response->viewData('data'))->keyBy(fn ($item) => $item['ikk']->kode);
        $this->assertEqualsWithDelta(85.714285714, $items['IKK 10']['total_target'], 0.000001);
        $this->assertEqualsWithDelta(100, $items['IKK 10']['persentase'], 0.000001);
        $this->assertCount(2, $items['IKK 10']['indikators']);
        $this->assertCount(1, $items['IKK 25']['indikators']);
        $this->assertEqualsWithDelta(93.922, $items['IKK 5']['total_realisasi_kumulatif'], 0.000001);
        $this->get('/kontrak?jenis=PK')->assertOk()->assertSee('93,92');
    }

    public function test_zero_target_sources_can_receive_realization_but_derived_rows_cannot(): void
    {
        $this->post('/store-realisasi', ['bulan' => 11, 'tahun' => 2026, 'realisasi' => [15 => 0]])->assertRedirect();
        $this->assertDatabaseHas('realisasis', ['id_kontrak' => 15, 'nilai' => 0]);
        $this->postJson('/store-realisasi', ['bulan' => 11, 'tahun' => 2026, 'realisasi' => [10 => 95.57]])->assertUnprocessable();
    }

    public function test_migration_round_trip_preserves_existing_fractional_and_large_targets(): void
    {
        $migration = require database_path('migrations/2026_11_16_050000_add_calculation_settings_to_kontraks.php');
        $migration->down();
        $this->assertEqualsWithDelta(90.5, (float) DB::table('kontraks')->where('id', 100)->value('target'), 0.000001);
        $migration->up();
        $this->assertSame('15450000000.000000', Kontrak::find(114)->target);
        $this->assertSame('90.500000', Kontrak::find(100)->target);
        $this->assertNull(Kontrak::find(100)->calculation_type);
    }
}
