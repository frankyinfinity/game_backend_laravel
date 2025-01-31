<?php

use Illuminate\Support\Facades\Route;

Route::get('planets', [App\Http\Controllers\Api\PlanetController::class, 'get'])->name('planets.get');
Route::get('regions/{planet_id}', [App\Http\Controllers\Api\RegionController::class, 'get'])->name('regions.get');
Route::get('map/{region_id}', [App\Http\Controllers\Api\MapController::class, 'get'])->name('map.get');