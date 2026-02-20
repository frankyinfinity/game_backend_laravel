<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    if (typeof AppData !== 'undefined') {
        if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
            AppData.open_modals = {};
        }
        AppData.open_modals[modalUid] = true;
    }

    // Re-center modal on open except objective modal, which has a fixed origin.
    const shouldRecenter = !(typeof modalUid === 'string' && modalUid.startsWith('objective_modal_'));

    // Re-center modal on every open using current renderer/window size.
    const bodyUid = modalUid + '_body';
    const headerUid = modalUid + '_header';
    const titleUid = modalUid + '_title';
    const closeBtnUid = modalUid + '_close_button';
    const closeTextUid = modalUid + '_close_text';
    const viewportUid = modalUid + '_content_viewport';
    const bodyObject = objects[bodyUid];
    const viewportObjectForCenter = objects[viewportUid];
    const renderW = (typeof app !== 'undefined' && app && app.renderer && app.renderer.width) ? app.renderer.width : ((typeof window !== 'undefined' && window.innerWidth) ? window.innerWidth : 1920);
    const renderH = (typeof app !== 'undefined' && app && app.renderer && app.renderer.height) ? app.renderer.height : ((typeof window !== 'undefined' && window.innerHeight) ? window.innerHeight : 1080);
    if (shouldRecenter && bodyObject && typeof bodyObject.width === 'number' && typeof bodyObject.height === 'number') {
        const targetX = Math.max(0, Math.floor((renderW - bodyObject.width) / 2));
        const targetY = Math.max(0, Math.floor((renderH - bodyObject.height) / 2));
        const deltaX = targetX - (bodyObject.x || 0);
        const deltaY = targetY - (bodyObject.y || 0);

        const shiftUid = function(uid, dx, dy) {
            if (!uid || (dx === 0 && dy === 0)) return;
            const isMultiLine = !!(objects[uid] && objects[uid].type === 'multi_line');
            if (objects[uid]) {
                if (typeof objects[uid].x === 'number') objects[uid].x += dx;
                if (typeof objects[uid].y === 'number') objects[uid].y += dy;
                if (isMultiLine && Array.isArray(objects[uid].points)) {
                    objects[uid].points = objects[uid].points.map(function(p) {
                        return { x: (Number(p.x) || 0) + dx, y: (Number(p.y) || 0) + dy };
                    });
                }
            }
            if (shapes[uid]) {
                if (!isMultiLine) {
                    if (typeof shapes[uid].x === 'number') shapes[uid].x += dx;
                    if (typeof shapes[uid].y === 'number') shapes[uid].y += dy;
                }
                if (isMultiLine && Array.isArray(objects[uid].points) && typeof shapes[uid].clear === 'function') {
                    // Important: keep Graphics at origin, move only points.
                    shapes[uid].x = 0;
                    shapes[uid].y = 0;
                    const lineThickness = (objects[uid] && typeof objects[uid].thickness === 'number') ? objects[uid].thickness : 1;
                    let lineColor = 0x000000;
                    const rawColor = objects[uid] ? objects[uid].color : null;
                    if (typeof rawColor === 'number') lineColor = rawColor;
                    else if (typeof rawColor === 'string') {
                        const c = rawColor.trim();
                        if (c.startsWith('#')) lineColor = parseInt(c.slice(1), 16) || 0x000000;
                        else if (/^0x[0-9a-f]+$/i.test(c)) lineColor = parseInt(c, 16) || 0x000000;
                        else if (/^[0-9a-f]{6}$/i.test(c)) lineColor = parseInt(c, 16) || 0x000000;
                    }
                    shapes[uid].clear();
                    shapes[uid].lineStyle(lineThickness, lineColor);
                    const pts = objects[uid].points;
                    if (pts.length > 0) {
                        shapes[uid].moveTo(pts[0].x, pts[0].y);
                        for (let i = 1; i < pts.length; i++) {
                            shapes[uid].lineTo(pts[i].x, pts[i].y);
                        }
                    }
                }
            }
        };

        if (deltaX !== 0 || deltaY !== 0) {
            [bodyUid, headerUid, titleUid, closeBtnUid, closeTextUid, viewportUid].forEach(function(uid) {
                shiftUid(uid, deltaX, deltaY);
            });

            if (viewportObjectForCenter && viewportObjectForCenter.attributes) {
                if (Array.isArray(viewportObjectForCenter.attributes.scroll_child_uids)) {
                    viewportObjectForCenter.attributes.scroll_child_uids.forEach(function(uid) {
                        shiftUid(uid, deltaX, deltaY);
                    });
                }

                const shiftMap = function(mapName) {
                    const map = viewportObjectForCenter.attributes[mapName];
                    if (!map || typeof map !== 'object') return;
                    Object.keys(map).forEach(function(k) {
                        if (typeof map[k] === 'number') map[k] += (mapName.indexOf('_x') !== -1 ? deltaX : deltaY);
                    });
                };
                shiftMap('scroll_base_positions_x');
                shiftMap('scroll_base_positions_y');
                if (viewportObjectForCenter.attributes.scroll_base_points && typeof viewportObjectForCenter.attributes.scroll_base_points === 'object') {
                    Object.keys(viewportObjectForCenter.attributes.scroll_base_points).forEach(function(k) {
                        const pts = viewportObjectForCenter.attributes.scroll_base_points[k];
                        if (!Array.isArray(pts)) return;
                        viewportObjectForCenter.attributes.scroll_base_points[k] = pts.map(function(p) {
                            return { x: (Number(p.x) || 0) + deltaX, y: (Number(p.y) || 0) + deltaY };
                        });
                    });
                }
                ['scroll_viewport_left', 'scroll_viewport_right', 'scroll_content_left', 'scroll_content_right'].forEach(function(k) {
                    if (typeof viewportObjectForCenter.attributes[k] === 'number') viewportObjectForCenter.attributes[k] += deltaX;
                });
                ['scroll_viewport_top', 'scroll_viewport_bottom', 'scroll_content_top', 'scroll_content_bottom'].forEach(function(k) {
                    if (typeof viewportObjectForCenter.attributes[k] === 'number') viewportObjectForCenter.attributes[k] += deltaY;
                });
            }
        }
    }

    const idsToAlwaysShow = [
        bodyUid,
        headerUid,
        titleUid,
        closeBtnUid,
        closeTextUid,
        viewportUid,
    ];

    idsToAlwaysShow.forEach(function(uid) {
        if (shapes[uid]) {
            shapes[uid].renderable = true;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = true;
        }
    });

    const viewportObject = objects[viewportUid];
    if (viewportObject && viewportObject.attributes && Array.isArray(viewportObject.attributes.scroll_child_uids)) {
        const initialRenderables = (viewportObject.attributes.scroll_initial_renderables && typeof viewportObject.attributes.scroll_initial_renderables === 'object')
            ? viewportObject.attributes.scroll_initial_renderables
            : {};
        const isPanelUid = function(uid) {
            return typeof uid === 'string' && uid.indexOf('_container_panel') !== -1;
        };

        viewportObject.attributes.scroll_child_uids.forEach(function(uid) {
            const shouldShow = initialRenderables[uid] === undefined ? true : !!initialRenderables[uid];
            const panelVisible = !!(objects[uid] && objects[uid].attributes && objects[uid].attributes.renderable);
            const finalShow = isPanelUid(uid) ? panelVisible : shouldShow;
            if (shapes[uid]) {
                shapes[uid].renderable = finalShow;
            }
            if (objects[uid] && objects[uid].attributes) {
                objects[uid].attributes.renderable = finalShow;
            }
        });
    }

    if (typeof window.refreshAllModalViewportMasks === 'function') {
        window.refreshAllModalViewportMasks();
    }
    if (typeof window.reapplyOpenModalsState === 'function') {
        window.reapplyOpenModalsState();
    }

    // Defensive cleanup: stale listeners can block subsequent drags.
    const moveHandlerName = '__modal_scroll_move_' + modalUid;
    const upHandlerName = '__modal_scroll_up_' + modalUid;
    if (window[moveHandlerName]) {
        window.removeEventListener('pointermove', window[moveHandlerName]);
    }
    if (window[upHandlerName]) {
        window.removeEventListener('pointerup', window[upHandlerName]);
        window.removeEventListener('pointercancel', window[upHandlerName]);
        window.removeEventListener('mouseup', window[upHandlerName]);
        window.removeEventListener('blur', window[upHandlerName]);
    }
    window['__modal_scroll_drag_' + modalUid] = null;

    if (typeof modalUid === 'string' && modalUid.startsWith('objective_modal_')) {
        let resolvedPlayerId = null;
        if (typeof playerId !== 'undefined') {
            resolvedPlayerId = playerId;
        } else if (typeof window !== 'undefined' && typeof window.playerId !== 'undefined') {
            resolvedPlayerId = window.playerId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.player_id !== 'undefined') {
            resolvedPlayerId = AppData.player_id;
        }

        let resolvedSessionId = null;
        if (typeof sessionId !== 'undefined') {
            resolvedSessionId = sessionId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.session_id !== 'undefined') {
            resolvedSessionId = AppData.session_id;
        }

        if (resolvedPlayerId) {
            $.ajax({
                url: `${BACK_URL}/api/auth/game/objective/modal_visibility`,
                type: 'POST',
                data: {
                    player_id: resolvedPlayerId,
                    session_id: resolvedSessionId,
                    modal_uid: modalUid,
                    renderable: true
                }
            });
        }
    }

    window.__disableGlobalPan = true;
};

window['__name__']();

</script>
