<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    const viewportUid = modalUid + '_content_viewport';
    const viewportObject = objects[viewportUid];
    if (!viewportObject || !viewportObject.attributes) {
        return;
    }

    const attrs = viewportObject.attributes;
    const childUids = Array.isArray(attrs.scroll_child_uids) ? attrs.scroll_child_uids : [];
    const basePointsMap = (attrs.scroll_base_points && typeof attrs.scroll_base_points === 'object') ? attrs.scroll_base_points : {};
    const initialRenderables = (attrs.scroll_initial_renderables && typeof attrs.scroll_initial_renderables === 'object') ? attrs.scroll_initial_renderables : {};
    if (childUids.length === 0) {
        return;
    }

    function toPixiColor(value, fallback) {
        if (value === null || value === undefined) return fallback;
        if (typeof value === 'number' && Number.isFinite(value)) return value;
        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (trimmed.startsWith('#')) {
                const parsed = parseInt(trimmed.slice(1), 16);
                return Number.isFinite(parsed) ? parsed : fallback;
            }
            if (/^0x[0-9a-f]+$/i.test(trimmed)) {
                const parsed = parseInt(trimmed, 16);
                return Number.isFinite(parsed) ? parsed : fallback;
            }
            if (/^[0-9a-f]{6}$/i.test(trimmed)) {
                const parsed = parseInt(trimmed, 16);
                return Number.isFinite(parsed) ? parsed : fallback;
            }
        }
        return fallback;
    }

    function getPointerPosition(e) {
        if (e && typeof e.clientX === 'number' && typeof e.clientY === 'number') {
            return { x: e.clientX, y: e.clientY };
        }
        if (e && e.data && e.data.originalEvent && typeof e.data.originalEvent.clientX === 'number' && typeof e.data.originalEvent.clientY === 'number') {
            return { x: e.data.originalEvent.clientX, y: e.data.originalEvent.clientY };
        }
        if (e && e.data && e.data.global && typeof e.data.global.x === 'number' && typeof e.data.global.y === 'number') {
            return { x: e.data.global.x, y: e.data.global.y };
        }
        return { x: 0, y: 0 };
    }

    const startPointer = getPointerPosition(typeof event !== 'undefined' ? event : null);

    const currentBaseX = {};
    const currentBaseY = {};
    let currentContentLeft = null;
    let currentContentRight = null;
    let currentContentTop = null;
    let currentContentBottom = null;
    childUids.forEach(function(uid) {
        const obj = objects[uid];
        const isMultiLine = obj && obj.type === 'multi_line' && Array.isArray(basePointsMap[uid]) && basePointsMap[uid].length > 0;
        const initiallyVisible = initialRenderables[uid] === undefined ? true : !!initialRenderables[uid];
        const currentlyVisible = !!(obj && obj.attributes && obj.attributes.renderable);
        const includeInBounds = initiallyVisible || currentlyVisible;

        if (objects[uid] && typeof objects[uid].x === 'number' && typeof objects[uid].y === 'number') {
            currentBaseX[uid] = objects[uid].x;
            currentBaseY[uid] = objects[uid].y;
        } else {
            currentBaseX[uid] = (attrs.scroll_base_positions_x && typeof attrs.scroll_base_positions_x[uid] === 'number')
                ? attrs.scroll_base_positions_x[uid]
                : 0;
            currentBaseY[uid] = (attrs.scroll_base_positions_y && typeof attrs.scroll_base_positions_y[uid] === 'number')
                ? attrs.scroll_base_positions_y[uid]
                : 0;
        }

        let left = currentBaseX[uid];
        let right = currentBaseX[uid];
        let top = currentBaseY[uid];
        let bottom = currentBaseY[uid];

        if (isMultiLine) {
            const xs = basePointsMap[uid].map(function(p) { return Number(p.x) || 0; });
            const ys = basePointsMap[uid].map(function(p) { return Number(p.y) || 0; });
            left = Math.min.apply(null, xs);
            right = Math.max.apply(null, xs);
            top = Math.min.apply(null, ys);
            bottom = Math.max.apply(null, ys);
            currentBaseX[uid] = left;
            currentBaseY[uid] = top;
        } else {
            const itemW = (attrs.scroll_item_widths && typeof attrs.scroll_item_widths[uid] === 'number') ? attrs.scroll_item_widths[uid] : 1;
            const itemH = (attrs.scroll_item_heights && typeof attrs.scroll_item_heights[uid] === 'number') ? attrs.scroll_item_heights[uid] : 1;
            right = currentBaseX[uid] + itemW;
            bottom = currentBaseY[uid] + itemH;
        }

        if (includeInBounds) {
            currentContentLeft = currentContentLeft === null ? left : Math.min(currentContentLeft, left);
            currentContentRight = currentContentRight === null ? right : Math.max(currentContentRight, right);
            currentContentTop = currentContentTop === null ? top : Math.min(currentContentTop, top);
            currentContentBottom = currentContentBottom === null ? bottom : Math.max(currentContentBottom, bottom);
        }
    });

    const state = {
        startPointerX: startPointer.x,
        startPointerY: startPointer.y,
        basePositionsX: currentBaseX,
        basePositionsY: currentBaseY,
        itemWidths: attrs.scroll_item_widths || {},
        itemHeights: attrs.scroll_item_heights || {},
        viewportLeft: attrs.scroll_viewport_left,
        viewportRight: attrs.scroll_viewport_right,
        viewportTop: attrs.scroll_viewport_top,
        viewportBottom: attrs.scroll_viewport_bottom,
        contentLeft: currentContentLeft !== null ? currentContentLeft : attrs.scroll_content_left,
        contentRight: currentContentRight !== null ? currentContentRight : attrs.scroll_content_right,
        contentTop: currentContentTop !== null ? currentContentTop : attrs.scroll_content_top,
        contentBottom: currentContentBottom !== null ? currentContentBottom : attrs.scroll_content_bottom
    };

    function moveItems(deltaX, deltaY) {
        const contentSpanX = state.contentRight - state.contentLeft;
        const viewportSpanX = state.viewportRight - state.viewportLeft;
        const contentSpan = state.contentBottom - state.contentTop;
        const viewportSpan = state.viewportBottom - state.viewportTop;
        let appliedDeltaX = deltaX;
        let appliedDeltaY = deltaY;

        if (contentSpanX > viewportSpanX) {
            const minDeltaX = state.viewportRight - state.contentRight;
            const maxDeltaX = state.viewportLeft - state.contentLeft;
            appliedDeltaX = Math.max(minDeltaX, Math.min(maxDeltaX, deltaX));
        } else {
            appliedDeltaX = 0;
        }

        if (contentSpan > viewportSpan) {
            const minDeltaY = state.viewportBottom - state.contentBottom;
            const maxDeltaY = state.viewportTop - state.contentTop;
            appliedDeltaY = Math.max(minDeltaY, Math.min(maxDeltaY, deltaY));
        } else {
            appliedDeltaY = 0;
        }

        childUids.forEach(function(uid) {
            const baseX = state.basePositionsX[uid];
            const baseY = state.basePositionsY[uid];
            const obj = objects[uid];
            const isMultiLine = obj && obj.type === 'multi_line' && Array.isArray(basePointsMap[uid]) && basePointsMap[uid].length > 0;
            if (typeof baseX !== 'number' || typeof baseY !== 'number') {
                return;
            }

            const newX = baseX + appliedDeltaX;
            const newY = baseY + appliedDeltaY;

            let left = newX;
            let right = newX;
            let top = newY;
            let bottom = newY;

            if (isMultiLine) {
                const translatedPoints = basePointsMap[uid].map(function(p) {
                    return {
                        x: (Number(p.x) || 0) + appliedDeltaX,
                        y: (Number(p.y) || 0) + appliedDeltaY
                    };
                });

                const xs = translatedPoints.map(function(p) { return p.x; });
                const ys = translatedPoints.map(function(p) { return p.y; });
                left = Math.min.apply(null, xs);
                right = Math.max.apply(null, xs);
                top = Math.min.apply(null, ys);
                bottom = Math.max.apply(null, ys);

                if (shapes[uid] && typeof shapes[uid].clear === 'function') {
                    const lineThickness = (obj && typeof obj.thickness === 'number') ? obj.thickness : 1;
                    const lineColor = toPixiColor((obj && obj.color !== undefined && obj.color !== null) ? obj.color : null, 0x000000);
                    shapes[uid].clear();
                    shapes[uid].lineStyle(lineThickness, lineColor);
                    if (translatedPoints.length > 0) {
                        shapes[uid].moveTo(translatedPoints[0].x, translatedPoints[0].y);
                        for (let i = 1; i < translatedPoints.length; i++) {
                            shapes[uid].lineTo(translatedPoints[i].x, translatedPoints[i].y);
                        }
                    }
                }

                if (obj) {
                    obj.points = translatedPoints;
                    obj.x = left;
                    obj.y = top;
                }
            } else {
                const fallbackWidth = (typeof state.itemWidths[uid] === 'number') ? state.itemWidths[uid] : 1;
                const fallbackHeight = (typeof state.itemHeights[uid] === 'number') ? state.itemHeights[uid] : 1;
                const shapeWidth = (shapes[uid] && typeof shapes[uid].width === 'number' && shapes[uid].width > 0) ? shapes[uid].width : fallbackWidth;
                const shapeHeight = (shapes[uid] && typeof shapes[uid].height === 'number' && shapes[uid].height > 0) ? shapes[uid].height : fallbackHeight;
                right = newX + shapeWidth;
                bottom = newY + shapeHeight;

                if (shapes[uid]) {
                    shapes[uid].x = newX;
                    shapes[uid].y = newY;
                }

                if (obj) {
                    obj.x = newX;
                    obj.y = newY;
                }
            }

            const intersectsX = right > state.viewportLeft && left < state.viewportRight;
            const intersectsY = bottom > state.viewportTop && top < state.viewportBottom;
            const isVisible = intersectsX && intersectsY;

            if (shapes[uid]) {
                shapes[uid].renderable = isVisible;
            }

            if (obj) {
                if (obj.attributes) {
                    obj.attributes.renderable = isVisible;
                }
            }
        });
    }

    const moveHandlerName = '__modal_scroll_move_' + modalUid;
    const upHandlerName = '__modal_scroll_up_' + modalUid;
    window['__modal_scroll_drag_' + modalUid] = state;
    window.__disableGlobalPan = true;

    window[moveHandlerName] = function(e) {
        const dragState = window['__modal_scroll_drag_' + modalUid];
        if (!dragState) {
            return;
        }

        const pointer = getPointerPosition(e);
        const pointerX = pointer.x;
        const pointerY = pointer.y;

        const deltaX = pointerX - dragState.startPointerX;
        const deltaY = pointerY - dragState.startPointerY;
        moveItems(deltaX, deltaY);
    };

    window[upHandlerName] = function() {
        window.removeEventListener('pointermove', window[moveHandlerName]);
        window.removeEventListener('pointerup', window[upHandlerName]);
        window['__modal_scroll_drag_' + modalUid] = null;
        window.__disableGlobalPan = false;
    };

    window.addEventListener('pointermove', window[moveHandlerName]);
    window.addEventListener('pointerup', window[upHandlerName]);
};

window['__name__']();

</script>
