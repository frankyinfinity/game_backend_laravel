<script>
window['__name__'] = function() {
    const playerId = __PLAYER_ID__;
    const modalUid = 'timeline_evolutiva_modal';

    // Rendering layer: test page uses mainLayer, the game frontend uses app.stage.
    const renderLayer = (typeof mainLayer !== 'undefined' && mainLayer)
        ? mainLayer
        : ((typeof app !== 'undefined' && app && app.stage) ? app.stage : null);

    // ===== addElementsToModal_<modalUid> (declared here, inspired by test:draw) =====
    const addElementsFunctionName = 'addElementsToModal_' + modalUid;
    window[addElementsFunctionName] = function(elements) {
        console.log('[Timeline] addElementsToModal called with:', elements ? elements.length : 0, 'elements');

        const viewportUid = modalUid + '_content_viewport';
        const viewportObject = objects[viewportUid];

        if (!viewportObject || !viewportObject.attributes) {
            console.error('[Timeline] Modal viewport not found:', viewportUid);
            return;
        }
        if (!renderLayer) {
            console.error('[Timeline] No render layer available (mainLayer/app.stage)');
            return;
        }
        if (!Array.isArray(elements) || elements.length === 0) {
            console.log('[Timeline] No elements to draw');
            return;
        }

        const contentX = viewportObject.attributes.scroll_viewport_left || 0;
        const contentY = viewportObject.attributes.scroll_viewport_top || 0;

        // Initialize scroll attributes if needed
        if (!viewportObject.attributes.scroll_child_uids) viewportObject.attributes.scroll_child_uids = [];
        if (!viewportObject.attributes.scroll_base_positions_x) viewportObject.attributes.scroll_base_positions_x = {};
        if (!viewportObject.attributes.scroll_base_positions_y) viewportObject.attributes.scroll_base_positions_y = {};
        if (!viewportObject.attributes.scroll_item_widths) viewportObject.attributes.scroll_item_widths = {};
        if (!viewportObject.attributes.scroll_item_heights) viewportObject.attributes.scroll_item_heights = {};
        if (!viewportObject.attributes.scroll_initial_renderables) viewportObject.attributes.scroll_initial_renderables = {};

        const trackElement = function(uid, x, y, w, h) {
            if (viewportObject.attributes.scroll_child_uids.indexOf(uid) === -1) {
                viewportObject.attributes.scroll_child_uids.push(uid);
            }
            viewportObject.attributes.scroll_base_positions_x[uid] = x;
            viewportObject.attributes.scroll_base_positions_y[uid] = y;
            viewportObject.attributes.scroll_item_widths[uid] = w;
            viewportObject.attributes.scroll_item_heights[uid] = h;
            viewportObject.attributes.scroll_initial_renderables[uid] = true;

            const itemRight = x + w;
            const itemBottom = y + h;
            if (viewportObject.attributes.scroll_content_left === undefined || x < viewportObject.attributes.scroll_content_left) viewportObject.attributes.scroll_content_left = x;
            if (viewportObject.attributes.scroll_content_right === undefined || itemRight > viewportObject.attributes.scroll_content_right) viewportObject.attributes.scroll_content_right = itemRight;
            if (viewportObject.attributes.scroll_content_top === undefined || y < viewportObject.attributes.scroll_content_top) viewportObject.attributes.scroll_content_top = y;
            if (viewportObject.attributes.scroll_content_bottom === undefined || itemBottom > viewportObject.attributes.scroll_content_bottom) viewportObject.attributes.scroll_content_bottom = itemBottom;
        };

        elements.forEach(function(element, index) {
            const uid = element.uid || (modalUid + '_custom_' + Date.now() + '_' + index);
            const x = contentX + (element.x || 0);
            const y = contentY + (element.y || 0);
            const color = (element.color !== null && element.color !== undefined) ? element.color : 0x000000;

            let drawItem, itemWidth, itemHeight, shape;

            if (element.type === 'square') {
                const size = element.size || 40;
                drawItem = { uid: uid, type: 'square', x: x, y: y, size: size, color: color, attributes: { renderable: true, z_index: (element.attributes && element.attributes.z_index) || 20020 } };
                itemWidth = itemHeight = size;
                const square = new Square(drawItem);
                square.build();
                square.render(app);
                shape = square.shape;
            } else if (element.type === 'circle') {
                const radius = element.radius || 20;
                drawItem = { uid: uid, type: 'circle', x: x, y: y, radius: radius, color: color, attributes: { renderable: true, z_index: (element.attributes && element.attributes.z_index) || 20020 } };
                itemWidth = itemHeight = radius * 2;
                const circle = new Circle(drawItem);
                circle.build();
                circle.render(app);
                shape = circle.shape;
            } else if (element.type === 'text') {
                const fontSize = element.fontSize || 16;
                drawItem = { uid: uid, type: 'text', x: x, y: y, text: element.text || '', color: color, fontSize: fontSize, fontFamily: element.fontFamily || 'Arial', attributes: { renderable: true, z_index: (element.attributes && element.attributes.z_index) || 20020 } };
                itemWidth = fontSize * 8;
                itemHeight = fontSize + 10;
                const textObj = new Text(drawItem);
                textObj.build();
                textObj.render(app);
                shape = textObj.shape;
            } else if (element.type === 'rectangle') {
                const width = element.width || 60;
                const height = element.height || 40;
                drawItem = { uid: uid, type: 'rectangle', x: x, y: y, width: width, height: height, color: color, attributes: { renderable: true, z_index: (element.attributes && element.attributes.z_index) || 20020 } };
                itemWidth = width;
                itemHeight = height;
                const rect = new Rectangle(drawItem);
                rect.build();
                rect.render(app);
                shape = rect.shape;
            } else if (element.type === 'rounded_rectangle') {
                const width = element.width || 60;
                const height = element.height || 40;
                const borderRadius = element.border_radius || 10;
                drawItem = { uid: uid, type: 'rounded_rectangle', x: x, y: y, width: width, height: height, color: color, border_radius: borderRadius, attributes: { renderable: true, z_index: 20020 } };
                itemWidth = width;
                itemHeight = height;
                const g = new PIXI.Graphics();
                g.beginFill(color);
                g.drawRoundedRect(x, y, width, height, borderRadius);
                g.endFill();
                g.renderable = true;
                g.zIndex = 20020;
                renderLayer.addChild(g);
                shape = g;
            } else if (element.type === 'rounded_rectangle_outline') {
                const width = element.width || 60;
                const height = element.height || 40;
                const borderRadius = element.border_radius || 10;
                const borderWidth = element.border_width || 2;
                drawItem = { uid: uid, type: 'rounded_rectangle_outline', x: x, y: y, width: width, height: height, color: color, border_radius: borderRadius, border_width: borderWidth, attributes: { renderable: true, z_index: 20020 } };
                itemWidth = width;
                itemHeight = height;
                const g = new PIXI.Graphics();
                g.lineStyle(borderWidth, color);
                g.drawRoundedRect(x, y, width, height, borderRadius);
                g.renderable = true;
                g.zIndex = 20020;
                renderLayer.addChild(g);
                shape = g;
            } else if (element.type === 'sprite') {
                const width = element.width || 100;
                const height = element.height || 100;
                const imageUrl = element.image_url || '';
                itemWidth = width;
                itemHeight = height;
                if (imageUrl) {
                    PIXI.Assets.load(imageUrl).then(function(texture) {
                        const sprite = new PIXI.Sprite(texture);
                        sprite.x = x;
                        sprite.y = y;
                        sprite.width = width;
                        sprite.height = height;
                        sprite.renderable = true;
                        sprite.zIndex = 20020;
                        renderLayer.addChild(sprite);
                        shapes[uid] = sprite;
                    }).catch(function(err) {
                        console.error('[Timeline] Sprite load error:', imageUrl, err);
                    });
                    drawItem = { uid: uid, type: 'sprite', x: x, y: y, width: width, height: height, image_url: imageUrl, attributes: { renderable: true, z_index: 20020 } };
                    objects[uid] = drawItem;
                    trackElement(uid, x, y, itemWidth, itemHeight);
                    return;
                }
                const g = new PIXI.Graphics();
                g.beginFill(0xE0E0E0);
                g.drawRect(x, y, width, height);
                g.endFill();
                g.renderable = true;
                g.zIndex = 20020;
                renderLayer.addChild(g);
                drawItem = { uid: uid, type: 'sprite', x: x, y: y, width: width, height: height, attributes: { renderable: true, z_index: 20020 } };
                shape = g;
            } else {
                console.warn('[Timeline] Unknown element type:', element.type);
                return;
            }

            objects[uid] = drawItem;
            if (shape) {
                shape.renderable = true;
                shapes[uid] = shape;
            }

            if (window.modalDynamicElements && window.modalDynamicElements[modalUid]) {
                window.modalDynamicElements[modalUid].push(uid);
            }

            trackElement(uid, x, y, itemWidth, itemHeight);
        });

        if (typeof window.refreshAllModalViewportMasks === 'function') {
            window.refreshAllModalViewportMasks();
        }
        if (app && app.stage) app.stage.sortChildren();
        console.log('[Timeline] Elements drawn into modal', modalUid);
    };

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
