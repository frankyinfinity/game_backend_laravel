<script>
    window['__name__'] = function() {
        let object_uid = object['uid'];
        let panel_uid = object_uid + '_panel';
        
        if (!shapes[panel_uid]) return;

        let isVisible = shapes[panel_uid].renderable;

        // Chiude tutti gli altri panel Element
        const elementPanels = Object.entries(objects).filter(([key, _]) => key.startsWith('element_') && key.endsWith('_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
        for (const [key, objectPanel] of Object.entries(elementPanels)) {
            if (shapes[key]) shapes[key].renderable = false;
            if (objectPanel['children']) {
                for (const childUid of objectPanel['children']) {
                    if (shapes[childUid]) shapes[childUid].renderable = false;
                }
            }
        }

        // Toggle Element Panel
        let show = !isVisible;
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = 10000;
        AppData.actual_focus_uid_element = show ? object_uid : null;

        // Gestione figli del pannello
        for (const childUid of objects[panel_uid]['children']) {
            if (shapes[childUid]) {
                if (childUid.includes('_btn_consume')) {
                    // Visibile solo se un Entity Panel è aperto
                    let canConsume = (show && AppData.actual_focus_uid_entity !== null && AppData.actual_focus_uid_entity !== undefined);
                    shapes[childUid].renderable = canConsume;
                    shapes[childUid].zIndex = 10002;
                } else {
                    shapes[childUid].renderable = show;
                    shapes[childUid].zIndex = 10001;
                }
            }
        }
    }
    window['__name__']();
</script>
