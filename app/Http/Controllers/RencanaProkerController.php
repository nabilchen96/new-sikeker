<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\RencanaProker;
use Validator;
use Barryvdh\DomPDF\Facade\Pdf;

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
            // 'bulan_mulai' => 'required',
            // 'bulan_akhir' => 'required',
            // 'minggu_mulai' => 'required',
            // 'minggu_akhir' => 'required',
            'id_proker' => 'required',
            'jenis_proker' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        RencanaProker::create([
            'id_proker' => $request->id_proker,
            'rencana_proker' => $request->rencana_proker,
            'jenis_proker' => $request->jenis_proker,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'bulan_mulai' => 0,
            'bulan_akhir' => 0,
            'minggu_mulai' => 0,
            'minggu_akhir' => 0,
        ]);

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
            // 'bulan_mulai' => 'required',
            // 'bulan_akhir' => 'required',
            // 'minggu_mulai' => 'required',
            // 'minggu_akhir' => 'required',
            'id_proker' => 'required',
            'jenis_proker' => 'required',
            'tgl_mulai' => 'required',
            'tgl_selesai' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = RencanaProker::find($request->id);
        $data->update([
            'id_proker' => $request->id_proker,
            'rencana_proker' => $request->rencana_proker,
            'jenis_proker' => $request->jenis_proker,
            'tgl_mulai' => $request->tgl_mulai,
            'tgl_selesai' => $request->tgl_selesai,
            'bulan_mulai' => 0,
            'bulan_akhir' => 0,
            'minggu_mulai' => 0,
            'minggu_akhir' => 0,
        ]);

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function updateStatus(Request  $request){
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status_rencana' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = RencanaProker::find($request->id);
        $data->update([
            'status_rencana' => $request->status_rencana
        ]);

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

    public function exportRencanaProker(){

        $data = DB::table('rencana_prokers')
            ->join('prokers', 'rencana_prokers.id_proker', '=', 'prokers.id')
            ->join('units', 'prokers.id_unit', '=', 'units.id')
            ->join('tahuns', 'prokers.id_tahun', '=', 'tahuns.id')
            ->where('tahuns.status', 'Aktif')
            ->select(
                'units.id as unit_id',
                'units.unit as nama_unit',
                'rencana_prokers.rencana_proker',
                'rencana_prokers.jenis_proker',
                'rencana_prokers.tgl_mulai',
                'rencana_prokers.tgl_selesai',
                'tahuns.tahun'
            )
            ->orderBy('units.unit')
            ->orderBy('rencana_prokers.tgl_mulai')
            ->get()
            ->groupBy('unit_id');

        $tahun = DB::table('tahuns')->where('status', 'Aktif')->first();

        $pdf = Pdf::loadView('backend.rencana_proker.export_pdf', [
            'data' => $data,
            'tahun' => $tahun
        ])
        ->setPaper('A4', 'portrait'); // bisa landscape juga

        return $pdf->stream('rencana-proker.pdf');
    }
}
