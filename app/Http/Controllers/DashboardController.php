<?php

namespace App\Http\Controllers;
use DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){

        $id_unit = Request('id_unit');
        $id_tahun = Request('id_tahun');

        // Ambil semua rencana proker
        $prokers = DB::table('rencana_prokers')
                ->leftJoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                ->leftJoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                ->leftJoin('units', 'units.id', '=', 'prokers.id_unit')
                ->leftJoin('aksi_prokers', 'aksi_prokers.id_rencana_proker', '=', 'rencana_prokers.id')
                ->select(
                    'rencana_prokers.*',
                    'tahuns.tahun',
                    'units.unit',
                    DB::raw('SUM(aksi_prokers.progress) as total_progress')
                )
                ->where('units.id', $id_unit)
                ->where('tahuns.id', $id_tahun)
                ->groupBy(
                    'rencana_prokers.id', 
                )->get();

        // Daftar bulan (gunakan key angka)
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        // Struktur bulan dan minggu
        $bulanMinggu = [];
        foreach ($bulanList as $nama) {
            $bulanMinggu[$nama] = [1,2,3,4];
        }

        return view('backend.dashboard.index', compact('prokers', 'bulanList', 'bulanMinggu'));
    }
}
