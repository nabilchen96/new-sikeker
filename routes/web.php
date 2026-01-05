<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//LOGIN
Route::get('/login', 'App\Http\Controllers\AuthController@login')->name('login');
Route::post('/loginProses', 'App\Http\Controllers\AuthController@loginProses');

Route::get('/', function () {
    return view('frontend.welcome');
});

//BACKEND
Route::group(['middleware' => 'auth'], function () {

    //DASHBOARD
    Route::get('/dashboard', 'App\Http\Controllers\DashboardController@index');
    
    //UNIT
    Route::get('/unit', 'App\Http\Controllers\UnitController@index');
    Route::get('/data-unit', 'App\Http\Controllers\UnitController@data');
    Route::post('/store-unit', 'App\Http\Controllers\UnitController@store');
    Route::post('/update-unit', 'App\Http\Controllers\UnitController@update');
    Route::post('/delete-unit', 'App\Http\Controllers\UnitController@delete');
    
    
    //TAHUN
    Route::get('/tahun', 'App\Http\Controllers\TahunController@index');
    Route::get('/data-tahun', 'App\Http\Controllers\TahunController@data');
    Route::post('/store-tahun', 'App\Http\Controllers\TahunController@store');
    Route::post('/update-tahun', 'App\Http\Controllers\TahunController@update');
    Route::post('/delete-tahun', 'App\Http\Controllers\TahunController@delete');
    
    //USER
    Route::get('/user', 'App\Http\Controllers\UserController@index');
    Route::get('/data-user', 'App\Http\Controllers\UserController@data');
    Route::post('/store-user', 'App\Http\Controllers\UserController@store');
    Route::post('/update-user', 'App\Http\Controllers\UserController@update');
    Route::post('/delete-user', 'App\Http\Controllers\UserController@delete');
    
    //APPROVAL
    Route::get('/approval', 'App\Http\Controllers\ApprovalController@index');
    Route::get('/data-approval', 'App\Http\Controllers\ApprovalController@data');
    Route::post('/store-approval', 'App\Http\Controllers\ApprovalController@store');
    Route::post('/update-approval', 'App\Http\Controllers\ApprovalController@update');
    Route::post('/delete-approval', 'App\Http\Controllers\ApprovalController@delete');


    //PROKER
    Route::get('/proker', 'App\Http\Controllers\ProkerController@index');
    Route::get('/data-proker', 'App\Http\Controllers\ProkerController@data');
    Route::post('/store-proker', 'App\Http\Controllers\ProkerController@store');
    Route::post('/update-proker', 'App\Http\Controllers\ProkerController@update');
    Route::post('/delete-proker', 'App\Http\Controllers\ProkerController@delete');
    Route::post('/ajukan-proker', 'App\Http\Controllers\ProkerController@ajukanProker');
    Route::post('/ubah-status-proker', 'App\Http\Controllers\ProkerController@ubahStatusProker');
    
    //RENCANA PROKER
    Route::get('/rencana-proker', 'App\Http\Controllers\RencanaProkerController@index');
    Route::get('/data-rencana-proker', 'App\Http\Controllers\RencanaProkerController@data');
    Route::post('/store-rencana-proker', 'App\Http\Controllers\RencanaProkerController@store');
    Route::post('/update-rencana-proker', 'App\Http\Controllers\RencanaProkerController@update');
    Route::post('/delete-rencana-proker', 'App\Http\Controllers\RencanaProkerController@delete');
    Route::post('/update-status-rencana-proker', 'App\Http\Controllers\RencanaProkerController@updateStatus');

    //AKSI PROKER
    Route::get('/aksi-proker', 'App\Http\Controllers\AksiProkerController@index');
    Route::get('/data-aksi-proker', 'App\Http\Controllers\AksiProkerController@data');
    Route::post('/store-aksi-proker', 'App\Http\Controllers\AksiProkerController@store');
    Route::post('/update-aksi-proker', 'App\Http\Controllers\AksiProkerController@update');
    Route::post('/delete-aksi-proker', 'App\Http\Controllers\AksiProkerController@delete');


    Route::get('file-manager', function(){
        return view('backend.file_manager.index');
    });
});



//LOGOUT
Route::get('/logout', function () {
    Auth::logout();
    return redirect('login');
})->name('logout');