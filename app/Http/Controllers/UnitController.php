<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Unit;
use Validator;

class UnitController extends Controller
{
    public function index()
    {
        return view('backend.unit.index');
    }

    public function data(Request $request)
    {
        $keyword = $request->keyword;

        $query = DB::table('units');

        if ($keyword) {
            $query->where('unit', 'like', "%$keyword%");
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        Unit::create($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil disimpan'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'unit' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = Unit::find($request->id);
        $data->update($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        Unit::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }
}
