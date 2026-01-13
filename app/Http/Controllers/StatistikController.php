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

        $chart = DB::table('units')
                ->leftJoin('prokers', 'prokers.id_unit', '=', 'units.id')
                ->leftJoin('rencana_prokers', 'rencana_prokers.id_proker', '=', 'prokers.id')
                ->leftJoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                ->select(
                    'units.unit',
                    'units.id as id_unit',
                    DB::raw('
                        SUM(CASE 
                            WHEN rencana_prokers.status_rencana = "Selesai" THEN 1 
                            ELSE 0 
                        END) as selesai
                    '),
                    DB::raw('
                        SUM(CASE 
                            WHEN rencana_prokers.status_rencana IS NULL 
                                AND rencana_prokers.id IS NOT NULL
                            THEN 1 
                            ELSE 0 
                        END) as belum
                    ')
                )
                ->where(function ($q) {
                    $q->where('tahuns.status', 'Aktif')
                    ->orWhereNull('tahuns.status'); // supaya unit tanpa proker tetap muncul
                })
                ->orderByRaw('selesai DESC');

            if ($bulan) {
                $chart->whereMonth('rencana_prokers.tgl_mulai', $bulan);
            }

            $chart = $chart
                ->groupBy('units.unit')
                ->orderBy('units.unit')
                ->get();

            $units = $chart->pluck('unit')->map(function ($unit) {
                return mb_strimwidth($unit, 0, 20, '…'); // aman untuk UTF-8
            });

            $selesai = $chart->pluck('selesai')->map(function ($v) {
                return (int) $v;
            });

            $belum = $chart->pluck('belum')->map(function ($v) {
                return (int) $v;
            });

        return view('backend.statistik.index', compact(
            'statistik',
            'chart',
            'units','selesai','belum'
        ));
    }

    public function detail(Request $request){

        $id_unit = $request->id_unit;
        $bulan = $request->bulan;

        $data = DB::table('rencana_prokers')
                ->leftJoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                ->leftJoin('units', 'prokers.id_unit', '=', 'units.id')
                ->leftJoin('tahuns', 'prokers.id_tahun', '=', 'tahuns.id')
                ->selectRaw('
                    rencana_prokers.rencana_proker,
                    rencana_prokers.tgl_mulai,
                    rencana_prokers.tgl_selesai,
                    rencana_prokers.status_rencana,
                    rencana_prokers.jenis_proker
                ')
                ->where('tahuns.status', 'Aktif')
                ->where('units.id', $id_unit)
                ->whereMonth('rencana_prokers.tgl_mulai', $bulan)
                ->get();

                // dd($data);

        $statistik = DB::table('rencana_prokers')
            ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
            ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
            ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
            ->selectRaw('
                units.unit,
                COUNT(*) as total_proker,
                SUM(CASE WHEN rencana_prokers.status_rencana = "Selesai" THEN 1 ELSE 0 END) as proker_selesai,
                SUM(CASE WHEN rencana_prokers.status_rencana IS NULL THEN 1 ELSE 0 END) as proker_belum
            ')
            ->where('prokers.id_unit', $id_unit)
            ->whereMonth('rencana_prokers.tgl_mulai', $bulan)
            ->where('tahuns.status', 'Aktif')->first();

        return view('backend.statistik.detail', compact(
            'data',
            'statistik'
        ));
    }
}
