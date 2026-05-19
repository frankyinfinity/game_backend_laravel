<style>
    /* Custom styles for modern editor layout */
    .tile-picker-item, .generator-picker-item {
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        border: 1.5px solid #e2e8f0;
        background-color: #ffffff;
        border-radius: 6px;
        margin-bottom: 6px;
        padding: 8px;
        display: flex;
        align-items: center;
        width: 100%;
        user-select: none;
    }
    
    .tile-picker-item:hover, .generator-picker-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .tile-picker-item.active, .generator-picker-item.active {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
        font-weight: 600 !important;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
    }

    .tile-picker-item img {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        border: 1px solid #cbd5e1;
        object-fit: cover;
    }

    .generator-avatar {
        width: 24px;
        height: 24px;
        background-color: #f8fafc;
        border: 2px solid #0f172a;
        border-radius: 4px;
        font-weight: 900;
        font-size: 11px;
        color: #0f172a;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #pending-changes-badge {
        animation: pulse-glow 2s infinite alternate;
    }

    @keyframes pulse-glow {
        0% {
            box-shadow: 0 0 4px rgba(217, 119, 6, 0.2);
        }
        100% {
            box-shadow: 0 0 10px rgba(217, 119, 6, 0.6);
        }
    }

    /* Scrollbar styling for palette lists */
    #tab-tiles > div, #tab-generators > div {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }

    #tab-tiles > div::-webkit-scrollbar, #tab-generators > div::-webkit-scrollbar {
        width: 6px;
    }

    #tab-tiles > div::-webkit-scrollbar-track, #tab-generators > div::-webkit-scrollbar-track {
        background: #f8fafc;
    }

    #tab-tiles > div::-webkit-scrollbar-thumb, #tab-generators > div::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
</style>

<div id="map-tooltip"
    style="display:none; position:fixed; background:#1F2937; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; pointer-events:none; z-index:9999; white-space:nowrap;">
</div>

