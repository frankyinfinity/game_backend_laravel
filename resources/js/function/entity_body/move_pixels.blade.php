<script>

(function() {
    var currentPixels = [];

    window['setPixelsContext_' + '__MODAL_UID__'] = function(pixels) {
        currentPixels = pixels;
    };

    window['movePixels_' + '__MODAL_UID__'] = function(direction) {
        if (currentPixels.length === 0) return;

        // Clear existing zone borders before moving
        var clearFn = window['clearZoneBorders_' + '__MODAL_UID__'];
        if (typeof clearFn === 'function') {
            clearFn();
        }

        // Calculate new positions for all pixels
        var movedPixels = [];
        var gridSize = 32;

        currentPixels.forEach(function(pixel) {
            var newX = pixel.x;
            var newY = pixel.y;

            switch(direction) {
                case 'up':
                    newY = Math.max(0, pixel.y - 1);
                    break;
                case 'down':
                    newY = Math.min(gridSize - 1, pixel.y + 1);
                    break;
                case 'left':
                    newX = Math.max(0, pixel.x - 1);
                    break;
                case 'right':
                    newX = Math.min(gridSize - 1, pixel.x + 1);
                    break;
            }

            // Only add if position changed
            if (newX !== pixel.x || newY !== pixel.y) {
                movedPixels.push({
                    oldX: pixel.x,
                    oldY: pixel.y,
                    newX: newX,
                    newY: newY,
                    has_zone: pixel.has_zone,
                    zone_color: pixel.zone_color,
                    zone_name: pixel.zone_name,
                    zone_border_top: pixel.zone_border_top,
                    zone_border_bottom: pixel.zone_border_bottom,
                    zone_border_left: pixel.zone_border_left,
                    zone_border_right: pixel.zone_border_right
                });
            }
        });

        // Clear old positions
        movedPixels.forEach(function(moved) {
            var oldCellUid = '__MODAL_UID___grid_cell_' + moved.oldY + '_' + moved.oldX;
            var oldCellShape = shapes[oldCellUid];
            if (oldCellShape) {
                oldCellShape.tint = 0xFFFFFF;
            }
        });

        // Set new positions
        movedPixels.forEach(function(moved) {
            var newCellUid = '__MODAL_UID___grid_cell_' + moved.newY + '_' + moved.newX;
            var newCellShape = shapes[newCellUid];
            if (newCellShape) {
                newCellShape.tint = 0x000000;
            }
        });

        // Update currentPixels array with new positions
        currentPixels.forEach(function(pixel) {
            var moved = movedPixels.find(function(m) { return m.oldX === pixel.x && m.oldY === pixel.y; });
            if (moved) {
                pixel.x = moved.newX;
                pixel.y = moved.newY;
            }
        });

        // Re-draw zone borders if there are zones
        if (currentPixels.some(function(p) { return p.has_zone; })) {
            var grayBorderColor = 0x808080;
            var zoneThickness = 3;
            var grayThickness = 1;

            currentPixels.forEach(function(pixel) {
                if (pixel.has_zone) {
                    var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
                    var cellShape = shapes[cellUid];
                    if (!cellShape) return;

                    var cx = cellShape.x;
                    var cy = cellShape.y;
                    var cw = cellShape.width;
                    var ch = cellShape.height;

                    // Helper to convert hex string to PIXI color number
                    function hexToPixiColor(hex) {
                        if (!hex) return 0xFF0000;
                        if (typeof hex === 'number') return hex;
                        hex = hex.replace('#', '');
                        return parseInt(hex, 16);
                    }

                    var zoneBorderColor = hexToPixiColor(pixel.zone_color);

                    function addBorderRect(rx, ry, rw, rh, color) {
                        var g = new PIXI.Graphics();
                        g.beginFill(color);
                        g.drawRect(0, 0, rw, rh);
                        g.endFill();
                        g.x = rx;
                        g.y = ry;
                        g.zIndex = 20045;
                        app.stage.sortableChildren = true;
                        app.stage.addChild(g);
                        window['__zoneBorderShapes_' + '__MODAL_UID__'].push(g);
                    }

                    // Gray base border for all zone pixels
                    addBorderRect(cx, cy, cw, grayThickness, grayBorderColor);
                    addBorderRect(cx, cy + ch - grayThickness, cw, grayThickness, grayBorderColor);
                    addBorderRect(cx, cy, grayThickness, ch, grayBorderColor);
                    addBorderRect(cx + cw - grayThickness, cy, grayThickness, ch, grayBorderColor);

                    // Zone color borders only on sides where neighbor is NOT same zone
                    if (pixel.zone_border_top)    addBorderRect(cx, cy, cw, zoneThickness, zoneBorderColor);
                    if (pixel.zone_border_bottom) addBorderRect(cx, cy + ch - zoneThickness, cw, zoneThickness, zoneBorderColor);
                    if (pixel.zone_border_left)   addBorderRect(cx, cy, zoneThickness, ch, zoneBorderColor);
                    if (pixel.zone_border_right)  addBorderRect(cx + cw - zoneThickness, cy, zoneThickness, ch, zoneBorderColor);
                }
            });
        }
    };
})();

</script>
