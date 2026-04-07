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
            if (objects[key]) {
                objects[key].attributes = objects[key].attributes || {};
                objects[key].attributes.renderable = false;
            }
            if (objectPanel['children']) {
                for (const childUid of objectPanel['children']) {
                    if (shapes[childUid]) shapes[childUid].renderable = false;
                    if (objects[childUid]) {
                        objects[childUid].attributes = objects[childUid].attributes || {};
                        objects[childUid].attributes.renderable = false;
                    }
                }
            }
            // Clear gene polling for other entities
            const otherUid = key.replace('_panel', '');
            if (window.AppData && window.AppData._genePollingIntervals && window.AppData._genePollingIntervals[otherUid]) {
                clearInterval(window.AppData._genePollingIntervals[otherUid]);
                delete window.AppData._genePollingIntervals[otherUid];
                console.log('[Gene Polling] Stopped for other entity:', otherUid);
            }
        }

        // Toggle Entity Panel
        let show = !isVisible;
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = 10000;
        if (objects[panel_uid]) {
            objects[panel_uid].attributes = objects[panel_uid].attributes || {};
            objects[panel_uid].attributes.renderable = show;
            objects[panel_uid].attributes.z_index = 10000;
        }
        AppData.actual_focus_uid_entity = show ? object_uid : null;

        // --- Gene Polling Management ---
        AppData._genePollingIntervals = AppData._genePollingIntervals || {};
        if (AppData._genePollingIntervals[object_uid]) {
            clearInterval(AppData._genePollingIntervals[object_uid]);
            delete AppData._genePollingIntervals[object_uid];
        }

        // --- Gene Polling Management is moved to the end of the function ---

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
            const childZIndex = (objects[childUid] && objects[childUid].attributes && typeof objects[childUid].attributes.z_index === 'number')
                ? objects[childUid].attributes.z_index
                : 10001;
            if (shapes[childUid]) {
                shapes[childUid].renderable = show;
                shapes[childUid].zIndex = childZIndex;
            }
            if (objects[childUid]) {
                objects[childUid].attributes = objects[childUid].attributes || {};
                objects[childUid].attributes.renderable = show;
                objects[childUid].attributes.z_index = childZIndex;
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
                        if (objects[childUid]) {
                            objects[childUid].attributes = objects[childUid].attributes || {};
                            objects[childUid].attributes.renderable = show;
                        }
                    }
                    if (childUid.includes('_btn_attack')) {
                        if (shapes[childUid]) shapes[childUid].renderable = show;
                        if (objects[childUid]) {
                            objects[childUid].attributes = objects[childUid].attributes || {};
                            objects[childUid].attributes.renderable = show;
                        }
                    }
                }
            }
        }
        if (app && app.stage) {
            app.stage.sortChildren();
        }

        // --- Gene Polling Management (moved to end to ensure UI is ready) ---
        if (show && object.attributes && object.attributes.ws_port) {
            const entityPort = object.attributes.ws_port;
            const entityWsUrl = '__gateway_base__' + entityPort;
            window.gameWebSockets = window.gameWebSockets || {};
            const entityWsKey = 'entity_' + entityPort;
            let entityWs = window.gameWebSockets[entityWsKey];

            const bindMessageHandler = (ws) => {
                ws.onmessage = (event) => {
                    try {
                        const response = JSON.parse(event.data);
                        if (response.command === 'get_genes') {
                            console.log('[Gene Polling] Response for ' + object_uid + ' arrived.');
                            if (response.genes && Array.isArray(response.genes)) {
                                response.genes.forEach(gene => {
                                    const baseUid = object_uid + '_progress_bar_' + gene.key;
                                    const textUid = baseUid + '_text';
                                    if (objects[textUid]) {
                                        objects[textUid].text = (gene.name || gene.key) + " (" + gene.value + ")";
                                        if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(textUid);
                                    }
                                    const barUid = baseUid + '_bar';
                                    const borderUid = baseUid + '_border';
                                    if (objects[barUid] && objects[borderUid]) {
                                        const min = (gene.min !== undefined) ? gene.min : 0;
                                        const max = (gene.max !== undefined) ? gene.max : 100;
                                        const range = max - min;
                                        const percent = range > 0 ? (gene.value - min) / range : 0;
                                        const clampedPercent = Math.max(0, Math.min(1, percent));
                                        const fullWidth = objects[borderUid].width || 200;
                                        const newWidth = (fullWidth - 4) * clampedPercent;
                                        objects[barUid].width = Math.max(0, newWidth);
                                        if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(barUid);
                                        const rangeUid = baseUid + '_range';
                                        if (objects[rangeUid]) {
                                            objects[rangeUid].text = "(" + min + " / " + max + ")";
                                            if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(rangeUid);
                                        }
                                    }
                                });
                                if (app && app.stage) app.stage.sortChildren();
                            }
                        }
                    } catch (e) {
                        console.error('[Entity WS] Parse Error:', e);
                    }
                };
            };

            const startPolling = (ws) => {
                console.log('[Gene Polling] Starting cycle for:', object_uid);
                const fetchGenes = (isImmediate = false) => {
                    if (ws && ws.readyState === 1) {
                        if (isImmediate) console.log('[Gene Polling] Sending IMMEDIATE refresh call...');
                        ws.send(JSON.stringify({ command: 'get_genes' }));
                    }
                };
                
                // Immediate refresh after a small delay to ensure rendering is complete
                setTimeout(() => fetchGenes(true), 50); 
                
                // Standard 2s cycle
                AppData._genePollingIntervals[object_uid] = setInterval(() => fetchGenes(false), 2000);
            };

            if (!entityWs || entityWs.readyState > 1) { // 2 = CLOSING, 3 = CLOSED
                entityWs = new WebSocket(entityWsUrl);
                window.gameWebSockets[entityWsKey] = entityWs;
                entityWs.onopen = () => {
                    bindMessageHandler(entityWs);
                    startPolling(entityWs);
                };
            } else if (entityWs.readyState === 1) {
                bindMessageHandler(entityWs);
                startPolling(entityWs);
            } else if (entityWs.readyState === 0) { // 0 = CONNECTING
                entityWs.addEventListener('open', () => {
                    bindMessageHandler(entityWs);
                    startPolling(entityWs);
                }, { once: true });
            }
        } else {
            console.log('[Gene Polling] Polling state inactive for:', object_uid);
        }
    }
    window['__name__']();
</script>
