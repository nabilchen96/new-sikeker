<?php

namespace Tests\Feature;

use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class RealisasiProgressTest extends TestCase
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

    private function item(string $code): array
    {
        $response = $this->get('/realisasi?jenis=PK&bulan=11&tahun=2026')->assertOk();

        return collect($response->viewData('data'))->first(fn ($item) => $item['ikk']->kode === $code);
    }

    private function convertToSheet(int $id): void
    {
        $rows = $this->get('/kontrak-sheet?indikator='.$id)->assertOk()->viewData('sheetRows');
        $this->postJson('/save-kontrak-sheet', ['id_parent' => 1000, 'jenis' => 'PK', 'rows' => $rows])->assertOk();
    }

    public function test_one_completed_source_produces_progress_and_does_not_write_zero_records(): void
    {
        Realisasi::create(['id_kontrak' => 23, 'periode' => '2026-11-01', 'nilai' => 206]);
        foreach ([false, true] as $sheet) {
            if ($sheet) {
                $this->convertToSheet(21);
            }
            $item = $this->item('IKK 3');
            $this->assertEqualsWithDelta(100 / 6, $item['persentase'], 0.000001);
            $this->assertNull($item['calculation_error']);
            $this->assertSame(1, Realisasi::count());
            $this->assertCount(6, $item['indikators']);
        }
    }

    public function test_all_sources_including_denominators_contribute_to_progress(): void
    {
        foreach ([12 => 54, 13 => 106, 14 => 910, 15 => 0, 17 => 10, 18 => 10, 19 => 10, 20 => 0] as $id => $value) {
            Realisasi::create(['id_kontrak' => $id, 'periode' => '2026-11-01', 'nilai' => $value]);
        }
        foreach ([false, true] as $sheet) {
            if ($sheet) {
                $this->convertToSheet(10);
            }
            $item = $this->item('IKK 2');
            $this->assertEqualsWithDelta((100 + 100 + 910 / 660 * 100 + 10 / 54 * 100 + 10 / 144 * 100 + 10 / 660 * 100) / 6, $item['persentase'], 0.000001);
            $this->assertEqualsWithDelta(820 / 858 * 100, $item['total_target'], 0.000001);
            $this->assertNull($item['calculation_error']);
        }
    }

    public function test_screenshot_is_54_50_percent_instead_of_100(): void
    {
        foreach ([12 => 54, 13 => 106, 14 => 660, 15 => 0, 17 => 10, 18 => 10, 19 => 10, 20 => 0] as $id => $value) {
            Realisasi::create(['id_kontrak' => $id, 'periode' => '2026-11-01', 'nilai' => $value]);
        }
        foreach ([false, true] as $sheet) {
            if ($sheet) {
                $this->convertToSheet(10);
            }
            $this->assertEqualsWithDelta(54.50, $this->item('IKK 2')['persentase'], 0.005);
        }
    }

    public function test_all_reported_targets_give_100_and_weighted_branches_match_excel_editor(): void
    {
        $calculator = new \App\Services\KontrakCalculator;
        foreach (['IKK 4', 'IKK 5', 'IKK 16'] as $code) {
            $node = \App\Models\Kontrak::with('childrenRecursive')->where('kode', $code)->firstOrFail();
            $results = $calculator->calculateTree($node);
            $actuals = [];
            foreach ($calculator->inputNodes($node, $results) as $input) {
                $actuals[(string) $input->id] = $results[(string) $input->id]['target'];
            }
            $this->assertEqualsWithDelta(100, (new \App\Services\KontrakAchievement)->percentage($node, $results, $actuals), 0.000001);
            $first = $calculator->inputNodes($node, $results)[0];
            $partial = [(string) $first->id => $actuals[(string) $first->id]];
            $expected = (new \App\Services\KontrakAchievement)->percentage($node, $results, $partial);
            $this->convertToSheet($node->id);
            $node->refresh()->load('childrenRecursive');
            $this->assertEqualsWithDelta($expected, (new \App\Services\KontrakAchievement)->percentage($node, $calculator->calculateTree($node), $partial), 0.000001);

        }
    }

    public function test_empty_entries_start_at_zero_even_with_an_excel_constant(): void
    {
        foreach (['IKK 1', 'IKK 3', 'IKK 14', 'IKK 24'] as $code) {
            $this->assertSame(0.0, $this->item($code)['persentase']);
        }
        $this->assertSame(0, Realisasi::count());
    }

    public function test_partial_weighted_progress_keeps_the_configured_weights(): void
    {
        Realisasi::create(['id_kontrak' => 83, 'periode' => '2026-11-01', 'nilai' => 55]);
        foreach ([false, true] as $sheet) {
            if ($sheet) {
                $this->convertToSheet(82);
            }
            $this->assertEqualsWithDelta(40, $this->item('IKK 14')['persentase'], 0.000001);
        }
    }

    public function test_previous_value_is_carried_forward_without_filling_the_current_input(): void
    {
        Realisasi::create(['id_kontrak' => 23, 'periode' => '2026-10-01', 'nilai' => 206]);
        $item = $this->item('IKK 3');
        $this->assertEqualsWithDelta(100 / 6, $item['persentase'], 0.000001);
        $source = $item['indikators']->firstWhere('id', 23);
        $this->assertNull($source->nilai_realisasi);
        $this->assertSame(206.0, $source->realisasi_sebelumnya);
        $this->assertSame(1, Realisasi::count());
    }

    public function test_green_summary_has_target_unit_and_progress_but_no_realization_column(): void
    {
        foreach (['PK', 'KPI'] as $jenis) {
            $html = $this->get('/realisasi?jenis='.$jenis.'&bulan=11&tahun=2026')->assertOk()->getContent();
            $old = libxml_use_internal_errors(true);
            $doc = new \DOMDocument;
            $doc->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors($old);
            $xpath = new \DOMXPath($doc);
            $headers = $xpath->query('(//thead[contains(@class,"bg-success")])[1]/tr[1]/th');
            $this->assertSame(4, $headers->length);
            $this->assertSame('Target', trim($headers->item(1)->textContent));
            $this->assertSame('Satuan', trim($headers->item(2)->textContent));
            $this->assertSame('% Capaian', trim($headers->item(3)->textContent));
            $this->assertStringContainsString('name="realisasi[', $html);
        }
    }
}
