<script>

(function() {
    var currentPixels = [];
    var currentAnchors = [];

    window['setPixelsContext_' + '__MODAL_UID__'] = function(pixels) {
        currentPixels = pixels;
    };

    window['setAnchorsContext_' + '__MODAL_UID__'] = function(anchors) {
        currentAnchors = anchors;
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
        var movedAnchors = [];
        var gridSize = 32;

        currentPixels.forEach(function(pixel) {
            var newX = pixel.x;
            var newY = pixel.y;
            var oldCellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var oldCellShape = shapes[oldCellUid];
            var pixelTint = oldCellShape ? oldCellShape.tint : 0x000000;

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
                    tint: pixelTint,
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

        // Calculate new positions for anchors
        currentAnchors.forEach(function(anchor) {
            var newX = anchor.x;
            var newY = anchor.y;

            switch(direction) {
                case 'up':
                    newY = Math.max(0, anchor.y - 1);
                    break;
                case 'down':
                    newY = Math.min(gridSize - 1, anchor.y + 1);
                    break;
                case 'left':
                    newX = Math.max(0, anchor.x - 1);
                    break;
                case 'right':
                    newX = Math.min(gridSize - 1, anchor.x + 1);
                    break;
            }

            // Only add if position changed
            if (newX !== anchor.x || newY !== anchor.y) {
                movedAnchors.push({
                    oldX: anchor.x,
                    oldY: anchor.y,
                    newX: newX,
                    newY: newY
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

        // Clear old anchor positions
        movedAnchors.forEach(function(moved) {
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
                newCellShape.tint = moved.tint;
            }
        });

        // Set new anchor positions (blue)
        movedAnchors.forEach(function(moved) {
            var newCellUid = '__MODAL_UID___grid_cell_' + moved.newY + '_' + moved.newX;
            var newCellShape = shapes[newCellUid];
            if (newCellShape) {
                newCellShape.tint = 0x0000FF; // Blue for anchors

                // Re-attach hover events to new cell
                newCellShape.eventMode = 'static';
                newCellShape.cursor = 'pointer';
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

        // Update currentAnchors array with new positions
        currentAnchors.forEach(function(anchor) {
            var moved = movedAnchors.find(function(m) { return m.oldX === anchor.x && m.oldY === anchor.y; });
            if (moved) {
                anchor.x = moved.newX;
                anchor.y = moved.newY;
            }
        });

        movedAnchors.forEach(function(moved) {
            var newCellUid = '__MODAL_UID___grid_cell_' + moved.newY + '_' + moved.newX;
            var newCellShape = shapes[newCellUid];
            var anchorData = currentAnchors.find(function(anchor) {
                return anchor.x === moved.newX && anchor.y === moved.newY;
            });

            if (!newCellShape || !anchorData) return;

            newCellShape.removeAllListeners('pointerover');
            newCellShape.removeAllListeners('pointerout');

            newCellShape.on('pointerover', function() {
                var showTooltipFn = window['showAnchorTooltip_' + '__MODAL_UID__'];
                if (typeof showTooltipFn === 'function') {
                    showTooltipFn(anchorData, newCellShape);
                }
            });

            newCellShape.on('pointerout', function() {
                var hideTooltipFn = window['hideAnchorTooltip_' + '__MODAL_UID__'];
                if (typeof hideTooltipFn === 'function') {
                    hideTooltipFn();
                }
            });
        });

        var refreshAnchorTooltipFn = window['refreshAnchorTooltip_' + '__MODAL_UID__'];
        if (typeof refreshAnchorTooltipFn === 'function') {
            refreshAnchorTooltipFn(currentAnchors);
        }

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
