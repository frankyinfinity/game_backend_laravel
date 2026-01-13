<script>

    window['__name__'] = function() {

        let action = '__action__';
        let port = '__port__';
        
        if (!port) {
            console.error('WebSocket port not found for this entity');
            return;
        }

        let wsUrl = 'ws://' + window.location.hostname + ':' + port;
        
        // Global cache
        window.gameWebSockets = window.gameWebSockets || {};
        let ws = window.gameWebSockets[port];

        const sendCommand = () => {
            ws.send(JSON.stringify({
                command: 'move',
                params: {
                    action: action
                }
            }));
        };

        if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
            ws = new WebSocket(wsUrl);
            window.gameWebSockets[port] = ws;

            ws.onopen = function() {
                console.log('WS Connected to ' + wsUrl);
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
    window['__name__']();

</script>
