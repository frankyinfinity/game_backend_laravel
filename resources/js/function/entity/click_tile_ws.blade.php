<script>
    window['__name__'] = function () {

        let actual_focus_uid_entity = AppData.actual_focus_uid_entity ?? null;
        let actual_focus_uid_element = AppData.actual_focus_uid_element ?? null;
        let i = '__i__';
        let j = '__j__';

        if (actual_focus_uid_entity !== null) {

            let playerId = '__PLAYER_ID__';
            let ports = (window.entityWsPorts && typeof window.entityWsPorts === 'object')
                ? window.entityWsPorts
                : JSON.parse('__ports__');
            window.entityWsPorts = ports;
            window.entityPortsPlayerId = window.entityPortsPlayerId || playerId;
            let port = ports[actual_focus_uid_entity];

            const connectAndSend = (resolvedPort) => {
                let wsUrl = '__gateway_base__' + resolvedPort;

                window.gameWebSockets = window.gameWebSockets || {};
                let ws = window.gameWebSockets[resolvedPort];

                const sendCommand = () => {
                    ws.send(JSON.stringify({
                        command: 'move',
                        params: {
                            target_i: i,
                            target_j: j
                        }
                    }));
                };

                if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
                    ws = new WebSocket(wsUrl);
                    window.gameWebSockets[resolvedPort] = ws;

                    ws.onopen = function () {
                        sendCommand();
                    };

                    ws.onmessage = function (event) {
                        let response = JSON.parse(event.data);
                        console.log('WS Response:', response);
                    };

                    ws.onerror = function (error) {
                        console.error('WS Error:', error);
                    };
                } else {
                    if (ws.readyState === WebSocket.OPEN) {
                        sendCommand();
                    } else if (ws.readyState === WebSocket.CONNECTING) {
                        ws.addEventListener('open', sendCommand, { once: true });
                    }
                }
            };

            if (!port) {
                if (typeof window.refreshEntityWebSocketPorts === 'function' && window.entityPortsPlayerId) {
                    window.refreshEntityWebSocketPorts(window.entityPortsPlayerId)
                        .then(function (refreshedPorts) {
                            const resolvedPorts = (refreshedPorts && typeof refreshedPorts === 'object')
                                ? refreshedPorts
                                : (window.entityWsPorts || {});
                            const refreshedPort = resolvedPorts[actual_focus_uid_entity];
                            if (!refreshedPort) {
                                console.error('WebSocket port not found for entity ' + actual_focus_uid_entity);
                                return;
                            }
                            connectAndSend(refreshedPort);
                        })
                        .catch(function (error) {
                            console.error('Failed to refresh websocket ports:', error);
                        });
                    return;
                }

                console.error('WebSocket port not found for entity ' + actual_focus_uid_entity);
                return;
            }
            connectAndSend(port);

        } else if (actual_focus_uid_element === null) {

            var mapContainerName = '__MAP_CONTAINER_NAME__';
            window.gameWebSockets = window.gameWebSockets || {};

            var updateTilePanelContent = function (lines) {
                var panelUid = 'tile_panel';

                var maxLines = 10;
                for (var li = 0; li < maxLines; li++) {
                    var lineUid = panelUid + '_content_' + li;
                    var lineText = li < lines.length ? ('* ' + lines[li]) : '';
                    if (shapes[lineUid]) {
                        shapes[lineUid].text = lineText;
                        shapes[lineUid].renderable = li < lines.length;
                    }
                    if (objects[lineUid]) {
                        objects[lineUid].text = lineText;
                        if (objects[lineUid].attributes) objects[lineUid].attributes.renderable = li < lines.length;
                    }
                }

                if (lines.length === 0) {
                    var emptyUid = panelUid + '_content_0';
                    if (shapes[emptyUid]) {
                        shapes[emptyUid].text = 'Nessun elemento';
                        shapes[emptyUid].renderable = true;
                    }
                    if (objects[emptyUid]) {
                        objects[emptyUid].text = 'Nessun elemento';
                        if (objects[emptyUid].attributes) objects[emptyUid].attributes.renderable = true;
                    }
                }
            };

            var startTilePanelRefresh = function (mapWs) {
                if (window.__tilePanelRefreshInterval) {
                    clearInterval(window.__tilePanelRefreshInterval);
                }

                window.__tilePanelRefreshInterval = setInterval(function () {
                    if (!mapWs || mapWs.readyState !== WebSocket.OPEN) return;

                    mapWs.send(JSON.stringify({
                        command: 'get_birth_region_details',
                        params: {
                            tile_i: i,
                            tile_j: j
                        }
                    }));

                    var handler = function (event) {
                        try {
                            var mapDetails = JSON.parse(event.data);
                            var detailData = (mapDetails.detail && mapDetails.detail.birth_region_detail_data) ? mapDetails.detail.birth_region_detail_data : [];
                            var lines = [];

                            detailData.forEach(function (item) {
                                var qty = item.quantity || 0;
                                var chemicalEl = null;
                                var complexEl = null;

                                if (item.json_chimical_element) {
                                    try { chemicalEl = JSON.parse(item.json_chimical_element); } catch (e) { }
                                }
                                if (item.json_complex_chimical_element) {
                                    try { complexEl = JSON.parse(item.json_complex_chimical_element); } catch (e) { }
                                }

                                if (chemicalEl) {
                                    lines.push((chemicalEl.name || 'Sconosciuto') + ' (' + (chemicalEl.symbol || '?') + '): ' + qty);
                                }
                                if (complexEl) {
                                    lines.push((complexEl.name || 'Sconosciuto') + ' (' + (complexEl.symbol || '?') + '): ' + qty);
                                }
                            });

                            updateTilePanelContent(lines);
                        } catch (e) {
                            console.error('Error refreshing tile panel:', e);
                        }
                        mapWs.removeEventListener('message', handler);
                    };
                    mapWs.addEventListener('message', handler);
                }, (2 * 1000));
            };

            var showTilePanel = function (mapWs) {
                mapWs.send(JSON.stringify({
                    command: 'get_birth_region_details',
                    params: {
                        tile_i: i,
                        tile_j: j
                    }
                }));

                var selectedTileUid = 'selected_tile_border_' + i + '_' + j;

                if (typeof AppData !== 'undefined' && AppData.__selectedTileBorderUid) {
                    var prevUid = AppData.__selectedTileBorderUid;
                    if (shapes[prevUid]) {
                        if (shapes[prevUid].parent) {
                            shapes[prevUid].parent.removeChild(shapes[prevUid]);
                        }
                        if (typeof shapes[prevUid].destroy === 'function') shapes[prevUid].destroy();
                        delete shapes[prevUid];
                        delete objects[prevUid];
                    }
                }

                if (shapes[selectedTileUid]) {
                    if (shapes[selectedTileUid].parent) {
                        shapes[selectedTileUid].parent.removeChild(shapes[selectedTileUid]);
                    }
                    if (typeof shapes[selectedTileUid].destroy === 'function') shapes[selectedTileUid].destroy();
                    delete shapes[selectedTileUid];
                    delete objects[selectedTileUid];
                }

                var tileSize = 40;
                var mapStartX = 0;
                var mapStartY = 80;
                var tileX = (j * tileSize) + mapStartX;
                var tileY = (i * tileSize) + mapStartY;
                var borderWidth = 3;

                var borderGraphics = new PIXI.Graphics();
                borderGraphics.lineStyle(borderWidth, 0xFFDD00, 1);
                borderGraphics.drawRect(tileX, tileY, tileSize, tileSize);

                if (typeof mainLayer !== 'undefined') {
                    mainLayer.addChild(borderGraphics);
                } else if (typeof app !== 'undefined' && app.stage) {
                    app.stage.addChild(borderGraphics);
                }

                borderGraphics.zIndex = 9999;
                if (typeof mainLayer !== 'undefined' && mainLayer.sortChildren) {
                    mainLayer.sortChildren();
                } else if (typeof app !== 'undefined' && app.stage && app.stage.sortChildren) {
                    app.stage.sortChildren();
                }

                shapes[selectedTileUid] = borderGraphics;
                objects[selectedTileUid] = { uid: selectedTileUid, attributes: { renderable: true, z_index: 9999 } };

                if (typeof AppData !== 'undefined') {
                    AppData.__selectedTileBorderUid = selectedTileUid;
                }

                var handler = function (event) {
                    try {
                        var mapDetails = JSON.parse(event.data);
                        console.log('Map tile details [' + i + ',' + j + ']:', mapDetails);

                        var lines = [];
                        var detailData = (mapDetails.detail && mapDetails.detail.birth_region_detail_data) ? mapDetails.detail.birth_region_detail_data : [];

                        detailData.forEach(function (item) {
                            var qty = item.quantity || 0;
                            var chemicalEl = null;
                            var complexEl = null;

                            if (item.json_chimical_element) {
                                try { chemicalEl = JSON.parse(item.json_chimical_element); } catch (e) { }
                            }
                            if (item.json_complex_chimical_element) {
                                try { complexEl = JSON.parse(item.json_complex_chimical_element); } catch (e) { }
                            }

                            if (chemicalEl) {
                                lines.push((chemicalEl.name || 'Sconosciuto') + ' (' + (chemicalEl.symbol || '?') + '): ' + qty);
                            }
                            if (complexEl) {
                                lines.push((complexEl.name || 'Sconosciuto') + ' (' + (complexEl.symbol || '?') + '): ' + qty);
                            }
                        });

                        console.log('Lines:', lines);

                        var panelUid = 'tile_panel';
                        var tileSize = 40;
                        var mapStartX = 0;
                        var mapStartY = 80;

                        var tileX = (j * tileSize) + mapStartX;
                        var tileY = (i * tileSize) + mapStartY;

                        var panelWidth = 320;
                        var panelHeight = 400;
                        var panelX = tileX + tileSize + 10;
                        var panelY = tileY;

                        if (panelX + panelWidth > window.innerWidth) {
                            panelX = tileX - panelWidth - 10;
                        }
                        if (panelY + panelHeight > window.innerHeight) {
                            panelY = window.innerHeight - panelHeight - 10;
                        }
                        if (panelY < 80) panelY = 80;

                        var allUids = [
                            panelUid + '_body',
                            panelUid + '_header',
                            panelUid + '_title',
                            panelUid + '_close_button',
                            panelUid + '_close_text'
                        ];
                        for (var ci = 0; ci < 10; ci++) {
                            allUids.push(panelUid + '_content_' + ci);
                        }

                        for (var hi = 0; hi < allUids.length; hi++) {
                            var huid = allUids[hi];
                            if (shapes[huid]) shapes[huid].renderable = false;
                            if (objects[huid] && objects[huid].attributes) {
                                objects[huid].attributes.renderable = false;
                            }
                        }

                        var bodyUid = panelUid + '_body';
                        var bodyObj = objects[bodyUid];
                        var bodyShape = shapes[bodyUid];
                        if (!bodyObj || !bodyShape) {
                            console.warn('Tile panel not found in draw objects');
                            return;
                        }

                        var dx = panelX - (bodyObj.x || 0);
                        var dy = panelY - (bodyObj.y || 0);

                        function movePanelElement(uid, deltaX, deltaY) {
                            var obj = objects[uid];
                            var shape = shapes[uid];
                            if (!obj || !shape) return;
                            if (typeof obj.x === 'number') {
                                obj.x += deltaX;
                                shape.x += deltaX;
                            }
                            if (typeof obj.y === 'number') {
                                obj.y += deltaY;
                                shape.y += deltaY;
                            }
                        }

                        for (var mi = 0; mi < allUids.length; mi++) {
                            movePanelElement(allUids[mi], dx, dy);
                        }

                        var titleUid = panelUid + '_title';
                        if (shapes[titleUid]) {
                            shapes[titleUid].text = 'Dettagli Tile [' + i + ', ' + j + ']';
                            shapes[titleUid].renderable = true;
                        }
                        if (objects[titleUid]) {
                            objects[titleUid].text = 'Dettagli Tile [' + i + ', ' + j + ']';
                            if (objects[titleUid].attributes) objects[titleUid].attributes.renderable = true;
                        }

                        updateTilePanelContent(lines);

                        bodyShape.renderable = true;
                        if (bodyObj.attributes) bodyObj.attributes.renderable = true;

                        var headerUid = panelUid + '_header';
                        if (shapes[headerUid]) { shapes[headerUid].renderable = true; }
                        if (objects[headerUid] && objects[headerUid].attributes) objects[headerUid].attributes.renderable = true;

                        var closeBtnUid = panelUid + '_close_button';
                        if (shapes[closeBtnUid]) { shapes[closeBtnUid].renderable = true; }
                        if (objects[closeBtnUid] && objects[closeBtnUid].attributes) objects[closeBtnUid].attributes.renderable = true;

                        var closeTxtUid = panelUid + '_close_text';
                        if (shapes[closeTxtUid]) { shapes[closeTxtUid].renderable = true; }
                        if (objects[closeTxtUid] && objects[closeTxtUid].attributes) objects[closeTxtUid].attributes.renderable = true;

                        if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
                            AppData.open_modals = {};
                        }
                        AppData.open_modals[panelUid] = true;

                        if (app && app.stage) app.stage.sortChildren();

                        startTilePanelRefresh(mapWs);

                    } catch (e) {
                        console.error('Error parsing map tile response:', e);
                    }
                    mapWs.removeEventListener('message', handler);
                };
                mapWs.addEventListener('message', handler);
            };

            var ensureMapWs = function (callback) {
                var mapWs = window.gameWebSockets[mapContainerName];

                if (mapWs && mapWs.readyState === WebSocket.OPEN) {
                    callback(mapWs);
                    return;
                }

                if (mapWs && mapWs.readyState === WebSocket.CONNECTING) {
                    mapWs.addEventListener('open', function () { callback(mapWs); }, { once: true });
                    return;
                }

                var wsUrl = window.__mapWsGatewayUrl || null;
                if (wsUrl) {
                    var ws = new WebSocket(wsUrl);
                    window.gameWebSockets[mapContainerName] = ws;
                    ws.onopen = function () { callback(ws); };
                    ws.onerror = function (err) { console.error('Map WS connect error:', err); };
                    return;
                }

                if (typeof $ !== 'undefined' && typeof BACK_URL !== 'undefined') {
                    var pid = '__PLAYER_ID__';
                    $.ajax({
                        url: BACK_URL + '/api/game/websocket_info',
                        type: 'POST',
                        data: { player_id: pid }
                    }).then(function (response) {
                        if (!response || !response.success || !response.containers) return;
                        response.containers.forEach(function (c) {
                            if (c.name === mapContainerName && c.ws_gateway_url) {
                                var ws = new WebSocket(c.ws_gateway_url);
                                window.gameWebSockets[mapContainerName] = ws;
                                window.__mapWsGatewayUrl = c.ws_gateway_url;
                                ws.onopen = function () { callback(ws); };
                                ws.onerror = function (err) { console.error('Map WS connect error:', err); };
                            }
                        });
                    }).catch(function (err) {
                        console.error('Failed to fetch websocket_info:', err);
                    });
                }
            };

            ensureMapWs(function (mapWs) {
                showTilePanel(mapWs);
            });

        }

    }
    window['__name__']();
</script>