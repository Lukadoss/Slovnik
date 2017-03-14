<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $districts = DB::table('district')->get();

    return view('welcome', compact('districts'));
});

Route::get('tmpl1', function (){
   return view('about');
});

Route::get('tmpl2', function (){
    return view('index');
});

Route::get('tmpl3', function (){
    return view('blog');
});