<script>
    window['__name__'] = function() {
        let object_uid = object['uid'];
        let panel_uid = object_uid + '_panel';

        if (!shapes[panel_uid]) return;

        let isVisible = shapes[panel_uid].renderable;

        function setChildrenVisibility(rootUid, show, zIndex) {
            const rootObject = objects[rootUid];
            if (!rootObject || !Array.isArray(rootObject['children'])) return;

            for (const childUid of rootObject['children']) {
                let childRenderable = show;
                const childObject = objects[childUid];
                const childAttributes = childObject && childObject.attributes ? childObject.attributes : {};
                const defaultChildZIndex = (typeof childAttributes.z_index === 'number')
                    ? childAttributes.z_index
                    : zIndex;
                if (shapes[childUid]) {
                    if (childUid.includes('_btn_consume')) {
                        let canConsume = (show && AppData.actual_focus_uid_entity !== null && AppData.actual_focus_uid_entity !== undefined);
                        childRenderable = canConsume;
                        shapes[childUid].renderable = canConsume;
                        shapes[childUid].zIndex = defaultChildZIndex;
                    } else if (childUid.includes('_btn_attack')) {
                        let canAttack = (show && AppData.actual_focus_uid_entity !== null && AppData.actual_focus_uid_entity !== undefined);
                        childRenderable = canAttack;
                        shapes[childUid].renderable = canAttack;
                        shapes[childUid].zIndex = defaultChildZIndex;
                    } else {
                        shapes[childUid].renderable = show;
                        shapes[childUid].zIndex = defaultChildZIndex;
                    }
                }

                if (childObject) {
                    childObject.attributes = childObject.attributes || {};
                    childObject.attributes.renderable = childRenderable;
                    childObject.attributes.z_index = defaultChildZIndex;
                }

                if (childObject && Array.isArray(childObject['children']) && childObject['children'].length > 0) {
                    setChildrenVisibility(childUid, show, zIndex + 1);
                }
            }
        }

        // Hide all other element panels first
        const elementPanels = Object.entries(objects)
            .filter(([key, _]) => key.startsWith('element_') && key.endsWith('_panel'))
            .reduce((obj, [key, value]) => {
                obj[key] = value;
                return obj;
            }, {});

        for (const [key, _objectPanel] of Object.entries(elementPanels)) {
            if (shapes[key]) shapes[key].renderable = false;
            setChildrenVisibility(key, false, 10001);

            // Clear gene polling for other elements
            const otherUid = key.replace('_panel', '');
            if (window.AppData && window.AppData._genePollingIntervals && window.AppData._genePollingIntervals[otherUid]) {
                clearInterval(window.AppData._genePollingIntervals[otherUid]);
                delete window.AppData._genePollingIntervals[otherUid];
                console.log('[Gene Polling] Stopped for other element:', otherUid);
            }
        }

        // Toggle this panel
        let show = !isVisible;
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = (objects[panel_uid] && objects[panel_uid].attributes && typeof objects[panel_uid].attributes.z_index === 'number')
            ? objects[panel_uid].attributes.z_index
            : 10000;
        if (objects[panel_uid]) {
            objects[panel_uid].attributes = objects[panel_uid].attributes || {};
            objects[panel_uid].attributes.renderable = show;
            objects[panel_uid].attributes.z_index = shapes[panel_uid].zIndex;
        }
        AppData.actual_focus_uid_element = show ? object_uid : null;

        // --- Gene Polling Management ---
        AppData._genePollingIntervals = AppData._genePollingIntervals || {};
        if (AppData._genePollingIntervals[object_uid]) {
            clearInterval(AppData._genePollingIntervals[object_uid]);
            delete AppData._genePollingIntervals[object_uid];
        }

        if (show && object.attributes && object.attributes.ws_port) {
            const elementPort = object.attributes.ws_port;
            const elementWsUrl = '__gateway_base__' + elementPort;
            window.gameWebSockets = window.gameWebSockets || {};
            const elementWsKey = 'element_' + elementPort;
            let elementWs = window.gameWebSockets[elementWsKey];

            const startPolling = (ws) => {
                console.log('[Gene Polling] Started for element:', object_uid);
                AppData._genePollingIntervals[object_uid] = setInterval(() => {
                    if (ws && ws.readyState === 1) { // 1 = OPEN
                        ws.send(JSON.stringify({ command: 'get_genes' }));
                    }
                }, 2000);
            };

            if (!elementWs || elementWs.readyState > 1) { // 2 = CLOSING, 3 = CLOSED
                elementWs = new WebSocket(elementWsUrl);
                window.gameWebSockets[elementWsKey] = elementWs;
                elementWs.onopen = () => startPolling(elementWs);
                elementWs.onmessage = (event) => {
                    try {
                        const response = JSON.parse(event.data);
                        if (response.command === 'get_genes') {
                            console.log('[Gene Polling] Genes for element ' + object_uid + ':', response.genes);
                            
                            if (response.genes && Array.isArray(response.genes)) {
                                response.genes.forEach(gene => {
                                    const baseUid = object_uid + '_progress_bar_' + gene.key;
                                    
                                    // 1. Update Text Label
                                    const textUid = baseUid + '_text';
                                    if (objects[textUid]) {
                                        objects[textUid].text = (gene.name || gene.key) + " (" + gene.value + ")";
                                        if (typeof redrawShapeFromObject === 'function') redrawShapeFromObject(textUid);
                                    }

                                    // 2. Update Progress Bar Width
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
                                    }
                                });
                                if (app && app.stage) app.stage.sortChildren();
                            }
                        }
                    } catch (e) {
                        console.error('[Element WS] Parse Error:', e);
                    }
                };
            } else if (elementWs.readyState === 1) {
                startPolling(elementWs);
            } else if (elementWs.readyState === 0) { // 0 = CONNECTING
                elementWs.addEventListener('open', () => startPolling(elementWs), { once: true });
            }
        } else {
            console.log('[Gene Polling] Stopped for element:', object_uid);
        }
        // ------------------------------

        // Toggle all descendants too, so nested structures remain visible
        setChildrenVisibility(panel_uid, show, 10001);
        if (app && app.stage) {
            app.stage.sortChildren();
        }
    }
    window['__name__']();
</script>
