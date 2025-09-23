<?php

use Illuminate\Support\Facades\Route;

Route::get('planets', [App\Http\Controllers\Api\PlanetController::class, 'get']);
Route::get('registration_genes', [App\Http\Controllers\Api\GeneController::class, 'getRegistration']);
Route::get('regions/{planet_id}', [App\Http\Controllers\Api\RegionController::class, 'get']);
Route::get('map/{region_id}', [App\Http\Controllers\Api\MapController::class, 'get']);

Route::group(['prefix' => 'auth'], function (){
    Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
});
