<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Proker;
use Validator;

class ProkerController extends Controller
{
    public function index()
    {
        return view('backend.proker.index');
    }

    public function data(Request $request)
    {
        $keyword = $request->keyword;

        $query = DB::table('prokers')
                ->leftjoin('tahuns', 'tahuns.id', '=', 'prokers.id_tahun')
                ->leftjoin('units', 'units.id', '=', 'prokers.id_unit')
                ->select(
                    'prokers.*',
                    'tahuns.tahun',
                    'units.unit'
                );

        if ($keyword) {
            $query->where('tahuns.tahun', 'like', "%$keyword%");
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_tahun' => 'required',
            'id_unit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        Proker::create(array_merge(
            $request->all(),
            [
                'status_approval' => 'Belum Mengajukan',
                'keterangan_ditolak' => '-'
            ]
        ));

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil disimpan'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'id_tahun' => 'required',
            'id_unit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = Proker::find($request->id);
        $data->update($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        Proker::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }

    public function ajukanProker(Request $request)
    {
        Proker::find($request->id)->update([
            'status_approval' => 'Dalam Pengajuan',
            'keterangan_ditolak' => '-'
        ]);

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diupdate'
        ]);
    }

    public function ubahStatusProker(Request $request)
    {
        Proker::find($request->id_proker)->update([
            'status_approval' => $request->status_approval,
            'keterangan_ditolak' => $request->keterangan_ditolak ?? '-'
        ]);

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diupdate'
        ]);
    }
}
