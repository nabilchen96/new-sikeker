<?php

namespace Tests\Feature;

use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\CascadingDatabase;
use Tests\TestCase;

class KontrakKpiParentTest extends TestCase
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
        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($doc);
    }

    public function test_kpi_objective_add_posts_the_objective_id_instead_of_the_first_iku_id(): void
    {
        $xpath = $this->xpath($this->get('/kontrak?jenis=KPI')->assertOk()->getContent());
        $links = $xpath->query('//a[@data-target="#modalTambahBagian" and @data-bs-indikator_induk="Sasaran KPI" and not(@data-bs-id)]');
        $this->assertSame(1, $links->length);
        $button = $links->item(0);
        $this->assertSame('2000', $button->getAttribute('data-bs-id_parent'));
        $this->assertStringContainsString('Tambah IKU', $button->textContent);
        $this->postJson('/store-bagian', [
            'id' => '', 'id_parent' => $button->getAttribute('data-bs-id_parent'), 'jenis' => 'KPI', 'kode' => 'IKU 15', 'sub_indikator' => 'IKU baru', 'target' => 12.5, 'jenis_target' => 'nilai', 'calculation_type' => 'manual',
        ])->assertOk();
        $this->assertDatabaseHas('kontraks', ['kode' => 'IKU 15', 'id_parent' => '2000', 'jenis' => 'KPI']);
        $detail = $xpath->query('//a[@data-target="#modalTambahBagian" and @data-bs-indikator_induk="Margin EBITDA (%)" and not(@data-bs-id)]')->item(0);
        $this->assertNotNull($detail);
        $this->assertSame('113', $detail->getAttribute('data-bs-id_parent'));
        $this->assertStringContainsString('Tambah rincian', $detail->textContent);
    }

    public function test_pk_and_empty_objective_actions_still_target_the_objective(): void
    {
        $xpath = $this->xpath($this->get('/kontrak?jenis=PK')->assertOk()->getContent());
        $button = $xpath->query('//a[@data-target="#modalTambahBagian" and @data-bs-indikator_induk="Sasaran PK" and not(@data-bs-id)]')->item(0);
        $this->assertSame('1000', $button->getAttribute('data-bs-id_parent'));
        $this->assertStringContainsString('Tambah IKK', $button->textContent);
        Kontrak::forceCreate(['id' => 3000, 'kode' => 'SK kosong', 'nama' => 'Sasaran kosong', 'jenis' => 'KPI', 'id_tahun' => 1]);
        $html = $this->get('/kontrak?jenis=KPI')->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $button = $xpath->query('//a[@data-target="#modalTambahBagian" and @data-bs-indikator_induk="Sasaran kosong" and not(@data-bs-id)]')->item(0);
        $this->assertSame('3000', $button->getAttribute('data-bs-id_parent'));
        $this->assertStringContainsString('hapusDataSasaranKinerja(3000)', $html);
        $this->assertStringNotContainsString('hapusDataSasaranKinerja(113)', $html);
    }

    private function misplaced(): Kontrak
    {
        return Kontrak::forceCreate(['id' => 3001, 'id_parent' => 113, 'kode' => 'IKU 15', 'nama' => 'IKU salah induk', 'jenis' => 'KPI', 'id_tahun' => 1, 'target' => 12.5, 'jenis_target' => 'nilai']);
    }

    public function test_misplaced_iku_can_be_promoted_without_losing_records_or_its_children(): void
    {
        $node = $this->misplaced();
        Kontrak::forceCreate(['id' => 3002, 'id_parent' => $node->id, 'kode' => '1', 'nama' => 'Keterangan', 'jenis' => 'KPI', 'id_tahun' => 1]);
        Realisasi::create(['id_kontrak' => $node->id, 'periode' => '2026-11-01', 'nilai' => 8]);
        DB::table('rencana_prokers')->insert(['id' => 1, 'id_pk' => (string) $node->id]);
        $this->postJson('/promote-kontrak-kpi', ['id' => $node->id])->assertOk()->assertJsonPath('responCode', 1);
        $this->assertSame('2000', Kontrak::find($node->id)->id_parent);
        $this->assertSame('3001', Kontrak::find(3002)->id_parent);
        $this->assertDatabaseHas('realisasis', ['id_kontrak' => 3001, 'nilai' => 8]);
        $this->assertDatabaseHas('rencana_prokers', ['id' => 1, 'id_pk' => '3001']);
        $this->postJson('/promote-kontrak-kpi', ['id' => $node->id])->assertOk();
    }

    public function test_promotion_rejects_formula_dependencies_and_non_kpi_nodes(): void
    {
        $node = $this->misplaced();
        Kontrak::find(113)->update(['calculation_type' => 'sum', 'calculation_config' => []]);
        $this->postJson('/promote-kontrak-kpi', ['id' => $node->id])->assertUnprocessable();
        $this->assertSame('113', Kontrak::find($node->id)->id_parent);
        Kontrak::find(113)->update(['calculation_type' => 'manual', 'calculation_config' => []]);
        $node->update(['calculation_type' => 'formula', 'calculation_config' => ['ast' => ['op' => 'ref', 'id' => '113']]]);
        $this->postJson('/promote-kontrak-kpi', ['id' => $node->id])->assertUnprocessable();
        $this->postJson('/promote-kontrak-kpi', ['id' => 12])->assertUnprocessable();
        $this->postJson('/promote-kontrak-kpi', ['id' => 2000])->assertUnprocessable();
        $this->assertSame('113', Kontrak::find($node->id)->id_parent);
    }
}
