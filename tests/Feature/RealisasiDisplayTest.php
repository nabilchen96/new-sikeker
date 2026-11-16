<?php

namespace Tests\Feature;

use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class RealisasiDisplayTest extends TestCase
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

    private function xpath(string $html): \DOMXPath
    {
        $old = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($old);

        return new \DOMXPath($document);
    }

    public function test_kpi_target_keeps_decimals_and_units_have_separate_columns(): void
    {
        Realisasi::create(['id_kontrak' => 113, 'periode' => '2026-10-01', 'nilai' => 1.5]);
        Realisasi::create(['id_kontrak' => 113, 'periode' => '2026-11-01', 'nilai' => 1.79]);
        $xpath = $this->xpath($this->get('/realisasi?jenis=KPI&bulan=11&tahun=2026')->assertOk()->getContent());
        $cells = $xpath->query('//input[@name="realisasi[113]"]/ancestor::tr[1]/td');
        $this->assertSame(7, $cells->length);
        $this->assertSame('1,79', trim($cells->item(2)->textContent));
        $this->assertSame('persen', trim($cells->item(3)->textContent));
        $this->assertSame('1,5', trim($cells->item(4)->textContent));
        $summary = $xpath->query('//thead[contains(@class,"bg-success")]/tr[2]')->item(0);
        $values = $xpath->query('./th', $summary);
        $this->assertSame(5, $values->length);
        $this->assertSame('1,79', trim($values->item(1)->textContent));
        $this->assertSame('1,79', trim($values->item(2)->textContent));
        $this->assertSame('persen', trim($values->item(3)->textContent));
        $large = $xpath->query('//input[@name="realisasi[114]"]/ancestor::tr[1]/td');
        $this->assertSame('15.450.000.000', trim($large->item(2)->textContent));
        $this->assertSame('rupiah', trim($large->item(3)->textContent));
    }

    public function test_pk_also_separates_target_and_unit_without_adding_decimal_zeros(): void
    {
        $xpath = $this->xpath($this->get('/realisasi?jenis=PK&bulan=11&tahun=2026')->assertOk()->getContent());
        $cells = $xpath->query('//input[@name="realisasi[8]"]/ancestor::tr[1]/td');
        $this->assertSame(8, $cells->length);
        $this->assertSame('107', trim($cells->item(2)->textContent));
        $this->assertSame('orang', trim($cells->item(3)->textContent));
        $summary = $xpath->query('//thead[contains(@class,"bg-success")]/tr[2]')->item(0);
        $values = $xpath->query('./th', $summary);
        $this->assertSame('91,45', trim($values->item(1)->textContent));
        $this->assertSame('persen', trim($values->item(3)->textContent));
    }

    public function test_save_keeps_kpi_tab_and_selected_period(): void
    {
        $response = $this->post('/store-realisasi', ['jenis' => 'KPI', 'bulan' => 5, 'tahun' => 2026, 'realisasi' => [113 => 1.79]]);
        $response->assertRedirect(url('/realisasi').'?jenis=KPI&bulan=5&tahun=2026');
        $this->assertDatabaseHas('realisasis', ['id_kontrak' => 113, 'nilai' => 1.79]);
        $this->assertSame('2026-05-01', Realisasi::where('id_kontrak', 113)->firstOrFail()->periode->toDateString());
        $this->get($response->headers->get('Location'))->assertOk()->assertViewHas('bulan', 5)->assertViewHas('tahun', 2026);
    }

    public function test_legacy_submissions_infer_the_tab_from_the_indicator(): void
    {
        foreach ([[113, 1.79, 'KPI'], [8, 107, 'PK']] as [$id,$value,$jenis]) {
            $this->post('/store-realisasi', ['bulan' => 2, 'tahun' => 2026, 'realisasi' => [$id => $value]])
                ->assertRedirect(url('/realisasi').'?jenis='.$jenis.'&bulan=2&tahun=2026');
        }
    }

    public function test_filters_and_tab_links_preserve_type_month_and_year(): void
    {
        foreach (['PK', 'KPI'] as $jenis) {
            $xpath = $this->xpath($this->get('/realisasi?jenis='.$jenis.'&bulan=3&tahun=2025')->assertOk()->getContent());
            foreach (['GET', 'POST'] as $method) {
                $this->assertSame($jenis, $xpath->query('//form[@method="'.$method.'"]/input[@name="jenis"]')->item(0)->getAttribute('value'));
                $this->assertSame('2025', $xpath->query('//form[@method="'.$method.'"]/input[@name="tahun"]')->item(0)->getAttribute('value'));
            }
            foreach ($xpath->query('//ul[@id="myTab"]//a') as $tab) {
                parse_str(parse_url($tab->getAttribute('href'), PHP_URL_QUERY), $query);
                $this->assertSame('3', $query['bulan']);
                $this->assertSame('2025', $query['tahun']);
            }
        }
    }

    public function test_mismatched_or_mixed_tabs_do_not_write_values(): void
    {
        $this->postJson('/store-realisasi', ['jenis' => 'PK', 'bulan' => 11, 'tahun' => 2026, 'realisasi' => [113 => 1.79]])->assertUnprocessable();
        $this->postJson('/store-realisasi', ['bulan' => 11, 'tahun' => 2026, 'realisasi' => [113 => 1.79, 8 => 107]])->assertUnprocessable();
        $this->assertSame(0, Realisasi::count());
    }

    public function test_saving_again_updates_the_same_indicator_period(): void
    {
        foreach ([1.79, 1.65] as $value) {
            $this->post('/store-realisasi', ['jenis' => 'KPI', 'bulan' => 11, 'tahun' => 2026, 'realisasi' => [113 => $value]])
                ->assertRedirect(url('/realisasi').'?jenis=KPI&bulan=11&tahun=2026');
        }
        $this->assertSame(1, Realisasi::where('id_kontrak', 113)->count());
        $this->assertSame('1.65', Realisasi::where('id_kontrak', 113)->firstOrFail()->nilai);
        $this->post('/store-realisasi', ['jenis' => 'KPI', 'bulan' => 12, 'tahun' => 2026, 'realisasi' => [113 => 1.8]])->assertRedirect();
        $this->assertSame(2, Realisasi::where('id_kontrak', 113)->count());
    }
}
