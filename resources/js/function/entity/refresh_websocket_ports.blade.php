<script>
    (function () {
        if (typeof $ === 'undefined' || typeof BACK_URL === 'undefined') {
            console.warn('Refresh WS: missing $ or BACK_URL');
            return Promise.resolve({});
        }

        window.refreshEntityWebSocketPorts = function (playerId) {
            return $.ajax({
                url: `${BACK_URL}/api/game/websocket_info`,
                type: 'POST',
                data: { player_id: playerId }
            }).then(function (response) {
                if (!response || !response.success || !Array.isArray(response.containers)) {
                    console.warn('Refresh WS: invalid websocket_info response');
                    return {};
                }

                const ports = {};
                response.containers.forEach((container) => {
                    if (container && container.uid && container.ws_port) {
                        ports[container.uid] = container.ws_port;
                    }
                });

                window.entityWsPorts = ports;
                window.entityPortsPlayerId = playerId;
                console.log('Refresh WS: ports updated', ports);
                return ports;
            }).catch(function (err) {
                console.error('Refresh WS: websocket_info request failed', err);
                throw err;
            });
        };

        return window.refreshEntityWebSocketPorts(__PLAYER_ID__);
    })();
</script>
