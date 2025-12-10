<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\AksiProker;
use Validator;

class AksiProkerController extends Controller
{
    public function index()
    {
        return view('backend.aksi_proker.index');
    }

    public function data(Request $request)
    {

        $keyword = Request('id_rencana_proker');

        $query = DB::table('aksi_prokers')
                    ->leftjoin('rencana_prokers', 'rencana_prokers.id', '=', 'aksi_prokers.id_rencana_proker')
                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                    ->select(
                        'aksi_prokers.*',
                        'rencana_prokers.rencana_proker',
                        'rencana_prokers.bulan_mulai',
                        'rencana_prokers.minggu_mulai',
                        'rencana_prokers.bulan_akhir',
                        'rencana_prokers.minggu_akhir',
                        'units.unit',
                        'tahuns.tahun'
                    );


        if (!empty($keyword)) {
            $query->where('rencana_prokers.id', $keyword);
        }

        if(Auth::user()->role == 'Anggota'){
            $query->where('units.id', Auth::user()->id_unit);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_rencana_proker' => 'required',
            'kegiatan_proker' => 'required',
            'progress'  => 'required',
            'bukti_kegiatan' => 'nullable|file|mimes:pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $rencana = DB::table('rencana_prokers')
                    ->leftjoin('prokers', 'prokers.id', '=', 'rencana_prokers.id_proker')
                    ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                    ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                    ->select('rencana_prokers.*', 'units.unit', 'tahuns.tahun')
                    ->where('rencana_prokers.id', $request->id_rencana_proker)
                    ->first();


                    
        // Upload file
        $fileName = null;
        if ($request->hasFile('bukti_kegiatan')) {
            $path = 'bukti_kegiatan/'.$rencana->tahun.'/'.$rencana->unit.'/'.$rencana->rencana_proker;
            $fileName = $request->file('bukti_kegiatan')->store($path, 'public');
        }

        AksiProker::create([
            'id_rencana_proker' => $request->id_rencana_proker,
            'bukti_kegiatan' => $fileName,
            'progress' => $request->progress,
            'kegiatan_proker' => $request->kegiatan_proker
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
            'id_rencana_proker' => 'required',
            'kegiatan_proker' => 'required',
            'progress'  => 'required',
            'bukti_kegiatan' => 'nullable|file|mimes:pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }
        
        // Upload file
        $fileName = null;
        if ($request->hasFile('bukti_kegiatan')) {
            $path = 'bukti_kegiatan/'.$rencana->tahun.'/'.$rencana->unit.'/'.$rencana->rencana_proker;
            $fileName = $request->file('bukti_kegiatan')->store($path, 'public');
        }

        
        $data = AksiProker::find($request->id);
        $data->update([
            'id_rencana_proker' => $request->id_rencana_proker,
            'bukti_kegiatan' => $fileName,
            'progress' => $request->progress,
            'kegiatan_proker' => $request->kegiatan_proker
        ]);

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        AksiProker::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }
}
