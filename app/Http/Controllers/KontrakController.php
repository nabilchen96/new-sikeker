<?php

namespace App\Http\Controllers;

use App\Http\Requests\KontrakBagianRequest;
use App\Http\Requests\KontrakSheetRequest;
use App\Http\Requests\KontrakTreeRequest;
use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Services\KontrakCalculator;
use App\Services\KontrakFormula;
use App\Services\KontrakSheet;
use App\Services\KontrakTreeWriter;
use DB;
use Illuminate\Http\Request;
use Validator;

class KontrakController extends Controller
{
    public function index(Request $request)
    {
        /*
         * ==========================================================
         * AMBIL SASARAN
         * ==========================================================
         */

        $sasaran = Kontrak::query()
            ->join('tahuns', 'tahuns.id', '=', 'kontraks.id_tahun')
            ->where('tahuns.status', 'Aktif')
            ->where('kontraks.jenis', Request('jenis') == 'PK' || Request('jenis') == null ? 'PK' : 'KPI')
            ->whereNull('kontraks.id_parent')
            ->with('childrenRecursive')
            // ->orderBy('kontraks.kode')

            ->select('kontraks.*')
            ->get();

        /*
         * ==========================================================
         * KUMPULKAN SEMUA ID KONTRAK
         * ==========================================================
         */

        $kontrakIds = collect();

        foreach ($sasaran as $item) {

            $kontrakIds->push($item->id);

            $this->collectKontrakIds(
                $item->childrenRecursive,
                $kontrakIds
            );
        }

        /*
         * ==========================================================
         * AMBIL REALISASI TERAKHIR SETIAP KONTRAK
         * ==========================================================
         *
         * Contoh:
         *
         * kontrak 1003
         * September = 100
         * Oktober   = 120
         * November  = 130
         *
         * Yang digunakan = 130
         */

        $realisasiTerakhir = Realisasi::query()
            ->whereIn(
                'id_kontrak',
                $kontrakIds->unique()
            )
            ->orderByDesc('periode')
            ->get()
            ->groupBy('id_kontrak')
            ->map(function ($items) {
                return $items->first();
            });

        /*
         * ==========================================================
         * BENTUK DATA TREE
         * ==========================================================
         */

        $data = $sasaran->map(function ($item) use ($realisasiTerakhir) {

            $rows = [];

            /*
             * Semua child dimasukkan ke rows
             */
            $this->flattenRows(
                $item->childrenRecursive,
                0,
                $rows
            );

            /*
             * Kalau sasaran tidak mempunyai child
             */
            if (count($rows) === 0) {

                $rows[] = [
                    'model' => $item,
                    'level' => 0,
                ];
            }

            /*
             * ======================================================
             * HITUNG TARGET / REALISASI SETIAP BARIS
             * ======================================================
             */

            $actuals = $realisasiTerakhir->map(fn ($record) => (float) $record->nilai)->all();
            $calculated = (new KontrakCalculator)->calculateTree($item, $actuals);
            foreach ($rows as &$row) {
                $kontrak = $row['model'];
                $result = $calculated[(string) $kontrak->id];
                $row['total_target'] = $result['target'];
                $row['total_realisasi'] = $result['actual'];
                $row['satuan_tampilan'] = $result['unit'];
                $row['calculation'] = $result;
                $row['editor_tree'] = $this->editorTree($kontrak, $calculated);
                $row['calculation_children'] = $kontrak->childrenRecursive->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode))->map(fn ($child) => [
                    'id' => (string) $child->id,
                    'label' => trim($child->kode.' — '.$child->nama),
                    'target' => $calculated[(string) $child->id]['target'],
                ])->values()->all();
            }
            unset($row);

            return [
                'sasaran' => $item,

                'rows' => $rows,

                'rowspan' => count($rows),
            ];
        });

        // dd($data);

        return view('backend.kontrak.index', [
            'data' => $data,
            'calculationForms' => $data->flatMap(fn ($group) => collect($group['rows'])->map(fn ($row) => [
                'id' => (string) $row['model']->id,
                'tree' => $row['editor_tree'],
                'type' => $row['model']->calculation_type,
                'config' => $row['model']->calculation_config,
                'effective_type' => $row['calculation']['type'],
                'computed_target' => $row['calculation']['target'],
                'unit' => $row['satuan_tampilan'],
                'effective_config' => $row['calculation']['config'],
                'children' => $row['calculation_children'],
            ]))->keyBy('id'),
        ]);
    }

    public function storeSasaranKinerja(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'jenis' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors(),
            ];
        } else {
            Kontrak::create([
                'id_parent' => null,
                'kode' => 0,
                'nama' => $request->nama,
                'jenis' => $request->jenis,
                'target' => null,
                'jenis_target' => null,
                'id_tahun' => DB::table('tahuns')->where('status', 'Aktif')->value('id'),
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan',
            ];
        }

        return response()->json($data);
    }

    public function updateSasaranKinerja(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'nama' => 'required',
            'jenis' => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors(),
            ];
        } else {

            $kontrak = Kontrak::find($request->id);
            $kontrak->update([
                'nama' => $request->nama,
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan',
            ];
        }

        return response()->json($data);
    }

    public function deleteSasaranKinerja(Request $request)
    {

        // dd($request->id);

        $data = Kontrak::find($request->id)->delete();

        $data = [
            'responCode' => 1,
            'respon' => 'Data Sukses Dihapus',
        ];

        return response()->json($data);
    }

    public function sheet(Request $request, KontrakSheet $sheet)
    {
        $jenis = $request->query('jenis') === 'KPI' ? 'KPI' : 'PK';
        $parents = Kontrak::whereNull('id_parent')->where('jenis', $jenis)
            ->whereIn('id_tahun', DB::table('tahuns')->where('status', 'Aktif')->pluck('id'))->get();
        $indicators = Kontrak::whereIn('id_parent', $parents->pluck('id'))->with('childrenRecursive')->get()
            ->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode))->values();
        $selected = $request->query('indikator') === 'baru' ? null : ($indicators->firstWhere('id', $request->query('indikator')) ?? $indicators->first());

        return view('backend.kontrak.sheet', [
            'jenis' => $jenis, 'parents' => $parents, 'indicators' => $indicators, 'selected' => $selected,
            'sheetRows' => $selected ? $sheet->rows($selected) : [['key' => 'new-root', 'id' => null, 'parent_key' => null, 'kode' => '', 'nama' => '', 'jenis_target' => 'persen', 'input' => '']],
        ]);
    }

    public function previewSheet(KontrakSheetRequest $request, KontrakSheet $sheet)
    {
        $data = $request->validated();

        return response()->json(['rows' => $sheet->preview($data['rows'], $data['jenis'])]);
    }

    public function saveSheet(KontrakSheetRequest $request, KontrakSheet $sheet, KontrakTreeWriter $writer)
    {
        $data = $request->validated();
        $root = $writer->save((int) $data['id_parent'], $data['jenis'], $sheet->compile($data['rows']), true);

        return response()->json(['id' => $root->id, 'rows' => $sheet->rows($root), 'message' => 'Tabel berhasil disimpan.']);
    }

    public function saveTree(KontrakTreeRequest $request, KontrakTreeWriter $writer)
    {
        $data = $request->validated();
        $root = $writer->save((int) $data['id_parent'], $data['jenis'], $data['root']);
        $result = (new KontrakCalculator)->calculateTree($root)[(string) $root->id];

        return response()->json(['responCode' => 1, 'respon' => 'Indikator dan seluruh rinciannya berhasil disimpan.', 'id' => $root->id, 'target' => $result['target']]);
    }

    private function editorTree($node, array $results): array
    {
        $result = $results[(string) $node->id];
        $config = $result['config'];
        foreach (['source_id', 'numerator_id', 'denominator_id'] as $field) {
            if (isset($config[$field])) {
                $config[$field] = 'id-'.$config[$field];
            }
        }
        if (isset($config['ast'])) {
            $config['ast'] = (new KontrakFormula)->mapReferences($config['ast'], fn ($id) => 'id-'.$id);
        }
        if (isset($config['weights'])) {
            $weights = [];
            foreach ($config['weights'] as $id => $weight) {
                $weights['id-'.$id] = $weight;
            }
            $config['weights'] = $weights;
        }
        if (isset($config['child_ids'])) {
            $config['child_ids'] = array_map(fn ($id) => $id === null ? null : 'id-'.$id, $config['child_ids']);
        }

        return [
            'key' => 'id-'.$node->id, 'id' => $node->id, 'kode' => $node->kode,
            'nama' => $node->nama, 'target' => $node->target, 'jenis_target' => $result['unit'],
            'calculation_type' => $result['type'], 'calculation_config' => $config,
            'children' => $node->childrenRecursive->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode))->map(fn ($child) => $this->editorTree($child, $results))->values()->all(),
        ];
    }

    public function storeBagian(KontrakBagianRequest $request)
    {
        Kontrak::create($this->bagianAttributes($request));

        return response()->json(['responCode' => 1, 'respon' => 'Data sukses disimpan.']);
    }

    public function updateBagian(KontrakBagianRequest $request)
    {
        Kontrak::findOrFail($request->id)->update($this->bagianAttributes($request));

        return response()->json(['responCode' => 1, 'respon' => 'Data sukses disimpan.']);
    }

    private function bagianAttributes(KontrakBagianRequest $request): array
    {
        return array_merge([
            'id_parent' => $request->id_parent,
            'kode' => $request->kode,
            'nama' => $request->sub_indikator,
            'jenis' => $request->jenis,
            'target' => $request->target,
            'jenis_target' => $request->jenis_target,
            'id_tahun' => DB::table('tahuns')->where('status', 'Aktif')->value('id'),
        ], $request->calculationSettings());
    }

    public function deleteBagian(Request $request)
    {

        // dd($request->id);

        $data = Kontrak::find($request->id)->delete();

        $data = [
            'responCode' => 1,
            'respon' => 'Data Sukses Dihapus',
        ];

        return response()->json($data);
    }

    /*
     * ==========================================================
     * KUMPULKAN ID KONTRAK SECARA RECURSIVE
     * ==========================================================
     */

    private function collectKontrakIds(
        $nodes,
        &$ids
    ): void {

        foreach ($nodes->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode)) as $node) {

            $ids->push($node->id);

            if (
                $node->childrenRecursive->isNotEmpty()
            ) {

                $this->collectKontrakIds(
                    $node->childrenRecursive,
                    $ids
                );
            }
        }
    }

    /*
     * ==========================================================
     * FLATTEN TREE UNTUK BLADE
     * ==========================================================
     */

    private function flattenRows(
        $nodes,
        int $level,
        array &$rows
    ): void {

        foreach ($nodes->sort(fn ($a, $b) => strnatcasecmp((string) $a->kode, (string) $b->kode)) as $node) {

            $rows[] = [
                'model' => $node,
                'level' => $level,
            ];

            if (
                $node->childrenRecursive->isNotEmpty()
            ) {

                $this->flattenRows(
                    $node->childrenRecursive,
                    $level + 1,
                    $rows
                );
            }
        }
    }
}
