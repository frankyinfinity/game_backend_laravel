<?php

use Illuminate\Support\Facades\Route;

//Get
Route::get('planets', [App\Http\Controllers\Api\PlanetController::class, 'get']);
Route::get('registration_genes', [App\Http\Controllers\Api\GeneController::class, 'getRegistration']);
Route::post('regions/{planet_id}', [App\Http\Controllers\Api\RegionController::class, 'get']);
Route::get('map/{region_id}', [App\Http\Controllers\Api\MapController::class, 'get']);

//Game (No Auth)
Route::post('/game/login', [App\Http\Controllers\Api\GameController::class, 'login'])->name('game.login');
Route::post('/game/register', [App\Http\Controllers\Api\GameController::class, 'register'])->name('game.register');
Route::post('/game/clear_login', [App\Http\Controllers\Api\GameController::class, 'clearLogin'])->name('game.clear_login');
Route::post('/game/home', [App\Http\Controllers\Api\GameController::class, 'home'])->name('game.home');
Route::post('/game/get_draw_item', [App\Http\Controllers\Api\GameController::class, 'getDrawItem'])->name('game.get_draw_item');
Route::post('/game/close', [App\Http\Controllers\Api\GameController::class, 'close'])->name('game.close');

Route::group(['prefix' => 'auth'], function (){

    //Auth
    Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

    //Game (Auth)
    Route::post('/game/set_element_in_map', [App\Http\Controllers\Api\GameController::class, 'setElementInMap'])->name('game.set_element_in_map');
    Route::post('/game/entity/consume', [App\Http\Controllers\EntityController::class, 'consume'])->name('game.entity.consume');

});
