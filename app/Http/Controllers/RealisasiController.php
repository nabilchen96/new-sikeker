<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Models\Realisasi;
use App\Models\RencanaProker;
use App\Services\KontrakCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RealisasiController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        $bulan = max(1, min(12, $bulan));
        $tahun = max(2000, min(2100, $tahun));

        $awalBulan = Carbon::create($tahun, $bulan, 1)
            ->startOfMonth();

        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $awalTahun = Carbon::create($tahun, 1, 1)
            ->startOfDay();

        $periode = $awalBulan->toDateString();

        $roots = Kontrak::query()
            ->leftJoin(
                'tahuns',
                'tahuns.id',
                '=',
                'kontraks.id_tahun'
            )
            ->where('tahuns.status', 'Aktif')
            ->whereNull('kontraks.id_parent')
            ->where('kontraks.jenis', Request('jenis') == 'PK' || Request('jenis') == null ? 'PK' : 'KPI')
            ->select('kontraks.*')
            ->with('childrenRecursive')
            ->orderBy('kontraks.kode')
            ->get();

        $data = [];

        $calculator = new KontrakCalculator;
        foreach ($roots as $root) {
            $targetResults = $calculator->calculateTree($root);
            foreach ($root->childrenRecursive as $ikk) {
                $indikators = collect($calculator->inputNodes($ikk, $targetResults))
                    ->filter(fn ($item) => $targetResults[(string) $item->id]['target'] !== null)
                    ->values();

                $ids = $indikators->pluck('id');

                $realisasiRows = collect();

                if ($ids->isNotEmpty()) {
                    $realisasiRows = Realisasi::query()
                        ->whereIn('id_kontrak', $ids)
                        ->whereBetween('periode', [
                            $awalTahun->toDateString(),
                            $akhirBulan->toDateString(),
                        ])
                        ->orderBy('periode')
                        ->get()
                        ->groupBy('id_kontrak');
                }

                $actuals = [];

                foreach ($indikators as $indikator) {
                    $rows = $realisasiRows->get(
                        $indikator->id,
                        collect()
                    );

                    $realisasiBulanIni = $rows->first(
                        fn ($r) => $r->periode->toDateString()
                            === $periode
                    );

                    $realisasiSebelumnya = $rows
                        ->filter(fn ($r) => $r->periode->lt($awalBulan)
                        )
                        ->last();

                    // Nilai terakhir sampai bulan yang dipilih.
                    $realisasiTerakhir = $realisasiBulanIni
                        ?? $realisasiSebelumnya;

                    $indikator->nilai_realisasi =
                        $realisasiBulanIni
                            ? (float) $realisasiBulanIni->nilai
                            : null;

                    $indikator->realisasi_sebelumnya =
                        $realisasiSebelumnya
                            ? (float) $realisasiSebelumnya->nilai
                            : null;

                    $indikator->nilai_realisasi_terakhir =
                        $realisasiTerakhir
                            ? (float) $realisasiTerakhir->nilai
                            : 0;

                    $indikator->realisasi_id =
                        $realisasiBulanIni?->id;

                    // Ambil aksi proker melalui rencana proker PK.
                    // Ambil aksi proker milik indikator ini DAN IKK induknya.
                    $idKontrakTerkait = array_filter([
                        $indikator->id,
                        $indikator->id_parent,
                    ]);

                    $rencanaIds = RencanaProker::query()
                        ->whereIn('id_pk', $idKontrakTerkait)
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id)
                        ->all();

                    $indikator->aksiProkers = \App\Models\AksiProker::query()
                        ->whereIn('id_rencana_proker', $rencanaIds)
                        ->orderBy('tgl_pengerjaan')
                        ->get();

                    $indikator->dokumen_terpilih =
                        $realisasiBulanIni
                            ? $realisasiBulanIni->aksiProkers
                                ->pluck('id')
                                ->map(fn ($id) => (int) $id)
                                ->all()
                            : [];

                    if ($realisasiTerakhir) {
                        $actuals[(string) $indikator->id] = (float) $realisasiTerakhir->nilai;
                    }
                }

                $result = $calculator->calculateTree($root, $actuals)[(string) $ikk->id];
                $totalTarget = $result['target'];
                $totalRealisasiTerakhir = $result['actual'];
                $persentase = $totalTarget !== null && $totalTarget > 0 && $totalRealisasiTerakhir !== null
                    ? ($totalRealisasiTerakhir / $totalTarget) * 100
                    : null;

                $data[] = [
                    'root' => $root,
                    'ikk' => $ikk,
                    'indikators' => $indikators,
                    'total_target' => $totalTarget,
                    'total_realisasi_kumulatif' => $totalRealisasiTerakhir,
                    'persentase' => $persentase,
                    'calculation_error' => $result['error'] ?? $result['actual_error'],
                    'unit' => $result['unit'],
                ];
            }
        }

        // Urutkan seluruh IKK sebelum ditampilkan.
        usort($data, function ($a, $b) {
            return strnatcasecmp(
                trim((string) $a['ikk']->kode),
                trim((string) $b['ikk']->kode)
            );
        });

        return view('backend.realisasi.index', [
            'data' => $data,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode' => $periode,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'realisasi' => ['required', 'array'],
            'realisasi.*' => ['nullable', 'numeric', 'min:0'],
            'dokumen' => ['nullable', 'array'],
            'dokumen.*' => ['nullable', 'array'],
            'dokumen.*.*' => [
                'integer',
                'distinct',
                'exists:aksi_prokers,id',
            ],
        ]);

        $bulan = (int) $validated['bulan'];
        $tahun = (int) $validated['tahun'];

        $periode = Carbon::create($tahun, $bulan, 1)
            ->startOfMonth()
            ->toDateString();

        DB::transaction(function () use (
            $validated,
            $periode

        ) {
            $nilaiInput = $validated['realisasi'];
            $dokumenInput = $validated['dokumen'] ?? [];

            $ids = collect(array_keys($nilaiInput))
                ->map(fn ($id) => (int) $id);

            $calculator = new KontrakCalculator;
            $allowedIds = [];
            $roots = Kontrak::whereNull('id_parent')
                ->whereIn('id_tahun', DB::table('tahuns')->where('status', 'Aktif')->pluck('id'))
                ->with('childrenRecursive')->get();
            foreach ($roots as $root) {
                $results = $calculator->calculateTree($root);
                foreach ($root->childrenRecursive as $ikk) {
                    foreach ($calculator->inputNodes($ikk, $results) as $input) {
                        if ($results[(string) $input->id]['target'] !== null) {
                            $allowedIds[] = (int) $input->id;
                        }
                    }
                }
            }
            $indikatorValid = array_values(array_intersect($ids->unique()->all(), array_unique($allowedIds)));

            if (count($indikatorValid) !== $ids->unique()->count()) {
                throw ValidationException::withMessages([
                    'realisasi' => 'Realisasi hanya diisi pada sumber manual yang memiliki target, termasuk target nol.',
                ]);
            }

            // Pastikan setiap dokumen memang berasal dari
            // rencana proker PK milik indikator yang bersangkutan.
            foreach ($dokumenInput as $indikatorId => $aksiIds) {
                $indikatorId = (int) $indikatorId;

                $rencanaIds = \App\Models\RencanaProker::query()
                    ->where('id_pk', $indikatorId)
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id);

                $aksiValid = \App\Models\AksiProker::query()
                    ->whereIn('id', $aksiIds ?? [])
                    ->whereIn('id_rencana_proker', $rencanaIds)
                    ->count();

                if ($aksiValid !== count(array_unique($aksiIds ?? []))) {
                    throw ValidationException::withMessages([
                        "dokumen.$indikatorId" => 'Dokumen dukung tidak sesuai dengan indikator PK.',
                    ]);
                }
            }

            foreach ($nilaiInput as $indikatorId => $nilai) {
                $indikatorId = (int) $indikatorId;

                // Input kosong tidak menghapus nilai yang sudah tersimpan.
                if ($nilai === null || $nilai === '') {
                    continue;
                }

                $realisasi = Realisasi::updateOrCreate(
                    [
                        'id_kontrak' => $indikatorId,
                        'periode' => $periode,
                    ],
                    [
                        'nilai' => $nilai,
                    ]
                );

                $realisasi->aksiProkers()->sync(
                    array_values(array_unique(
                        array_map(
                            'intval',
                            $dokumenInput[$indikatorId] ?? []
                        )
                    ))
                );
            }
        });

        return redirect()
            ->to(url('/realisasi').'?'.http_build_query([
                'bulan' => $bulan,
                'tahun' => $tahun,
            ]))
            ->with('success', 'Realisasi berhasil disimpan.');
    }

    public function detail(Request $request, int $id)
    {
        $tahun = (int) $request->query('tahun', now()->year);

        abort_if($tahun < 2000 || $tahun > 2100, 404);

        $indikator = \App\Models\Kontrak::query()
            ->where('id', $id)
            ->whereNotNull('target')
            ->where('target', '>=', 0)
            ->with([
                'realisasis' => function ($query) use ($tahun) {
                    $query->whereYear('periode', $tahun)
                        ->orderBy('periode');
                },
            ])
            ->firstOrFail();

        // Kelompokkan realisasi berdasarkan bulan.
        $realisasiPerBulan = $indikator->realisasis
            ->keyBy(function ($realisasi) {
                return \Carbon\Carbon::parse(
                    $realisasi->periode
                )->month;
            });

        $riwayat = collect(range(1, 12))->map(function ($bulan) use (
            $realisasiPerBulan
        ) {
            $realisasi = $realisasiPerBulan->get($bulan);

            return [
                'bulan' => $bulan,
                'nama_bulan' => \Carbon\Carbon::create()
                    ->month($bulan)
                    ->locale('id')
                    ->translatedFormat('F'),
                'periode' => $realisasi?->periode,
                'nilai' => $realisasi?->nilai,
                'tanggal_pengisian' => $realisasi?->updated_at,
            ];
        });

        // Realisasi terbaru yang tersedia pada tahun terpilih.
        $realisasiTerbaru = $indikator->realisasis
            ->sortByDesc('periode')
            ->first();

        $persentase = $indikator->target > 0 && $realisasiTerbaru
            ? ($realisasiTerbaru->nilai / $indikator->target) * 100
            : 0;

        return view('backend.realisasi.detail', compact(
            'indikator',
            'tahun',
            'riwayat',
            'realisasiTerbaru',
            'persentase'
        ));
    }
}
