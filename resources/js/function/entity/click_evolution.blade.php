<script>
    window['__name__'] = function() {
        const entityUid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        if (!entityUid) {
            console.warn('Evolution click: no selected entity uid');
            return;
        }

        const modalUid = entityUid + '_evolution_modal';

        const toggleModal = function() {
            if (typeof AppData !== 'undefined') {
                if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
                    AppData.open_modals = {};
                }
            }

            const bodyUid = modalUid + '_body';
            const bodyShape = shapes[bodyUid];
            const currentlyOpen = bodyShape && bodyShape.renderable;
            const show = !currentlyOpen;

            const idsToToggle = [
                modalUid + '_body', modalUid + '_header', modalUid + '_title',
                modalUid + '_close_button', modalUid + '_close_text', modalUid + '_content_viewport',
            ];

            idsToToggle.forEach(function(uid) {
                if (shapes[uid]) shapes[uid].renderable = show;
                if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = show;
            });

            // Show/hide the grid
            if (typeof window['showGrid_' + modalUid] === 'function') {
                window['showGrid_' + modalUid](show);
            }

            if (typeof AppData !== 'undefined') AppData.open_modals[modalUid] = show;
            window.__disableGlobalPan = show;
            if (app && app.stage) app.stage.sortChildren();
        };

        toggleModal();
    }
    window['__name__']();
</script>