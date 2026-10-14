<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Models\Realisasi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RealisasiController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        $bulan = (int) $request->input('bulan', $now->month);
        $tahun = (int) $request->input('tahun', $now->year);

        // Validasi periode.
        $bulan = max(1, min(12, $bulan));
        $tahun = max(2000, min(2100, $tahun));

        $awalBulan = Carbon::create($tahun, $bulan, 1)
            ->startOfMonth();

        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $awalTahun = Carbon::create($tahun, 1, 1)
            ->startOfDay();

        $periode = $awalBulan->toDateString();

        /*
         * Parent tertinggi.
         * Anak langsungnya adalah IKK.
         */
        $roots = Kontrak::query()
            ->whereNull('id_parent')
            ->with('childrenRecursive')
            ->orderBy('kode')
            ->get();

        $data = [];

        foreach ($roots as $root) {
            foreach ($root->childrenRecursive as $ikk) {

                // Kumpulkan seluruh keturunan IKK.
                $indikators = collect();

                $this->collectDescendants(
                    $ikk,
                    $indikators
                );

                // Hanya indikator dengan target > 0.
                $indikators = $indikators
                    ->filter(fn ($item) =>
                        $item->target !== null
                        && (float) $item->target > 0
                    )
                    ->values();

                $indikatorIds = $indikators->pluck('id');

                /*
                 * Realisasi bulan terpilih.
                 */
                $realisasiBulanan = collect();

                /*
                 * Akumulasi realisasi dari Januari
                 * sampai akhir bulan terpilih.
                 */
                $realisasiKumulatif = collect();

                if ($indikatorIds->isNotEmpty()) {
                    $realisasiBulanan = Realisasi::query()
                        ->whereIn('id_kontrak', $indikatorIds)
                        ->whereBetween('periode', [
                            $awalBulan->toDateString(),
                            $akhirBulan->toDateString(),
                        ])
                        ->get()
                        ->keyBy('id_kontrak');

                    $realisasiKumulatif = Realisasi::query()
                        ->whereIn('id_kontrak', $indikatorIds)
                        ->whereBetween('periode', [
                            $awalTahun->toDateString(),
                            $akhirBulan->toDateString(),
                        ])
                        ->selectRaw(
                            'id_kontrak, SUM(nilai) as total_nilai'
                        )
                        ->groupBy('id_kontrak')
                        ->get()
                        ->keyBy('id_kontrak');
                }

                $totalTarget = 0;
                $totalRealisasiBulan = 0;
                $totalRealisasiKumulatif = 0;

                foreach ($indikators as $indikator) {
                    $target = (float) $indikator->target;

                    $bulanan = $realisasiBulanan
                        ->get($indikator->id);

                    $kumulatif = $realisasiKumulatif
                        ->get($indikator->id);

                    $indikator->nilai_realisasi = $bulanan
                        ? (float) $bulanan->nilai
                        : 0;

                    $indikator->realisasi_kumulatif = $kumulatif
                        ? (float) $kumulatif->total_nilai
                        : 0;

                    $totalTarget += $target;

                    $totalRealisasiBulan +=
                        $indikator->nilai_realisasi;

                    $totalRealisasiKumulatif +=
                        $indikator->realisasi_kumulatif;
                }

                /*
                 * Capaian hijau menggunakan realisasi kumulatif.
                 * Target dihitung satu kali, bukan per bulan.
                 */
                $persentase = $totalTarget > 0
                    ? ($totalRealisasiKumulatif / $totalTarget) * 100
                    : 0;

                $data[] = [
                    'root' => $root,
                    'ikk' => $ikk,
                    'indikators' => $indikators,
                    'total_target' => $totalTarget,
                    'total_realisasi' => $totalRealisasiBulan,
                    'total_realisasi_kumulatif' =>
                        $totalRealisasiKumulatif,
                    'persentase' => $persentase,
                ];
            }
        }

        return view('backend.realisasi.index', [
            'data' => $data,
            'bulan' => $bulan,
            'tahun' => $tahun,
            'periode' => $periode,
        ]);
    }

    /**
     * Mengumpulkan semua keturunan IKK secara datar.
     */
    private function collectDescendants(
        Kontrak $parent,
        Collection &$result
    ): void {
        foreach ($parent->childrenRecursive as $child) {
            $result->push($child);

            $this->collectDescendants(
                $child,
                $result
            );
        }
    }
}