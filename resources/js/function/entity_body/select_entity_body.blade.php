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
        
        // Draw black pixels on grid + gray border for zone pixels
        var pixels = data.pixels_json ? JSON.parse(data.pixels_json) : [];
        var borderColor = 0x808080;
        var borderThickness = 2;

        pixels.forEach(function(pixel) {
            var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (!cellShape) return;

            cellShape.tint = 0x000000;

            // If this black pixel is also part of a zone, draw gray border
            if (pixel.has_zone) {
                var cx = cellShape.x;
                var cy = cellShape.y;
                var cw = cellShape.width;
                var ch = cellShape.height;

                function addBorderRect(rx, ry, rw, rh) {
                    var g = new PIXI.Graphics();
                    g.beginFill(borderColor);
                    g.drawRect(0, 0, rw, rh);
                    g.endFill();
                    g.x = rx;
                    g.y = ry;
                    g.zIndex = 20045;
                    app.stage.addChild(g);
                    zoneBorderShapes.push(g);
                }

                addBorderRect(cx, cy, cw, borderThickness);                       // top
                addBorderRect(cx, cy + ch - borderThickness, cw, borderThickness); // bottom
                addBorderRect(cx, cy, borderThickness, ch);                       // left
                addBorderRect(cx + cw - borderThickness, cy, borderThickness, ch); // right
            }
        });
    };
})();

</script>
