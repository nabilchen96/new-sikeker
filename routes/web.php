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

Route::get('/', function () {
    return view('frontend.welcome');
});


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
