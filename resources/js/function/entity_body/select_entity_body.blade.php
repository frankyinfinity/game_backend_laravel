<script>

(function() {
    var selectedBodyUid = null;
    var selectedRect = null;
    var lastDrawnCells = [];

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
        
        // Select new
        var shape = shapes[elementUid];
        if (shape) {
            shape.tint = 0x00FF00;
            selectedRect = shape;
            selectedBodyUid = elementUid;
        }
        
        // Draw black pixels on grid
        var pixels = data.pixels_json ? JSON.parse(data.pixels_json) : [];
        
        pixels.forEach(function(pixel) {
            var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (cellShape) {
                cellShape.tint = 0x000000;
            }
        });
    };
})();

</script>
