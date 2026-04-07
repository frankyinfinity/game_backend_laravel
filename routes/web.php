<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes([
    'login' => false,
]);

Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);

Route::get('/register-custom', function () {
    return view('auth.custom_register');
})->name('register.custom');


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

//Test
Route::get('/test', [App\Http\Controllers\TestController::class, 'index'])->name('test');
Route::post('/test/action', [App\Http\Controllers\TestController::class, 'action'])->name('test.action');

Route::group(['middleware' => ['auth']], function () {

    //Broadcasting
    Route::post('/broadcasting/auth', [App\Http\Controllers\BroadcastingController::class, 'auth']);

    //Container
    Route::get('/containers', [App\Http\Controllers\ContainerController::class, 'index'])->name('containers.index');
    Route::post('/containers/list/table', [App\Http\Controllers\ContainerController::class, 'listPlayersDataTable'])->name('containers.players.datatable');
    Route::get('/containers/{player}', [App\Http\Controllers\ContainerController::class, 'show'])->name('containers.show')->whereNumber('player');
    Route::get('/containers/{player}/snapshot', [App\Http\Controllers\ContainerController::class, 'snapshot'])->name('containers.snapshot')->whereNumber('player');
    Route::get('/containers/{player}/volume-file', [App\Http\Controllers\ContainerController::class, 'volumeFile'])->name('containers.volume-file')->whereNumber('player');
    Route::post('/containers/{player}/list/table', [App\Http\Controllers\ContainerController::class, 'listDataTable'])->name('containers.datatable')->whereNumber('player');
    Route::post('/containers/{container}/start', [App\Http\Controllers\ContainerController::class, 'start'])->name('containers.start')->whereNumber('container');
    Route::post('/containers/{container}/stop', [App\Http\Controllers\ContainerController::class, 'stop'])->name('containers.stop')->whereNumber('container');
    Route::post('/containers/{container}/restart', [App\Http\Controllers\ContainerController::class, 'restart'])->name('containers.restart')->whereNumber('container');
    Route::get('/containers/{container}/logs', [App\Http\Controllers\ContainerController::class, 'logs'])->name('containers.logs')->whereNumber('container');
    Route::get('/containers/{container}/inspect', [App\Http\Controllers\ContainerController::class, 'inspect'])->name('containers.inspect')->whereNumber('container');
    Route::post('/containers/{container}/exec', [App\Http\Controllers\ContainerController::class, 'exec'])->name('containers.exec')->whereNumber('container');
    Route::post('/containers/bulk-action', [App\Http\Controllers\ContainerController::class, 'bulkAction'])->name('containers.bulk-action');
    Route::post('/containers/delete', [App\Http\Controllers\ContainerController::class, 'delete'])->name('containers.delete');

    //User
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::post('/users/list/table', [App\Http\Controllers\UserController::class, 'listDataTable'])->name('users.datatable');
    Route::post('/users/delete', [App\Http\Controllers\UserController::class, 'delete'])->name('users.delete');

    //Tile
    Route::resource('tiles', App\Http\Controllers\TileController::class);
    Route::post('/tiles/list/table', [App\Http\Controllers\TileController::class, 'listDataTable'])->name('tiles.datatable');
    Route::post('/tiles/delete', [App\Http\Controllers\TileController::class, 'delete'])->name('tiles.delete');

    //Climate
    Route::resource('climates', App\Http\Controllers\ClimateController::class);
    Route::post('/climates/list/table', [App\Http\Controllers\ClimateController::class, 'listDataTable'])->name('climates.datatable');
    Route::post('/climates/delete', [App\Http\Controllers\ClimateController::class, 'delete'])->name('climates.delete');

    //Planet
    Route::resource('planets', App\Http\Controllers\PlanetController::class);
    Route::post('/planets/list/table', [App\Http\Controllers\PlanetController::class, 'listDataTable'])->name(name: 'planets.datatable');
    Route::post('/planets/delete', action: [App\Http\Controllers\PlanetController::class, 'delete'])->name('planets.delete');

    //Region
    Route::post('/regions/list/table/{planet_id}', [App\Http\Controllers\RegionController::class, 'listDataTable'])->name(name: 'regions.datatable');
    Route::get('/regions/page/create/{planet_id}', [App\Http\Controllers\RegionController::class, 'create'])->name(name: 'regions.create');
    Route::get('/regions/page/show/{id}', [App\Http\Controllers\RegionController::class, 'show'])->name(name: 'regions.show');
    Route::get('/regions/page/edit/{id}', [App\Http\Controllers\RegionController::class, 'edit'])->name(name: 'regions.edit');
    Route::get('/regions/page/edit-map/{id}', [App\Http\Controllers\RegionController::class, 'editMap'])->name(name: 'regions.edit-map');
    Route::post('/regions/action/store', [App\Http\Controllers\RegionController::class, 'store'])->name(name: 'regions.store');
    Route::put('/regions/action/update/{id}', [App\Http\Controllers\RegionController::class, 'update'])->name(name: 'regions.update');
    Route::post('/regions/action/delete', action: [App\Http\Controllers\RegionController::class, 'delete'])->name('regions.delete');
    Route::post('/regions/action/tile', action: [App\Http\Controllers\RegionController::class, 'updateTile'])->name('regions.tile');
    Route::post('/regions/action/tiles-batch', action: [App\Http\Controllers\RegionController::class, 'updateTiles'])->name('regions.tiles-batch');

    //Entity
    Route::get('entities/position', [App\Http\Controllers\EntityController::class, 'position']);
    Route::get('entities/genes', [App\Http\Controllers\EntityController::class, 'genes']);

    //Element
    Route::get('elements/genes', [App\Http\Controllers\ElementController::class, 'genes']);

    //Element Types
    Route::resource('element-types', App\Http\Controllers\ElementTypeController::class);
    Route::post('/element-types/list/table', [App\Http\Controllers\ElementTypeController::class, 'listDataTable'])->name('element-types.datatable');
    Route::post('/element-types/delete', [App\Http\Controllers\ElementTypeController::class, 'delete'])->name('element-types.delete');

    //Chimical Elements
    Route::resource('chimical-elements', App\Http\Controllers\ChimicalElementController::class);
    Route::post('/chimical-elements/list/table', [App\Http\Controllers\ChimicalElementController::class, 'listDataTable'])->name('chimical-elements.datatable');
    Route::post('/chimical-elements/delete', [App\Http\Controllers\ChimicalElementController::class, 'delete'])->name('chimical-elements.delete');

    //Complex Chimical Elements
    Route::resource('complex-chimical-elements', App\Http\Controllers\ComplexChimicalElementController::class);
    Route::post('/complex-chimical-elements/list/table', [App\Http\Controllers\ComplexChimicalElementController::class, 'listDataTable'])->name('complex-chimical-elements.datatable');
    Route::post('/complex-chimical-elements/delete', [App\Http\Controllers\ComplexChimicalElementController::class, 'delete'])->name('complex-chimical-elements.delete');
    Route::get('/complex-chimical-elements/{complexChimicalElement}/tree-data', [App\Http\Controllers\ComplexChimicalElementController::class, 'treeData'])->name('complex-chimical-elements.tree-data');

    //Complex Chimical Element Details
    Route::post('/complex-chimical-elements/{complexChimicalElement}/details', [App\Http\Controllers\ComplexChimicalElementDetailController::class, 'listDataTable'])->name('complex-chimical-elements.details.datatable');
    Route::post('/complex-chimical-elements/{complexChimicalElement}/details/store', [App\Http\Controllers\ComplexChimicalElementDetailController::class, 'store'])->name('complex-chimical-elements.details.store');
    Route::delete('/complex-chimical-elements/{complexChimicalElement}/details/{detail}', [App\Http\Controllers\ComplexChimicalElementDetailController::class, 'destroy'])->name('complex-chimical-elements.details.destroy');
    Route::post('/complex-chimical-elements/{complexChimicalElement}/details/delete', [App\Http\Controllers\ComplexChimicalElementDetailController::class, 'delete'])->name('complex-chimical-elements.details.delete');

    //Generator Chimical Elements
    Route::resource('generator-chimical-elements', App\Http\Controllers\GeneratorChimicalElementController::class);
    Route::post('/generator-chimical-elements/list/table', [App\Http\Controllers\GeneratorChimicalElementController::class, 'listDataTable'])->name('generator-chimical-elements.datatable');
    Route::post('/generator-chimical-elements/delete', [App\Http\Controllers\GeneratorChimicalElementController::class, 'delete'])->name('generator-chimical-elements.delete');

    //Elements
    Route::resource('elements', App\Http\Controllers\ElementController::class);
    Route::post('/elements/list/table', [App\Http\Controllers\ElementController::class, 'listDataTable'])->name('elements.datatable');
    Route::post('/elements/delete', [App\Http\Controllers\ElementController::class, 'delete'])->name('elements.delete');
    Route::post('/elements/{element}/save-graphics', [App\Http\Controllers\ElementController::class, 'saveGraphics'])->name('elements.save-graphics');
    Route::post('/elements/{element}/brain/neurons', [App\Http\Controllers\ElementController::class, 'saveBrainNeuron'])->name('elements.brain.neurons.save');
    Route::delete('/elements/{element}/brain/neurons', [App\Http\Controllers\ElementController::class, 'deleteBrainNeuron'])->name('elements.brain.neurons.delete');
    Route::post('/elements/{element}/brain/neuron-links', [App\Http\Controllers\ElementController::class, 'saveNeuronLink'])->name('elements.brain.neuron-links.save');
    Route::delete('/elements/{element}/brain/neuron-links', [App\Http\Controllers\ElementController::class, 'deleteNeuronLink'])->name('elements.brain.neuron-links.delete');

    //Scores
    Route::resource('scores', App\Http\Controllers\ScoreController::class);
    Route::post('/scores/list/table', [App\Http\Controllers\ScoreController::class, 'listDataTable'])->name('scores.datatable');
    Route::post('/scores/delete', [App\Http\Controllers\ScoreController::class, 'destroy'])->name('scores.delete');
    Route::post('/scores/{score}/save-graphics', [App\Http\Controllers\ScoreController::class, 'saveGraphics'])->name('scores.save-graphics');

    //Ages
    Route::resource('ages', App\Http\Controllers\AgeController::class);
    Route::post('/ages/list/table', [App\Http\Controllers\AgeController::class, 'listDataTable'])->name('ages.datatable');
    Route::post('/ages/delete', [App\Http\Controllers\AgeController::class, 'delete'])->name('ages.delete');
    Route::get('/ages/{age}/move-up', [App\Http\Controllers\AgeController::class, 'moveUp'])->name('ages.move-up');
    Route::get('/ages/{age}/move-down', [App\Http\Controllers\AgeController::class, 'moveDown'])->name('ages.move-down');

    //Phases
    Route::resource('ages.phases', App\Http\Controllers\PhaseController::class);
    Route::post('/ages/{age}/phases/list/table', [App\Http\Controllers\PhaseController::class, 'listDataTable'])->name('ages.phases.datatable');
    Route::post('/ages/{age}/phases/delete', [App\Http\Controllers\PhaseController::class, 'delete'])->name('ages.phases.delete');
    Route::get('/ages/{age}/phases/{phase}/move-up', [App\Http\Controllers\PhaseController::class, 'moveUp'])->name('ages.phases.move-up');
    Route::get('/ages/{age}/phases/{phase}/move-down', [App\Http\Controllers\PhaseController::class, 'moveDown'])->name('ages.phases.move-down');
    Route::get('/ages/{age}/phases/{phase}/data', [App\Http\Controllers\PhaseController::class, 'getData'])->name('ages.phases.data');

    //Phase Columns
    Route::post('/ages/{age}/phases/{phase}/columns', [App\Http\Controllers\PhaseColumnController::class, 'store'])->name('ages.phases.columns.store');
    Route::delete('/ages/{age}/phases/{phase}/columns/{phaseColumn}', [App\Http\Controllers\PhaseColumnController::class, 'destroy'])->name('ages.phases.columns.destroy');

    //Targets (Obiettivi)
    Route::get('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}', [App\Http\Controllers\TargetController::class, 'show'])->name('ages.phases.columns.targets.show');
    Route::post('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets', [App\Http\Controllers\TargetController::class, 'store'])->name('ages.phases.columns.targets.store');
    Route::put('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}', [App\Http\Controllers\TargetController::class, 'update'])->name('ages.phases.columns.targets.update');
    Route::delete('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}', [App\Http\Controllers\TargetController::class, 'destroy'])->name('ages.phases.columns.targets.destroy');
    Route::get('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-has-scores', [App\Http\Controllers\TargetHasScoreController::class, 'index'])->name('ages.phases.columns.targets.target-has-scores.index');
    Route::post('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-has-scores', [App\Http\Controllers\TargetHasScoreController::class, 'store'])->name('ages.phases.columns.targets.target-has-scores.store');
    Route::put('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-has-scores/{targetHasScore}', [App\Http\Controllers\TargetHasScoreController::class, 'update'])->name('ages.phases.columns.targets.target-has-scores.update');
    Route::delete('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-has-scores/{targetHasScore}', [App\Http\Controllers\TargetHasScoreController::class, 'destroy'])->name('ages.phases.columns.targets.target-has-scores.destroy');

    //Target Links (Collegamenti tra obiettivi)
    Route::get('/ages/{age}/phases/{phase}/target-links', [App\Http\Controllers\TargetLinkController::class, 'phaseIndex'])->name('ages.phases.target-links.index');
    Route::get('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-links', [App\Http\Controllers\TargetLinkController::class, 'index'])->name('ages.phases.columns.targets.target-links.index');
    Route::post('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-links', [App\Http\Controllers\TargetLinkController::class, 'store'])->name('ages.phases.columns.targets.target-links.store');
    Route::get('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-links/{targetLink}', [App\Http\Controllers\TargetLinkController::class, 'show'])->name('ages.phases.columns.targets.target-links.show');
    Route::delete('/ages/{age}/phases/{phase}/columns/{phaseColumn}/targets/{target}/target-links/{targetLink}', [App\Http\Controllers\TargetLinkController::class, 'destroy'])->name('ages.phases.columns.targets.target-links.destroy');

    // Neurons
    Route::get('/neurons/{neuron}/border-uid', [App\Http\Controllers\NeuronController::class, 'getBorderUid'])->name('neurons.border-uid');
    Route::post('/neurons/broadcast-update', [App\Http\Controllers\NeuronController::class, 'broadcastNeuronUpdate'])->name('neurons.broadcast-update');

});
