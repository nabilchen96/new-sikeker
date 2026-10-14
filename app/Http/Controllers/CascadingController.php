<?php

namespace App\Http\Controllers;

use App\Models\Cascading;
use App\Models\Kontrak;
use App\Models\User;
use Illuminate\Http\Request;
use DB;


class CascadingController extends Controller
{


    public function index()
    {

        $data = Kontrak::with([
                'cascadings.user.unit'
            ])
            ->whereNotNull('id_parent')
            ->whereIn('id_parent', function($query){

                $query->select('id')
                    ->from('kontraks')
                    ->whereNull('id_parent');

            })
            ->orderBy('kode')
            ->get();

        // dd($data);

        $kontraks = Kontrak::with([
                    'cascadings.user.unit'
                ])
                ->whereNotNull('id_parent')
                ->whereIn('id_parent', function($query){

                    $query->select('id')
                        ->from('kontraks')
                        ->whereNull('id_parent');

                })
                ->orderBy('kode')
                ->get();


        $users = DB::table('users')
                    ->leftjoin('units', 'units.id', '=', 'users.id_unit')
                    ->select(
                        'users.name',
                        'units.unit',
                        'users.id'
                    )
                    ->get();


        return view(
            'backend.cascading.index',
            compact(
                'kontraks',
                'data',
                'users'
            )
        );
    }



    public function data(Request $request)
    {

        $data = Kontrak::with([
                'cascadings.user'
            ])
            ->whereNotNull('id_parent')
            ->whereIn('id_parent', function($query){

                $query->select('id')
                    ->from('kontraks')
                    ->whereNull('id_parent');

            })
            ->orderBy('kode')
            ->get();


        return response()->json([
            'data'=>$data
        ]);

    }



    public function store(Request $request)
    {


        $request->validate([

            'id_kontrak'=>'required',
            'id_user'=>'required'

        ]);


        Cascading::updateOrCreate(

            [
                'id_kontrak'=>$request->id_kontrak,
                'id_user'=>$request->id_user
            ],

            [
                'id_kontrak'=>$request->id_kontrak,
                'id_user'=>$request->id_user
            ]

        );



        return response()->json([

            'responCode'=>1,
            'respon'=>'Data berhasil disimpan'

        ]);


    }



    public function delete(Request $request)
    {

        Cascading::find($request->id)->delete();


        return response()->json([

            'responCode'=>1,
            'respon'=>'Data berhasil dihapus'

        ]);

    }


}