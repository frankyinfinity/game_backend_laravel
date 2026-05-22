<script>

(function() {
    var selectedBodyUid = null;
    var selectedRect = null;
    var lastDrawnCells = [];
    var zoneBorderShapes = [];

    function clearZoneBorders() {
        zoneBorderShapes.forEach(function(s) {
            if (s.parent) s.parent.removeChild(s);
            if (typeof s.destroy === 'function') s.destroy();
        });
        zoneBorderShapes = [];
    }

    window['clearZoneBorders_' + '__MODAL_UID__'] = clearZoneBorders;

    window['resetEntityBodyGrid_' + '__MODAL_UID__'] = function() {
        if (selectedRect) {
            selectedRect.tint = 0xFFFFFF;
            selectedRect = null;
        }
        selectedBodyUid = null;
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cellUid = '__MODAL_UID___grid_cell_' + row + '_' + col;
                var cellShape = shapes[cellUid];
                if (cellShape) {
                    cellShape.tint = 0xFFFFFF;
                }
            }
        }
        clearZoneBorders();
    };

    window['__name__'] = function(elementUid) {
        var obj = objects[elementUid];
        if (!obj || !obj['attributes']) return;
        
        var cellData = obj['attributes']['cell_data'];
        if (!cellData) return;
        
        try {
            var data = JSON.parse(cellData);
        } catch(e) { return; }
        
        // Deselect previous
        if (selectedRect) {
            selectedRect.tint = 0xFFFFFF;
        }
        
        // Reset all grid cells to white
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cellUid = '__MODAL_UID___grid_cell_' + row + '_' + col;
                var cellShape = shapes[cellUid];
                if (cellShape) {
                    cellShape.tint = 0xFFFFFF;
                }
            }
        }

        // Clear previous zone borders
        clearZoneBorders();
        
        // Select new
        var shape = shapes[elementUid];
        if (shape) {
            shape.tint = 0x00FF00;
            selectedRect = shape;
            selectedBodyUid = elementUid;
        }
        
        // Draw black pixels on grid + gray border for zone pixels + red border where zone edge
        var pixels = data.pixels_json ? JSON.parse(data.pixels_json) : [];
        var grayBorderColor = 0x808080;
        var zoneBorderColor = 0xFF0000;
        var grayThickness = 1;
        var zoneThickness = 3;

        pixels.forEach(function(pixel) {
            var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (!cellShape) return;

            cellShape.tint = 0x000000;

            // Draw gray border for zone pixels + red borders where zone edge
            if (pixel.has_zone) {
                var cx = cellShape.x;
                var cy = cellShape.y;
                var cw = cellShape.width;
                var ch = cellShape.height;

                function addBorderRect(rx, ry, rw, rh, color) {
                    var g = new PIXI.Graphics();
                    g.beginFill(color);
                    g.drawRect(0, 0, rw, rh);
                    g.endFill();
                    g.x = rx;
                    g.y = ry;
                    g.zIndex = 20045;
                    app.stage.addChild(g);
                    zoneBorderShapes.push(g);
                }

                // Gray base border for all zone pixels
                addBorderRect(cx, cy, cw, grayThickness, grayBorderColor);
                addBorderRect(cx, cy + ch - grayThickness, cw, grayThickness, grayBorderColor);
                addBorderRect(cx, cy, grayThickness, ch, grayBorderColor);
                addBorderRect(cx + cw - grayThickness, cy, grayThickness, ch, grayBorderColor);

                // Red borders only on sides where neighbor is NOT same zone
                if (pixel.zone_border_top)    addBorderRect(cx, cy, cw, zoneThickness, zoneBorderColor);
                if (pixel.zone_border_bottom) addBorderRect(cx, cy + ch - zoneThickness, cw, zoneThickness, zoneBorderColor);
                if (pixel.zone_border_left)   addBorderRect(cx, cy, zoneThickness, ch, zoneBorderColor);
                if (pixel.zone_border_right)  addBorderRect(cx + cw - zoneThickness, cy, zoneThickness, ch, zoneBorderColor);
            }
        });
    };
})();

</script>
