<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Mappa</h5>
    </div>
    <div class="card-body" style="overflow: auto;">
        <div class="row">
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
                updateTileUrl: "{{ route('regions.tile') }}",
                csrfToken: $('meta[name="csrf-token"]').attr('content')
            };

            let tile_selected_id = String(regionConfig.defaultTileId);
            let tile_selected_color = regionConfig.defaultTileColor;
            const tileGraphics = {};
            const tileColors = {};
            const tilePickerItems = {};
            const hexToNumber = (hexColor) => parseInt(hexColor.replace('#', '0x'), 16);

            for (let i = 0; i < regionConfig.height; i++) {
                for (let j = 0; j < regionConfig.width; j++) {
                    tileColors[i + '_' + j] = regionConfig.defaultTileColor;
                }
            }

            regionConfig.map.forEach(item => {
                if (item && item.tile && item.tile.color !== undefined) {
                    tileColors[item.i + '_' + item.j] = item.tile.color;
                }
            });

            const mapApp = new PIXI.Application({
                width: regionConfig.width * regionConfig.tileSize,
                height: regionConfig.height * regionConfig.tileSize,
                antialias: false,
                backgroundAlpha: 0
            });
            document.getElementById('region-map-pixi').appendChild(mapApp.view);

            const pickerCols = 3;
            const pickerPadding = 10;
            const pickerGap = 8;
            const pickerButtonW = 180;
            const pickerButtonH = 38;
            const pickerRows = Math.ceil(regionConfig.tiles.length / pickerCols);
            const pickerWidth = pickerPadding * 2 + pickerCols * pickerButtonW + (pickerCols - 1) * pickerGap;
            const pickerHeight = pickerPadding * 2 + pickerRows * pickerButtonH + (pickerRows - 1) * pickerGap;

            const pickerApp = new PIXI.Application({
                width: pickerWidth,
                height: pickerHeight,
                antialias: true,
                backgroundColor: 0xF8FAFC
            });
            document.getElementById('region-tile-picker-pixi').appendChild(pickerApp.view);

            function drawMapTile(i, j, color) {
                const key = i + '_' + j;
                const previousGraphic = tileGraphics[key];
                if (previousGraphic) {
                    mapApp.stage.removeChild(previousGraphic);
                    previousGraphic.destroy();
                }

                const graphic = new PIXI.Graphics();
                graphic.beginFill(hexToNumber(color));
                graphic.lineStyle(1, 0xFFFFFF, 0.5);
                graphic.drawRect(
                    j * regionConfig.tileSize,
                    i * regionConfig.tileSize,
                    regionConfig.tileSize,
                    regionConfig.tileSize
                );
                graphic.endFill();
                graphic.eventMode = 'static';
                graphic.cursor = 'pointer';
                graphic.on('pointertap', function () {
                    saveTile(i, j);
                });

                tileGraphics[key] = graphic;
                mapApp.stage.addChild(graphic);
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
                Object.keys(tilePickerItems).forEach(function (tileId) {
                    drawPickerItem(tilePickerItems[tileId], tileId === tile_selected_id);
                });
            }

            function buildPicker() {
                regionConfig.tiles.forEach(function (tile, index) {
                    const row = Math.floor(index / pickerCols);
                    const col = index % pickerCols;
                    const x = pickerPadding + col * (pickerButtonW + pickerGap);
                    const y = pickerPadding + row * (pickerButtonH + pickerGap);

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

                    const label = new PIXI.Text(tile.name, {
                        fontFamily: 'Arial',
                        fontSize: 13,
                        fill: 0x1F2937
                    });
                    label.x = 36;
                    label.y = 10;
                    itemContainer.addChild(label);

                    itemContainer.on('pointertap', function () {
                        tile_selected_id = String(tile.id);
                        tile_selected_color = tile.color;
                        updatePickerSelection();
                    });

                    const pickerItem = {
                        background: background,
                        label: label
                    };
                    tilePickerItems[String(tile.id)] = pickerItem;
                    drawPickerItem(pickerItem, String(tile.id) === tile_selected_id);
                    pickerApp.stage.addChild(itemContainer);
                });
            }

            function saveTile(tile_i, tile_j) {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': regionConfig.csrfToken
                    }
                });

                $.ajax({
                    url: regionConfig.updateTileUrl,
                    type: 'POST',
                    data: {
                        region_id: regionConfig.id,
                        tile_id: tile_selected_id,
                        tile_i: tile_i,
                        tile_j: tile_j
                    },
                    success: function (result) {
                        if (result.success) {
                            tileColors[tile_i + '_' + tile_j] = tile_selected_color;
                            drawMapTile(tile_i, tile_j, tile_selected_color);
                        } else {
                            let msg = 'Si e verificato un errore.';
                            if (result.msg != null) {
                                msg = result.msg;
                            }
                            $.notify({ title: 'Ops!', message: msg }, { type: 'warning' });
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Ops!',
                            text: 'Si e verificato un errore imprevisto.',
                            type: 'danger',
                            showCancelButton: false,
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-info',
                            confirmButtonText: 'Ho Capito!'
                        });
                    }
                });
            }

            for (let i = 0; i < regionConfig.height; i++) {
                for (let j = 0; j < regionConfig.width; j++) {
                    drawMapTile(i, j, tileColors[i + '_' + j]);
                }
            }

            buildPicker();
        });
    </script>
@stop
