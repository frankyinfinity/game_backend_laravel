<script>
    window['__name__'] = function() {

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
                
                // Global cache for WebSockets
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

                    ws.onopen = function() {
                        sendCommand();
                    };

                    ws.onmessage = function(event) {
                        let response = JSON.parse(event.data);
                        console.log('WS Response:', response);
                    };

                    ws.onerror = function(error) {
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
            var mapWs = window.gameWebSockets[mapContainerName];

            if (!mapWs || mapWs.readyState === WebSocket.CLOSED || mapWs.readyState === WebSocket.CLOSING) {
                console.warn('Map WebSocket not available for container: ' + mapContainerName);
                return;
            }

            if (mapWs.readyState === WebSocket.OPEN) {
                mapWs.send(JSON.stringify({
                    command: 'get_birth_region_details',
                    params: {
                        tile_i: i,
                        tile_j: j
                    }
                }));

                var handler = function(event) {
                    try {
                        var mapDetails = JSON.parse(event.data);
                        console.log('Map tile details [' + i + ',' + j + ']:', mapDetails);
                    } catch (e) {
                        console.error('Error parsing map tile response:', e);
                    }
                    mapWs.removeEventListener('message', handler);
                };
                mapWs.addEventListener('message', handler);
            }

        }

    }
    window['__name__']();
</script>
