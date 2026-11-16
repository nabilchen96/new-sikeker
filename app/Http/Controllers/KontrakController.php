<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Models\Realisasi;
use Illuminate\Http\Request;
use Validator;
use DB;

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

            // Target berbobot IKK 4 dan IKK 5 mengikuti spreadsheet cascading.
            $targetBerbobot = [];
            foreach ($rows as $candidate) {
                $ikk = $candidate['model'];
                $ikkKode = strtoupper(trim($ikk->kode ?? ''));
                if (!in_array($ikkKode, ['IKK 4', 'IKK 5'], true)) {
                    continue;
                }

                $targets = $ikkKode === 'IKK 4'
                    ? $this->hitungTargetAkreditasi($ikk, $realisasiTerakhir)
                    : $this->hitungTargetPeningkatanSdm($ikk, $realisasiTerakhir);
                $targetBerbobot[$ikk->id] = $targets['IKK'];
                foreach ($ikk->childrenRecursive as $child) {
                    $childKode = strtoupper(trim($child->kode ?? ''));
                    if (array_key_exists($childKode, $targets)) {
                        $targetBerbobot[$child->id] = $targets[$childKode];
                    }
                }
            }

            foreach ($rows as &$row) {

                $kontrak = $row['model'];

                /*
                 * Kode kontrak
                 */
                $kode = strtoupper(
                    trim($kontrak->kode ?? '')
                );


                /*
                 * --------------------------------------------------
                 * DEFAULT
                 * --------------------------------------------------
                 */

                $row['total_target'] = null;

                $row['total_realisasi'] = null;

                $row['realisasi_terakhir'] =
                    $realisasiTerakhir->get($kontrak->id);

                $row['satuan_tampilan'] =
                    $kontrak->jenis_target;

                $row['hasil_persentase'] = null;


                /*
                 * ==================================================
                 * 1. LEAF / INDIKATOR TERBAWAH
                 * ==================================================
                 *
                 * Tidak punya anak.
                 *
                 * Target diambil langsung dari kontraks.
                 * Realisasi diambil dari realisasis.
                 */

                if ($kontrak->childrenRecursive->isEmpty()) {

                    /*
                     * TARGET
                     */
                    if (
                        $kontrak->target !== null &&
                        $kontrak->target !== '' &&
                        is_numeric($kontrak->target)
                    ) {

                        $row['total_target'] =
                            (float) $kontrak->target;
                    }


                    /*
                     * REALISASI TERAKHIR
                     */
                    if ($row['realisasi_terakhir']) {

                        $row['total_realisasi'] =
                            (float)
                            $row['realisasi_terakhir']->nilai;
                    }
                }


                /*
                 * ==================================================
                 * 2. NODE YANG MEMPUNYAI ANAK
                 * ==================================================
                 *
                 * Contoh:
                 *
                 * A
                 * ├── 1
                 * ├── 2
                 * ├── 3
                 * └── 4
                 *
                 * B
                 * ├── 1
                 * ├── 2
                 * ├── 3
                 * └── 4
                 *
                 * IKK
                 * ├── A
                 * └── B
                 */

                if ($kontrak->childrenRecursive->isNotEmpty()) {

                    $total = $this->hitungTotalNode(
                        $kontrak,
                        $realisasiTerakhir
                    );


                    $row['total_target'] =
                        $total['target'];

                    $row['total_realisasi'] =
                        $total['realisasi'];


                    // Satuan tetap memakai isian node ini, bukan satuan anaknya.
                }


                /*
                 * ==================================================
                 * 3. KHUSUS IKK
                 * ==================================================
                 *
                 * IKK selalu berupa persentase.
                 *
                 * Target = target sub IKK A / target sub IKK B × 100.
                 *
                 * Realisasi =
                 *
                 * total realisasi
                 * ---------------- × 100
                 * total target
                 */

                if (str_starts_with($kode, 'IKK')) {

                    $row['satuan_tampilan'] =
                        'persen';


                    /*
                     * Gunakan dua sub IKK langsung. Target masing-masing
                     * sub dihitung seperti baris A/B, tanpa menjumlahkan
                     * kembali sub-sub IKK ke target IKK.
                     */
                    $subIkk = $kontrak->childrenRecursive->keyBy(function ($child) {
                        return strtoupper(trim($child->kode ?? ''));
                    });

                    $row['total_target'] = null;

                    $pasangan = null;
                    if ($subIkk->has('A') && $subIkk->has('B')) {
                        $pasangan = ['A', 'B'];
                    } elseif (
                        $subIkk->count() === 2 &&
                        $subIkk->has('1') && $subIkk->has('2') &&
                        $subIkk->get('1')->childrenRecursive->isEmpty() &&
                        $subIkk->get('2')->childrenRecursive->isEmpty()
                    ) {
                        // Dua sub langsung tanpa rincian: 1 / 2 × 100 (contoh IKK 10).
                        $pasangan = ['1', '2'];
                    }

                    if ($pasangan !== null) {
                        $targetA = $this->hitungTotalNode($subIkk->get($pasangan[0]), $realisasiTerakhir)['target'];
                        $targetB = $this->hitungTotalNode($subIkk->get($pasangan[1]), $realisasiTerakhir)['target'];

                        if ($targetB > 0) {
                            $row['total_target'] = ($targetA / $targetB) * 100;
                        }
                    }


                    /*
                     * Hitung persentase
                     */
                    if (
                        $row['total_realisasi'] !== null
                    ) {

                        /*
                         * Realisasi tetap memakai total target node,
                         * terpisah dari rasio target A/B di atas.
                         */

                        $total = $this->hitungTotalNode(
                            $kontrak,
                            $realisasiTerakhir
                        );


                        if ($total['target'] > 0) {

                            $row['hasil_persentase'] =
                                (
                                    $total['realisasi']
                                    /
                                    $total['target']
                                ) * 100;
                        }
                    }


                    /*
                     * Untuk IKK, nilai realisasi yang ditampilkan
                     * adalah hasil persentase.
                     */
                    $row['total_realisasi'] =
                        $row['hasil_persentase'];
                }
                if (array_key_exists($kontrak->id, $targetBerbobot)) {
                    $row['total_target'] = $targetBerbobot[$kontrak->id];
                }
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
        ]);
    }

    public function storeSasaranKinerja(Request $request){
         $validator = Validator::make($request->all(), [
            'nama'      => 'required',
            'jenis'     => 'required'
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors()
            ];
        } else {
            Kontrak::create([
               'id_parent'      => null,
               'kode'           => 0,
               'nama'           => $request->nama,
               'jenis'          => $request->jenis,
               'target'         => null,
               'jenis_target'   => null,
               'id_tahun'       => DB::table('tahuns')->where('status', 'Aktif')->value('id')
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan'
            ];
        }

        return response()->json($data);
    }

    public function updateSasaranKinerja(Request $request){
         $validator = Validator::make($request->all(), [
            'id'        => 'required',
            'nama'      => 'required',
            'jenis'     => 'required'
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors()
            ];
        } else {

            $kontrak = Kontrak::find($request->id);
            $kontrak->update([
               'nama'   => $request->nama,
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan'
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
            'respon' => 'Data Sukses Dihapus'
        ];

        return response()->json($data);
    }

    public function storeBagian(Request $request){
         $validator = Validator::make($request->all(), [
            'sub_indikator'      => 'required',
            'id_parent'     => 'required',
            'kode'     => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors()
            ];
        } else {
            Kontrak::create([
               'id_parent'      => $request->id_parent,
               'kode'           => $request->kode,
               'nama'           => $request->sub_indikator,
               'jenis'          => $request->jenis,
               'target'         => $request->target ?? null,
               'jenis_target'   => $request->jenis_target ?? null,
               'id_tahun'       => DB::table('tahuns')->where('status', 'Aktif')->value('id')
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan'
            ];
        }

        return response()->json($data);
    }

        public function updateBagian(Request $request){
         $validator = Validator::make($request->all(), [
            'sub_indikator'      => 'required',
            'id_parent'     => 'required',
            'kode'     => 'required',
        ]);

        if ($validator->fails()) {
            $data = [
                'responCode' => 0,
                'respon' => $validator->errors()
            ];
        } else {
            
            $kontrak = Kontrak::find($request->id);
            $kontrak->update([

               'id_parent'      => $request->id_parent,
               'kode'           => $request->kode,
               'nama'           => $request->sub_indikator,
               'jenis'          => $request->jenis,
               'target'         => $request->target ?? null,
               'jenis_target'   => $request->jenis_target ?? null,
               'id_tahun'       => DB::table('tahuns')->where('status', 'Aktif')->value('id')
               
            ]);

            $data = [
                'responCode' => 1,
                'respon' => 'Data Sukses Disimpan'
            ];
        }

        return response()->json($data);
    }

    public function deleteBagian(Request $request)
    {

        // dd($request->id);

        $data = Kontrak::find($request->id)->delete();

        $data = [
            'responCode' => 1,
            'respon' => 'Data Sukses Dihapus'
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

        foreach ($nodes as $node) {

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

        foreach ($nodes as $node) {

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


    // Anak A/B berisi persentase mentah; baris A/B menampilkan kontribusi berbobot.
    private function hitungTargetPeningkatanSdm($ikk, $realisasiTerakhir): array
    {
        $sub = $ikk->childrenRecursive->keyBy(function ($child) {
            return strtoupper(trim($child->kode ?? ''));
        });
        $targets = ['A' => null, 'B' => null];
        foreach (['A' => 0.4, 'B' => 0.6] as $kode => $bobot) {
            if (!$sub->has($kode)) {
                continue;
            }
            $child = $sub->get($kode);
            if ($child->childrenRecursive->isNotEmpty()) {
                $targets[$kode] = $this->hitungTotalNode($child, $realisasiTerakhir)['target'] * $bobot;
            } elseif (is_numeric($child->target)) {
                // Tanpa rincian, target tersimpan sudah berupa kontribusi berbobot.
                $targets[$kode] = (float) $child->target;
            }
        }
        $targets['IKK'] = $targets['A'] !== null && $targets['B'] !== null
            ? $targets['A'] + $targets['B']
            : null;

        return $targets;
    }

    // Target berbobot khusus akreditasi; nilai mentah tetap dipakai untuk realisasi.
    private function hitungTargetAkreditasi($ikk, $realisasiTerakhir): array
    {
        $sub = $ikk->childrenRecursive->keyBy(function ($child) {
            return strtoupper(trim($child->kode ?? ''));
        });
        $targetA = null;
        $targetB = null;

        if ($sub->has('A') && $sub->get('A')->childrenRecursive->isNotEmpty()) {
            $targetA = $this->hitungTotalNode($sub->get('A'), $realisasiTerakhir)['target'] * 60;
        }

        if ($sub->has('B')) {
            $rincian = $sub->get('B')->childrenRecursive->keyBy(function ($child) {
                return trim((string) $child->kode);
            });
            if ($rincian->has('1') && $rincian->has('2')) {
                $pembilang = $this->hitungTotalNode($rincian->get('1'), $realisasiTerakhir)['target'];
                $pembagi = $this->hitungTotalNode($rincian->get('2'), $realisasiTerakhir)['target'];
                if ($pembagi > 0) {
                    $targetB = ($pembilang / $pembagi) * 40;
                }
            }
        }

        return [
            'A' => $targetA,
            'B' => $targetB,
            'IKK' => $targetA !== null && $targetB !== null ? $targetA + $targetB : null,
        ];
    }


    /*
     * ==========================================================
     * HITUNG TOTAL NODE
     * ==========================================================
     *
     * Fungsi ini digunakan untuk A, B, maupun IKK.
     *
     * Kalau leaf:
     *
     * target      = target leaf
     * realisasi   = realisasi terakhir
     *
     * Kalau parent:
     *
     * target      = total seluruh anak
     * realisasi   = total seluruh anak
     */

    private function hitungTotalNode(
        $kontrak,
        $realisasiTerakhir
    ): array {

        $totalTarget = 0;

        $totalRealisasi = 0;


        /*
         * ======================================================
         * LEAF
         * ======================================================
         */

        if (
            $kontrak->childrenRecursive->isEmpty()
        ) {

            /*
             * TARGET
             */
            if (
                $kontrak->target !== null &&
                $kontrak->target !== '' &&
                is_numeric($kontrak->target)
            ) {

                $totalTarget =
                    (float) $kontrak->target;
            }


            /*
             * REALISASI TERAKHIR
             */
            $realisasi =
                $realisasiTerakhir->get(
                    $kontrak->id
                );


            if ($realisasi) {

                $totalRealisasi =
                    (float) $realisasi->nilai;
            }


            return [
                'target' => $totalTarget,
                'realisasi' => $totalRealisasi,
            ];
        }


        /*
         * ======================================================
         * PARENT
         * ======================================================
         */

        foreach (
            $kontrak->childrenRecursive
            as $child
        ) {

            $hasil =
                $this->hitungTotalNode(
                    $child,
                    $realisasiTerakhir
                );


            $totalTarget +=
                $hasil['target'];


            $totalRealisasi +=
                $hasil['realisasi'];
        }


        return [
            'target' => $totalTarget,
            'realisasi' => $totalRealisasi,
        ];
    }


    /*
     * ==========================================================
     * AMBIL SATUAN DARI LEAF
     * ==========================================================
     *
     * Contoh:
     *
     * A
     * └── 1 → orang
     *
     * Maka A → orang
     */

    private function ambilSatuanLeaf(
        $kontrak
    ) {

        foreach (
            $kontrak->childrenRecursive
            as $child
        ) {

            /*
             * Kalau masih parent,
             * turun lagi.
             */

            if (
                $child->childrenRecursive->isNotEmpty()
            ) {

                $satuan =
                    $this->ambilSatuanLeaf(
                        $child
                    );


                if ($satuan) {
                    return $satuan;
                }
            }


            /*
             * Sudah sampai leaf
             */
            if (
                $child->jenis_target !== null &&
                $child->jenis_target !== ''
            ) {

                return $child->jenis_target;
            }
        }


        return null;
    }
}
