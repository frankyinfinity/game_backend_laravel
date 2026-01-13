<script>
    window['__name__'] = function() {

        let actual_focus_uid_entity = AppData.actual_focus_uid_entity ?? null;
        if (actual_focus_uid_entity !== null) {

            let i = '__i__';
            let j = '__j__';
            let ports = JSON.parse('__ports__');
            let port = ports[actual_focus_uid_entity];

            if (!port) {
                console.error('WebSocket port not found for entity ' + actual_focus_uid_entity);
                return;
            }

            let wsUrl = 'ws://' + window.location.hostname + ':' + port;
            
            // Global cache for WebSockets
            window.gameWebSockets = window.gameWebSockets || {};
            let ws = window.gameWebSockets[port];

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
                window.gameWebSockets[port] = ws;

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

        }

    }
    window['__name__']();
</script>
