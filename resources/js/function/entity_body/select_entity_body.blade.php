<script>

(function() {
    var selectedBodyUid = null;
    var selectedRect = null;
    var lastDrawnCells = [];
    var currentPixels = [];
    var zonePanelOriginalPositions = null;

    // Use window variable to share zoneBorderShapes with update_zone_color
    window['__zoneBorderShapes_' + '__MODAL_UID__'] = window['__zoneBorderShapes_' + '__MODAL_UID__'] || [];

    function clearZoneBorders() {
        window['__zoneBorderShapes_' + '__MODAL_UID__'].forEach(function(s) {
            if (s.parent) s.parent.removeChild(s);
            if (typeof s.destroy === 'function') s.destroy();
        });
        window['__zoneBorderShapes_' + '__MODAL_UID__'] = [];
    }

    window['clearZoneBorders_' + '__MODAL_UID__'] = clearZoneBorders;

    var sliderUids = __SLIDER_UIDS__;

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
        sliderUids.forEach(function(uid) {
            var s = shapes[uid];
            if (s) s.renderable = false;
        });
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
            sliderUids.forEach(function(uid) {
                var s = shapes[uid];
                if (s) {
                    zonePanelOriginalPositions[uid] = { x: s.x, y: s.y };
                }
            });
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
        sliderUids.forEach(function(uid) {
            var s = shapes[uid];
            var orig = zonePanelOriginalPositions[uid];
            if (s && orig) {
                s.x = orig.x + deltaX;
                s.y = orig.y + deltaY;
            }
        });
    }

    function showZonePanel(zoneColor, zoneName, nearX, nearY, r, g, b) {
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

        sliderUids.forEach(function(uid) {
            var s = shapes[uid];
            if (s) {
                if (s.parent) s.parent.removeChild(s);
                app.stage.addChild(s);
            }
        });

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
        sliderUids.forEach(function(uid) {
            var s = shapes[uid];
            if (s) s.renderable = true;
        });

        // Get current color from the first pixel of the zone (or use provided r,g,b)
        var currentR = r || 0;
        var currentG = g || 0;
        var currentB = b || 0;

        // Try to get the actual current color from the first zone pixel
        var zonePixels = currentPixels.filter(function(p) { return p.has_zone && p.zone_name === zoneName; });
        if (zonePixels.length > 0) {
            var firstPixel = zonePixels[0];
            var cellUid = '__MODAL_UID___grid_cell_' + firstPixel.y + '_' + firstPixel.x;
            var cellShape = shapes[cellUid];
            if (cellShape && cellShape.tint !== 0x000000) {
                // Extract RGB from current tint
                currentR = (cellShape.tint >> 16) & 255;
                currentG = (cellShape.tint >> 8) & 255;
                currentB = cellShape.tint & 255;
            }
        }

        // Update slider values from current zone color
        updateSlider('__MODAL_UID___slider_red', currentR);
        updateSlider('__MODAL_UID___slider_green', currentG);
        updateSlider('__MODAL_UID___slider_blue', currentB);
    }

    function updateSlider(sliderPrefix, value) {
        var knob = shapes[sliderPrefix + '_knob'];
        var trackBg = shapes[sliderPrefix + '_track_bg'];
        var trackFill = shapes[sliderPrefix + '_track_fill'];
        if (trackBg) {
            var ratio = value / 255;
            var trackWidth = trackBg.width;
            var newX = trackBg.x + ratio * trackWidth;
            if (knob) knob.x = newX;
            if (trackFill) {
                var newWidth = Math.max(1, newX - trackBg.x);
                trackFill.width = newWidth;
            }
        }
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
        if (!cellShape) return;

        var parts = cellUid.replace('__MODAL_UID___grid_cell_', '').split('_');
        var cellY = parseInt(parts[0]);
        var cellX = parseInt(parts[1]);

        var clickedPixel = currentPixels.find(function(p) { return p.x === cellX && p.y === cellY; });
        if (clickedPixel) {
            // Use pixel color (black = 0,0,0) instead of zone color
            var r = 0, g = 0, b = 0;
            if (clickedPixel.has_zone) {
                var rgb = hexToRgb(clickedPixel.zone_color);
                showZonePanel(clickedPixel.zone_color, clickedPixel.zone_name, cellShape.x, cellShape.y, r, g, b);
                // Set zone context for color updates
                var setContextFn = window['setZoneContext_' + '__MODAL_UID__'];
                if (typeof setContextFn === 'function') {
                    setContextFn(clickedPixel.zone_name, currentPixels.filter(function(p) { return p.has_zone && p.zone_name === clickedPixel.zone_name; }));
                }
            } else {
                showZonePanel('#000000', 'Pixel', cellShape.x, cellShape.y, r, g, b);
            }
        }
    }

    function hexToRgb(hex) {
        if (!hex) return { r: 0, g: 0, b: 0 };
        if (typeof hex === 'number') {
            return {
                r: (hex >> 16) & 255,
                g: (hex >> 8) & 255,
                b: hex & 255
            };
        }
        hex = hex.replace('#', '');
        var bigint = parseInt(hex, 16);
        if (isNaN(bigint)) return { r: 0, g: 0, b: 0 };
        return {
            r: (bigint >> 16) & 255,
            g: (bigint >> 8) & 255,
            b: bigint & 255
        };
    }

    // Tooltip functions for anchor cells (HTML tooltip)
    var anchorTooltipElement = null;

    function showAnchorTooltip(anchor, cellShape) {
        if (!anchorTooltipElement) {
            anchorTooltipElement = document.createElement('div');
            anchorTooltipElement.style.position = 'absolute';
            anchorTooltipElement.style.backgroundColor = 'white';
            anchorTooltipElement.style.border = '1px solid black';
            anchorTooltipElement.style.padding = '4px 8px';
            anchorTooltipElement.style.fontSize = '12px';
            anchorTooltipElement.style.fontFamily = 'Arial';
            anchorTooltipElement.style.zIndex = '9999';
            anchorTooltipElement.style.pointerEvents = 'none';
            document.body.appendChild(anchorTooltipElement);
        }

        anchorTooltipElement.textContent = '#' + anchor.id + ' (X: ' + anchor.x + ' - Y: ' + anchor.y + ')';
        anchorTooltipElement.style.display = 'block';

        // Position tooltip near the cell
        var canvas = app.renderer.view;
        var rect = canvas.getBoundingClientRect();
        anchorTooltipElement.style.left = (rect.left + cellShape.x) + 'px';
        anchorTooltipElement.style.top = (rect.top + cellShape.y - 25) + 'px';
    }

    function hideAnchorTooltip() {
        if (anchorTooltipElement) {
            anchorTooltipElement.style.display = 'none';
        }
    }

    // Expose functions globally
    window['hideAnchorTooltip_' + '__MODAL_UID__'] = hideAnchorTooltip;
    window['showAnchorTooltip_' + '__MODAL_UID__'] = showAnchorTooltip;

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

            // Show direction buttons when EntityBody is selected
            var dirButtons = ['dir_up', 'dir_down', 'dir_left', 'dir_right'];
            dirButtons.forEach(function(dir) {
                var buttonUid = '__MODAL_UID___' + dir;
                var textUid = '__MODAL_UID___' + dir + '_text';
                if (shapes[buttonUid]) shapes[buttonUid].renderable = true;
                if (shapes[textUid]) shapes[textUid].renderable = true;
            });

            // Show direction title and container
            var dirTitle = shapes['__MODAL_UID___dir_title'];
            if (dirTitle) dirTitle.renderable = true;
            var dirContainer = shapes['__MODAL_UID___dir_container'];
            if (dirContainer) dirContainer.renderable = true;
        }

        // Draw black pixels on grid + gray border for zone pixels + zone color border where zone edge
        var pixels = data.pixels_json ? JSON.parse(data.pixels_json) : [];
        currentPixels = pixels; // Store for grid cell clicks

        // Set pixels context for move function
        var setPixelsFn = window['setPixelsContext_' + '__MODAL_UID__'];
        if (typeof setPixelsFn === 'function') {
            setPixelsFn(pixels);
        }

        // Draw EntityAnchor cell in blue (without borders) - AFTER drawing black pixels
        var anchors = data.anchors ? data.anchors : [];
        window['__anchorData_' + '__MODAL_UID__'] = anchors; // Store anchor data for hover tooltips
        anchors.forEach(function(anchor) {
            var anchorCellUid = '__MODAL_UID___grid_cell_' + anchor.y + '_' + anchor.x;
            var anchorCellShape = shapes[anchorCellUid];
            if (anchorCellShape) {
                anchorCellShape.tint = 0x0000FF; // Blue

                // Add hover events for tooltip
                anchorCellShape.eventMode = 'static';
                anchorCellShape.cursor = 'pointer';

                anchorCellShape.on('pointerover', function() {
                    showAnchorTooltip(anchor, anchorCellShape);
                });

                anchorCellShape.on('pointerout', function() {
                    hideAnchorTooltip();
                });
            }
        });

        // Set anchors context for move function
        var setAnchorsFn = window['setAnchorsContext_' + '__MODAL_UID__'];
        if (typeof setAnchorsFn === 'function') {
            setAnchorsFn(anchors);
        }

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
                    window['__zoneBorderShapes_' + '__MODAL_UID__'].push(g);
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
