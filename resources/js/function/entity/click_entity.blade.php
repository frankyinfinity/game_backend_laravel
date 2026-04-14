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
            if (window.AppData && window.AppData._chimicalPollingIntervals && window.AppData._chimicalPollingIntervals[otherUid]) {
                clearInterval(window.AppData._chimicalPollingIntervals[otherUid]);
                delete window.AppData._chimicalPollingIntervals[otherUid];
                console.log('[Chimical Polling] Stopped for other entity:', otherUid);
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

        // --- Chimical Elements Polling Management ---
        AppData._chimicalPollingIntervals = AppData._chimicalPollingIntervals || {};
        if (AppData._chimicalPollingIntervals[object_uid]) {
            clearInterval(AppData._chimicalPollingIntervals[object_uid]);
            delete AppData._chimicalPollingIntervals[object_uid];
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
                                        const modifier = (gene.modifier !== undefined) ? gene.modifier : null;
                                        
                                        let useModifiedRange = modifier !== null && modifier > 0;
                                        let displayMax = useModifiedRange ? max + modifier : max;
                                        let range = displayMax - min;
                                        
                                        let valueToUse = gene.value;
                                        if (!useModifiedRange && modifier !== null && modifier < 0) {
                                            valueToUse = Math.min(gene.value, max);
                                        }
                                        
                                        const percent = range > 0 ? (valueToUse - min) / range : 0;
                                        const clampedPercent = Math.max(0, Math.min(1, percent));
                                        const fullWidth = (objects[borderUid]?.width || 200);
                                        const newWidth = (fullWidth - 4) * clampedPercent;
                                        objects[barUid].width = Math.max(0, newWidth);
                                        if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(barUid);
                                        
                                        const rangeUid = baseUid + '_range';
                                        if (objects[rangeUid]) {
                                            let rangeText = "[" + min + " / " + max;
                                            if (modifier !== null && modifier !== 0) {
                                                rangeText += " (" + (modifier >= 0 ? '+' : '') + modifier + ")";
                                            }
                                            rangeText += "]";
                                            objects[rangeUid].text = rangeText;
                                            if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(rangeUid);
                                        }
                                        
                                        const modifierBarUid = baseUid + '_modifier_bar';
                                        const modifierTextUid = baseUid + '_modifier_text';
                                        if (modifier !== null) {
                                            const modifierRange = Math.abs(modifier);
                                            const baseRange = useModifiedRange ? (max + modifier - min) : (max - min);
                                            const modifierPercent = baseRange > 0 ? modifierRange / baseRange : 0;
                                            const modifierBarWidth = (fullWidth - 4) * Math.min(1, modifierPercent);
                                            const borderX = objects[borderUid]?.x || 0;
                                            
                                            let modifierColor;
                                            if (modifier > 0) modifierColor = 0x228B22;
                                            else if (modifier < 0) modifierColor = 0xFFA500;
                                            else modifierColor = 0x404040;
                                            
                                            if (objects[modifierBarUid]) {
                                                objects[modifierBarUid].x = borderX + 2 + (fullWidth - 4) - modifierBarWidth;
                                                objects[modifierBarUid].width = Math.max(0, modifierBarWidth);
                                                if (objects[modifierBarUid].style) {
                                                    objects[modifierBarUid].style.fill = modifierColor;
                                                } else {
                                                    objects[modifierBarUid].color = modifierColor;
                                                }
                                                if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(modifierBarUid);
                                            }
                                            if (objects[modifierTextUid]) {
                                                objects[modifierTextUid].text = modifier > 0 ? '+' : (modifier < 0 ? '-' : '');
                                                objects[modifierTextUid].x = borderX + 2 + (fullWidth - 4) - (modifierBarWidth / 2);
                                                if (objects[modifierTextUid].style) {
                                                    objects[modifierTextUid].style.fill = 0xFFFFFF;
                                                } else {
                                                    objects[modifierTextUid].color = 0xFFFFFF;
                                                }
                                                if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(modifierTextUid);
                                            }
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

        // --- Chimical Elements Polling Management ---
        if (show && object.attributes && object.attributes.ws_port) {
            const entityPort = object.attributes.ws_port;
            const entityWsUrl = '__gateway_base__' + entityPort;
            window.gameWebSockets = window.gameWebSockets || {};
            const chimicalWsKey = 'entity_chimical_' + entityPort;
            let chimicalWs = window.gameWebSockets[chimicalWsKey];

            const bindChimicalMessageHandler = (ws) => {
                ws.onmessage = (event) => {
                    try {
                        const response = JSON.parse(event.data);
                        if (response.command === 'get_chimical_elements') {
                            if (response.chimical_elements && Array.isArray(response.chimical_elements)) {
                                window.__currentChimicalData = response.chimical_elements;
                                response.chimical_elements.forEach(chimical => {
                                    const baseUid = 'bar_chimical_element_' + chimical.id;
                                    const valueUid = baseUid + '_value';
                                    const lineUid = baseUid + '_line';
                                    const borderUid = baseUid + '_glass_border';
                                    
                                    if (objects[valueUid]) {
                                        objects[valueUid].text = String(chimical.value);
                                        if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(valueUid);
                                    }
                                    
                                    if (objects[borderUid]) {
                                        let min = parseInt(chimical.min) || 0;
                                        let max = parseInt(chimical.max) || 100;
                                        let value = parseInt(chimical.value) || 0;
                                        
                                        let range = max - min;
                                        if (range <= 0) {
                                            range = 1;
                                        }
                                        
                                        let percent = range > 0 ? (value - min) / range : 0;
                                        percent = Math.max(0, Math.min(1, percent));
                                        
                                        const borderX = objects[borderUid].x || 0;
                                        const fullWidth = objects[borderUid].width || 300;
                                        const innerX = borderX + 1;
                                        const innerWidth = fullWidth - 2;
                                        const indicatorX = innerX + (percent * innerWidth);
                                        
                                        if (objects[lineUid]) {
                                            objects[lineUid].x = indicatorX - 1;
                                            if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(lineUid);
                                        }
                                        
                                        if (objects[valueUid]) {
                                            objects[valueUid].x = indicatorX;
                                            if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(valueUid);
                                        }
                                    }
                                });
                                if (app && app.stage) app.stage.sortChildren();
                            }
                        }
                    } catch (e) {
                        console.error('[Entity WS] Chimical Parse Error:', e);
                    }
                };
            };

            const startChimicalPolling = (ws) => {
                console.log('[Chimical Polling] Starting cycle for:', object_uid);
                const fetchChimical = (isImmediate = false) => {
                    if (ws && ws.readyState === 1) {
                        if (isImmediate) console.log('[Chimical Polling] Sending IMMEDIATE refresh call...');
                        ws.send(JSON.stringify({ command: 'get_chimical_elements' }));
                    }
                };
                
                setTimeout(() => fetchChimical(true), 50);
                
                AppData._chimicalPollingIntervals[object_uid] = setInterval(() => fetchChimical(false), 2000);
            };

            if (!chimicalWs || chimicalWs.readyState > 1) {
                chimicalWs = new WebSocket(entityWsUrl);
                window.gameWebSockets[chimicalWsKey] = chimicalWs;
                chimicalWs.onopen = () => {
                    bindChimicalMessageHandler(chimicalWs);
                    startChimicalPolling(chimicalWs);
                };
            } else if (chimicalWs.readyState === 1) {
                bindChimicalMessageHandler(chimicalWs);
                startChimicalPolling(chimicalWs);
            } else if (chimicalWs.readyState === 0) {
                chimicalWs.addEventListener('open', () => {
                    bindChimicalMessageHandler(chimicalWs);
                    startChimicalPolling(chimicalWs);
                }, { once: true });
            }
        } else {
            console.log('[Chimical Polling] Polling state inactive for:', object_uid);
        }
    }
    window['__name__']();
</script>
