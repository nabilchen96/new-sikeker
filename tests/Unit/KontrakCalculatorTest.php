<?php

namespace Tests\Unit;

use App\Models\Kontrak;
use App\Services\KontrakCalculator;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class KontrakCalculatorTest extends TestCase
{
    private function node(array $data): Kontrak
    {
        $node = new Kontrak;
        $node->forceFill(array_intersect_key($data, array_flip(['id', 'kode', 'nama', 'jenis', 'target', 'jenis_target', 'calculation_type', 'calculation_config'])));
        $node->setRelation('childrenRecursive', new Collection(array_map(fn ($child) => $this->node($child), $data['children'] ?? [])));

        return $node;
    }

    public function test_all_workbook_targets_match_the_original_cached_values(): void
    {
        $fixture = json_decode(file_get_contents(__DIR__.'/../Fixtures/cascading_2026.json'), true);
        $calculator = new KontrakCalculator;
        $checked = 0;
        foreach ($fixture['nodes'] as $data) {
            $node = $this->node($data);
            $results = $calculator->calculateTree($node);
            $check = function ($item) use (&$check, &$checked, $results) {
                if (is_numeric($item['expected'])) {
                    $this->assertNull($results[$item['id']]['error'], $item['cell']);
                    $this->assertEqualsWithDelta((float) $item['expected'], $results[$item['id']]['target'], 0.000001, $item['cell'].' '.($item['formula'] ?? 'manual'));
                    $checked++;
                }
                foreach ($item['children'] as $child) {
                    $check($child);
                }
            };
            $check($data);
            // Setting all manual realizations to their targets must yield the same aggregate.
            $actuals = [];
            foreach ($calculator->inputNodes($node, $results) as $input) {
                $actuals[(string) $input->id] = (float) $input->target;
            }
            $actualResult = $calculator->calculateTree($node, $actuals)[$node->id];
            $this->assertEqualsWithDelta((float) $data['expected'], $actualResult['actual'], 0.000001, $data['kode'].' realization');
        }
        $this->assertGreaterThan(100, $checked);
    }

    public function test_explicit_formulas_ignore_codes_and_preserve_each_nodes_unit(): void
    {
        $node = $this->node(['id' => 1, 'kode' => 'IKK 5', 'jenis' => 'PK', 'jenis_target' => 'nilai',
            'calculation_type' => 'ratio', 'calculation_config' => ['numerator_id' => 3, 'denominator_id' => 2, 'factor' => 100],
            'children' => [
                ['id' => 2, 'kode' => 'foo', 'target' => 21, 'jenis_target' => 'dokumen'],
                ['id' => 3, 'kode' => 'bar', 'target' => 18, 'jenis_target' => 'kegiatan'],
            ],
        ]);
        $calculator = new KontrakCalculator;
        $results = $calculator->calculateTree($node);
        $this->assertEqualsWithDelta(85.714285714, $results[1]['target'], 0.000001);
        $this->assertSame('nilai', $results[1]['unit']);
        $this->assertSame('dokumen', $results[2]['unit']);
        $this->assertSame('kegiatan', $results[3]['unit']);
        $this->assertNull($results[1]['actual']);
        $this->assertEqualsWithDelta(50, $calculator->calculateTree($node, [2 => 20, 3 => 10])[1]['actual'], 0.000001);
    }

    public function test_zero_missing_and_deleted_sources_do_not_turn_into_fake_zero_results(): void
    {
        $calculator = new KontrakCalculator;
        $config = ['numerator_id' => 1, 'denominator_id' => 2, 'factor' => 100];
        $children = [1 => ['target' => 18], 2 => ['target' => 0]];
        [$value, $error] = $calculator->evaluate('ratio', $config, $children, 'target', null);
        $this->assertNull($value);
        $this->assertStringContainsString('Pembagi nol', $error);
        unset($children[2]);
        $this->assertNull($calculator->evaluate('ratio', $config, $children, 'target', null)[0]);
        $children = [1 => ['target' => 0], 2 => ['target' => 21]];
        $this->assertSame(0.0, $calculator->evaluate('ratio', $config, $children, 'target', null)[0]);
    }

    public function test_manual_parent_is_not_replaced_when_explanatory_children_are_added(): void
    {
        $node = $this->node(['id' => 1, 'kode' => 'IKK 25', 'jenis' => 'PK', 'target' => 90.5, 'jenis_target' => 'nilai', 'children' => [
            ['id' => 2, 'kode' => '1', 'target' => 500, 'jenis_target' => 'rupiah'],
        ]]);
        $calculator = new KontrakCalculator;
        $results = $calculator->calculateTree($node);
        $this->assertSame(90.5, $results[1]['target']);
        $this->assertSame([1], array_map(fn ($input) => $input->id, $calculator->inputNodes($node, $results)));
    }

    public function test_weighted_percentage_is_applied_once_after_adding_children(): void
    {
        $node = $this->node(['id' => 1, 'kode' => 'IKK 5', 'jenis' => 'PK', 'children' => [
            ['id' => 2, 'kode' => 'A', 'target' => 36.58, 'jenis_target' => 'dokumen', 'children' => [['id' => 3, 'kode' => '1', 'target' => 91.45, 'jenis_target' => 'nilai']]],
            ['id' => 4, 'kode' => 'B', 'target' => 57.34, 'children' => [['id' => 5, 'kode' => '1', 'target' => 95.57, 'jenis_target' => 'persen']]],
        ]]);
        $results = (new KontrakCalculator)->calculateTree($node);
        $this->assertEqualsWithDelta(93.922, $results[1]['target'], 0.000001);
        $this->assertEqualsWithDelta(36.58, $results[2]['target'], 0.000001);
        $this->assertSame('dokumen', $results[2]['unit']);
        $this->assertSame('nilai', $results[3]['unit']);
        $this->assertSame('persen', $results[5]['unit']);
    }

    public function test_unassigned_or_all_zero_weights_are_incomplete_instead_of_a_zero_target(): void
    {
        $calculator = new KontrakCalculator;
        $children = [1 => ['target' => 55], 2 => ['target' => 100]];
        foreach ([[], [1 => 0, 2 => 0], [1 => 40]] as $weights) {
            [$value, $error] = $calculator->evaluate('weighted_sum', ['weights' => $weights], $children, 'target', null);
            $this->assertNull($value);
            $this->assertNotNull($error);
        }
        [$value, $error] = $calculator->evaluate('weighted_sum', ['weights' => [1 => 40, 2 => 0]], $children, 'target', null);
        $this->assertSame(22.0, $value);
        $this->assertNull($error);
    }
}
