<script>
    window['__name__'] = function() {
        let entity_uid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        let element_uid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_element : null;

        console.log('Action: Attack (WS)');
        console.log('Entity UID (Source):', entity_uid);
        console.log('Element UID (Target):', element_uid);

        if (!entity_uid || !element_uid) {
            console.warn('Missing focus: Entity or Element panel might be closed.');
            return;
        }

        // element_id numerico: prima dagli attributes dell'oggetto, fallback al valore iniettato dal backend
        let element_id = null;
        try {
            if (typeof objects !== 'undefined' && objects[element_uid] && objects[element_uid].attributes) {
                element_id = objects[element_uid].attributes.element_id;
            }
        } catch (e) {}
        if (element_id === null || element_id === undefined || element_id === '') {
            element_id = '__element_id__';
        }
        // Se il placeholder non è stato sostituito o è vuoto, prova a parsare l'uid (element_<id>_I_J)
        if (element_id === '__element_id__' || element_id === '') {
            const parts = (element_uid || '').split('_');
            element_id = (parts.length >= 2) ? parts[1] : null;
        }
        element_id = parseInt(element_id, 10);
        if (isNaN(element_id)) {
            console.error('Attack: element_id non valido per', element_uid);
            return;
        }
        console.log('Element ID (Target):', element_id);

        let playerId = '__PLAYER_ID__';
        let ports = (window.entityWsPorts && typeof window.entityWsPorts === 'object')
            ? window.entityWsPorts
            : {};
        window.entityWsPorts = ports;
        window.entityPortsPlayerId = window.entityPortsPlayerId || playerId;
        let port = ports[entity_uid];

        const sendAttackCommand = (ws) => {
            ws.send(JSON.stringify({
                command: 'attack',
                params: {
                    entity_uid: entity_uid,
                    element_id: element_id
                }
            }));
        };

        const onAttackResponse = function(event) {
            let response;
            try {
                response = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            if (!response || response.command !== 'attack') {
                return; // ignora benvenuto e risposte di altri comandi
            }
            console.log('WS Attack response:', response);
        };

        const connectAndSend = (resolvedPort) => {
            let wsUrl = '__gateway_base__' + resolvedPort;

            window.gameWebSockets = window.gameWebSockets || {};
            let ws = window.gameWebSockets[resolvedPort];

            // Listener isolato: non sovrascrive gli onmessage di altri comandi
            const attachListener = (socket) => {
                socket.addEventListener('message', onAttackResponse);
            };

            if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
                ws = new WebSocket(wsUrl);
                window.gameWebSockets[resolvedPort] = ws;

                ws.onopen = function () {
                    console.log('WS Connected to ' + wsUrl);
                    sendAttackCommand(ws);
                };

                ws.onerror = function (error) {
                    console.error('WS Error:', error);
                };
                attachListener(ws);
            } else {
                attachListener(ws);
                if (ws.readyState === WebSocket.OPEN) {
                    sendAttackCommand(ws);
                } else if (ws.readyState === WebSocket.CONNECTING) {
                    ws.addEventListener('open', function() { sendAttackCommand(ws); }, { once: true });
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
                        const refreshedPort = resolvedPorts[entity_uid];
                        if (!refreshedPort) {
                            console.error('WebSocket port not found for entity ' + entity_uid);
                            return;
                        }
                        connectAndSend(refreshedPort);
                    })
                    .catch(function (error) {
                        console.error('Failed to refresh websocket ports:', error);
                    });
                return;
            }

            console.error('WebSocket port not found for entity ' + entity_uid);
            return;
        }

        connectAndSend(port);
    }
    window['__name__']();
</script>
