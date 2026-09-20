<script>
    window['__name__'] = function() {
        const panelUid = '__PANEL_UID__';
        if (!panelUid) return;

        // The panel uid is <element_uid>_panel: used to reset focus and polling.
        const elementUid = panelUid.replace(/_panel$/, '');

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
            if (AppData.actual_focus_uid_element === elementUid) {
                AppData.actual_focus_uid_element = null;
            }

            // --- Gene Polling Management ---
            if (AppData._genePollingIntervals && AppData._genePollingIntervals[elementUid]) {
                clearInterval(AppData._genePollingIntervals[elementUid]);
                delete AppData._genePollingIntervals[elementUid];
            }

            // --- Chimical Elements Polling Management ---
            if (AppData._chimicalPollingIntervals && AppData._chimicalPollingIntervals[elementUid]) {
                clearInterval(AppData._chimicalPollingIntervals[elementUid]);
                delete AppData._chimicalPollingIntervals[elementUid];
            }
        }

        if (app && app.stage) {
            app.stage.sortChildren();
        }

        console.log('[Element Panel] Closed panel:', panelUid);
    };
    window['__name__']();
</script>
