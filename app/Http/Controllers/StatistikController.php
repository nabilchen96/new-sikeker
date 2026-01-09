<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class StatistikController extends Controller
{
    public function index(Request $request){

        $bulan = $request->bulan;

       $statistik = DB::table('rencana_prokers')
            ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
            ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
            ->selectRaw('
                COUNT(*) as total_proker,
                SUM(CASE WHEN rencana_prokers.status_rencana = "Selesai" THEN 1 ELSE 0 END) as proker_selesai,
                SUM(CASE WHEN rencana_prokers.status_rencana IS NULL THEN 1 ELSE 0 END) as proker_belum
            ')->where('tahuns.status', 'Aktif');


        if ($bulan) {
            $statistik->whereMonth('rencana_prokers.tgl_mulai', $bulan);
        }

        $statistik = $statistik->first();

    /**
     * =========================
     * 2. DATA CHART (STACKED BAR)
     * =========================
     */
    $chart = DB::table('rencana_prokers')
            ->leftJoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
            ->leftJoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
            ->leftJoin('units', 'units.id', '=', 'prokers.id_unit')
            ->select(
                'units.unit',
                DB::raw('SUM(CASE WHEN rencana_prokers.status_rencana = "Selesai" THEN 1 ELSE 0 END) as selesai'),
                DB::raw('SUM(CASE WHEN rencana_prokers.status_rencana IS NULL THEN 1 ELSE 0 END) as belum')
            )
            ->where('tahuns.status', 'Aktif');

        if ($bulan) {
            $chart->whereMonth('rencana_prokers.tgl_mulai', $bulan);
        }

        $chart = $chart
            ->groupBy('units.unit')
            ->get();

        /**
         * =========================
         * 3. FORMAT DATA HIGHCHARTS
         * =========================
         */
        $categories = $chart->pluck('unit')->map(function($text) {
            return strlen($text) > 24 ? substr($text, 0, 24) . '...' : $text;
        })->values();

        $dataSelesai = $chart->pluck('selesai')
            ->map(fn ($v) => (int) $v)
            ->values();

        $dataBelum = $chart->pluck('belum')
            ->map(fn ($v) => (int) $v)
            ->values();

        // dd($dataBelum);

        return view('backend.statistik.index', compact(
            'statistik',
            'categories',
            'dataSelesai',
            'dataBelum'
        ));
    }
}
