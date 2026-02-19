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
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = 10000;
        AppData.actual_focus_uid_target = show ? target_uid : null;

        // Get target title and description from attributes
        let target_title = object['attributes']['target_title'] || 'Obiettivo';
        let target_description = object['attributes']['target_description'] || 'Nessuna descrizione disponibile';

        // Manage panel children and update text
        for (const childUid of objects[panel_uid]['children']) {
            if (shapes[childUid]) {
                shapes[childUid].renderable = show;
                shapes[childUid].zIndex = 10001;
                
                // Update text for title and description
                if (childUid.endsWith('_panel_title')) {
                    shapes[childUid].text = target_title;
                } else if (childUid.endsWith('_panel_description')) {
                    shapes[childUid].text = target_description;
                }
            }
        }
    }
    window['__name__']();
</script>