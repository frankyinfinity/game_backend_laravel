<script>
    window['__name__'] = function() {
        const panelUid = '__PANEL_UID__';
        if (!panelUid) return;

        // The panel uid is <entity_uid>_panel: used to reset focus and polling.
        const entityUid = panelUid.replace(/_panel$/, '');

        // Hide the panel and every nested child (with their attributes) recursively.
        const hideDrawTree = function(uid) {
            if (shapes[uid]) {
                shapes[uid].renderable = false;
            }

            const drawObject = objects[uid];
            if (!drawObject) return;

            drawObject.attributes = drawObject.attributes || {};
            drawObject.attributes.renderable = false;

            if (Array.isArray(drawObject.children)) {
                drawObject.children.forEach(function(childUid) {
                    hideDrawTree(childUid);
                });
            }
        };

        hideDrawTree(panelUid);

        if (typeof AppData !== 'undefined') {
            if (AppData.actual_focus_uid_entity === entityUid) {
                AppData.actual_focus_uid_entity = null;
            }

            // --- Gene Polling Management ---
            if (AppData._genePollingIntervals && AppData._genePollingIntervals[entityUid]) {
                clearInterval(AppData._genePollingIntervals[entityUid]);
                delete AppData._genePollingIntervals[entityUid];
            }

            // --- Chimical Elements Polling Management ---
            if (AppData._chimicalPollingIntervals && AppData._chimicalPollingIntervals[entityUid]) {
                clearInterval(AppData._chimicalPollingIntervals[entityUid]);
                delete AppData._chimicalPollingIntervals[entityUid];
            }

            // Consume/Attack buttons of the focused element require the entity panel to be open.
            if (AppData.actual_focus_uid_element) {
                const elementPanel = objects[AppData.actual_focus_uid_element + '_panel'];
                if (elementPanel && Array.isArray(elementPanel.children)) {
                    elementPanel.children.forEach(function(childUid) {
                        if (childUid.indexOf('_btn_consume') === -1 && childUid.indexOf('_btn_attack') === -1) {
                            return;
                        }
                        if (shapes[childUid]) {
                            shapes[childUid].renderable = false;
                        }
                        if (objects[childUid]) {
                            objects[childUid].attributes = objects[childUid].attributes || {};
                            objects[childUid].attributes.renderable = false;
                        }
                    });
                }
            }
        }

        if (app && app.stage) {
            app.stage.sortChildren();
        }

        console.log('[Entity Panel] Closed panel:', panelUid);
    };
    window['__name__']();
</script>
