<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Tahun;
use Validator;

class TahunController extends Controller
{
    public function index()
    {
        return view('backend.tahun.index');
    }

    public function data(Request $request)
    {
        $keyword = $request->keyword;

        $query = DB::table('tahuns');

        if ($keyword) {
            $query->where('tahun', 'like', "%$keyword%");
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tahun' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        Tahun::create($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil disimpan'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'tahun' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = Tahun::find($request->id);
        $data->update($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        Tahun::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }
}
