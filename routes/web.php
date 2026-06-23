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

    //WebSocket
    Route::get('/websocket', [App\Http\Controllers\WebSocketController::class, 'index'])->name('websocket.index');
    Route::post('/websocket/list/table', [App\Http\Controllers\WebSocketController::class, 'listPlayersDataTable'])->name('websocket.players.datatable');
    Route::get('/websocket/{player}', [App\Http\Controllers\WebSocketController::class, 'show'])->name('websocket.show')->whereNumber('player');

    //WebSocket Container Monitor
    Route::get('/websocket-containers', [App\Http\Controllers\WebSocketContainerController::class, 'index'])->name('websocket.containers.index');
    Route::get('/websocket-containers/list', [App\Http\Controllers\WebSocketContainerController::class, 'listContainersJson'])->name('websocket.containers.list');
    Route::get('/websocket-containers/status', [App\Http\Controllers\WebSocketContainerController::class, 'containerStatusJson'])->name('websocket.containers.status');
    Route::get('/websocket-containers/players', [App\Http\Controllers\WebSocketContainerController::class, 'listPlayers'])->name('websocket.containers.players');

    //Container
    Route::get('/containers', [App\Http\Controllers\ContainerController::class, 'index'])->name('containers.index');
    Route::post('/containers/list/table', [App\Http\Controllers\ContainerController::class, 'listPlayersDataTable'])->name('containers.players.datatable');
    Route::get('/containers/{player}', [App\Http\Controllers\ContainerController::class, 'show'])->name('containers.show')->whereNumber('player');
    Route::get('/containers/{player}/snapshot', [App\Http\Controllers\ContainerController::class, 'snapshot'])->name('containers.snapshot')->whereNumber('player');
    Route::get('/containers/{player}/volume-file', [App\Http\Controllers\ContainerController::class, 'volumeFile'])->name('containers.volume-file')->whereNumber('player');
    Route::post('/containers/{player}/list/table', [App\Http\Controllers\ContainerController::class, 'listDataTable'])->name('containers.datatable')->whereNumber('player');
    Route::get('/game/birth-region/tiles', [App\Http\Controllers\Api\GameController::class, 'getBirthRegionTiles'])->name('game.birth-region.tiles');
    Route::post('/game/element-has-position/create', [App\Http\Controllers\Api\GameController::class, 'createElementHasPosition'])->name('game.element-has-position.create');
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

    //Family Tile
    Route::resource('family-tiles', App\Http\Controllers\FamilyTileController::class);
    Route::post('/family-tiles/list/table', [App\Http\Controllers\FamilyTileController::class, 'listDataTable'])->name('family-tiles.datatable');
    Route::post('/family-tiles/delete', [App\Http\Controllers\FamilyTileController::class, 'delete'])->name('family-tiles.delete');
    Route::post('/family-tiles/{familyTile}/update-limits', [App\Http\Controllers\FamilyTileController::class, 'updateLimits'])->name('family-tiles.update-limits');
    Route::get('/family-tiles/{familyTile}/diffusions', [App\Http\Controllers\FamilyTileController::class, 'diffusions'])->name('family-tiles.diffusions');
    Route::post('/family-tiles/{familyTile}/diffusions', [App\Http\Controllers\FamilyTileController::class, 'storeDiffusion'])->name('family-tiles.diffusions.store');
    Route::put('/family-tiles/{familyTile}/diffusions/{diffusion}', [App\Http\Controllers\FamilyTileController::class, 'updateDiffusion'])->name('family-tiles.diffusions.update');
    Route::delete('/family-tiles/{familyTile}/diffusions/{diffusion}', [App\Http\Controllers\FamilyTileController::class, 'destroyDiffusion'])->name('family-tiles.diffusions.destroy');
    Route::get('/family-tiles/{familyTile}/element-limit/{elementId}/{type}', [App\Http\Controllers\FamilyTileController::class, 'getElementLimit'])->name('family-tiles.element-limit');

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
    Route::post('/regions/action/generate-images/{id}', [App\Http\Controllers\RegionController::class, 'generateImages'])->name(name: 'regions.generate-images');
    Route::post('/regions/action/complete-region/{id}', [App\Http\Controllers\RegionController::class, 'completeRegion'])->name(name: 'regions.complete-region');
    Route::post('/regions/action/delete', action: [App\Http\Controllers\RegionController::class, 'delete'])->name('regions.delete');
    Route::post('/regions/action/tile', action: [App\Http\Controllers\RegionController::class, 'updateTile'])->name('regions.tile');
    Route::post('/regions/action/tiles-batch', action: [App\Http\Controllers\RegionController::class, 'updateTiles'])->name('regions.tiles-batch');

    //Entity
    Route::get('entities/position', [App\Http\Controllers\EntityController::class, 'position']);
    Route::get('entities/genes', [App\Http\Controllers\EntityController::class, 'genes']);
    Route::get('entities/chimical-elements', [App\Http\Controllers\EntityController::class, 'chimicalElements']);

    //Entity Bodies
    Route::resource('entity-bodies', App\Http\Controllers\EntityBodyController::class);
    Route::post('/entity-bodies/list/table', [App\Http\Controllers\EntityBodyController::class, 'listDataTable'])->name('entity-bodies.datatable');
    Route::post('/entity-bodies/delete', [App\Http\Controllers\EntityBodyController::class, 'bulkDelete'])->name('entity-bodies.delete');
    Route::post('/entity-bodies/action/toggle-state', [App\Http\Controllers\EntityBodyController::class, 'toggleState'])->name('entity-bodies.toggle-state');

    Route::group([
        'as' => 'entity-bodies.zones.',
        'prefix' => '/entity-bodies/{entityBody}/zones',
    ], function () {
        Route::get('/', [App\Http\Controllers\EntityBodyZoneController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\EntityBodyZoneController::class, 'store'])->name('store');
        Route::post('/{zone}/add-detail', [App\Http\Controllers\EntityBodyZoneController::class, 'addDetail'])->name('add-detail');
        Route::post('/{zone}/remove-detail/{detail}', [App\Http\Controllers\EntityBodyZoneController::class, 'removeDetail'])->name('remove-detail');
        Route::post('/{zone}/replace-details', [App\Http\Controllers\EntityBodyZoneController::class, 'replaceDetails'])->name('replace-details');
        Route::post('/{zone}/save-pixels', [App\Http\Controllers\EntityBodyZoneController::class, 'savePixels'])->name('save-pixels');
        Route::put('/{zone}', [App\Http\Controllers\EntityBodyZoneController::class, 'update'])->name('update');
        Route::delete('/{zone}', [App\Http\Controllers\EntityBodyZoneController::class, 'destroy'])->name('destroy');
    });

    //Element
    Route::get('elements/genes', [App\Http\Controllers\ElementController::class, 'genes']);
    Route::get('elements/chimical-elements', [App\Http\Controllers\ElementController::class, 'chimicalElements']);
    Route::get('elements/status', [App\Http\Controllers\ElementController::class, 'status']);

    //Element Types
    Route::resource('element-types', App\Http\Controllers\ElementTypeController::class);
    Route::post('/element-types/list/table', [App\Http\Controllers\ElementTypeController::class, 'listDataTable'])->name('element-types.datatable');
    Route::post('/element-types/delete', [App\Http\Controllers\ElementTypeController::class, 'delete'])->name('element-types.delete');

    //Element Type Components
    Route::resource('element-type-components', App\Http\Controllers\ElementTypeComponentController::class);
    Route::post('/element-type-components/list/table', [App\Http\Controllers\ElementTypeComponentController::class, 'listDataTable'])->name('element-type-components.datatable');
    Route::post('/element-type-components/delete', [App\Http\Controllers\ElementTypeComponentController::class, 'delete'])->name('element-type-components.delete');

    //Element Components
    Route::resource('element-components', App\Http\Controllers\ElementComponentController::class);
    Route::post('/element-components/list/table', [App\Http\Controllers\ElementComponentController::class, 'listDataTable'])->name('element-components.datatable');
    Route::post('/element-components/delete', [App\Http\Controllers\ElementComponentController::class, 'delete'])->name('element-components.delete');
    Route::post('/element-components/{elementComponent}/toggle-state', [App\Http\Controllers\ElementComponentController::class, 'toggleState'])->name('element-components.toggle-state');

    // Element Components Geni
    Route::post('/element-components/{elementComponent}/genes/table', [App\Http\Controllers\ElementComponentController::class, 'genesDataTable'])->name('element-components.genes.datatable');
    Route::get('/element-components/{elementComponent}/genes/available', [App\Http\Controllers\ElementComponentController::class, 'getAvailableGenes'])->name('element-components.genes.available');
    Route::post('/element-components/{elementComponent}/genes', [App\Http\Controllers\ElementComponentController::class, 'storeGene'])->name('element-components.genes.store');
    Route::delete('/element-components/genes/{elementComponentHasGene}', [App\Http\Controllers\ElementComponentController::class, 'destroyGene'])->name('element-components.genes.destroy');

    // Element Components Elementi Chimici
    Route::post('/element-components/{elementComponent}/rules/table', [App\Http\Controllers\ElementComponentController::class, 'rulesDataTable'])->name('element-components.rules.datatable');
    Route::get('/element-components/{elementComponent}/rules/available', [App\Http\Controllers\ElementComponentController::class, 'getAvailableRules'])->name('element-components.rules.available');
    Route::post('/element-components/{elementComponent}/rules', [App\Http\Controllers\ElementComponentController::class, 'storeRule'])->name('element-components.rules.store');
    Route::delete('/element-components/rules/{elementComponentHasRule}', [App\Http\Controllers\ElementComponentController::class, 'destroyRule'])->name('element-components.rules.destroy');

    // Element Components Effetti Consumo
    Route::post('/element-components/{elementComponent}/consumption/table', [App\Http\Controllers\ElementComponentController::class, 'consumptionDataTable'])->name('element-components.consumption.datatable');
    Route::get('/element-components/{elementComponent}/consumption/available', [App\Http\Controllers\ElementComponentController::class, 'getAvailableConsumptionGenes'])->name('element-components.consumption.available');
    Route::post('/element-components/{elementComponent}/consumption', [App\Http\Controllers\ElementComponentController::class, 'storeConsumptionEffect'])->name('element-components.consumption.store');
    Route::delete('/element-components/consumption/{consumptionEffect}', [App\Http\Controllers\ElementComponentController::class, 'destroyConsumptionEffect'])->name('element-components.consumption.destroy');

    // Element Components Brain (Cervello)
    Route::post('/element-components/{elementComponent}/brain/grid', [App\Http\Controllers\ElementComponentController::class, 'saveBrainGrid'])->name('element-components.brain.grid.save');
    Route::post('/element-components/{elementComponent}/brain/neurons', [App\Http\Controllers\ElementComponentController::class, 'saveBrainNeuron'])->name('element-components.brain.neurons.save');
    Route::patch('/element-components/{elementComponent}/brain/neurons/{neuron}/move', [App\Http\Controllers\ElementComponentController::class, 'moveBrainNeuron'])->name('element-components.brain.neurons.move');
    Route::post('/element-components/{elementComponent}/brain/neurons/{neuron}/condition-orders', [App\Http\Controllers\ElementComponentController::class, 'saveNeuronConditionOrders'])->name('element-components.brain.neurons.condition-orders.save');
    Route::delete('/element-components/{elementComponent}/brain/neurons', [App\Http\Controllers\ElementComponentController::class, 'deleteBrainNeuron'])->name('element-components.brain.neurons.delete');
    Route::post('/element-components/{elementComponent}/brain/neuron-links', [App\Http\Controllers\ElementComponentController::class, 'saveNeuronLink'])->name('element-components.brain.neuron-links.save');
    Route::delete('/element-components/{elementComponent}/brain/neuron-links', [App\Http\Controllers\ElementComponentController::class, 'deleteNeuronLink'])->name('element-components.brain.neuron-links.delete');
    Route::post('/element-components/{elementComponent}/brain/circuits/{circuit}/toggle-active', [App\Http\Controllers\ElementComponentController::class, 'toggleCircuitActive'])->name('element-components.brain.circuits.toggle-active');
    Route::delete('/element-components/{elementComponent}/brain/circuits/{circuit}', [App\Http\Controllers\ElementComponentController::class, 'deleteBrainCircuit'])->name('element-components.brain.circuits.delete');

    //Chimical Elements
    Route::resource('chimical-elements', App\Http\Controllers\ChimicalElementController::class);
    Route::post('/chimical-elements/list/table', [App\Http\Controllers\ChimicalElementController::class, 'listDataTable'])->name('chimical-elements.datatable');
    Route::post('/chimical-elements/delete', [App\Http\Controllers\ChimicalElementController::class, 'delete'])->name('chimical-elements.delete');

    //Graphics AI
    Route::get('/graphics-editor/ai-models', [App\Http\Controllers\GraphicsAiController::class, 'models'])->name('graphics-editor.ai-models');
    Route::post('/graphics-editor/ai-generate', [App\Http\Controllers\GraphicsAiController::class, 'generate'])->name('graphics-editor.ai-generate');

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

    //Genes
    Route::get('/genes', [App\Http\Controllers\GeneController::class, 'index'])->name('genes.index');
    Route::post('/genes/list/table', [App\Http\Controllers\GeneController::class, 'listDataTable'])->name('genes.datatable');
    Route::get('/genes/{gene}/edit', [App\Http\Controllers\GeneController::class, 'edit'])->name('genes.edit')->whereNumber('gene');
    Route::put('/genes/{gene}', [App\Http\Controllers\GeneController::class, 'update'])->name('genes.update')->whereNumber('gene');
    Route::get('/genes/{gene}', [App\Http\Controllers\GeneController::class, 'show'])->name('genes.show')->whereNumber('gene');

    //Generator Chimical Elements
    Route::resource('generator-chimical-elements', App\Http\Controllers\GeneratorChimicalElementController::class);
    Route::post('/generator-chimical-elements/list/table', [App\Http\Controllers\GeneratorChimicalElementController::class, 'listDataTable'])->name('generator-chimical-elements.datatable');
    Route::post('/generator-chimical-elements/delete', [App\Http\Controllers\GeneratorChimicalElementController::class, 'delete'])->name('generator-chimical-elements.delete');

    //Rule Chimical Elements
    Route::resource('rule-chimical-elements', App\Http\Controllers\RuleChimicalElementController::class)->except(['create']);
    Route::post('/rule-chimical-elements/list/table', [App\Http\Controllers\RuleChimicalElementController::class, 'listDataTable'])->name('rule-chimical-elements.datatable');
    Route::get('/rule-chimical-elements/list/all', [App\Http\Controllers\RuleChimicalElementController::class, 'list'])->name('rule-chimical-elements.list.all');
    Route::get('/rule-chimical-elements/{ruleChimicalElement}/replicate', [App\Http\Controllers\RuleChimicalElementController::class, 'replicate'])->name('rule-chimical-elements.replicate');
    Route::post('/rule-chimical-elements/delete', [App\Http\Controllers\RuleChimicalElementController::class, 'delete'])->name('rule-chimical-elements.delete');
    Route::post('/rule-chimical-elements/{ruleChimicalElement}/details', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'store'])->name('rule-chimical-elements.detail.store');
    Route::post('/rule-chimical-elements/{ruleChimicalElement}/save-all', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'saveAll'])->name('rule-chimical-elements.detail.saveAll');
    Route::post('/rule-chimical-elements/{ruleChimicalElement}/details/{detail}', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'update'])->name('rule-chimical-elements.detail.update');
    Route::get('/rule-chimical-elements/{ruleChimicalElement}/reload', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'reload'])->name('rule-chimical-elements.detail.reload');
    Route::delete('/rule-chimical-elements/{ruleChimicalElement}/details/{detail}', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'destroy'])->name('rule-chimical-elements.detail.destroy');

    //Rule Chimical Element Detail Effects
    Route::get('/rule-chimical-elements/details/{detail}/effects', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'listEffects'])->name('rule-chimical-elements.detail.effects.list');
    Route::post('/rule-chimical-elements/details/{detail}/effects', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'storeEffect'])->name('rule-chimical-elements.detail.effects.store');
    Route::put('/rule-chimical-elements/effects/{effect}', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'updateEffect'])->name('rule-chimical-elements.detail.effects.update');
    Route::delete('/rule-chimical-elements/effects/{effect}', [App\Http\Controllers\RuleChimicalElementDetailController::class, 'destroyEffect'])->name('rule-chimical-elements.detail.effects.destroy');

    //Elements Diffusion
    Route::get('/elements/diffusion', [App\Http\Controllers\ElementController::class, 'diffusionIndex'])->name('elements.diffusion.index');
    Route::get('/elements/{element}/diffusion', [App\Http\Controllers\ElementController::class, 'diffusionShow'])->name('elements.diffusion.show');
    Route::put('/elements/{element}/diffusion', [App\Http\Controllers\ElementController::class, 'diffusionUpdate'])->name('elements.diffusion.update');

    //Elements
    Route::resource('elements', App\Http\Controllers\ElementController::class);
    Route::post('/elements/list/table', [App\Http\Controllers\ElementController::class, 'listDataTable'])->name('elements.datatable');
    Route::post('/elements/delete', [App\Http\Controllers\ElementController::class, 'delete'])->name('elements.delete');
    Route::post('/elements/{element}/save-graphics', [App\Http\Controllers\ElementController::class, 'saveGraphics'])->name('elements.save-graphics');
    Route::post('/elements/{element}/brain/neurons', [App\Http\Controllers\ElementController::class, 'saveBrainNeuron'])->name('elements.brain.neurons.save');
    Route::patch('/elements/{element}/brain/neurons/{neuron}/move', [App\Http\Controllers\ElementController::class, 'moveBrainNeuron'])->name('elements.brain.neurons.move');
    Route::post('/elements/{element}/brain/neurons/{neuron}/condition-orders', [App\Http\Controllers\ElementController::class, 'saveNeuronConditionOrders'])->name('elements.brain.neurons.condition-orders.save');
    Route::delete('/elements/{element}/brain/neurons', [App\Http\Controllers\ElementController::class, 'deleteBrainNeuron'])->name('elements.brain.neurons.delete');
    Route::post('/elements/{element}/brain/neuron-links', [App\Http\Controllers\ElementController::class, 'saveNeuronLink'])->name('elements.brain.neuron-links.save');
    Route::delete('/elements/{element}/brain/neuron-links', [App\Http\Controllers\ElementController::class, 'deleteNeuronLink'])->name('elements.brain.neuron-links.delete');
    Route::post('/elements/{element}/brain/circuits/{circuit}/toggle-active', [App\Http\Controllers\ElementController::class, 'toggleCircuitActive'])->name('elements.brain.circuits.toggle-active');
    Route::delete('/elements/{element}/brain/circuits/{circuit}', [App\Http\Controllers\ElementController::class, 'deleteBrainCircuit'])->name('elements.brain.circuits.delete');

    //Scores
    Route::resource('scores', App\Http\Controllers\ScoreController::class);
    Route::post('/scores/list/table', [App\Http\Controllers\ScoreController::class, 'listDataTable'])->name('scores.datatable');
    Route::post('/scores/delete', [App\Http\Controllers\ScoreController::class, 'destroy'])->name('scores.delete');
    Route::post('/scores/{score}/save-graphics', [App\Http\Controllers\ScoreController::class, 'saveGraphics'])->name('scores.save-graphics');

    // Entity Type Components
    Route::resource('entity-type-components', App\Http\Controllers\EntityTypeComponentController::class);
    Route::post('/entity-type-components/list/table', [App\Http\Controllers\EntityTypeComponentController::class, 'listDataTable'])->name('entity-type-components.datatable');
    Route::post('/entity-type-components/delete', [App\Http\Controllers\EntityTypeComponentController::class, 'delete'])->name('entity-type-components.delete');

    //Entity Components
    Route::resource('entity-components', App\Http\Controllers\EntityComponentController::class);
    Route::post('/entity-components/list/table', [App\Http\Controllers\EntityComponentController::class, 'listDataTable'])->name('entity-components.datatable');
    Route::post('/entity-components/delete', [App\Http\Controllers\EntityComponentController::class, 'delete'])->name('entity-components.delete');
    Route::post('/entity-components/{entityComponent}/toggle-state', [App\Http\Controllers\EntityComponentController::class, 'toggleState'])->name('entity-components.toggle-state');

    // Entity Components Geni
    Route::post('/entity-components/{entityComponent}/genes/table', [App\Http\Controllers\EntityComponentController::class, 'genesDataTable'])->name('entity-components.genes.datatable');
    Route::get('/entity-components/{entityComponent}/genes/available', [App\Http\Controllers\EntityComponentController::class, 'getAvailableGenes'])->name('entity-components.genes.available');
    Route::post('/entity-components/{entityComponent}/genes', [App\Http\Controllers\EntityComponentController::class, 'storeGene'])->name('entity-components.genes.store');
    Route::delete('/entity-components/genes/{entityComponentHasGene}', [App\Http\Controllers\EntityComponentController::class, 'destroyGene'])->name('entity-components.genes.destroy');

    // Entity Components Elementi Chimici
    Route::post('/entity-components/{entityComponent}/rules/table', [App\Http\Controllers\EntityComponentController::class, 'rulesDataTable'])->name('entity-components.rules.datatable');
    Route::get('/entity-components/{entityComponent}/rules/available', [App\Http\Controllers\EntityComponentController::class, 'getAvailableRules'])->name('entity-components.rules.available');
    Route::post('/entity-components/{entityComponent}/rules', [App\Http\Controllers\EntityComponentController::class, 'storeRule'])->name('entity-components.rules.store');
    Route::delete('/entity-components/rules/{entityComponentHasRule}', [App\Http\Controllers\EntityComponentController::class, 'destroyRule'])->name('entity-components.rules.destroy');

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

    // Entity Anchors
    Route::get('/entity-anchors', [App\Http\Controllers\EntityAnchorController::class, 'index'])->name('entity-anchors.index');
    Route::post('/entity-anchors', [App\Http\Controllers\EntityAnchorController::class, 'store'])->name('entity-anchors.store');
    Route::delete('/entity-anchors/{entityAnchor}', [App\Http\Controllers\EntityAnchorController::class, 'destroy'])->name('entity-anchors.destroy');

    // Element Anchors
    Route::get('/element-anchors', [App\Http\Controllers\ElementAnchorController::class, 'index'])->name('element-anchors.index');
    Route::post('/element-anchors', [App\Http\Controllers\ElementAnchorController::class, 'store'])->name('element-anchors.store');
    Route::delete('/element-anchors/{elementAnchor}', [App\Http\Controllers\ElementAnchorController::class, 'destroy'])->name('element-anchors.destroy');
});
