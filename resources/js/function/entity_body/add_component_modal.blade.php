<script>
(function() {
    var addModalUid = '__ADD_MODAL_UID__';
    var parentModalUid = '__MODAL_UID__';
    var selectedComponentData = null;
    var baseMainGridPixels = [];
    var baseMainGridPixelsInitialized = false;
    var addedComponents = [];
    var componentListContainer = null;
    var disabledMainModalInteractivity = [];
    var disabledAddModalInteractivity = [];
    var componentBadgesContainer = null;
    var badgeTooltipElement = null;
    var anchorTooltipElement = null;
    var selectedLinkAnchors = { left: null, right: null };
    var linkPreviewLine = null;
    var highlightedComponentIndex = null;
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

    function setAddModalInteractivityDisabled(disabled) {
        if (!disabled) {
            disabledAddModalInteractivity.forEach(function(entry) {
                if (!entry.shape) return;
                entry.shape.eventMode = entry.eventMode;
                entry.shape.cursor = entry.cursor;
            });
            disabledAddModalInteractivity = [];
            return;
        }

        disabledAddModalInteractivity = [];
        Object.keys(shapes).forEach(function(uid) {
            if (uid.indexOf(addModalUid) !== 0) return;
            if (uid.indexOf(addModalUid + '_preview_') === 0) return;

            var shape = shapes[uid];
            if (!shape) return;
            disabledAddModalInteractivity.push({
                shape: shape,
                eventMode: shape.eventMode,
                cursor: shape.cursor
            });
            shape.eventMode = 'none';
            shape.cursor = 'default';
        });
    }

    function ensureAnchorTooltipElement() {
        if (anchorTooltipElement) return anchorTooltipElement;

        anchorTooltipElement = document.createElement('div');
        anchorTooltipElement.style.position = 'absolute';
        anchorTooltipElement.style.zIndex = '130000';
        anchorTooltipElement.style.display = 'none';
        anchorTooltipElement.style.pointerEvents = 'none';
        anchorTooltipElement.style.whiteSpace = 'pre-line';
        anchorTooltipElement.style.background = '#ffffff';
        anchorTooltipElement.style.border = '1px solid #000';
        anchorTooltipElement.style.borderRadius = '6px';
        anchorTooltipElement.style.padding = '6px 8px';
        anchorTooltipElement.style.fontFamily = 'Arial';
        anchorTooltipElement.style.fontSize = '12px';
        anchorTooltipElement.style.color = '#000';
        document.body.appendChild(anchorTooltipElement);
        return anchorTooltipElement;
    }

    function hideAnchorTooltip() {
        if (anchorTooltipElement) {
            anchorTooltipElement.style.display = 'none';
            anchorTooltipElement.textContent = '';
        }
    }

    function closePreviewModal() {
        ['body','title','close_rect','close_text','grid_bg','grid_border_top','grid_border_bottom','grid_border_left','grid_border_right','border_top','border_bottom','border_left','border_right'].forEach(function(suffix) {
            var shape = shapes[addModalUid + '_preview_' + suffix];
            if (shape) shape.renderable = false;
        });
        setAddModalInteractivityDisabled(false);
        if (linkPreviewLine) {
            linkPreviewLine.renderable = true;
        }
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cell = shapes[addModalUid + '_preview_cell_' + row + '_' + col];
                if (cell) cell.renderable = false;
            }
        }
    }

    function openPreviewModal() {
        if (!selectedLinkAnchors.left || !selectedLinkAnchors.right) return;

        if (componentListContainer) componentListContainer.style.display = 'none';
        hideAnchorTooltip();
        hideBadgeTooltip();

        if (typeof hideTooltip === 'function') {
            hideTooltip();
        }

        setAddModalInteractivityDisabled(true);

        if (linkPreviewLine) {
            linkPreviewLine.renderable = false;
        }

        ['body','title','close_rect','close_text','grid_bg','grid_border_top','grid_border_bottom','grid_border_left','grid_border_right','border_top','border_bottom','border_left','border_right'].forEach(function(suffix) {
            var shape = shapes[addModalUid + '_preview_' + suffix];
            if (shape) shape.renderable = true;
        });

        var bodyPixels = window.__addModalBodyPixels || [];
        var componentPixels = window.__addModalComponentPixels || [];
        var dx = selectedLinkAnchors.left.anchor.x - selectedLinkAnchors.right.anchor.x;
        var dy = selectedLinkAnchors.left.anchor.y - selectedLinkAnchors.right.anchor.y;

        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cell = shapes[addModalUid + '_preview_cell_' + row + '_' + col];
                if (!cell) continue;
                cell.tint = 0xFFFFFF;
                cell.renderable = true;
            }
        }

        bodyPixels.forEach(function(pixel) {
            var cell = shapes[addModalUid + '_preview_cell_' + pixel.y + '_' + pixel.x];
            if (cell) cell.tint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
        });

        componentPixels.forEach(function(pixel) {
            var targetX = pixel.x + dx;
            var targetY = pixel.y + dy;
            if (targetX < 0 || targetX > 31 || targetY < 0 || targetY > 31) return;
            var cell = shapes[addModalUid + '_preview_cell_' + targetY + '_' + targetX];
            if (cell) cell.tint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
        });
    }

    function clearLinkPreview() {
        selectedLinkAnchors = { left: null, right: null };
        if (linkPreviewLine) {
            if (linkPreviewLine.parent) linkPreviewLine.parent.removeChild(linkPreviewLine);
            if (typeof linkPreviewLine.destroy === 'function') linkPreviewLine.destroy();
            linkPreviewLine = null;
        }
        ['confirm_button_rect', 'confirm_button_text', 'preview_button_rect', 'preview_button_text', 'cancel_button_rect', 'cancel_button_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + '_' + suffix];
            if (shape) shape.renderable = false;
            if (objects[addModalUid + '_' + suffix] && objects[addModalUid + '_' + suffix].attributes) {
                objects[addModalUid + '_' + suffix].attributes.renderable = false;
            }
        });
    }

    function drawLinkPreviewLine() {
        if (!selectedLinkAnchors.left || !selectedLinkAnchors.right) return;

        if (linkPreviewLine) {
            if (linkPreviewLine.parent) linkPreviewLine.parent.removeChild(linkPreviewLine);
            if (typeof linkPreviewLine.destroy === 'function') linkPreviewLine.destroy();
        }

        var x1 = selectedLinkAnchors.left.x;
        var y1 = selectedLinkAnchors.left.y;
        var x2 = selectedLinkAnchors.right.x;
        var y2 = selectedLinkAnchors.right.y;
        var dx = x2 - x1;
        var dy = y2 - y1;
        var distance = Math.sqrt((dx * dx) + (dy * dy));
        var dashLength = 8;
        var gapLength = 6;
        var step = dashLength + gapLength;

        var line = new PIXI.Graphics();
        line.lineStyle(2, 0x333333, 1);
        for (var traveled = 0; traveled < distance; traveled += step) {
            var startRatio = traveled / distance;
            var endRatio = Math.min(traveled + dashLength, distance) / distance;
            var sx = x1 + (dx * startRatio);
            var sy = y1 + (dy * startRatio);
            var ex = x1 + (dx * endRatio);
            var ey = y1 + (dy * endRatio);
            line.moveTo(sx, sy);
            line.lineTo(ex, ey);
        }
        line.zIndex = 125000;
        app.stage.sortableChildren = true;
        app.stage.addChild(line);
        linkPreviewLine = line;

        ['confirm_button_rect', 'confirm_button_text', 'preview_button_rect', 'preview_button_text', 'cancel_button_rect', 'cancel_button_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + '_' + suffix];
            if (shape) shape.renderable = true;
            if (objects[addModalUid + '_' + suffix] && objects[addModalUid + '_' + suffix].attributes) {
                objects[addModalUid + '_' + suffix].attributes.renderable = true;
            }
        });
    }

    function setupAnchorInteractions(side, anchors) {
        (anchors || []).forEach(function(anchor) {
            var cellUid = addModalUid + '_' + side + '_cell_' + anchor.y + '_' + anchor.x;
            var cellShape = shapes[cellUid];
            if (!cellShape) return;

            cellShape.eventMode = 'static';
            cellShape.cursor = 'pointer';
            if (typeof cellShape.removeAllListeners === 'function') {
                cellShape.removeAllListeners('pointerover');
                cellShape.removeAllListeners('pointerout');
                cellShape.removeAllListeners('pointerdown');
            }

            cellShape.on('pointerover', function() {
                var tooltip = ensureAnchorTooltipElement();
                var rect = app.renderer.view.getBoundingClientRect();
                tooltip.textContent = '#' + anchor.id + ' (X: ' + anchor.x + ' - Y: ' + anchor.y + ')';
                tooltip.style.left = (rect.left + cellShape.x) + 'px';
                tooltip.style.top = (rect.top + cellShape.y - 28) + 'px';
                tooltip.style.display = 'block';
            });

            cellShape.on('pointerout', function() {
                hideAnchorTooltip();
            });

            cellShape.on('pointerdown', function() {
                selectedLinkAnchors[side] = {
                    id: anchor.id,
                    x: cellShape.x + (cellShape.width / 2),
                    y: cellShape.y + (cellShape.height / 2),
                    side: side,
                    anchor: anchor
                };

                if (selectedLinkAnchors.left && selectedLinkAnchors.right) {
                    drawLinkPreviewLine();
                }
            });
        });
    }

    function getBaseMainGridSnapshot() {
        return baseMainGridPixels.slice();
    }

    function setComponentHighlight(index) {
        highlightedComponentIndex = typeof index === 'number' ? index : null;
        redrawAddedComponentsOnMainGrid();
    }

    function updateSaveButtonVisibility(forceVisible) {
        var visible = typeof forceVisible === 'boolean' ? forceVisible : addedComponents.length > 0;
        ['_save_button_rect', '_save_button_text'].forEach(function(suffix) {
            var shape = shapes[parentModalUid + suffix];
            if (shape) shape.renderable = visible;
            if (objects[parentModalUid + suffix] && objects[parentModalUid + suffix].attributes) {
                objects[parentModalUid + suffix].attributes.renderable = visible;
            }
        });
    }

    function clearAddedComponents() {
        addedComponents = [];
        highlightedComponentIndex = null;
        redrawAddedComponentsOnMainGrid();
        renderAddedComponentsList();
        updateSaveButtonVisibility();
    }

    window['resetAddComponentState_' + parentModalUid] = function() {
        addedComponents = [];
        highlightedComponentIndex = null;
        hideComponentBadges();
        hideAnchorTooltip();
        hideBadgeTooltip();
        clearLinkPreview();
        closePreviewModal();
        if (componentListContainer) {
            componentListContainer.style.display = 'none';
            componentListContainer.innerHTML = '';
        }
        updateSaveButtonVisibility(false);
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var cell = shapes[parentModalUid + '_grid_cell_' + row + '_' + col];
                if (cell) {
                    cell.tint = 0xFFFFFF;
                }
            }
        }
        baseMainGridPixels = [];
        baseMainGridPixelsInitialized = false;
    };

    function ensureComponentListContainer() {
        if (componentListContainer) return componentListContainer;

        componentListContainer = document.createElement('div');
        componentListContainer.style.position = 'absolute';
        componentListContainer.style.zIndex = '1000';
        componentListContainer.style.display = 'none';
        componentListContainer.style.background = 'transparent';
        componentListContainer.style.border = 'none';
        componentListContainer.style.borderRadius = '0';
        componentListContainer.style.padding = '0';
        componentListContainer.style.boxSizing = 'border-box';
        componentListContainer.style.minWidth = '260px';
        document.body.appendChild(componentListContainer);
        return componentListContainer;
    }

    function renderAddedComponentsList() {
        var container = ensureComponentListContainer();
        var gridBg = shapes[parentModalUid + '_grid_bg'];
        var canvasRect = app.renderer.view.getBoundingClientRect();
        if (!gridBg || !addedComponents.length) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        container.style.left = (canvasRect.left + gridBg.x + gridBg.width + 8) + 'px';
        container.style.top = (canvasRect.top + gridBg.y + 20) + 'px';
        var rows = addedComponents.map(function(item, index) {
            return '<tr data-component-row-index="' + index + '">' +
                '<td style="padding:6px 8px;border-bottom:1px solid #ddd;">' + item.name + '</td>' +
                '<td style="padding:6px 8px;border-bottom:1px solid #ddd;text-align:right;">' +
                '<button type="button" data-remove-component-index="' + index + '" style="background:#c82333;color:#fff;border:1px solid #000;border-radius:4px;padding:2px 8px;cursor:pointer;">X</button>' +
                '</td>' +
                '</tr>';
        }).join('');
        container.innerHTML = '<div style="font-family:Arial;font-size:14px;font-weight:bold;margin-bottom:8px;">Componenti aggiunti</div>' +
            '<table style="border-collapse:collapse;width:100%;font-family:Arial;font-size:12px;border:1px solid #000;background:#fff;"><tbody>' + rows + '</tbody></table>';
        container.style.display = 'block';

        Array.prototype.forEach.call(container.querySelectorAll('[data-component-row-index]'), function(row) {
            row.onmouseenter = function() {
                var index = parseInt(row.getAttribute('data-component-row-index'), 10);
                if (!isNaN(index)) {
                    setComponentHighlight(index);
                }
            };
            row.onmouseleave = function() {
                setComponentHighlight(null);
            };
        });

        Array.prototype.forEach.call(container.querySelectorAll('[data-remove-component-index]'), function(btn) {
            btn.onclick = function(event) {
                event.stopPropagation();
                var index = parseInt(btn.getAttribute('data-remove-component-index'), 10);
                if (!isNaN(index)) {
                    if (highlightedComponentIndex === index) {
                        highlightedComponentIndex = null;
                    }
                    addedComponents.splice(index, 1);
                    redrawAddedComponentsOnMainGrid();
                    renderAddedComponentsList();
                    updateSaveButtonVisibility();
                }
            };
        });
    }

    function redrawAddedComponentsOnMainGrid() {
        for (var row = 0; row < 32; row++) {
            for (var col = 0; col < 32; col++) {
                var resetCell = shapes[parentModalUid + '_grid_cell_' + row + '_' + col];
                if (resetCell) {
                    resetCell.tint = 0xFFFFFF;
                }
            }
        }

        if (!baseMainGridPixelsInitialized) {
            return;
        }

        baseMainGridPixels.forEach(function(pixel) {
            var baseCell = shapes[parentModalUid + '_grid_cell_' + pixel.y + '_' + pixel.x];
            if (baseCell) {
                baseCell.tint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
            }
        });

        addedComponents.forEach(function(item, itemIndex) {
            (item.pixels || []).forEach(function(pixel) {
                var targetX = pixel.x + item.dx;
                var targetY = pixel.y + item.dy;
                if (targetX < 0 || targetX > 31 || targetY < 0 || targetY > 31) return;
                var cell = shapes[parentModalUid + '_grid_cell_' + targetY + '_' + targetX];
                if (cell) {
                    var componentTint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
                    var baseTint = typeof cell.tint === 'number' ? cell.tint : 0xFFFFFF;
                    var tint = baseTint === 0xFFFFFF ? componentTint : baseTint;
                    if (highlightedComponentIndex === itemIndex) {
                        var r = (tint >> 16) & 255;
                        var g = (tint >> 8) & 255;
                        var b = tint & 255;
                        r = Math.min(255, Math.round((r * 0.65) + (255 * 0.35)));
                        g = Math.min(255, Math.round((g * 0.65) + (213 * 0.35)));
                        b = Math.min(255, Math.round((b * 0.65) + (79 * 0.35)));
                        tint = (r << 16) | (g << 8) | b;
                    }
                    cell.tint = tint;
                }
            });
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
        var instructionText = shapes[addModalUid + '_instruction_text'];
        if (instructionText) instructionText.renderable = false;
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
        hideAnchorTooltip();
        clearLinkPreview();
        closePreviewModal();
        renderAddedComponentsList();
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

        hideAnchorTooltip();
        clearLinkPreview();
        closePreviewModal();
        if (componentListContainer) componentListContainer.style.display = 'none';
        setMainModalInteractivityDisabled(true);
        updateSaveButtonVisibility(addedComponents.length > 0);

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
        var instructionText = shapes[addModalUid + '_instruction_text'];
        if (instructionText) instructionText.renderable = true;
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

        var confirmAction = function() {
            console.log('conferma link anchor', selectedLinkAnchors);
            if (!selectedLinkAnchors.left || !selectedLinkAnchors.right || !selectedComponentData) return;
            var dx = selectedLinkAnchors.left.anchor.x - selectedLinkAnchors.right.anchor.x;
            var dy = selectedLinkAnchors.left.anchor.y - selectedLinkAnchors.right.anchor.y;
            addedComponents.push({
                id: selectedComponentData.id || null,
                name: selectedComponentData.name || 'Componente',
                pixels: window.__addModalComponentPixels || [],
                dx: dx,
                dy: dy,
                body_anchor: selectedLinkAnchors.left.anchor,
                component_anchor: selectedLinkAnchors.right.anchor
            });
            redrawAddedComponentsOnMainGrid();
            renderAddedComponentsList();
            updateSaveButtonVisibility();
            window['closeAddComponentModal_' + parentModalUid]();
        };
        var previewAction = function() {
            console.log('preview link anchor', selectedLinkAnchors);
            openPreviewModal();
        };
        var cancelAction = function() { clearLinkPreview(); };
        ['_confirm_button_rect', '_confirm_button_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + suffix];
            if (shape) {
                shape.eventMode = 'static';
                shape.cursor = 'pointer';
                if (typeof shape.removeAllListeners === 'function') shape.removeAllListeners('pointerdown');
                shape.on('pointerdown', confirmAction);
            }
        });
        ['_preview_button_rect', '_preview_button_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + suffix];
            if (shape) {
                shape.eventMode = 'static';
                shape.cursor = 'pointer';
                if (typeof shape.removeAllListeners === 'function') shape.removeAllListeners('pointerdown');
                shape.on('pointerdown', previewAction);
            }
        });
        ['_cancel_button_rect', '_cancel_button_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + suffix];
            if (shape) {
                shape.eventMode = 'static';
                shape.cursor = 'pointer';
                if (typeof shape.removeAllListeners === 'function') shape.removeAllListeners('pointerdown');
                shape.on('pointerdown', cancelAction);
            }
        });

        // Populate left grid with a real copy of the CURRENT main assembler grid state
        if (!baseMainGridPixelsInitialized) {
            baseMainGridPixels = [];
        }
        var bodyPixels = [];
        var bodyAnchors = assemblerGridSnapshot.anchors || [];
        var snapshotPixels = assemblerGridSnapshot.pixels || [];

        for (var sourceRow = 0; sourceRow < 32; sourceRow++) {
            for (var sourceCol = 0; sourceCol < 32; sourceCol++) {
                var mainCellUid = parentModalUid + '_grid_cell_' + sourceRow + '_' + sourceCol;
                var mainCellShape = shapes[mainCellUid];
                if (!mainCellShape) continue;
                if (mainCellShape.tint === 0xFFFFFF) continue;

                var existingPixel = snapshotPixels.find(function(p) {
                    return p.x === sourceCol && p.y === sourceRow;
                });

                var copiedPixel = {
                    x: sourceCol,
                    y: sourceRow,
                    tint: mainCellShape.tint,
                    has_zone: existingPixel ? !!existingPixel.has_zone : false,
                    zone_border_top: existingPixel ? !!existingPixel.zone_border_top : false,
                    zone_border_bottom: existingPixel ? !!existingPixel.zone_border_bottom : false,
                    zone_border_left: existingPixel ? !!existingPixel.zone_border_left : false,
                    zone_border_right: existingPixel ? !!existingPixel.zone_border_right : false,
                    zone_color: existingPixel ? existingPixel.zone_color : null,
                    zone_name: existingPixel ? existingPixel.zone_name : null,
                    zone_id: existingPixel && typeof existingPixel.zone_id !== 'undefined' ? existingPixel.zone_id : null
                };

                bodyPixels.push(copiedPixel);
                if (!baseMainGridPixelsInitialized) {
                    baseMainGridPixels.push({
                        x: copiedPixel.x,
                        y: copiedPixel.y,
                        tint: copiedPixel.tint,
                        zone_name: copiedPixel.zone_name,
                        zone_color: copiedPixel.zone_color,
                        zone_id: copiedPixel.zone_id
                    });
                }


            }
        }

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

        baseMainGridPixelsInitialized = true;
        window.__addModalBodyPixels = bodyPixels;
        drawZoneBordersForGrid('left', bodyPixels, 100045);
        setupAnchorInteractions('left', bodyAnchors);

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

        window.__addModalComponentPixels = componentPixels;
        drawZoneBordersForGrid('right', componentPixels, 100045);
        setupAnchorInteractions('right', componentAnchors);
        showComponentBadges(selectedComponentData);

        ['_preview_close_rect', '_preview_close_text'].forEach(function(suffix) {
            var shape = shapes[addModalUid + suffix];
            if (shape) {
                shape.eventMode = 'static';
                shape.cursor = 'pointer';
                if (typeof shape.removeAllListeners === 'function') shape.removeAllListeners('pointerdown');
                shape.on('pointerdown', closePreviewModal);
            }
        });

        var saveAction = function() {
            var selectedBodyUid = window['__selectedBodyUid_' + parentModalUid];
            var bodyObj = selectedBodyUid ? objects[selectedBodyUid] : null;
            var bodyData = bodyObj && bodyObj.attributes && bodyObj.attributes.cell_data ? JSON.parse(bodyObj.attributes.cell_data) : null;
            var zoneRgbMap = {};
            (baseMainGridPixels || []).forEach(function(pixel) {
                if (!pixel.zone_name && !pixel.zone_color) return;
                var r = (pixel.tint >> 16) & 255;
                var g = (pixel.tint >> 8) & 255;
                var b = pixel.tint & 255;
                var key = (typeof pixel.zone_id !== 'undefined' ? pixel.zone_id : '') + '|' + (pixel.zone_name || pixel.zone_color || '');
                if (!zoneRgbMap[key]) {
                    zoneRgbMap[key] = {
                        zone_id: typeof pixel.zone_id !== 'undefined' ? pixel.zone_id : null,
                        zone: pixel.zone_name || null,
                        rgb: r + ',' + g + ',' + b
                    };
                }
            });

            var pixelsMap = {};
            (baseMainGridPixels || []).forEach(function(pixel) {
                if (!pixel || typeof pixel.x === 'undefined' || typeof pixel.y === 'undefined') return;
                var baseTint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
                if (baseTint === 0xFFFFFF) return;
                pixelsMap[pixel.x + '|' + pixel.y] = {
                    x: pixel.x,
                    y: pixel.y,
                    tint: baseTint
                };
            });
            addedComponents.forEach(function(item) {
                (item.pixels || []).forEach(function(pixel) {
                    var targetX = pixel.x + item.dx;
                    var targetY = pixel.y + item.dy;
                    if (targetX < 0 || targetX > 31 || targetY < 0 || targetY > 31) return;
                    var componentTint = typeof pixel.tint === 'number' ? pixel.tint : 0x000000;
                    var key = targetX + '|' + targetY;
                    if (!pixelsMap[key] || pixelsMap[key].tint === 0xFFFFFF) {
                        pixelsMap[key] = {
                            x: targetX,
                            y: targetY,
                            tint: componentTint
                        };
                    }
                });
            });

            var payload = {
                body_selected: bodyData ? { id: bodyData.id || null, name: bodyData.name || null } : null,
                zones_rgb: Object.values(zoneRgbMap),
                pixels: Object.values(pixelsMap).map(function(pixel) {
                    var r = (pixel.tint >> 16) & 255;
                    var g = (pixel.tint >> 8) & 255;
                    var b = pixel.tint & 255;
                    return {
                        x: pixel.x,
                        y: pixel.y,
                        rgb: r + ',' + g + ',' + b
                    };
                }),
                components: addedComponents.map(function(item) {
                    return {
                        id: item.id,
                        name: item.name,
                        link_to_body: {
                            body_anchor: item.body_anchor,
                            component_anchor: item.component_anchor
                        }
                    };
                })
            };
            var payloadJson = JSON.stringify(payload);
            console.log(payloadJson);

            var assemblerButtonUid = parentModalUid.replace('objective_modal_assembler_', '');
            var jsonOutputText = shapes[assemblerButtonUid + '_json_output_value_text'];
            if (jsonOutputText) {
                jsonOutputText.text = payloadJson;
            }
            if (objects[assemblerButtonUid + '_json_output_body_input'] && objects[assemblerButtonUid + '_json_output_body_input'].attributes) {
                objects[assemblerButtonUid + '_json_output_body_input'].attributes.value = payloadJson;
            }

            var testInputText = shapes['assembler_form_json_value_text'];
            if (testInputText) {
                testInputText.text = payloadJson;
            }
            if (objects['assembler_form_json_body_input'] && objects['assembler_form_json_body_input'].attributes) {
                objects['assembler_form_json_body_input'].attributes.value = payloadJson;
            }
            var assemblerSquare = shapes[assemblerButtonUid + '_square'];
            if (assemblerSquare) {
                assemblerSquare.tint = 0x00FF00;
            }
            if (objects[assemblerButtonUid + '_square']) {
                objects[assemblerButtonUid + '_square'].color = 0x00FF00;
            }
            ['_reset_rect', '_reset_text'].forEach(function(suffix) {
                var resetShape = shapes[assemblerButtonUid + suffix];
                if (resetShape) {
                    resetShape.renderable = true;
                }
                if (objects[assemblerButtonUid + suffix] && objects[assemblerButtonUid + suffix].attributes) {
                    objects[assemblerButtonUid + suffix].attributes.renderable = true;
                }
            });
            window['assemblerSaved_' + parentModalUid] = true;

            var closeButtonObj = objects[parentModalUid + '_close_button'];
            var closeScript = closeButtonObj && closeButtonObj.attributes && closeButtonObj.attributes.interactives && closeButtonObj.attributes.interactives.items
                ? closeButtonObj.attributes.interactives.items.pointerdown
                : null;
            if (typeof closeScript === 'string' && closeScript.length > 0) {
                try {
                    eval(closeScript);
                } catch (error) {
                    console.error(error);
                }
            }
        };

        window['saveAssemblerComponents_' + parentModalUid] = saveAction;

        ['_save_button_rect', '_save_button_text'].forEach(function(suffix) {
            var saveShape = shapes[parentModalUid + suffix];
            if (saveShape) {
                saveShape.eventMode = 'static';
                saveShape.cursor = 'pointer';
            }
        });

        updateSaveButtonVisibility();

        ['_back_button_rect', '_back_button_text'].forEach(function(suffix) {
            var backShape = shapes[parentModalUid + suffix];
            if (backShape) {
                if (typeof backShape.removeAllListeners === 'function') {
                    backShape.removeAllListeners('pointerdown');
                }
                backShape.eventMode = 'static';
                backShape.cursor = 'pointer';
                backShape.on('pointerdown', function() {
                    clearAddedComponents();
                    var backHandler = objects[parentModalUid + suffix] && objects[parentModalUid + suffix].attributes && objects[parentModalUid + suffix].attributes.interactives && objects[parentModalUid + suffix].attributes.interactives.items
                        ? objects[parentModalUid + suffix].attributes.interactives.items.pointerdown
                        : null;
                    if (typeof backHandler === 'string') {
                        try {
                            eval(backHandler);
                        } catch (error) {
                            console.error(error);
                        }
                    }
                });
            }
        });
    };
})();
</script>
