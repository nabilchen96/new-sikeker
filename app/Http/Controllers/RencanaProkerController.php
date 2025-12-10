<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\RencanaProker;
use Validator;

class RencanaProkerController extends Controller
{
    public function index()
    {
        $proker = DB::table('prokers')
                ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                ->select(
                    'tahuns.tahun',
                    'units.unit',
                    'prokers.status_approval',
                    'prokers.keterangan_ditolak',
                    'prokers.id'
                )->where('prokers.id', Request('id_proker'))->first();

        return view('backend.rencana_proker.index', [
            'proker' => $proker
        ]);
    }

    public function data(Request $request)
    {
        $keyword = $request->keyword;

        $query = DB::table('rencana_prokers')
                ->leftJoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                ->leftJoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                ->leftJoin('units', 'units.id', '=', 'prokers.id_unit')
                ->leftJoin('aksi_prokers', 'aksi_prokers.id_rencana_proker', '=', 'rencana_prokers.id')
                ->select(
                    'rencana_prokers.*',
                    'tahuns.tahun',
                    'units.unit',
                    'prokers.status_approval',
                    DB::raw('SUM(aksi_prokers.progress) as total_progress')
                )
                ->where('rencana_prokers.id_proker', $request->id_proker)
                ->groupBy(
                    'rencana_prokers.id', 
                );


        if ($keyword) {
            $query->where('rencana_prokers.rencana_proker', 'like', "%$keyword%");
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rencana_proker' => 'required',
            'bulan_mulai' => 'required',
            'bulan_akhir' => 'required',
            'minggu_mulai' => 'required',
            'minggu_akhir' => 'required',
            'id_proker' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        RencanaProker::create($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil disimpan'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'rencana_proker' => 'required',
            'bulan_mulai' => 'required',
            'bulan_akhir' => 'required',
            'minggu_mulai' => 'required',
            'minggu_akhir' => 'required',
            'id_proker' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = RencanaProker::find($request->id);
        $data->update($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        RencanaProker::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }
}
