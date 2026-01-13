<script>

    window['__name__'] = function() {

        let action = '__action__';
        let port = '__port__';
        
        if (!port) {
            console.error('WebSocket port not found for this entity');
            return;
        }

        let wsUrl = 'ws://' + window.location.hostname + ':' + port;
        let ws = new WebSocket(wsUrl);

        ws.onopen = function() {
            console.log('WS Connected to ' + wsUrl);
            ws.send(JSON.stringify({
                command: 'move',
                params: {
                    action: action
                }
            }));
        };

        ws.onmessage = function(event) {
            let response = JSON.parse(event.data);
            console.log('WS Response:', response);
            ws.close();
        };

        ws.onerror = function(error) {
            console.error('WS Error:', error);
        };

    }
    window['__name__']();

</script>
