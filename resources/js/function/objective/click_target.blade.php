<script>
    window['__name__'] = function() {
        let target_uid = object['uid'];
        // The panel UID is the target container UID with '_container' replaced by '_container_panel'
        // target_uid ends with '_container', so we need to add '_panel' to get the panel UID
        let panel_uid = target_uid + '_panel';
        
        if (!shapes[panel_uid]) return;

        let isVisible = shapes[panel_uid].renderable;

        // Close all other target panels
        const targetPanels = Object.entries(objects).filter(([key, _]) => key.endsWith('_container_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
        for (const [key, objectPanel] of Object.entries(targetPanels)) {
            if (shapes[key]) shapes[key].renderable = false;
            if (objectPanel['children']) {
                for (const childUid of objectPanel['children']) {
                    if (shapes[childUid]) shapes[childUid].renderable = false;
                }
            }
        }

        // Toggle Target Panel
        let show = !isVisible;
        const panelZIndex = 50000;
        const panelChildZIndex = 50001;

        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = panelZIndex;
        AppData.actual_focus_uid_target = show ? target_uid : null;
        AppData.actual_focus_target_player_id = show
            ? ((object['attributes'] && object['attributes']['target_player_id']) ? object['attributes']['target_player_id'] : null)
            : null;

        // Get target title and description from attributes
        let target_title = object['attributes']['target_title'] || 'Obiettivo';
        let target_description = object['attributes']['target_description'] || 'Nessuna descrizione disponibile';
        let target_state = object['attributes']['target_state'] || 'locked';

        const hasAnyInProgressTarget = function() {
            if (typeof objects === 'undefined') return false;
            return Object.keys(objects).some(function(uid) {
                if (!uid.endsWith('_container')) return false;
                const attrs = (objects[uid] && objects[uid]['attributes']) ? objects[uid]['attributes'] : null;
                return attrs && attrs['target_state'] === 'in_progress';
            });
        };

        if (typeof AppData !== 'undefined') {
            AppData.objective_has_active_in_progress = hasAnyInProgressTarget();
        }
        const getStateLabel = function(state) {
            if (state === 'locked') return 'Bloccato';
            if (state === 'unlocked') return 'Sbloccato';
            if (state === 'in_progress') return 'In corso';
            if (state === 'completed') return 'Completato';
            return state || '';
        };

        // Manage panel children and update text
        for (const childUid of objects[panel_uid]['children']) {
            if (shapes[childUid]) {
                const isStartButton = childUid.endsWith('_panel_start_btn') || childUid.endsWith('_panel_start_btn_text');
                if (isStartButton) {
                    const hasActiveInProgress = (typeof AppData !== 'undefined') && !!AppData.objective_has_active_in_progress;
                    shapes[childUid].renderable = show && target_state === 'unlocked' && !hasActiveInProgress;
                } else {
                    shapes[childUid].renderable = show;
                }
                shapes[childUid].zIndex = panelChildZIndex;
                
                // Update text for title and description
                if (childUid.endsWith('_panel_title')) {
                    shapes[childUid].text = target_title;
                } else if (childUid.endsWith('_panel_description')) {
                    shapes[childUid].text = target_description;
                } else if (childUid.endsWith('_panel_state_value')) {
                    shapes[childUid].text = getStateLabel(target_state);
                }
            }
        }

        // Ensure zIndex changes are applied immediately in both normal and test renderers.
        if (shapes[panel_uid] && shapes[panel_uid].parent && typeof shapes[panel_uid].parent.sortChildren === 'function') {
            shapes[panel_uid].parent.sortChildren();
        }
        if (typeof app !== 'undefined' && app && app.stage && typeof app.stage.sortChildren === 'function') {
            app.stage.sortChildren();
        }
    }
    window['__name__']();
</script>
