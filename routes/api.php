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
Route::post('/game/clear', [App\Http\Controllers\Api\GameController::class, 'clear'])->name('game.clear');

Route::group(['prefix' => 'auth'], function (){

    //Auth
    Route::post('register', [App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('login', [App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);

    //Game (Auth)
    Route::post('/game/set_element_in_map', [App\Http\Controllers\Api\GameController::class, 'setElementInMap'])->name('game.set_element_in_map');
    Route::post('/game/entity/movement', [App\Http\Controllers\Api\GameController::class, 'movement'])->name('game.entity.movement');
    Route::post('/game/entity/consume', [App\Http\Controllers\Api\GameController::class, 'consume'])->name('game.entity.consume');
    Route::post('/game/entity/attack', [App\Http\Controllers\Api\GameController::class, 'attack'])->name('game.entity.attack');
    Route::post('/game/entity/division', [App\Http\Controllers\Api\GameController::class, 'division'])->name('game.entity.division');
    Route::post('/game/get_tiles_by_birth_region', [App\Http\Controllers\Api\GameController::class, 'getTilesByBirthRegion'])->name('game.get_tiles_by_birth_region');
    Route::post('/game/get_birth_region_details', [App\Http\Controllers\Api\GameController::class, 'getBirthRegionDetails'])->name('game.get_birth_region_details');
    Route::post('/game/calculate_chimical_element', [App\Http\Controllers\Api\GameController::class, 'calculateChimicalElement'])->name('game.calculate_chimical_element');
    Route::post('/game/consume_chimical_element', [App\Http\Controllers\Api\GameController::class, 'consumeChimicalElement'])->name('game.consume_chimical_element');
    Route::post('/game/player_values/reset', [App\Http\Controllers\Api\GameController::class, 'resetPlayerValues'])->name('game.player_values.reset');
    Route::post('/game/player_values/get', [App\Http\Controllers\Api\GameController::class, 'getPlayerValues'])->name('game.player_values.get');
    Route::post('/game/check_modifier', [App\Http\Controllers\Api\GameController::class, 'checkModifier'])->name('game.check_modifier');
    Route::post('/game/objective/modal_visibility', [App\Http\Controllers\Api\GameController::class, 'setObjectiveModalVisibility'])->name('game.objective.modal_visibility');
    Route::post('/game/objective/start', [App\Http\Controllers\Api\GameController::class, 'startObjective'])->name('game.objective.start');
    Route::post('/game/objective/check', [App\Http\Controllers\Api\GameController::class, 'checkObjective'])->name('game.objective.check');
    Route::post('/game/brain', [App\Http\Controllers\Api\GameController::class, 'brain'])->name('game.brain');
    Route::post('/game/brain_schedule/finish', [App\Http\Controllers\Api\GameController::class, 'finishBrainSchedule'])->name('game.brain_schedule.finish');
    Route::post('/game/sync_object_cache', [App\Http\Controllers\Api\GameController::class, 'syncObjectCache'])->name('game.sync_object_cache');
     Route::post('/game/entity/check_degradation', [App\Http\Controllers\Api\GameController::class, 'checkEntityDegradation'])->name('game.entity.check_degradation');
     Route::post('/game/element/check_degradation', [App\Http\Controllers\Api\GameController::class, 'checkElementDegradation'])->name('game.element.check_degradation');
     Route::post('/game/entity/apply_gene_effects', [App\Http\Controllers\Api\GameController::class, 'applyGeneEffects'])->name('game.entity.apply_gene_effects');
    Route::post('/game/information/update', [App\Http\Controllers\Api\GameController::class, 'updateInformation'])->name('game.information.update');

});
Route::post('/game/websocket_info', [App\Http\Controllers\Api\GameController::class, 'websocketInfo'])->name('game.websocket_info');
Route::post('/game/player/container_data', [App\Http\Controllers\Api\GameController::class, 'getPlayerContainerData'])->name('game.player.container_data');
Route::post('/game/container/action', [App\Http\Controllers\Api\GameController::class, 'containerAction'])->name('game.container.action');
