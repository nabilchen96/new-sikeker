<?php

namespace Tests\Feature;

use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Models\User;
use App\Services\KontrakCalculator;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class KontrakTreeEntryTest extends TestCase
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

    private function node(string $key, string $type = 'manual', $target = null, array $children = [], array $config = []): array
    {
        return ['key' => $key, 'id' => null, 'kode' => $key, 'nama' => 'Indikator '.$key, 'target' => $target, 'jenis_target' => 'nilai', 'calculation_type' => $type, 'calculation_config' => $config, 'children' => $children];
    }

    private function save(array $root)
    {
        return $this->postJson('/save-kontrak-tree', ['id_parent' => 1000, 'jenis' => 'PK', 'root' => $root]);
    }

    public function test_accreditation_structure_and_source_ids_are_created_in_one_save(): void
    {
        $root = $this->node('IKK 4', 'sum', null, [
            $this->node('A', 'scale', null, [$this->node('a1', 'manual', '1')], ['source_id' => 'a1', 'factor' => 60]),
            $this->node('B', 'ratio', null, [$this->node('b1', 'manual', '2'), $this->node('b2', 'manual', '3')], ['numerator_id' => 'b1', 'denominator_id' => 'b2', 'factor' => 40]),
        ]);
        $root['jenis_target'] = 'persen';
        $response = $this->save($root)->assertOk()->assertJsonPath('responCode', 1);
        $parent = Kontrak::with('childrenRecursive')->findOrFail($response->json('id'));
        $results = (new KontrakCalculator)->calculateTree($parent);
        $this->assertEqualsWithDelta(86.6666666667, $results[$parent->id]['target'], 0.000001);
        $a = $parent->childrenRecursive->firstWhere('kode', 'A');
        $b = $parent->childrenRecursive->firstWhere('kode', 'B');
        $this->assertEqualsWithDelta(60, $results[$a->id]['target'], 0.000001);
        $this->assertEqualsWithDelta(26.6666666667, $results[$b->id]['target'], 0.000001);
        $this->assertSame((string) $a->childrenRecursive->first()->id, $a->calculation_config['source_id']);
    }

    public function test_weighted_tree_can_be_created_and_updated_without_changing_ids_or_realizations(): void
    {
        $root = $this->node('IKK 14', 'weighted_sum', null, [$this->node('1', 'manual', 55), $this->node('2', 'manual', 100), $this->node('3', 'manual', '80,95')], ['weights' => ['1' => 40, '2' => 40, '3' => 20]]);
        $response = $this->save($root)->assertOk();
        $this->assertEqualsWithDelta(78.19, $response->json('target'), 0.000001);
        $parent = Kontrak::with('childrenRecursive')->find($response->json('id'));
        $root['id'] = $parent->id;
        foreach ($root['children'] as &$child) {
            $child['id'] = $parent->childrenRecursive->firstWhere('kode', $child['kode'])->id;
        }
        unset($child);
        $id = $root['children'][0]['id'];
        $record = Realisasi::create(['id_kontrak' => $id, 'periode' => '2026-11-01', 'nilai' => 50]);
        $root['children'][0]['target'] = 70;
        $this->save($root)->assertOk();
        $this->assertSame('70.000000', Kontrak::find($id)->target);
        $this->assertDatabaseHas('realisasis', ['id' => $record->id, 'id_kontrak' => $id, 'nilai' => 50]);
        $this->assertSame(3, Kontrak::where('id_parent', $parent->id)->count());
        $root['children'][] = $this->node('4', 'manual', 20);
        $root['calculation_config']['weights']['4'] = 0;
        $this->save($root)->assertOk();
        $this->assertSame(4, Kontrak::where('id_parent', $parent->id)->count());
    }

    private function existingRatio(): array
    {
        $root = $this->node('IKK 10', 'ratio', null, [$this->node('70', 'manual', 18), $this->node('71', 'manual', 21)], ['numerator_id' => '70', 'denominator_id' => '71', 'factor' => 100]);
        $root['id'] = 69;
        $root['children'][0]['id'] = 70;
        $root['children'][1]['id'] = 71;

        return $root;
    }

    public function test_invalid_descendant_rolls_back_parent_edits_and_new_rows(): void
    {
        $root = $this->existingRatio();
        $before = Kontrak::count();
        $name = Kontrak::find(69)->nama;
        $root['nama'] = 'Should roll back';
        $root['children'][] = $this->node('new', 'manual', 5);
        $foreign = $this->node('foreign', 'manual', 5);
        $foreign['id'] = 8;
        $root['children'][] = $foreign;
        $this->save($root)->assertUnprocessable();
        $this->assertSame($before, Kontrak::count());
        $this->assertSame($name, Kontrak::find(69)->nama);
        $this->assertDatabaseMissing('kontraks', ['kode' => 'new', 'id_parent' => 69]);
    }

    public function test_existing_rows_cannot_be_silently_removed_or_referenced_from_another_branch(): void
    {
        $root = $this->existingRatio();
        array_pop($root['children']);
        $root['calculation_config']['denominator_id'] = null;
        $this->save($root)->assertUnprocessable();
        $this->assertDatabaseHas('kontraks', ['id' => 71, 'id_parent' => 69]);
        $root = $this->existingRatio();
        $root['calculation_config']['numerator_id'] = 'elsewhere';
        $this->save($root)->assertUnprocessable();
        $root = $this->existingRatio();
        $root['children'][1]['key'] = '70';
        $this->save($root)->assertUnprocessable();
        $root = $this->existingRatio();
        $root['children'][1]['id'] = 70;
        $this->save($root)->assertUnprocessable();
    }

    public function test_drafts_large_targets_and_units_are_supported_in_the_complete_form(): void
    {
        $this->save($this->node('draft', 'weighted_sum'))->assertOk()->assertJsonPath('target', null);
        $this->save($this->node('draft-ratio', 'ratio'))->assertOk()->assertJsonPath('target', null);
        $root = $this->node('KPI besar', 'manual', '15450000000');
        $root['jenis_target'] = 'rupiah';
        $response = $this->save($root)->assertOk();
        $this->assertSame('15450000000.000000', Kontrak::find($response->json('id'))->target);
        $this->assertSame('rupiah', Kontrak::find($response->json('id'))->jenis_target);
    }

    public function test_all_existing_excel_indicators_can_be_saved_from_the_complete_editor_without_changing_targets(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/cascading_2026.json')), true);
        foreach (['PK' => 1000, 'KPI' => 2000] as $jenis => $parentId) {
            $forms = $this->get('/kontrak?jenis='.$jenis)->assertOk()->viewData('calculationForms');
            foreach ($fixture['nodes'] as $data) {
                if ($data['jenis'] !== $jenis) {
                    continue;
                }
                $tree = $forms[$data['id']]['tree'];
                $response = $this->postJson('/save-kontrak-tree', ['id_parent' => $parentId, 'jenis' => $jenis, 'root' => $tree])->assertOk();
                $this->assertEqualsWithDelta((float) $data['expected'], $response->json('target'), 0.000001, $data['kode']);
            }
        }
    }
}
