<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Auth;
use App\Models\Approval;
use Validator;

class ApprovalController extends Controller
{
    public function index()
    {
        return view('backend.approval.index');
    }

    public function data(Request $request)
    {
        $keyword = $request->keyword;

        $query = DB::table('approvals')
                    ->leftjoin('users', 'users.id', '=', 'approvals.id_user')
                    ->leftjoin('units', 'units.id', '=', 'approvals.id_unit')
                    ->select(
                        'approvals.*',
                        'users.name',
                        'units.unit'
                    );

        if ($keyword) {
            $query->where('users.name', 'like', "%$keyword%")
                ->orWhere('units.unit', 'like', "%$keyword%");
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_unit' => 'required',
            'id_user' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        Approval::create($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil disimpan'
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'id_unit' => 'required',
            'id_user' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'responCode' => 0,
                'respon' => $validator->errors(),
            ]);
        }

        $data = Approval::find($request->id);
        $data->update($request->all());

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil diperbarui'
        ]);
    }

    public function delete(Request $request)
    {
        Approval::find($request->id)->delete();

        return response()->json([
            'responCode' => 1,
            'respon' => 'Data berhasil dihapus'
        ]);
    }
}
