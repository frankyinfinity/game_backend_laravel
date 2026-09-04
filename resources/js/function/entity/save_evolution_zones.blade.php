<script>
window['__name__'] = function() {
    var modalUid = '__MODAL_UID__';
    var pixels = window['_evoPixels_' + modalUid] || [];
    var modified = window['_evoModifiedZones_' + modalUid] || {};

    // Build a zone info map (zone_id -> zone_name) from the pixel data
    var zoneInfo = {};
    for (var i = 0; i < pixels.length; i++) {
        var zid = pixels[i].zone_id;
        if (zid !== null && zid !== undefined && !zoneInfo[zid]) {
            zoneInfo[zid] = {
                zone_id: zid,
                zone_name: pixels[i].name || '',
            };
        }
    }

    // Build the JSON array of modified zones with the required structure
    var modifiedZones = [];
    for (var key in modified) {
        if (modified.hasOwnProperty(key)) {
            var zoneId   = parseInt(key, 10);
            var pc       = modified[key];

            // Ensure pc is treated as a number
            if (typeof pc !== 'number') {
                pc = parseInt(pc, 16);
            }

            var r = (pc >> 16) & 255;
            var g = (pc >> 8) & 255;
            var b = pc & 255;

            modifiedZones.push({
                zone_id:   zoneId,
                zone_name: zoneInfo[zoneId] ? zoneInfo[zoneId].zone_name : '',
                r: r,
                g: g,
                b: b
            });
        }
    }

    var jsonString = JSON.stringify(modifiedZones, null, 2);

    // Expose the generated JSON via a window variable for external inspection
    window['_evoSavedZones_' + modalUid] = jsonString;

    if (modifiedZones.length === 0) {
        console.log('[Evolution Save] Nessuna zona modificata');
        return;
    }

    console.log('[Evolution Save] Zonal modificate salvate:', jsonString);

    // ===== Invio del JSON finale al websocket 'save' del container evolution =====
    var port = '__port__';
    if (!port) {
        console.error('[Evolution Save] WebSocket port not found for evolution container');
        return;
    }

    var wsUrl = '__gateway_base__' + port;

    // Global cache
    window.gameWebSockets = window.gameWebSockets || {};
    var ws = window.gameWebSockets[port];

    var sendCommand = function() {
        ws.send(JSON.stringify({
            command: 'save',
            entity_id: parseInt('__ENTITY_ID__', 10),
            zones: modifiedZones
        }));
    };

    var onMessage = function(event) {
        var response;
        try {
            response = JSON.parse(event.data);
        } catch (e) {
            response = event.data;
        }
        console.log('[Evolution Save] WS Response:', response);
    };

    // Funzione per chiudere la modal di evoluzione
    var closeModal = function() {
        var idsToHide = [
            modalUid + '_body', modalUid + '_header', modalUid + '_title',
            modalUid + '_close_button', modalUid + '_close_text', modalUid + '_content_viewport',
            modalUid + '_save_button_rect', modalUid + '_save_button_text',
        ];

        idsToHide.forEach(function(uid) {
            if (shapes[uid]) shapes[uid].renderable = false;
            if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
        });

        // Nasconde anche la griglia se presente
        if (typeof window['showGrid_' + modalUid] === 'function') {
            window['showGrid_' + modalUid](false);
        }

        if (typeof AppData !== 'undefined') AppData.open_modals[modalUid] = false;
        window.__disableGlobalPan = false;
        if (app && app.stage) app.stage.sortChildren();
    };

    // Funzione per rendere grigio il pulsante 'evoluzione' nell'entity panel
    var grayOutEvolutionButton = function() {
        var entityUid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        if (!entityUid) {
            console.warn('[Evolution Save] No entity uid found for graying out evolution button');
            return;
        }

        var evolutionButtonRectUid = entityUid + '_button_evolution_rect';
        var evolutionButtonTextUid = entityUid + '_button_evolution_text';

        // Cambia il colore solo del rect a grigio scuro (mantieni il testo bianco) e rimuovi il pointer
        if (shapes[evolutionButtonRectUid]) {
            shapes[evolutionButtonRectUid].tint = 0x404040;
            shapes[evolutionButtonRectUid].renderable = true;
            shapes[evolutionButtonRectUid].interactive = false;
            shapes[evolutionButtonRectUid].buttonMode = false;
        }
        if (shapes[evolutionButtonTextUid]) {
            shapes[evolutionButtonTextUid].renderable = true;
        }

        // Rimuovi l'interattività dal pulsante (rendendolo non cliccabile)
        if (objects[evolutionButtonRectUid]) {
            objects[evolutionButtonRectUid].attributes = objects[evolutionButtonRectUid].attributes || {};
            objects[evolutionButtonRectUid].attributes.interactives = {};
        }

        console.log('[Evolution Save] Evolution button grayed out for entity:', entityUid);
    };

    if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
        ws = new WebSocket(wsUrl);
        window.gameWebSockets[port] = ws;

        ws.onopen = function() {
            console.log('[Evolution Save] WS Connected to ' + wsUrl);
            sendCommand();
        };
        ws.onmessage = function(event) {
            onMessage(event);
            // Chiude la modal dopo aver ricevuto la risposta dal server
            closeModal();
            // Rende grigio il pulsante 'evoluzione'
            grayOutEvolutionButton();
        };
        ws.onerror = function(error) {
            console.error('[Evolution Save] WS Error:', error);
        };
    } else {
        if (ws.readyState === WebSocket.OPEN) {
            sendCommand();
        } else if (ws.readyState === WebSocket.CONNECTING) {
            ws.addEventListener('open', sendCommand, { once: true });
        }
        // Chiude la modal dopo aver inviato il comando
        // Usiamo onmessage per chiudere la modal dopo la risposta
        var originalOnMessage = ws.onmessage;
        ws.onmessage = function(event) {
            if (originalOnMessage) originalOnMessage(event);
            closeModal();
            // Rende grigio il pulsante 'evoluzione'
            grayOutEvolutionButton();
        };
    }
};
window['__name__']();
</script>
