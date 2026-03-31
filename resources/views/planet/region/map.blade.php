<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Mappa</h5>
    </div>
    <div class="card-body" style="overflow: auto;">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="d-flex flex-wrap" style="gap: 8px;">
                    <button type="button" class="btn btn-primary btn-sm js-map-tool" data-tool="paint">
                        <i class="fa fa-paint-brush"></i> Pennello
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm js-map-tool" data-tool="fill">
                        <i class="fa fa-fill-drip"></i> Riempi
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm js-map-tool" data-tool="eraser">
                        <i class="fa fa-eraser"></i> Gomma
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm js-map-tool" data-tool="picker">
                        <i class="fa fa-eye-dropper"></i> Pipetta
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="map-tool-undo">
                        <i class="fa fa-undo"></i> Annulla
                    </button>
                    <div class="d-flex align-items-center ml-2">
                        <label for="map-brush-size" class="mb-0 mr-2">Dimensione</label>
                        <select id="map-brush-size" class="form-control form-control-sm" style="width: 90px;">
                            <option value="1" selected>1x1</option>
                            <option value="2">2x2</option>
                            <option value="3">3x3</option>
                            <option value="4">4x4</option>
                            <option value="5">5x5</option>
                            <option value="custom">Custom</option>
                        </select>
                        <input type="number" id="map-brush-size-custom-w" class="form-control form-control-sm ml-2" min="1" max="25" value="6" style="width: 70px; display: none;">
                        <span id="map-brush-size-custom-sep" class="ml-1 mr-1" style="display: none;">x</span>
                        <input type="number" id="map-brush-size-custom-h" class="form-control form-control-sm" min="1" max="25" value="6" style="width: 70px; display: none;">
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3" style="overflow: auto">
                <div id="region-tile-picker-pixi" style="display: inline-block; border: 1px solid #d9d9d9; border-radius: 4px;"></div>
            </div>
            <div class="col-12" style="overflow: auto">
                <div id="region-map-pixi" style="display: inline-block; border: 1px solid #d9d9d9; border-radius: 4px;"></div>
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
                tileSize: 35,
                map: {!! json_encode($map) !!},
                tiles: @json($tiles->map(function ($tile) {
                    return [
                        'id' => $tile->id,
                        'name' => $tile->name,
                        'color' => $tile->color
                    ];
                })->values()),
                generators: @json($generatorsData),
                updateTilesBatchUrl: "{{ route('regions.tiles-batch') }}",
                csrfToken: $('meta[name="csrf-token"]').attr('content')
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
            const mapTilesLayer = new PIXI.Container();
            const mapPreviewLayer = new PIXI.Container();
            mapApp.stage.addChild(mapTilesLayer);
            mapApp.stage.addChild(mapPreviewLayer);

            const pickerCols = 3;
            const pickerPadding = 10;
            const pickerGap = 8;
            const pickerButtonW = 180;
            const pickerButtonH = 38;
            const pickerSectionGap = 30;
            const pickerTitleH = 28;
            const pickerSectionWidth = pickerPadding + pickerCols * pickerButtonW + (pickerCols - 1) * pickerGap;
            const pickerWidth = pickerSectionWidth * 2 + pickerSectionGap;
            const pickerHeight = 800;

            const pickerApp = new PIXI.Application({
                width: pickerWidth,
                height: pickerHeight,
                antialias: true,
                backgroundColor: 0xF8FAFC
            });
            document.getElementById('region-tile-picker-pixi').appendChild(pickerApp.view);

            function setActiveTool(toolName) {
                activeTool = toolName;
                $('.js-map-tool').removeClass('btn-primary').addClass('btn-outline-primary');
                $('.js-map-tool[data-tool="' + toolName + '"]').removeClass('btn-outline-primary').addClass('btn-primary');
                refreshPreviewFromHover();
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

                const hasPending = getPendingPayload().length > 0;
                saveButton.prop('disabled', isSaving || !hasPending);
            }

            function drawMapTile(i, j, state) {
                const key = getKey(i, j);
                const previousGraphic = tileGraphics[key];
                if (previousGraphic) {
                    mapTilesLayer.removeChild(previousGraphic);
                    previousGraphic.destroy();
                }

                const graphic = new PIXI.Graphics();
                graphic.beginFill(hexToNumber(state.color));
                graphic.lineStyle(1, 0xFFFFFF, 0.5);
                graphic.drawRect(
                    j * regionConfig.tileSize,
                    i * regionConfig.tileSize,
                    regionConfig.tileSize,
                    regionConfig.tileSize
                );
                graphic.endFill();

                if (state.generatorId && state.generatorSymbol) {
                    graphic.lineStyle(2, 0x000000, 1);
                    graphic.drawRect(
                        j * regionConfig.tileSize + 1,
                        i * regionConfig.tileSize + 1,
                        regionConfig.tileSize - 2,
                        regionConfig.tileSize - 2
                    );

                    const symbolText = new PIXI.Text(state.generatorSymbol, {
                        fontFamily: 'Arial',
                        fontSize: 14,
                        fontWeight: 'bold',
                        fill: 0x000000,
                        align: 'center'
                    });
                    symbolText.anchor.set(0.5);
                    symbolText.x = j * regionConfig.tileSize + regionConfig.tileSize / 2;
                    symbolText.y = i * regionConfig.tileSize + regionConfig.tileSize / 2;
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
                graphic.on('pointerover', function () {
                    hoveredCell = { i: i, j: j };
                    if (isPainting && activeTool === 'paint') {
                        handleTileInteraction(i, j, true);
                    }
                    refreshPreviewFromHover();
                });
                graphic.on('pointerout', function () {
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

            function drawPickerItem(item, isSelected) {
                item.background.clear();
                item.background.lineStyle(1, isSelected ? 0x2563EB : 0xCBD5E1, 1);
                item.background.beginFill(isSelected ? 0x2563EB : 0xFFFFFF);
                item.background.drawRoundedRect(0, 0, pickerButtonW, pickerButtonH, 8);
                item.background.endFill();
                item.label.style.fill = isSelected ? 0xFFFFFF : 0x1F2937;
            }

            function updatePickerSelection() {
                const selectedKey = tile_selected_generator_id ? ('gen_' + tile_selected_generator_id) : tile_selected_id;
                Object.keys(tilePickerItems).forEach(function (key) {
                    drawPickerItem(tilePickerItems[key], key === selectedKey);
                });
            }

            function buildPicker() {
                const labelMaxW = pickerButtonW - 46;

                function truncateText(text, maxWidth, style) {
                    const measure = new PIXI.Text(text, style);
                    if (measure.width <= maxWidth) {
                        measure.destroy();
                        return text;
                    }
                    let truncated = text;
                    while (truncated.length > 0) {
                        truncated = truncated.slice(0, -1);
                        measure.text = truncated + '...';
                        if (measure.width <= maxWidth) {
                            measure.destroy();
                            return truncated + '...';
                        }
                    }
                    measure.destroy();
                    return '...';
                }

                const tilesTitle = new PIXI.Text('Tile', {
                    fontFamily: 'Arial',
                    fontSize: 14,
                    fontWeight: 'bold',
                    fill: 0x374151
                });
                tilesTitle.x = pickerPadding;
                tilesTitle.y = pickerPadding;
                pickerApp.stage.addChild(tilesTitle);

                const tilesStartY = pickerPadding + pickerTitleH;

                regionConfig.tiles.forEach(function (tile, index) {
                    const row = Math.floor(index / pickerCols);
                    const col = index % pickerCols;
                    const x = pickerPadding + col * (pickerButtonW + pickerGap);
                    const y = tilesStartY + row * (pickerButtonH + pickerGap);

                    const itemContainer = new PIXI.Container();
                    itemContainer.x = x;
                    itemContainer.y = y;
                    itemContainer.eventMode = 'static';
                    itemContainer.cursor = 'pointer';

                    const background = new PIXI.Graphics();
                    itemContainer.addChild(background);

                    const swatch = new PIXI.Graphics();
                    swatch.beginFill(hexToNumber(tile.color));
                    swatch.drawRoundedRect(10, 10, 18, 18, 4);
                    swatch.endFill();
                    itemContainer.addChild(swatch);

                    const labelStyle = { fontFamily: 'Arial', fontSize: 13, fill: 0x1F2937 };
                    const labelText = truncateText(tile.name, labelMaxW, labelStyle);
                    const label = new PIXI.Text(labelText, labelStyle);
                    label.x = 36;
                    label.y = 10;
                    itemContainer.addChild(label);

                    itemContainer.on('pointertap', function () {
                        setSelectedTile(tile.id, tile.color, null, '');
                        setActiveTool('paint');
                    });

                    const pickerItem = {
                        background: background,
                        label: label,
                        itemId: String(tile.id)
                    };
                    tilePickerItems[String(tile.id)] = pickerItem;
                    drawPickerItem(pickerItem, String(tile.id) === tile_selected_id && !tile_selected_generator_id);
                    pickerApp.stage.addChild(itemContainer);
                });

                const tilesEndY = tilesStartY + Math.ceil(regionConfig.tiles.length / pickerCols) * (pickerButtonH + pickerGap);

                let genEndY = tilesStartY;

                if (regionConfig.generators.length > 0) {
                    const genOffsetX = pickerSectionWidth + pickerSectionGap;

                    const genTitle = new PIXI.Text('Generatori', {
                        fontFamily: 'Arial',
                        fontSize: 14,
                        fontWeight: 'bold',
                        fill: 0x374151
                    });
                    genTitle.x = genOffsetX;
                    genTitle.y = pickerPadding;
                    pickerApp.stage.addChild(genTitle);

                    regionConfig.generators.forEach(function (gen, index) {
                        const row = Math.floor(index / pickerCols);
                        const col = index % pickerCols;
                        const x = genOffsetX + col * (pickerButtonW + pickerGap);
                        const y = tilesStartY + row * (pickerButtonH + pickerGap);

                        const itemContainer = new PIXI.Container();
                        itemContainer.x = x;
                        itemContainer.y = y;
                        itemContainer.eventMode = 'static';
                        itemContainer.cursor = 'pointer';

                        const background = new PIXI.Graphics();
                        itemContainer.addChild(background);

                        const swatch = new PIXI.Graphics();
                        swatch.beginFill(0xE5E7EB);
                        swatch.lineStyle(2, 0x000000, 1);
                        swatch.drawRoundedRect(10, 10, 18, 18, 4);
                        swatch.endFill();
                        itemContainer.addChild(swatch);

                        if (gen.symbol) {
                            const symText = new PIXI.Text(gen.symbol, {
                                fontFamily: 'Arial',
                                fontSize: 10,
                                fontWeight: 'bold',
                                fill: 0x000000,
                                align: 'center'
                            });
                            symText.anchor.set(0.5);
                            symText.x = 19;
                            symText.y = 19;
                            itemContainer.addChild(symText);
                        }

                        const labelStyle = { fontFamily: 'Arial', fontSize: 13, fill: 0x1F2937 };
                        const fullText = gen.name + ' (' + gen.symbol + ')';
                        const labelText = truncateText(fullText, labelMaxW, labelStyle);
                        const label = new PIXI.Text(labelText, labelStyle);
                        label.x = 36;
                        label.y = 10;
                        itemContainer.addChild(label);

                        const genId = 'gen_' + gen.id;
                        itemContainer.on('pointertap', function () {
                            setSelectedTile(tile_selected_id, tile_selected_color, gen.id, gen.symbol);
                            setActiveTool('paint');
                        });

                        const pickerItem = {
                            background: background,
                            label: label,
                            itemId: genId
                        };
                        tilePickerItems[genId] = pickerItem;
                        drawPickerItem(pickerItem, tile_selected_generator_id === gen.id);
                        pickerApp.stage.addChild(itemContainer);
                    });

                    genEndY = tilesStartY + Math.ceil(regionConfig.generators.length / pickerCols) * (pickerButtonH + pickerGap);
                }

                const finalHeight = Math.max(tilesEndY, genEndY) + pickerPadding;
                pickerApp.renderer.resize(pickerWidth, finalHeight);
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
                drawMapTile(i, j, tileStates[key]);
                updateDirtyState(key);
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
                    applyChange(cell.i, cell.j, {
                        id: tile_selected_id,
                        color: tile_selected_color,
                        generatorId: tile_selected_generator_id,
                        generatorSymbol: tile_selected_generator_symbol
                    }, changeSet);
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
                    drawMapTile(item.i, item.j, tileStates[key]);
                    updateDirtyState(key);
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

            $(document).on('change', '#map-brush-size', function () {
                const value = String($(this).val());
                if (value === 'custom') {
                    $('#map-brush-size-custom-w').show();
                    $('#map-brush-size-custom-sep').show();
                    $('#map-brush-size-custom-h').show();
                    const customW = parseInt($('#map-brush-size-custom-w').val(), 10);
                    const customH = parseInt($('#map-brush-size-custom-h').val(), 10);
                    brushWidth = Number.isInteger(customW) ? Math.max(1, Math.min(25, customW)) : 1;
                    brushHeight = Number.isInteger(customH) ? Math.max(1, Math.min(25, customH)) : 1;
                    refreshPreviewFromHover();
                    return;
                }

                $('#map-brush-size-custom-w').hide();
                $('#map-brush-size-custom-sep').hide();
                $('#map-brush-size-custom-h').hide();
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
