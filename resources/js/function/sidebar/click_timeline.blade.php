<script>
window['__name__'] = function() {
    const playerId = __PLAYER_ID__;
    const modalUid = 'timeline_evolutiva_modal';

    // ===== addElementsToModal_<modalUid> =====
    // The generic renderer lives in js/function/modal/add_elements_to_modal.blade.php
    // and is inlined below by SidebarDraw via the __INCLUDE_ADD_ELEMENTS_TO_MODAL__ marker.
    const addElementsFunctionName = 'addElementsToModal_' + modalUid;
    // __INCLUDE_ADD_ELEMENTS_TO_MODAL__
    window[addElementsFunctionName] = window.__createAddElementsToModalRenderer(modalUid, 'Timeline');

    // Initialize array for this modal
    if (!window.modalDynamicElements) window.modalDynamicElements = {};
    window.modalDynamicElements[modalUid] = [];

    // ===== 1) Close the sidebar =====
    const sidebarBackgroundUid = 'appbar_' + playerId + '_sidebar_background';
    const sidebarButtonUids = [
        'appbar_' + playerId + '_sidebar_objectives_button_rect',
        'appbar_' + playerId + '_sidebar_objectives_button_text',
        'appbar_' + playerId + '_sidebar_timeline_button_rect',
        'appbar_' + playerId + '_sidebar_timeline_button_text',
        'appbar_' + playerId + '_sidebar_logout_button_rect',
        'appbar_' + playerId + '_sidebar_logout_button_text'
    ];

    const sidebarBackground = shapes[sidebarBackgroundUid];
    if (sidebarBackground && sidebarBackground.renderable) {
        const targetX = -300;
        const startX = sidebarBackground.x;
        const duration = 300;
        const startTime = performance.now();

        const animateSidebar = function(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            sidebarBackground.x = startX + (targetX - startX) * easeProgress;

            if (progress < 1) {
                requestAnimationFrame(animateSidebar);
            } else {
                sidebarBackground.renderable = false;
                sidebarButtonUids.forEach(function(uid) {
                    if (shapes[uid]) shapes[uid].renderable = false;
                });
                sidebarBackground.x = targetX;

                // Stop PlayerValue polling for sidebar
                if (typeof AppData !== 'undefined' && AppData._playerValuePollingIntervals && AppData._playerValuePollingIntervals['sidebar_' + playerId]) {
                    clearInterval(AppData._playerValuePollingIntervals['sidebar_' + playerId]);
                    delete AppData._playerValuePollingIntervals['sidebar_' + playerId];
                    console.log('[PlayerValue Polling] Stopped for sidebar (timeline clicked)');
                }
            }
        };

        requestAnimationFrame(animateSidebar);
    }

    // ===== 2) Open the timeline modal =====
    if (typeof AppData !== 'undefined') {
        if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
            AppData.open_modals = {};
        }
        AppData.open_modals[modalUid] = true;
    }

    // Remove previously drawn dynamic elements: the modal must open empty.
    const cleanupViewportUid = modalUid + '_content_viewport';
    const cleanupViewportObject = objects[cleanupViewportUid];
    const dynamicUids = (window.modalDynamicElements && Array.isArray(window.modalDynamicElements[modalUid]))
        ? window.modalDynamicElements[modalUid]
        : [];
    dynamicUids.forEach(function(uid) {
        if (shapes[uid]) {
            if (shapes[uid].parent) {
                shapes[uid].parent.removeChild(shapes[uid]);
            }
            if (typeof shapes[uid].destroy === 'function') {
                shapes[uid].destroy();
            }
            delete shapes[uid];
        }
        delete objects[uid];
        if (cleanupViewportObject && cleanupViewportObject.attributes) {
            const attrs = cleanupViewportObject.attributes;
            if (Array.isArray(attrs.scroll_child_uids)) {
                attrs.scroll_child_uids = attrs.scroll_child_uids.filter(function(c) { return c !== uid; });
            }
            ['scroll_base_positions_x', 'scroll_base_positions_y', 'scroll_item_widths', 'scroll_item_heights', 'scroll_initial_renderables'].forEach(function(k) {
                if (attrs[k]) delete attrs[k][uid];
            });
        }
    });
    window.modalDynamicElements[modalUid] = [];
    if (cleanupViewportObject && cleanupViewportObject.attributes) {
        const attrs = cleanupViewportObject.attributes;
        attrs.scroll_content_left = attrs.scroll_viewport_left;
        attrs.scroll_content_right = attrs.scroll_viewport_right;
        attrs.scroll_content_top = attrs.scroll_viewport_top;
        attrs.scroll_content_bottom = attrs.scroll_viewport_bottom;
    }

    // The modal stays at its designed origin (20,20) - no re-centering.
    const bodyUid = modalUid + '_body';
    const headerUid = modalUid + '_header';
    const titleUid = modalUid + '_title';
    const closeBtnUid = modalUid + '_close_button';
    const closeTextUid = modalUid + '_close_text';
    const viewportUid = modalUid + '_content_viewport';

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

    window.__disableGlobalPan = true;
    if (app && app.stage) app.stage.sortChildren();

    // ===== 3) WebSocket 'get_timeline' on the evolution container =====
    const evolutionPort = '__port__';
    if (!evolutionPort) {
        console.error('[Timeline] WebSocket port not found for evolution container');
        return;
    }

    const wsUrl = '__gateway_base__' + evolutionPort;
    console.log('[Timeline] Connecting to evolution WS:', wsUrl);

    window.gameWebSockets = window.gameWebSockets || {};
    let ws = window.gameWebSockets[evolutionPort];

    const sendCommand = function() {
        ws.send(JSON.stringify({ command: 'get_timeline', modalUid: modalUid }));
    };

    const onMessage = function(event) {
        let response;
        try {
            response = JSON.parse(event.data);
        } catch (e) {
            console.error('[Timeline] Error parsing WS response:', e);
            return;
        }

        // Ignore non-timeline messages (e.g. connection greeting)
        if (!response || response.command !== 'get_timeline') {
            return;
        }

        ws.removeEventListener('message', onMessage);

        console.log('[Timeline] get_timeline response:', response);
        if (response.success && Array.isArray(response.elements)) {
            const fn = window[addElementsFunctionName];
            if (typeof fn === 'function') {
                fn(response.elements);
            } else {
                console.error('[Timeline] addElementsToModal function not found:', addElementsFunctionName);
            }
        } else {
            console.error('[Timeline] get_timeline failed:', response.error || response);
        }
    };

    if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
        ws = new WebSocket(wsUrl);
        window.gameWebSockets[evolutionPort] = ws;

        ws.onopen = function() {
            console.log('[Timeline] WS Connected to ' + wsUrl);
            sendCommand();
        };
        ws.addEventListener('message', onMessage);
        ws.onerror = function(error) {
            console.error('[Timeline] WS Error:', error);
        };
    } else {
        if (ws.readyState === WebSocket.OPEN) {
            sendCommand();
        } else if (ws.readyState === WebSocket.CONNECTING) {
            ws.addEventListener('open', sendCommand, { once: true });
        }
        ws.addEventListener('message', onMessage);
    }
};
window['__name__']();
</script>
