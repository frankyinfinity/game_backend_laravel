<script>
    window['__name__'] = function() {
        const showBottomRightAlert = function(message) {
            if (!message) {
                return;
            }

            const containerId = 'game-toast-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.position = 'fixed';
                container.style.right = '16px';
                container.style.bottom = '16px';
                container.style.zIndex = '999999';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '8px';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.background = '#d32f2f';
            toast.style.color = '#ffffff';
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '8px';
            toast.style.fontSize = '14px';
            toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.22)';
            toast.style.maxWidth = '320px';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 180ms ease';

            container.appendChild(toast);
            requestAnimationFrame(function() {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 180);
            }, 3000);
        };

        const entityUid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        if (!entityUid) {
            console.warn('Division click: no selected entity uid');
            return;
        }

        let port = '__port__';
        if (!port) {
            console.error('WebSocket port not found for this entity');
            return;
        }

        let wsUrl = '__gateway_base__' + port;

        // Cache globale dei WebSocket (stesso pattern di movement_ws)
        window.gameWebSockets = window.gameWebSockets || {};
        let ws = window.gameWebSockets[port];

        const sendCommand = () => {
            ws.send(JSON.stringify({
                command: 'division',
                params: {
                    entity_uid: entityUid
                }
            }));
        };

        // Risolve il player_id: costante iniettata dal backend (__PLAYER_ID__),
        // altrimenti playerId globale del frontend → window.playerId → AppData.player_id
        const resolvePlayerId = function() {
            if (typeof __PLAYER_ID__ !== 'undefined' && __PLAYER_ID__) {
                return __PLAYER_ID__;
            }
            if (typeof playerId !== 'undefined') {
                return playerId;
            }
            if (typeof window !== 'undefined' && typeof window.playerId !== 'undefined') {
                return window.playerId;
            }
            if (typeof AppData !== 'undefined' && typeof AppData.player_id !== 'undefined') {
                return AppData.player_id;
            }
            return null;
        };

        const sendDivisionItemsToFrontend = function(items) {
            if (!Array.isArray(items) || items.length === 0) {
                return Promise.resolve();
            }

            const payload = {
                request_id: 'division_' + Date.now(),
                player_id: resolvePlayerId(),
                items: items
            };

            if (typeof window.processDrawInterfaceEvent === 'function') {
                return Promise.resolve(window.processDrawInterfaceEvent(payload));
            }

            console.warn('Division: processDrawInterfaceEvent non disponibile', payload);
            return Promise.resolve();
        };

        // Dopo il draw degli items: aggiorna window.entityWsPorts con la porta
        // WebSocket della nuova entity (stessa mappa entity_uid → ws_port
        // popolata da refresh_websocket_ports via /api/game/websocket_info e
        // letta da click_tile_ws per risolvere la porta dell'entity).
        // La risposta WS di divisione non contiene new_entity_uid: uid e porta
        // vengono letti dall'item image della nuova entity (EntityDraw).
        const updateEntityWsPorts = function(items) {
            if (!Array.isArray(items)) {
                return;
            }

            window.entityWsPorts = (window.entityWsPorts && typeof window.entityWsPorts === 'object')
                ? window.entityWsPorts
                : {};

            items.forEach(function(item) {
                if (!item || item.type !== 'draw' || !item.object) {
                    return;
                }

                const obj = item.object;
                const newEntityPort = obj.attributes ? obj.attributes.ws_port : null;
                if (!newEntityPort || !obj.uid) {
                    return;
                }

                window.entityWsPorts[obj.uid] = newEntityPort;
                console.log('Division: entityWsPorts updated for new entity ' + obj.uid + ' → port ' + newEntityPort);
            });
        };

        const onDivisionResponse = function(event) {
            let response;
            try {
                response = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            if (!response || response.command !== 'division') {
                return; // ignora benvenuto e risposte di altri comandi
            }
            console.log('WS Division response:', response);
            ws.removeEventListener('message', onDivisionResponse);
            if (response.success) {
                // Items di draw restituiti dal backend: nessun DrawRequest,
                // vengono passati alla pipeline di disegno del frontend.
                // Al termine del draw: aggiorna window.entityWsPorts con la
                // porta WebSocket della nuova entity appena creata.
                sendDivisionItemsToFrontend(response.items).then(function() {
                    updateEntityWsPorts(response.items);
                });
                showBottomRightAlert('Divisione avviata per ' + entityUid);
            } else {
                showBottomRightAlert(response.error || 'Divisione non disponibile');
            }
        };

        if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
            ws = new WebSocket(wsUrl);
            window.gameWebSockets[port] = ws;

            ws.onopen = function() {
                console.log('WS Connected to ' + wsUrl);
                sendCommand();
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

        // Listener isolato: non sovrascrive gli onmessage di altri comandi
        // (es. movimento) eventualmente già registrati sul socket.
        ws.addEventListener('message', onDivisionResponse);
    }
    window['__name__']();
</script>
