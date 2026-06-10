<script>
(function() {
    var addModalUid = '__ADD_MODAL_UID__';
    var parentModalUid = '__MODAL_UID__';
    var selectedComponentData = null;
    var disabledMainModalInteractivity = [];
    var componentBadgesContainer = null;
    var badgeTooltipElement = null;
    var addModalBorderShapes = {
        left: [],
        right: []
    };

    function setMainModalInteractivityDisabled(disabled) {
        var prefix = parentModalUid;

        if (!disabled) {
            disabledMainModalInteractivity.forEach(function(entry) {
                if (!entry.shape) return;
                entry.shape.eventMode = entry.eventMode;
                entry.shape.cursor = entry.cursor;
            });
            disabledMainModalInteractivity = [];
            return;
        }

        disabledMainModalInteractivity = [];
        Object.keys(shapes).forEach(function(uid) {
            if (uid.indexOf(prefix) !== 0) return;
            if (uid.indexOf(addModalUid) === 0) return;

            var shape = shapes[uid];
            if (!shape) return;

            disabledMainModalInteractivity.push({
                shape: shape,
                eventMode: shape.eventMode,
                cursor: shape.cursor
            });

            shape.eventMode = 'none';
            shape.cursor = 'default';
        });
    }

    function ensureBadgeTooltipElement() {
        if (badgeTooltipElement) return badgeTooltipElement;

        badgeTooltipElement = document.createElement('div');
        badgeTooltipElement.style.position = 'absolute';
        badgeTooltipElement.style.zIndex = '130000';
        badgeTooltipElement.style.display = 'none';
        badgeTooltipElement.style.pointerEvents = 'none';
        badgeTooltipElement.style.whiteSpace = 'pre-line';
        badgeTooltipElement.style.background = '#ffffff';
        badgeTooltipElement.style.border = '1px solid #000';
        badgeTooltipElement.style.borderRadius = '6px';
        badgeTooltipElement.style.padding = '8px 10px';
        badgeTooltipElement.style.fontFamily = 'Arial';
        badgeTooltipElement.style.fontSize = '12px';
        badgeTooltipElement.style.color = '#000';
        badgeTooltipElement.style.maxWidth = '320px';
        badgeTooltipElement.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
        document.body.appendChild(badgeTooltipElement);
        return badgeTooltipElement;
    }

    function hideBadgeTooltip() {
        if (badgeTooltipElement) {
            badgeTooltipElement.style.display = 'none';
            badgeTooltipElement.textContent = '';
        }
    }

    function ensureComponentBadgesContainer() {
        if (componentBadgesContainer) return componentBadgesContainer;

        componentBadgesContainer = document.createElement('div');
        componentBadgesContainer.style.position = 'absolute';
        componentBadgesContainer.style.zIndex = '120000';
        componentBadgesContainer.style.display = 'none';
        componentBadgesContainer.style.pointerEvents = 'none';
        componentBadgesContainer.style.maxWidth = '320px';
        componentBadgesContainer.style.background = '#f5f5f5';
        componentBadgesContainer.style.border = '1px solid #000';
        componentBadgesContainer.style.borderRadius = '8px';
        componentBadgesContainer.style.padding = '14px';
        componentBadgesContainer.style.boxSizing = 'border-box';
        componentBadgesContainer.style.boxShadow = '0 2px 6px rgba(0,0,0,0.12)';
        document.body.appendChild(componentBadgesContainer);
        return componentBadgesContainer;
    }

    function hideComponentBadges() {
        if (componentBadgesContainer) {
            componentBadgesContainer.style.display = 'none';
            componentBadgesContainer.innerHTML = '';
        }
        hideBadgeTooltip();
    }

    function renderBadgeSection(title, items) {
        if (!items || !items.length) return '';

        var badgesHtml = items.map(function(item) {
            var tooltipAttr = item.tooltip
                ? ' data-badge-tooltip="' + item.tooltip.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '"'
                : '';
            var imageHtml = item.image_url
                ? '<img src="' + item.image_url + '" style="width:20px;height:20px;image-rendering:pixelated;border-radius:4px;flex:0 0 auto;">'
                : '<div style="width:20px;height:20px;border:1px solid #999;border-radius:4px;background:#fff;flex:0 0 auto;"></div>';

            return '<div' + tooltipAttr + ' style="display:inline-flex;align-items:center;gap:6px;background:#ffffff;border:1px solid #000;border-radius:999px;padding:6px 10px;margin:0 8px 8px 0;font-family:Arial;font-size:12px;color:#000;pointer-events:auto;">' + imageHtml + '<span>' + item.name + '</span></div>';
        }).join('');

        return '<div style="margin-top:12px;"><div style="font-family:Arial;font-size:13px;font-weight:bold;color:#000;margin-bottom:8px;">' + title + '</div><div>' + badgesHtml + '</div></div>';
    }

    function showComponentBadges(componentData) {
        var container = ensureComponentBadgesContainer();
        var canvas = app.renderer.view;
        var rect = canvas.getBoundingClientRect();
        var rightGridBg = shapes[addModalUid + '_right_grid_bg'];

        var left = rect.left + 700;
        var top = rect.top + 140;

        if (rightGridBg) {
            left = rect.left + rightGridBg.x + rightGridBg.width + 24;
            top = rect.top + rightGridBg.y;
        }

        container.style.left = left + 'px';
        container.style.top = top + 'px';
        container.innerHTML =
            '<div style="font-family:Arial;font-size:16px;font-weight:bold;color:#000;margin-bottom:6px;">Dettagli componente</div>' +
            renderBadgeSection('Geni', componentData.genes_badges || []) +
            renderBadgeSection('Elementi chimici', componentData.chimical_badges || []);
        container.style.display = container.innerHTML ? 'block' : 'none';

        Array.prototype.forEach.call(container.querySelectorAll('[data-badge-tooltip]'), function(el) {
            el.addEventListener('mouseenter', function() {
                var tooltip = ensureBadgeTooltipElement();
                tooltip.textContent = el.getAttribute('data-badge-tooltip') || '';
                tooltip.style.left = (el.getBoundingClientRect().left) + 'px';
                tooltip.style.top = (el.getBoundingClientRect().bottom + 6) + 'px';
                tooltip.style.display = tooltip.textContent ? 'block' : 'none';
            });
            el.addEventListener('mouseleave', function() {
                hideBadgeTooltip();
            });
        });
    }

    function clearAddModalZoneBorders(side) {
        var shapesToClear = addModalBorderShapes[side] || [];
        shapesToClear.forEach(function(s) {
            if (s.parent) s.parent.removeChild(s);
            if (typeof s.destroy === 'function') s.destroy();
        });
        addModalBorderShapes[side] = [];
    }

    function hexToPixiColor(hex) {
        if (!hex) return 0xFF0000;
        if (typeof hex === 'number') return hex;
        hex = hex.replace('#', '');
        return parseInt(hex, 16);
    }

    function drawZoneBordersForGrid(side, pixels, zIndex) {
        clearAddModalZoneBorders(side);

        if (!pixels || !pixels.some(function(p) { return p.has_zone; })) {
            return;
        }

        var grayBorderColor = 0x808080;
        var zoneThickness = 3;
        var grayThickness = 1;

        function addBorderRect(rx, ry, rw, rh, color) {
            var g = new PIXI.Graphics();
            g.beginFill(color);
            g.drawRect(0, 0, rw, rh);
            g.endFill();
            g.x = rx;
            g.y = ry;
            g.zIndex = zIndex;
            app.stage.sortableChildren = true;
            app.stage.addChild(g);
            addModalBorderShapes[side].push(g);
        }

        pixels.forEach(function(pixel) {
            if (!pixel.has_zone) return;

            var cellUid = addModalUid + '_' + side + '_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (!cellShape) return;

            var cx = cellShape.x;
            var cy = cellShape.y;
            var cw = cellShape.width;
            var ch = cellShape.height;
            var zoneBorderColor = hexToPixiColor(pixel.zone_color);

            addBorderRect(cx, cy, cw, grayThickness, grayBorderColor);
            addBorderRect(cx, cy + ch - grayThickness, cw, grayThickness, grayBorderColor);
            addBorderRect(cx, cy, grayThickness, ch, grayBorderColor);
            addBorderRect(cx + cw - grayThickness, cy, grayThickness, ch, grayBorderColor);

            if (pixel.zone_border_top) addBorderRect(cx, cy, cw, zoneThickness, zoneBorderColor);
            if (pixel.zone_border_bottom) addBorderRect(cx, cy + ch - zoneThickness, cw, zoneThickness, zoneBorderColor);
            if (pixel.zone_border_left) addBorderRect(cx, cy, zoneThickness, ch, zoneBorderColor);
            if (pixel.zone_border_right) addBorderRect(cx + cw - zoneThickness, cy, zoneThickness, ch, zoneBorderColor);
        });
    }

    // Close the add component modal
    window['closeAddComponentModal_' + parentModalUid] = function() {
        var body = shapes[addModalUid + '_body'];
        var header = shapes[addModalUid + '_header'];
        var title = shapes[addModalUid + '_title'];
        var closeButton = shapes[addModalUid + '_close_button'];
        var closeText = shapes[addModalUid + '_close_text'];
        var leftBg = shapes[addModalUid + '_left_bg'];
        var leftTitle = shapes[addModalUid + '_left_title'];
        var leftGridBg = shapes[addModalUid + '_left_grid_bg'];
        var rightBg = shapes[addModalUid + '_right_bg'];
        var rightTitle = shapes[addModalUid + '_right_title'];
        var rightGridBg = shapes[addModalUid + '_right_grid_bg'];
        var separator = shapes[addModalUid + '_separator'];

        // Hide all modal elements
        if (body) body.renderable = false;
        if (header) header.renderable = false;
        if (title) title.renderable = false;
        if (closeButton) closeButton.renderable = false;
        if (closeText) closeText.renderable = false;
        if (leftBg) leftBg.renderable = false;
        if (leftTitle) leftTitle.renderable = false;
        if (leftGridBg) leftGridBg.renderable = false;
        if (rightBg) rightBg.renderable = false;
        if (rightTitle) rightTitle.renderable = false;
        if (rightGridBg) rightGridBg.renderable = false;
        if (separator) separator.renderable = false;

        clearAddModalZoneBorders('left');
        clearAddModalZoneBorders('right');
        hideComponentBadges();
        setMainModalInteractivityDisabled(false);

        var redrawMainZoneBorders = window['redrawZoneBorders_' + parentModalUid];
        if (typeof redrawMainZoneBorders === 'function') {
            redrawMainZoneBorders();
        }

        // Hide all grid cells
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var leftCellUid = addModalUid + '_left_cell_' + row + '_' + col;
                var rightCellUid = addModalUid + '_right_cell_' + row + '_' + col;
                if (shapes[leftCellUid]) shapes[leftCellUid].renderable = false;
                if (shapes[rightCellUid]) shapes[rightCellUid].renderable = false;
            }
        }

        // Hide modal borders
        ['top', 'bottom', 'left', 'right'].forEach(function(side) {
            var border = shapes[addModalUid + '_border_' + side];
            if (border) border.renderable = false;
        });

        // Hide grid borders
        ['left', 'right'].forEach(function(prefix) {
            ['top', 'bottom', 'left', 'right'].forEach(function(side) {
                var border = shapes[addModalUid + '_' + prefix + '_grid_border_' + side];
                if (border) border.renderable = false;
            });
        });
    };

    // Open the add component modal and populate with selected data
    window['openAddComponentModal_' + parentModalUid] = function(componentUid) {
        console.log('openAddComponentModal called with componentUid:', componentUid);

        // Get selected EntityBody data
        var selectedBodyUid = window['__selectedBodyUid_' + parentModalUid];

        if (!selectedBodyUid) {
            console.warn('No EntityBody selected');
            alert('Seleziona prima un EntityBody');
            return;
        }

        if (!componentUid) {
            console.warn('No EntityComponent UID provided');
            return;
        }

        var bodyObj = objects[selectedBodyUid];
        var componentObj = objects[componentUid];

        console.log('bodyObj:', bodyObj);
        console.log('componentObj:', componentObj);

        if (!bodyObj || !componentObj) {
            console.warn('Selected objects not found');
            return;
        }

        selectedComponentData = componentObj.attributes.cell_data ? JSON.parse(componentObj.attributes.cell_data) : null;

        var getAssemblerGridSnapshot = window['getAssemblerGridSnapshot_' + parentModalUid];
        var assemblerGridSnapshot = typeof getAssemblerGridSnapshot === 'function'
            ? getAssemblerGridSnapshot()
            : null;

        console.log('assemblerGridSnapshot:', assemblerGridSnapshot);
        console.log('selectedComponentData:', selectedComponentData);

        if (!assemblerGridSnapshot || !selectedComponentData) {
            console.warn('No cell data found');
            return;
        }

        var clearMainZoneBorders = window['clearZoneBorders_' + parentModalUid];
        if (typeof clearMainZoneBorders === 'function') {
            clearMainZoneBorders();
        }

        var hideMainAnchorTooltip = window['hideAnchorTooltip_' + parentModalUid];
        if (typeof hideMainAnchorTooltip === 'function') {
            hideMainAnchorTooltip();
        }

        if (typeof hideTooltip === 'function') {
            hideTooltip();
        }

        setMainModalInteractivityDisabled(true);

        // Show modal elements
        var body = shapes[addModalUid + '_body'];
        var header = shapes[addModalUid + '_header'];
        var title = shapes[addModalUid + '_title'];
        var closeButton = shapes[addModalUid + '_close_button'];
        var closeText = shapes[addModalUid + '_close_text'];
        var leftBg = shapes[addModalUid + '_left_bg'];
        var leftTitle = shapes[addModalUid + '_left_title'];
        var leftGridBg = shapes[addModalUid + '_left_grid_bg'];
        var rightBg = shapes[addModalUid + '_right_bg'];
        var rightTitle = shapes[addModalUid + '_right_title'];
        var rightGridBg = shapes[addModalUid + '_right_grid_bg'];
        var separator = shapes[addModalUid + '_separator'];

        if (body) body.renderable = true;
        if (header) header.renderable = true;
        if (title) title.renderable = true;
        if (closeButton) closeButton.renderable = true;
        if (closeText) closeText.renderable = true;
        if (leftBg) leftBg.renderable = true;
        if (leftTitle) leftTitle.renderable = true;
        if (leftGridBg) leftGridBg.renderable = true;
        if (rightBg) rightBg.renderable = true;
        if (rightTitle) rightTitle.renderable = true;
        if (rightGridBg) rightGridBg.renderable = true;
        if (separator) separator.renderable = true;

        // Show modal borders
        ['top', 'bottom', 'left', 'right'].forEach(function(side) {
            var border = shapes[addModalUid + '_border_' + side];
            if (border) border.renderable = true;
        });

        // Show grid borders
        ['left', 'right'].forEach(function(prefix) {
            ['top', 'bottom', 'left', 'right'].forEach(function(side) {
                var border = shapes[addModalUid + '_' + prefix + '_grid_border_' + side];
                if (border) border.renderable = true;
            });
        });

        // Populate left grid with the CURRENT assembler grid state
        var bodyPixels = assemblerGridSnapshot.pixels || [];
        var bodyAnchors = assemblerGridSnapshot.anchors || [];

        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cellUid = addModalUid + '_left_cell_' + row + '_' + col;
                var cellShape = shapes[cellUid];
                if (!cellShape) continue;

                cellShape.tint = 0xFFFFFF;

                var pixel = bodyPixels.find(function(p) { return p.x === col && p.y === row; });
                if (pixel) {
                    cellShape.tint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
                }

                var anchor = bodyAnchors.find(function(a) { return a.x === col && a.y === row; });
                if (anchor) {
                    cellShape.tint = 0x0000FF;
                }

                cellShape.renderable = true;
            }
        }

        drawZoneBordersForGrid('left', bodyPixels, 100045);

        // Populate right grid (EntityComponent)
        var componentPixels = selectedComponentData.pixels_json ? JSON.parse(selectedComponentData.pixels_json) : [];
        var componentAnchors = selectedComponentData.anchors || [];

        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cellUid = addModalUid + '_right_cell_' + row + '_' + col;
                var cellShape = shapes[cellUid];
                if (!cellShape) continue;

                cellShape.tint = 0xFFFFFF;

                // Check if this pixel has a component pixel
                var pixel = componentPixels.find(function(p) { return p.x === col && p.y === row; });
                if (pixel) {
                    cellShape.tint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
                }

                // Check if this pixel has an anchor
                var anchor = componentAnchors.find(function(a) { return a.x === col && a.y === row; });
                if (anchor) {
                    cellShape.tint = 0x0000FF;
                }

                cellShape.renderable = true;
            }
        }

        drawZoneBordersForGrid('right', componentPixels, 100045);
        showComponentBadges(selectedComponentData);
    };
})();
</script>
