<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth']], function (){

    //Users
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::post('/users/list/table', [App\Http\Controllers\UserController::class, 'listDataTable'])->name('users.datatable');
    Route::post('/users/delete', [App\Http\Controllers\UserController::class, 'delete'])->name('users.delete');

    //Tiles
    Route::resource('tiles', App\Http\Controllers\TileController::class);
    Route::post('/tiles/list/table', [App\Http\Controllers\TileController::class, 'listDataTable'])->name('tiles.datatable');
    Route::post('/tiles/delete', [App\Http\Controllers\TileController::class, 'delete'])->name('tiles.delete');

});