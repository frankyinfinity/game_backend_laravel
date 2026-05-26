<script>

(function() {
    var selectedBodyUid = null;
    var selectedRect = null;
    var lastDrawnCells = [];
    var zoneBorderShapes = [];
    var currentPixels = [];
    var zonePanelOriginalPositions = null;

    function clearZoneBorders() {
        zoneBorderShapes.forEach(function(s) {
            if (s.parent) s.parent.removeChild(s);
            if (typeof s.destroy === 'function') s.destroy();
        });
        zoneBorderShapes = [];
    }

    window['clearZoneBorders_' + '__MODAL_UID__'] = clearZoneBorders;

    function hideZonePanel() {
        var panel = shapes['__MODAL_UID___zone_panel'];
        var colorSquare = shapes['__MODAL_UID___zone_color_square'];
        var nameText = shapes['__MODAL_UID___zone_name_text'];
        var closeButton = shapes['__MODAL_UID___zone_close_button'];
        var closeText = shapes['__MODAL_UID___zone_close_text'];
        var borderTop = shapes['__MODAL_UID___zone_border_top'];
        var borderBottom = shapes['__MODAL_UID___zone_border_bottom'];
        var borderLeft = shapes['__MODAL_UID___zone_border_left'];
        var borderRight = shapes['__MODAL_UID___zone_border_right'];
        if (panel) panel.renderable = false;
        if (colorSquare) colorSquare.renderable = false;
        if (nameText) nameText.renderable = false;
        if (closeButton) closeButton.renderable = false;
        if (closeText) closeText.renderable = false;
        if (borderTop) borderTop.renderable = false;
        if (borderBottom) borderBottom.renderable = false;
        if (borderLeft) borderLeft.renderable = false;
        if (borderRight) borderRight.renderable = false;
    }

    function positionZonePanel(nearX, nearY) {
        var panel = shapes['__MODAL_UID___zone_panel'];
        var colorSquare = shapes['__MODAL_UID___zone_color_square'];
        var nameText = shapes['__MODAL_UID___zone_name_text'];
        var closeButton = shapes['__MODAL_UID___zone_close_button'];
        var closeText = shapes['__MODAL_UID___zone_close_text'];
        var borderTop = shapes['__MODAL_UID___zone_border_top'];
        var borderBottom = shapes['__MODAL_UID___zone_border_bottom'];
        var borderLeft = shapes['__MODAL_UID___zone_border_left'];
        var borderRight = shapes['__MODAL_UID___zone_border_right'];

        if (!panel) return;

        if (!zonePanelOriginalPositions) {
            zonePanelOriginalPositions = {
                panel: { x: panel.x, y: panel.y },
                colorSquare: { x: colorSquare.x, y: colorSquare.y },
                nameText: { x: nameText.x, y: nameText.y },
                closeButton: { x: closeButton.x, y: closeButton.y },
                closeText: { x: closeText.x, y: closeText.y },
                borderTop: borderTop ? { x: borderTop.x, y: borderTop.y } : null,
                borderBottom: borderBottom ? { x: borderBottom.x, y: borderBottom.y } : null,
                borderLeft: borderLeft ? { x: borderLeft.x, y: borderLeft.y } : null,
                borderRight: borderRight ? { x: borderRight.x, y: borderRight.y } : null
            };
        }

        var newPanelX = nearX + 15;
        var newPanelY = nearY + 15;
        var deltaX = newPanelX - zonePanelOriginalPositions.panel.x;
        var deltaY = newPanelY - zonePanelOriginalPositions.panel.y;

        panel.x = zonePanelOriginalPositions.panel.x + deltaX;
        panel.y = zonePanelOriginalPositions.panel.y + deltaY;
        colorSquare.x = zonePanelOriginalPositions.colorSquare.x + deltaX;
        colorSquare.y = zonePanelOriginalPositions.colorSquare.y + deltaY;
        nameText.x = zonePanelOriginalPositions.nameText.x + deltaX;
        nameText.y = zonePanelOriginalPositions.nameText.y + deltaY;
        closeButton.x = zonePanelOriginalPositions.closeButton.x + deltaX;
        closeButton.y = zonePanelOriginalPositions.closeButton.y + deltaY;
        closeText.x = zonePanelOriginalPositions.closeText.x + deltaX;
        closeText.y = zonePanelOriginalPositions.closeText.y + deltaY;
        if (borderTop && zonePanelOriginalPositions.borderTop) {
            borderTop.x = zonePanelOriginalPositions.borderTop.x + deltaX;
            borderTop.y = zonePanelOriginalPositions.borderTop.y + deltaY;
        }
        if (borderBottom && zonePanelOriginalPositions.borderBottom) {
            borderBottom.x = zonePanelOriginalPositions.borderBottom.x + deltaX;
            borderBottom.y = zonePanelOriginalPositions.borderBottom.y + deltaY;
        }
        if (borderLeft && zonePanelOriginalPositions.borderLeft) {
            borderLeft.x = zonePanelOriginalPositions.borderLeft.x + deltaX;
            borderLeft.y = zonePanelOriginalPositions.borderLeft.y + deltaY;
        }
        if (borderRight && zonePanelOriginalPositions.borderRight) {
            borderRight.x = zonePanelOriginalPositions.borderRight.x + deltaX;
            borderRight.y = zonePanelOriginalPositions.borderRight.y + deltaY;
        }
    }

    function showZonePanel(zoneColor, zoneName, nearX, nearY) {
        var panel = shapes['__MODAL_UID___zone_panel'];
        var colorSquare = shapes['__MODAL_UID___zone_color_square'];
        var nameText = shapes['__MODAL_UID___zone_name_text'];
        var closeButton = shapes['__MODAL_UID___zone_close_button'];
        var closeText = shapes['__MODAL_UID___zone_close_text'];
        var borderTop = shapes['__MODAL_UID___zone_border_top'];
        var borderBottom = shapes['__MODAL_UID___zone_border_bottom'];
        var borderLeft = shapes['__MODAL_UID___zone_border_left'];
        var borderRight = shapes['__MODAL_UID___zone_border_right'];

        if (!panel || !colorSquare || !nameText) return;

        // Add panel and its elements to stage, ensuring they are on top
        app.stage.sortableChildren = true;
        
        // Remove from current parent if any, then add to stage to ensure they render last (on top)
        if (panel.parent) panel.parent.removeChild(panel);
        if (colorSquare.parent) colorSquare.parent.removeChild(colorSquare);
        if (nameText.parent) nameText.parent.removeChild(nameText);
        if (closeButton.parent) closeButton.parent.removeChild(closeButton);
        if (closeText.parent) closeText.parent.removeChild(closeText);
        if (borderTop && borderTop.parent) borderTop.parent.removeChild(borderTop);
        if (borderBottom && borderBottom.parent) borderBottom.parent.removeChild(borderBottom);
        if (borderLeft && borderLeft.parent) borderLeft.parent.removeChild(borderLeft);
        if (borderRight && borderRight.parent) borderRight.parent.removeChild(borderRight);
        
        // Add borders first (lower in display list), then panel (on top)
        if (borderTop) app.stage.addChild(borderTop);
        if (borderBottom) app.stage.addChild(borderBottom);
        if (borderLeft) app.stage.addChild(borderLeft);
        if (borderRight) app.stage.addChild(borderRight);
        app.stage.addChild(panel);
        app.stage.addChild(colorSquare);
        app.stage.addChild(nameText);
        app.stage.addChild(closeButton);
        app.stage.addChild(closeText);

        // Position panel near clicked cell if coordinates provided
        if (typeof nearX === 'number' && typeof nearY === 'number') {
            positionZonePanel(nearX, nearY);
        }

        // Convert hex color to PIXI color
        function hexToPixiColor(hex) {
            if (!hex) return 0x000000;
            if (typeof hex === 'number') return hex;
            hex = hex.replace('#', '');
            return parseInt(hex, 16);
        }

        var pixiColor = hexToPixiColor(zoneColor);

        // Update panel content
        colorSquare.tint = pixiColor;
        nameText.text = zoneName || 'Unknown';

        // Show panel
        panel.renderable = true;
        colorSquare.renderable = true;
        nameText.renderable = true;
        closeButton.renderable = true;
        closeText.renderable = true;
        if (borderTop) borderTop.renderable = true;
        if (borderBottom) borderBottom.renderable = true;
        if (borderLeft) borderLeft.renderable = true;
        if (borderRight) borderRight.renderable = true;
    }

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

    // Grid cell click handler - shows zone info panel
    window['clickGridCell_' + '__MODAL_UID__'] = function(cellUid) {
        var cellShape = shapes[cellUid];
        if (!cellShape || cellShape.tint !== 0x000000) return;

        var parts = cellUid.replace('__MODAL_UID___grid_cell_', '').split('_');
        var cellY = parseInt(parts[0]);
        var cellX = parseInt(parts[1]);

        var clickedPixel = currentPixels.find(function(p) { return p.x === cellX && p.y === cellY; });
        if (clickedPixel && clickedPixel.has_zone) {
            showZonePanel(clickedPixel.zone_color, clickedPixel.zone_name, cellShape.x, cellShape.y);
        }
    };

    window['__name__'] = function(elementUid) {
        var obj = objects[elementUid];
        if (!obj || !obj['attributes']) return;

        var cellData = obj['attributes']['cell_data'];
        if (!cellData) return;

        // Hide zone panel when selecting a new EntityBody
        hideZonePanel();

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

        // Draw black pixels on grid + gray border for zone pixels + zone color border where zone edge
        var pixels = data.pixels_json ? JSON.parse(data.pixels_json) : [];
        currentPixels = pixels; // Store for grid cell clicks
        var grayBorderColor = 0x808080;
        var zoneBorderColor = 0xFF0000;

        // Helper to convert hex string to PIXI color number
        function hexToPixiColor(hex) {
            if (!hex) return 0xFF0000;
            if (typeof hex === 'number') return hex;
            hex = hex.replace('#', '');
            return parseInt(hex, 16);
        }
        var grayThickness = 1;
        var zoneThickness = 3;

        pixels.forEach(function(pixel) {
            var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (!cellShape) return;

            cellShape.tint = 0x000000;

            // Draw gray border for zone pixels + zone color borders where zone edge
            if (pixel.has_zone) {
                var cx = cellShape.x;
                var cy = cellShape.y;
                var cw = cellShape.width;
                var ch = cellShape.height;
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
