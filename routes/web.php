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

    //Climates
    Route::resource('climates', App\Http\Controllers\ClimateController::class);
    Route::post('/climates/list/table', [App\Http\Controllers\ClimateController::class, 'listDataTable'])->name('climates.datatable');
    Route::post('/climates/delete', [App\Http\Controllers\ClimateController::class, 'delete'])->name('climates.delete');

    //Planets
    Route::resource('planets', App\Http\Controllers\PlanetController::class);
    Route::post('/planets/list/table', [App\Http\Controllers\PlanetController::class, 'listDataTable'])->name(name: 'planets.datatable');
    Route::post('/planets/delete', action: [App\Http\Controllers\PlanetController::class, 'delete'])->name('planets.delete');

    //Regions
    Route::post('/regions/list/table/{planet_id}', [App\Http\Controllers\RegionController::class, 'listDataTable'])->name(name: 'regions.datatable');
    Route::get('/regions/page/create/{planet_id}', [App\Http\Controllers\RegionController::class, 'create'])->name(name: 'regions.create');
    Route::get('/regions/page/show/{id}', [App\Http\Controllers\RegionController::class, 'show'])->name(name: 'regions.show');
    Route::get('/regions/page/edit/{id}', [App\Http\Controllers\RegionController::class, 'edit'])->name(name: 'regions.edit');
    Route::post('/regions/action/store', [App\Http\Controllers\RegionController::class, 'store'])->name(name: 'regions.store');
    Route::put('/regions/action/update/{id}', [App\Http\Controllers\RegionController::class, 'update'])->name(name: 'regions.update');
    Route::post('/regions/action/delete', action: [App\Http\Controllers\RegionController::class, 'delete'])->name('regions.delete');

});