<div class="row">
    @if(!isset($isReadOnly) || !$isReadOnly)
    <!-- Sidebar -->
    <div class="col-md-4 col-lg-3 mb-3">
        <!-- Tools Card -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-header pb-2">
                <h5 class="card-title font-weight-bold m-0"><i class="fa fa-tools mr-1 text-primary"></i> Strumenti</h5>
            </div>
            <div class="card-body p-3">
                <!-- Action Buttons Grid -->
                <div class="row g-2 mb-3">
                    <div class="col-6 mb-2">
                        <button type="button" class="btn btn-primary btn-block btn-sm py-2 js-map-tool" data-tool="paint" title="Pennello (Disegna tile singole o con dimensione)">
                            <i class="fa fa-paint-brush d-block mb-1"></i> Pennello
                        </button>
                    </div>
                    <div class="col-6 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-block btn-sm py-2 js-map-tool" data-tool="fill" title="Secchiello (Riempi area contigua)">
                            <i class="fa fa-fill-drip d-block mb-1"></i> Riempi
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-primary btn-block btn-sm py-2 js-map-tool" data-tool="eraser" title="Gomma (Cancella tile)">
                            <i class="fa fa-eraser d-block mb-1"></i> Gomma
                        </button>
                    </div>
                    <div class="col-6">
                        <button type="button" class="btn btn-outline-primary btn-block btn-sm py-2 js-map-tool" data-tool="picker" title="Pipetta (Seleziona tile dalla mappa)">
                            <i class="fa fa-eye-dropper d-block mb-1"></i> Pipetta
                        </button>
                    </div>
                </div>

                <hr class="my-3">

                <!-- Brush and Undo Row -->
                <div class="form-group mb-2">
                    <label class="small font-weight-bold text-muted text-uppercase mb-1">Dimensione Pennello</label>
                    <div class="d-flex align-items-center">
                        <select id="map-brush-size" class="form-control form-control-sm custom-select" style="flex: 1;">
                            <option value="1" selected>1x1</option>
                            <option value="2">2x2</option>
                            <option value="3">3x3</option>
                            <option value="4">4x4</option>
                            <option value="5">5x5</option>
                            <option value="custom">Custom</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="map-tool-undo" title="Annulla ultima azione">
                            <i class="fa fa-undo"></i>
                        </button>
                    </div>
                </div>

                <!-- Custom Brush Sizes -->
                <div id="custom-brush-dims" class="form-row mt-2" style="display: none;">
                    <div class="col-6">
                        <input type="number" id="map-brush-size-custom-w" class="form-control form-control-sm" min="1" max="25" value="6" placeholder="W">
                    </div>
                    <div class="col-6">
                        <input type="number" id="map-brush-size-custom-h" class="form-control form-control-sm" min="1" max="25" value="6" placeholder="H">
                    </div>
                </div>
            </div>
        </div>

        <!-- Palette Card -->
        <div class="card card-outline card-success shadow-sm mb-0">
            <div class="card-header pb-2">
                <h5 class="card-title font-weight-bold m-0"><i class="fa fa-palette mr-1 text-success"></i> Tavolozza</h5>
            </div>
            <div class="card-body p-3">
                <!-- Nav Tabs for Tiles / Generators -->
                <ul class="nav nav-pills nav-justified mb-3" id="palette-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active py-1 px-2 small font-weight-bold" id="tiles-tab" data-toggle="pill" href="#tab-tiles" role="tab" aria-controls="tab-tiles" aria-selected="true">
                            <i class="fa fa-th mr-1"></i> Tile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-1 px-2 small font-weight-bold" id="generators-tab" data-toggle="pill" href="#tab-generators" role="tab" aria-controls="tab-generators" aria-selected="false">
                            <i class="fa fa-bolt mr-1"></i> Generatori
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="palette-tabs-content">
                    <!-- Tiles Tab -->
                    <div class="tab-pane show active" id="tab-tiles" role="tabpanel" aria-labelledby="tiles-tab">
                        <!-- Family Filter -->
                        <div class="form-group mb-2">
                            <label for="tile-family-filter" class="small font-weight-bold text-muted text-uppercase mb-1">Famiglia Tile</label>
                            <select id="tile-family-filter" class="form-control form-control-sm custom-select">
                                <option value="all">Tutte le famiglie</option>
                                @foreach($familyTiles as $ft)
                                    <option value="{{ $ft->id }}">{{ $ft->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tiles Search -->
                        <div class="form-group mb-3">
                            <input type="text" id="tile-search-input" class="form-control form-control-sm" placeholder="Cerca tile per nome...">
                        </div>

                        <!-- HTML Tiles List -->
                        <div class="pr-1" style="max-height: 350px; overflow-y: auto;">
                            <div id="html-tiles-container">
                                <!-- Will be populated by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Generators Tab -->
                    <div class="tab-pane" id="tab-generators" role="tabpanel" aria-labelledby="generators-tab">
                        <!-- Generators Search -->
                        <div class="form-group mb-3">
                            <input type="text" id="generator-search-input" class="form-control form-control-sm" placeholder="Cerca generatore...">
                        </div>

                        <!-- HTML Generators List -->
                        <div class="pr-1" style="max-height: 350px; overflow-y: auto;">
                            <div id="html-generators-container">
                                <!-- Will be populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Map Workspace -->
    <div class="{{ (!isset($isReadOnly) || !$isReadOnly) ? 'col-md-8 col-lg-9' : 'col-12' }}">
        <div class="card card-outline card-secondary shadow-sm mb-0">
            <div class="card-header pb-2 d-flex align-items-center justify-content-between">
                <h5 class="card-title font-weight-bold m-0"><i class="fa fa-map mr-1 text-secondary"></i> Area di Disegno</h5>
                @if(!isset($isReadOnly) || !$isReadOnly)
                <div class="card-tools ml-auto">
                    <span class="badge badge-warning shadow-sm font-weight-normal py-1 px-2" id="pending-changes-badge" style="display:none; font-size: 85%;">
                        <i class="fa fa-exclamation-triangle mr-1"></i> <span id="pending-count">0</span> modifiche
                    </span>
                </div>
                @endif
            </div>
            <div class="card-body p-0 d-flex align-items-center justify-content-center" style="background-color: #f1f5f9; min-height: 550px; overflow: auto; position: relative;">
                <div id="region-map-pixi" class="shadow-sm my-3 mx-auto" style="border: 1px solid #cbd5e1; border-radius: 4px; background: #ffffff;"></div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
<script>
    $(document).ready(function () {
        const regionConfig = {
            id: {{ $region->id }},
            width: {{ $region->width }},
            height: {{ $region->height }},
            defaultTileId: {{ $region->climate->defaultTile->id }},
            defaultTileColor: "{{ $region->climate->defaultTile->color }}",
            originalImage: "{{ $region->original_image }}",
            tileSize: {{ \App\Helper\Helper::TILE_SIZE }},
            map: {!! json_encode($map) !!},
            tiles: {!! json_encode($tiles->map(function ($tile) {
                return [
                    'id' => $tile->id,
                    'family_tile_id' => $tile->family_tile_id,
                    'name' => $tile->name,
                    'color' => $tile->color
                ];
            })->values()) !!},
            generators: @json($generatorsData),
            updateTilesBatchUrl: "{{ route('regions.tiles-batch') }}",
            csrfToken: $('meta[name="csrf-token"]').attr('content'),
            isReadOnly: {{ isset($isReadOnly) && $isReadOnly ? 'true' : 'false' }}
        };
        const mapItems = Array.isArray(regionConfig.map)
            ? regionConfig.map
            : (regionConfig.map && typeof regionConfig.map === 'object' ? Object.values(regionConfig.map) : []);

        let tile_selected_id = String(regionConfig.defaultTileId);
        let tile_selected_color = regionConfig.defaultTileColor;
        let tile_selected_generator_id = null;
        let tile_selected_generator_symbol = '';
        let activeTool = 'paint';
        let brushWidth = 1;
        let brushHeight = 1;
        let isPainting = false;
        let dragChangedKeys = {};
        let isSaving = false;
        let hoveredCell = null;
        const undoStack = [];
        const maxUndoSteps = 25;
        const batchChunkSize = 300;
        const tileGraphics = {};
        const tileStates = {};
        const initialTileStates = {};
        const pendingChanges = {};
        const tilePickerItems = {};
        const tileById = {};
        const generatorById = {};
        const hexToNumber = (hexColor) => parseInt(hexColor.replace('#', '0x'), 16);
        const getKey = (i, j) => i + '_' + j;
        const defaultState = {
            id: String(regionConfig.defaultTileId),
            color: regionConfig.defaultTileColor,
            generatorId: null,
            generatorSymbol: ''
        };

        regionConfig.tiles.forEach(function (tile) {
            tileById[String(tile.id)] = tile;
        });

        regionConfig.generators.forEach(function (gen) {
            generatorById[String(gen.id)] = gen;
        });

        for (let i = 0; i < regionConfig.height; i++) {
            for (let j = 0; j < regionConfig.width; j++) {
                tileStates[getKey(i, j)] = { id: defaultState.id, color: defaultState.color, generatorId: null, generatorSymbol: '' };
            }
        }

        mapItems.forEach(item => {
            if (item && item.tile && item.i !== undefined && item.j !== undefined) {
                tileStates[getKey(item.i, item.j)] = {
                    id: String(item.tile.id),
                    color: item.tile.color,
                    generatorId: item.generator ? item.generator.id : null,
                    generatorSymbol: item.generator ? (item.generator.symbol || '') : ''
                };
            }
        });

        Object.keys(tileStates).forEach(function (key) {
            initialTileStates[key] = {
                id: tileStates[key].id,
                color: tileStates[key].color,
                generatorId: tileStates[key].generatorId,
                generatorSymbol: tileStates[key].generatorSymbol
            };
        });

        const mapApp = new PIXI.Application({
            width: regionConfig.width * regionConfig.tileSize,
            height: regionConfig.height * regionConfig.tileSize,
            antialias: false,
            backgroundAlpha: 0
        });
        document.getElementById('region-map-pixi').appendChild(mapApp.view);

        const mapBackgroundLayer = new PIXI.Container();
        mapApp.stage.addChild(mapBackgroundLayer);

        if (regionConfig.originalImage) {
            const bgSprite = PIXI.Sprite.from('/storage/map_tiles/' + regionConfig.id + '/' + regionConfig.originalImage);
            bgSprite.width = regionConfig.width * regionConfig.tileSize;
            bgSprite.height = regionConfig.height * regionConfig.tileSize;
            mapBackgroundLayer.addChild(bgSprite);
        }

        const mapTilesLayer = new PIXI.Container();
        mapApp.stage.addChild(mapTilesLayer);

        const mapGridLayer = new PIXI.Container();
        mapGridLayer.eventMode = 'none';
        mapApp.stage.addChild(mapGridLayer);

        const gridGraphics = new PIXI.Graphics();
        gridGraphics.lineStyle(1, 0xFFFFFF, 0.7);
        for(let i = 0; i <= regionConfig.height; i++) {
            gridGraphics.moveTo(0, i * regionConfig.tileSize);
            gridGraphics.lineTo(regionConfig.width * regionConfig.tileSize, i * regionConfig.tileSize);
        }
        for(let j = 0; j <= regionConfig.width; j++) {
            gridGraphics.moveTo(j * regionConfig.tileSize, 0);
            gridGraphics.lineTo(j * regionConfig.tileSize, regionConfig.height * regionConfig.tileSize);
        }
        mapGridLayer.addChild(gridGraphics);

        const mapPreviewLayer = new PIXI.Container();
        mapApp.stage.addChild(mapPreviewLayer);

        function setActiveTool(toolName) {
            activeTool = toolName;
            $('.js-map-tool').each(function() {
                const tool = $(this).data('tool');
                if (tool === toolName) {
                    $(this).removeClass('btn-outline-primary btn-outline-danger btn-outline-warning btn-primary').addClass('btn-primary');
                } else {
                    $(this).removeClass('btn-primary');
                    if (tool === 'eraser') {
                        $(this).addClass('btn-outline-danger');
                    } else if (tool === 'picker') {
                        $(this).addClass('btn-outline-warning');
                    } else {
                        $(this).addClass('btn-outline-primary');
                    }
                }
            });
            refreshPreviewFromHover();
        }

        function showTooltipMap(event, text) {
            const tip = document.getElementById('map-tooltip');
            tip.textContent = text;
            tip.style.display = 'block';
            const rect = mapApp.view.getBoundingClientRect();
            tip.style.left = (rect.left + event.global.x + 10) + 'px';
            tip.style.top = (rect.top + event.global.y - 30) + 'px';
        }

        function hideTooltip() {
            document.getElementById('map-tooltip').style.display = 'none';
        }

        function setSelectedTile(tileId, tileColor, generatorId, generatorSymbol) {
            tile_selected_id = String(tileId);
            tile_selected_color = tileColor;
            tile_selected_generator_id = generatorId || null;
            tile_selected_generator_symbol = generatorSymbol || '';
            updatePickerSelection();
            refreshPreviewFromHover();
        }

        function updateDirtyState(key) {
            const initial = initialTileStates[key];
            const current = tileStates[key];
            if (!initial || !current) {
                return;
            }

            if (initial.id === current.id && initial.color === current.color && initial.generatorId === current.generatorId) {
                delete pendingChanges[key];
                return;
            }

            pendingChanges[key] = {
                i: parseInt(key.split('_')[0], 10),
                j: parseInt(key.split('_')[1], 10),
                tile_id: current.id,
                generator_id: current.generatorId
            };
        }

        function getPendingPayload() {
            return Object.values(pendingChanges);
        }

        function refreshSaveButtonState() {
            const saveButton = $('#map-save-all');
            if (!saveButton.length) {
                return;
            }

            const pending = getPendingPayload();
            const hasPending = pending.length > 0;
            saveButton.prop('disabled', isSaving || !hasPending);

            const badge = $('#pending-changes-badge');
            if (badge.length) {
                if (hasPending) {
                    $('#pending-count').text(pending.length);
                    badge.show();
                } else {
                    badge.hide();
                }
            }
        }

        function drawMapTile(i, j, state) {
            const key = getKey(i, j);
            const previousGraphic = tileGraphics[key];
            if (previousGraphic) {
                mapTilesLayer.removeChild(previousGraphic);
                previousGraphic.destroy();
            }

            const graphic = new PIXI.Container();
            graphic.x = j * regionConfig.tileSize;
            graphic.y = i * regionConfig.tileSize;
            graphic.hitArea = new PIXI.Rectangle(0, 0, regionConfig.tileSize, regionConfig.tileSize);

            if (pendingChanges[key] || String(state.id) !== String(regionConfig.defaultTileId)) {
                 const sprite = PIXI.Sprite.from('/storage/tiles/' + state.id + '.png');
                 sprite.width = regionConfig.tileSize;
                 sprite.height = regionConfig.tileSize;
                 graphic.addChild(sprite);
            }

            if (state.generatorId && state.generatorSymbol) {
                const symBg = new PIXI.Graphics();
                symBg.lineStyle(2, 0x000000, 1);
                symBg.drawRect(1, 1, regionConfig.tileSize - 2, regionConfig.tileSize - 2);
                graphic.addChild(symBg);

                const symbolText = new PIXI.Text(state.generatorSymbol, {
                    fontFamily: 'Arial',
                    fontSize: 14,
                    fontWeight: 'bold',
                    fill: 0x000000,
                    align: 'center'
                });
                symbolText.anchor.set(0.5);
                symbolText.x = regionConfig.tileSize / 2;
                symbolText.y = regionConfig.tileSize / 2;
                graphic.addChild(symbolText);
            }

            graphic.eventMode = 'static';
            graphic.cursor = 'pointer';
            graphic.on('pointerdown', function () {
                isPainting = true;
                dragChangedKeys = {};
                hoveredCell = { i: i, j: j };
                handleTileInteraction(i, j, true);
                refreshPreviewFromHover();
            });
            graphic.on('pointerover', function (event) {
                hoveredCell = { i: i, j: j };
                if (isPainting && activeTool === 'paint') {
                    handleTileInteraction(i, j, true);
                }
                const tileObj = tileById[String(state.id)];
                let tipText = tileObj ? tileObj.name : 'Tile';
                if (state.generatorId && state.generatorSymbol) {
                    const genObj = generatorById[String(state.generatorId)];
                    tipText += ' - ' + (genObj ? genObj.name + ' (' + genObj.symbol + ')' : state.generatorSymbol);
                }
                showTooltipMap(event, tipText);
                refreshPreviewFromHover();
            });
            graphic.on('pointerout', function () {
                hideTooltip();
                if (hoveredCell && hoveredCell.i === i && hoveredCell.j === j) {
                    hoveredCell = null;
                    clearPreview();
                }
            });
            graphic.on('pointertap', function () {
                if (activeTool !== 'paint') {
                    handleTileInteraction(i, j, false);
                }
            });

            tileGraphics[key] = graphic;
            mapTilesLayer.addChild(graphic);
        }

        function clearPreview() {
            mapPreviewLayer.removeChildren().forEach(function (child) {
                child.destroy();
            });
        }

        function getFillPreviewCells(startI, startJ) {
            const startKey = getKey(startI, startJ);
            const startState = tileStates[startKey];
            if (!startState || startState.id === tile_selected_id) {
                return [];
            }

            const cells = [];
            const queue = [{ i: startI, j: startJ }];
            let queueIndex = 0;
            const visited = {};
            visited[startKey] = true;

            while (queueIndex < queue.length) {
                const node = queue[queueIndex];
                queueIndex += 1;
                const key = getKey(node.i, node.j);
                const state = tileStates[key];
                if (!state || state.id !== startState.id) {
                    continue;
                }

                cells.push({ i: node.i, j: node.j });

                const neighbors = [
                    { i: node.i - 1, j: node.j },
                    { i: node.i + 1, j: node.j },
                    { i: node.i, j: node.j - 1 },
                    { i: node.i, j: node.j + 1 }
                ];

                neighbors.forEach(function (next) {
                    if (next.i < 0 || next.j < 0 || next.i >= regionConfig.height || next.j >= regionConfig.width) {
                        return;
                    }
                    const nextKey = getKey(next.i, next.j);
                    if (visited[nextKey]) {
                        return;
                    }
                    visited[nextKey] = true;
                    queue.push(next);
                });
            }

            return cells;
        }

        function getPreviewCells(i, j) {
            if (activeTool === 'paint' || activeTool === 'eraser') {
                return getBrushCells(i, j);
            }
            if (activeTool === 'fill') {
                return getFillPreviewCells(i, j);
            }
            if (activeTool === 'picker') {
                return [{ i: i, j: j }];
            }
            return [];
        }

        function drawPreview(i, j) {
            clearPreview();
            const cells = getPreviewCells(i, j);
            if (!cells.length) {
                return;
            }

            const previewColor = activeTool === 'eraser' ? defaultState.color : tile_selected_color;
            const previewHex = hexToNumber(previewColor);

            cells.forEach(function (cell) {
                const g = new PIXI.Graphics();
                if (activeTool === 'picker') {
                    g.lineStyle(2, 0xF59E0B, 1);
                    g.drawRect(
                        cell.j * regionConfig.tileSize + 1,
                        cell.i * regionConfig.tileSize + 1,
                        regionConfig.tileSize - 2,
                        regionConfig.tileSize - 2
                    );
                } else {
                    g.beginFill(previewHex, 0.35);
                    g.lineStyle(1, 0xFFFFFF, 0.5);
                    g.drawRect(
                        cell.j * regionConfig.tileSize,
                        cell.i * regionConfig.tileSize,
                        regionConfig.tileSize,
                        regionConfig.tileSize
                    );
                    g.endFill();
                }
                mapPreviewLayer.addChild(g);
            });
        }

        function refreshPreviewFromHover() {
            if (hoveredCell) {
                drawPreview(hoveredCell.i, hoveredCell.j);
            } else {
                clearPreview();
            }
        }

        function updatePickerSelection() {
            // Remove active classes
            $('.tile-picker-item').removeClass('active');
            $('.generator-picker-item').removeClass('active');
            
            // Add active to selected items
            if (tile_selected_generator_id) {
                $(`.generator-picker-item[data-generator-id="${tile_selected_generator_id}"]`).addClass('active');
                // Switch to generators tab in the pills if not active
                $('#generators-tab').tab('show');
            } else {
                $(`.tile-picker-item[data-tile-id="${tile_selected_id}"]`).addClass('active');
                // Switch to tiles tab in the pills if not active
                $('#tiles-tab').tab('show');
            }
        }

        function buildPicker() {
            const familyFilter = $('#tile-family-filter').val() || 'all';
            const tileSearch = ($('#tile-search-input').val() || '').toLowerCase();
            const genSearch = ($('#generator-search-input').val() || '').toLowerCase();

            // Render Tiles
            const filteredTiles = regionConfig.tiles.filter(function (tile) {
                if (familyFilter !== 'all' && String(tile.family_tile_id) !== String(familyFilter)) {
                    return false;
                }
                if (tileSearch && !tile.name.toLowerCase().includes(tileSearch)) {
                    return false;
                }
                return true;
            });

            let tilesHtml = '';
            if (filteredTiles.length === 0) {
                tilesHtml = '<div class="text-center text-muted py-3 small w-100">Nessuna tile trovata</div>';
            } else {
                tilesHtml = '<div class="row w-100 m-0">';
                filteredTiles.forEach(function (tile) {
                    const isSelected = (String(tile.id) === tile_selected_id && !tile_selected_generator_id);
                    tilesHtml += `
                        <div class="col-6 mb-2 px-1">
                            <div class="tile-picker-item ${isSelected ? 'active' : ''}" data-tile-id="${tile.id}" data-tile-color="${tile.color}" title="${tile.name}">
                                <img src="/storage/tiles/${tile.id}.png" class="mr-2">
                                <span class="text-truncate small" style="max-width: calc(100% - 32px); font-size: 11px;">${tile.name}</span>
                            </div>
                        </div>
                    `;
                });
                tilesHtml += '</div>';
            }
            $('#html-tiles-container').html(tilesHtml);

            // Render Generators
            const filteredGenerators = regionConfig.generators.filter(function (gen) {
                if (genSearch && !gen.name.toLowerCase().includes(genSearch) && !gen.symbol.toLowerCase().includes(genSearch)) {
                    return false;
                }
                return true;
            });

            let gensHtml = '';
            if (filteredGenerators.length === 0) {
                gensHtml = '<div class="text-center text-muted py-3 small w-100">Nessun generatore trovato</div>';
            } else {
                gensHtml = '<div class="row w-100 m-0">';
                filteredGenerators.forEach(function (gen) {
                    const isSelected = (String(gen.id) === String(tile_selected_generator_id));
                    gensHtml += `
                        <div class="col-6 mb-2 px-1">
                            <div class="generator-picker-item ${isSelected ? 'active' : ''}" data-generator-id="${gen.id}" data-generator-symbol="${gen.symbol}" title="${gen.name} (${gen.symbol})">
                                <div class="generator-avatar mr-2">${gen.symbol}</div>
                                <span class="text-truncate small" style="max-width: calc(100% - 32px); font-size: 11px;">${gen.name}</span>
                            </div>
                        </div>
                    `;
                });
                gensHtml += '</div>';
            }
            $('#html-generators-container').html(gensHtml);
        }

        function saveTilesBatch(tiles, onError, onComplete) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': regionConfig.csrfToken
                }
            });

            $.ajax({
                url: regionConfig.updateTilesBatchUrl,
                type: 'POST',
                data: {
                    region_id: regionConfig.id,
                    tiles: tiles
                },
                success: function (result) {
                    if (!result.success) {
                        if (typeof onError === 'function') {
                            onError();
                        }
                        let msg = 'Si e verificato un errore.';
                        if (result.msg != null) {
                            msg = result.msg;
                        }
                        $.notify({ title: 'Ops!', message: msg }, { type: 'warning' });
                        if (typeof onComplete === 'function') {
                            onComplete(false);
                        }
                        return;
                    }
                    if (typeof onComplete === 'function') {
                        onComplete(true);
                    }
                },
                error: function () {
                    if (typeof onError === 'function') {
                        onError();
                    }
                    Swal.fire({
                        title: 'Ops!',
                        text: 'Si e verificato un errore imprevisto.',
                        type: 'danger',
                        showCancelButton: false,
                        buttonsStyling: false,
                        confirmButtonClass: 'btn btn-info',
                        confirmButtonText: 'Ho Capito!'
                    });
                    if (typeof onComplete === 'function') {
                        onComplete(false);
                    }
                }
            });
        }

        function saveTilesInChunks(tilesPayload, onError, onComplete) {
            if (!Array.isArray(tilesPayload) || !tilesPayload.length) {
                if (typeof onComplete === 'function') {
                    onComplete();
                }
                return;
            }

            const chunks = [];
            for (let i = 0; i < tilesPayload.length; i += batchChunkSize) {
                chunks.push(tilesPayload.slice(i, i + batchChunkSize));
            }

            let chunkIndex = 0;
            const runNext = function (ok) {
                if (ok === false) {
                    return;
                }

                if (chunkIndex >= chunks.length) {
                    if (typeof onComplete === 'function') {
                        onComplete();
                    }
                    return;
                }

                const chunk = chunks[chunkIndex];
                chunkIndex += 1;
                saveTilesBatch(chunk, onError, runNext);
            };

            runNext(true);
        }

        function applyChange(i, j, newState, changeSet) {
            const key = getKey(i, j);
            const current = tileStates[key];
            if (!current || (current.id === newState.id && current.color === newState.color && current.generatorId === (newState.generatorId || null))) {
                return;
            }

            if (!changeSet[key]) {
                changeSet[key] = {
                    i: i,
                    j: j,
                    before: { id: current.id, color: current.color, generatorId: current.generatorId, generatorSymbol: current.generatorSymbol },
                    after: { id: newState.id, color: newState.color, generatorId: newState.generatorId || null, generatorSymbol: newState.generatorSymbol || '' }
                };
            } else {
                changeSet[key].after = { id: newState.id, color: newState.color, generatorId: newState.generatorId || null, generatorSymbol: newState.generatorSymbol || '' };
            }

            tileStates[key] = { id: newState.id, color: newState.color, generatorId: newState.generatorId || null, generatorSymbol: newState.generatorSymbol || '' };
            updateDirtyState(key);
            drawMapTile(i, j, tileStates[key]);
        }

        function getBrushCells(centerI, centerJ) {
            const cells = [];
            const halfH = Math.floor(brushHeight / 2);
            const halfW = Math.floor(brushWidth / 2);
            const startI = centerI - halfH;
            const startJ = centerJ - halfW;

            for (let i = startI; i < startI + brushHeight; i++) {
                for (let j = startJ; j < startJ + brushWidth; j++) {
                    if (i < 0 || j < 0 || i >= regionConfig.height || j >= regionConfig.width) {
                        continue;
                    }
                    cells.push({ i: i, j: j });
                }
            }
            return cells;
        }

        function pushUndo(changeSet) {
            const keys = Object.keys(changeSet);
            if (!keys.length) {
                return;
            }
            undoStack.push(keys.map(function (k) { return changeSet[k]; }));
            if (undoStack.length > maxUndoSteps) {
                undoStack.shift();
            }
        }

        function paintAt(i, j, changeSet) {
            const cells = getBrushCells(i, j);
            cells.forEach(function (cell) {
                const key = getKey(cell.i, cell.j);
                if (dragChangedKeys[key]) {
                    return;
                }
                dragChangedKeys[key] = true;

                const current = tileStates[key];
                if (tile_selected_generator_id && current && current.id === tile_selected_id) {
                    applyChange(cell.i, cell.j, {
                        id: current.id,
                        color: current.color,
                        generatorId: tile_selected_generator_id,
                        generatorSymbol: tile_selected_generator_symbol
                    }, changeSet);
                } else {
                    applyChange(cell.i, cell.j, {
                        id: tile_selected_id,
                        color: tile_selected_color,
                        generatorId: tile_selected_generator_id,
                        generatorSymbol: tile_selected_generator_symbol
                    }, changeSet);
                }
            });
        }

        function fillAt(startI, startJ, changeSet) {
            const startKey = getKey(startI, startJ);
            const startState = tileStates[startKey];
            if (!startState) {
                return;
            }
            if (startState.id === tile_selected_id && startState.generatorId === tile_selected_generator_id) {
                return;
            }

            const queue = [{ i: startI, j: startJ }];
            let queueIndex = 0;
            const visited = {};
            visited[startKey] = true;

            while (queueIndex < queue.length) {
                const node = queue[queueIndex];
                queueIndex += 1;
                const key = getKey(node.i, node.j);
                const state = tileStates[key];
                if (!state || state.id !== startState.id) {
                    continue;
                }

                applyChange(node.i, node.j, {
                    id: tile_selected_id,
                    color: tile_selected_color,
                    generatorId: tile_selected_generator_id,
                    generatorSymbol: tile_selected_generator_symbol
                }, changeSet);

                const neighbors = [
                    { i: node.i - 1, j: node.j },
                    { i: node.i + 1, j: node.j },
                    { i: node.i, j: node.j - 1 },
                    { i: node.i, j: node.j + 1 }
                ];

                neighbors.forEach(function (next) {
                    if (next.i < 0 || next.j < 0 || next.i >= regionConfig.height || next.j >= regionConfig.width) {
                        return;
                    }
                    const nextKey = getKey(next.i, next.j);
                    if (visited[nextKey]) {
                        return;
                    }
                    visited[nextKey] = true;
                    queue.push(next);
                });
            }
        }

        function eraseAt(i, j, changeSet) {
            const cells = getBrushCells(i, j);
            cells.forEach(function (cell) {
                applyChange(cell.i, cell.j, { id: defaultState.id, color: defaultState.color, generatorId: null, generatorSymbol: '' }, changeSet);
            });
        }

        function pickAt(i, j) {
            const state = tileStates[getKey(i, j)];
            if (!state) {
                return;
            }

            const tile = tileById[state.id];
            if (!tile) {
                return;
            }
            setSelectedTile(tile.id, tile.color, state.generatorId, state.generatorSymbol);
            setActiveTool('paint');
        }

        function handleTileInteraction(i, j, fromDrag) {
            if (regionConfig.isReadOnly) {
                return;
            }
            
            const changeSet = {};
            if (activeTool === 'paint') {
                paintAt(i, j, changeSet);
                if (!fromDrag) {
                    dragChangedKeys = {};
                }
            } else if (activeTool === 'fill') {
                fillAt(i, j, changeSet);
            } else if (activeTool === 'eraser') {
                eraseAt(i, j, changeSet);
            } else if (activeTool === 'picker') {
                pickAt(i, j);
            }

            if (activeTool !== 'picker') {
                pushUndo(changeSet);
                refreshSaveButtonState();
            }
        }

        function undoLast() {
            const last = undoStack.pop();
            if (!last || !last.length) {
                return;
            }

            last.forEach(function (item) {
                const key = getKey(item.i, item.j);
                tileStates[key] = { id: item.before.id, color: item.before.color, generatorId: item.before.generatorId || null, generatorSymbol: item.before.generatorSymbol || '' };
                updateDirtyState(key);
                drawMapTile(item.i, item.j, tileStates[key]);
            });
            refreshSaveButtonState();
        }

        function saveAllChanges() {
            if (isSaving) {
                return;
            }

            const payload = getPendingPayload().map(function (item) {
                return {
                    tile_i: item.i,
                    tile_j: item.j,
                    tile_id: item.tile_id,
                    generator_id: item.generator_id || null
                };
            });

            if (!payload.length) {
                $.notify({ title: 'Info', message: 'Nessuna modifica da salvare.' }, { type: 'info' });
                return;
            }

            isSaving = true;
            const saveButton = $('#map-save-all');
            if (saveButton.length) {
                saveButton.html('<i class="fa fa-spinner fa-spin"></i> Salvataggio...');
            }
            refreshSaveButtonState();

            saveTilesInChunks(
                payload,
                function () {
                    isSaving = false;
                    if (saveButton.length) {
                        saveButton.html('<i class="fa fa-save"></i> Salva');
                    }
                    refreshSaveButtonState();
                },
                function () {
                    Object.keys(pendingChanges).forEach(function (key) {
                        initialTileStates[key] = {
                            id: tileStates[key].id,
                            color: tileStates[key].color,
                            generatorId: tileStates[key].generatorId,
                            generatorSymbol: tileStates[key].generatorSymbol
                        };
                        delete pendingChanges[key];
                    });
                    undoStack.length = 0;
                    isSaving = false;
                    if (saveButton.length) {
                        saveButton.html('<i class="fa fa-save"></i> Salva');
                    }
                    refreshSaveButtonState();
                    $.notify({ title: 'OK', message: 'Mappa salvata correttamente.' }, { type: 'success' });
                }
            );
        }

        for (let i = 0; i < regionConfig.height; i++) {
            for (let j = 0; j < regionConfig.width; j++) {
                drawMapTile(i, j, tileStates[getKey(i, j)]);
            }
        }

        buildPicker();
        setActiveTool('paint');
        refreshSaveButtonState();

        $(document).on('pointerup mouseup', function () {
            isPainting = false;
            dragChangedKeys = {};
            refreshPreviewFromHover();
        });

        $(document).on('click', '.js-map-tool', function () {
            setActiveTool($(this).data('tool'));
        });

        $(document).on('click', '.tile-picker-item', function () {
            const tileId = $(this).data('tile-id');
            const tileColor = $(this).data('tile-color');
            setSelectedTile(tileId, tileColor, null, '');
            setActiveTool('paint');
        });

        $(document).on('click', '.generator-picker-item', function () {
            const genId = $(this).data('generator-id');
            const genSymbol = $(this).data('generator-symbol');
            tile_selected_generator_id = genId;
            tile_selected_generator_symbol = genSymbol;
            updatePickerSelection();
            refreshPreviewFromHover();
            setActiveTool('paint');
        });

        $(document).on('change', '#tile-family-filter', function () {
            buildPicker();
        });

        $(document).on('input', '#tile-search-input', function () {
            buildPicker();
        });

        $(document).on('input', '#generator-search-input', function () {
            buildPicker();
        });

        $(document).on('change', '#map-brush-size', function () {
            const value = String($(this).val());
            if (value === 'custom') {
                $('#custom-brush-dims').show();
                const customW = parseInt($('#map-brush-size-custom-w').val(), 10);
                const customH = parseInt($('#map-brush-size-custom-h').val(), 10);
                brushWidth = Number.isInteger(customW) ? Math.max(1, Math.min(25, customW)) : 1;
                brushHeight = Number.isInteger(customH) ? Math.max(1, Math.min(25, customH)) : 1;
                refreshPreviewFromHover();
                return;
            }

            $('#custom-brush-dims').hide();
            const size = parseInt(value, 10);
            const safeSize = Number.isInteger(size) ? Math.max(1, Math.min(25, size)) : 1;
            brushWidth = safeSize;
            brushHeight = safeSize;
            refreshPreviewFromHover();
        });

        $(document).on('input change', '#map-brush-size-custom-w, #map-brush-size-custom-h', function () {
            if (String($('#map-brush-size').val()) !== 'custom') {
                return;
            }
            const customW = parseInt($('#map-brush-size-custom-w').val(), 10);
            const customH = parseInt($('#map-brush-size-custom-h').val(), 10);
            brushWidth = Number.isInteger(customW) ? Math.max(1, Math.min(25, customW)) : 1;
            brushHeight = Number.isInteger(customH) ? Math.max(1, Math.min(25, customH)) : 1;
            refreshPreviewFromHover();
        });

        $(document).on('click', '#map-tool-undo', function () {
            undoLast();
        });

        $(document).on('click', '#map-save-all', function () {
            saveAllChanges();
        });

        window.addEventListener('beforeunload', function (event) {
            if (getPendingPayload().length > 0 && !isSaving) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    });
</script>
@stop