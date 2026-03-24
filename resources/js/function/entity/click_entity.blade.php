<script>
    window['__name__'] = function() {
        let object_uid = object['uid'];
        let panel_uid = object_uid + '_panel';
        let playerPort = '__player_port__';

        if (!shapes[panel_uid]) return;

        let isVisible = shapes[panel_uid].renderable;

        // Chiude tutti gli altri panel Entity
        const entityPanels = Object.entries(objects)
            .filter(([key, _]) => !key.startsWith('element_') && key.endsWith('_panel'))
            .reduce((obj, [key, value]) => { obj[key] = value; return obj; }, {});
        for (const [key, objectPanel] of Object.entries(entityPanels)) {
            if (shapes[key]) shapes[key].renderable = false;
            if (objectPanel['children']) {
                for (const childUid of objectPanel['children']) {
                    if (shapes[childUid]) shapes[childUid].renderable = false;
                }
            }
        }

        // Toggle Entity Panel
        let show = !isVisible;
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = 10000;
        AppData.actual_focus_uid_entity = show ? object_uid : null;

        const resolveDivisionEnabled = () => {
            const wsResponse = (typeof AppData !== 'undefined') ? AppData.player_values_ws_response : null;
            if (!wsResponse || typeof wsResponse !== 'object') return false;

            // Preferred shape from player docker ws: { data: { values: {...} } }
            const valuesFromDocker = wsResponse.data && wsResponse.data.values;
            if (valuesFromDocker && typeof valuesFromDocker === 'object') {
                return !!valuesFromDocker.division;
            }

            // Fallback shape if raw API response is stored directly
            const directValues = wsResponse.values;
            if (directValues && typeof directValues === 'object') {
                return !!directValues.division;
            }

            return false;
        };

        const applyDivisionButtonVisibility = () => {
            const divisionEnabled = resolveDivisionEnabled();
            const divisionButtonUids = [object_uid + '_button_division_rect', object_uid + '_button_division_text'];
            divisionButtonUids.forEach((uid) => {
                if (shapes[uid]) {
                    shapes[uid].renderable = !!(show && divisionEnabled);
                }
            });
        };

        // Figli del pannello entity
        for (const childUid of objects[panel_uid]['children']) {
            if (shapes[childUid]) {
                shapes[childUid].renderable = show;
                shapes[childUid].zIndex = 10001;
            }
        }
        applyDivisionButtonVisibility();

        // Quando apri il pannello entity, richiede player values al docker player via WS.
        if (show && playerPort) {
            let wsUrl = '__gateway_base__' + playerPort;

            window.gameWebSockets = window.gameWebSockets || {};
            let wsKey = 'player_' + playerPort;
            let ws = window.gameWebSockets[wsKey];

            const requestPlayerValues = () => {
                ws.send(JSON.stringify({
                    command: 'get_player_values'
                }));
            };

            const bindPlayerMessageHandler = () => {
                ws.onmessage = function(event) {
                    try {
                        let response = JSON.parse(event.data);
                        if (typeof AppData !== 'undefined') {
                            AppData.player_values_ws_response = response;
                        }
                        window.playerValuesWsResponse = response;
                        console.log('Player WS Response:', response);
                        applyDivisionButtonVisibility();
                    } catch (e) {
                        console.error('Player WS Parse Error:', e);
                    }
                };
            };

            if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
                ws = new WebSocket(wsUrl);
                window.gameWebSockets[wsKey] = ws;

                ws.onopen = function() {
                    bindPlayerMessageHandler();
                    requestPlayerValues();
                };

                ws.onerror = function(error) {
                    console.error('Player WS Error:', error);
                };
            } else if (ws.readyState === WebSocket.OPEN) {
                bindPlayerMessageHandler();
                requestPlayerValues();
            } else if (ws.readyState === WebSocket.CONNECTING) {
                ws.addEventListener('open', function() {
                    bindPlayerMessageHandler();
                    requestPlayerValues();
                }, { once: true });
            }
        }

        // Reattivita: se c'e un elemento aperto, aggiorna i suoi pulsanti consume/attack
        if (AppData.actual_focus_uid_element) {
            let elPanelUid = AppData.actual_focus_uid_element + '_panel';
            if (objects[elPanelUid]) {
                for (const childUid of objects[elPanelUid]['children']) {
                    if (childUid.includes('_btn_consume')) {
                        if (shapes[childUid]) shapes[childUid].renderable = show;
                    }
                    if (childUid.includes('_btn_attack')) {
                        if (shapes[childUid]) shapes[childUid].renderable = show;
                    }
                }
            }
        }
    }
    window['__name__']();
</script>
