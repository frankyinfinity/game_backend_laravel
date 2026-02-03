<script>
    window['__name__'] = function() {
        let object_uid = object['uid'];
        let panel_uid = object_uid + '_panel';
        
        if (!shapes[panel_uid]) return;

        let isVisible = shapes[panel_uid].renderable;

        // Chiude tutti gli altri panel Entity
        const entityPanels = Object.entries(objects).filter(([key, _]) => !key.startsWith('element_') && key.endsWith('_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
        for (const [key, objectPanel] of Object.entries(entityPanels)) {
            shapes[key].renderable = false;
            for (const childUid of objectPanel['children']) {
                if (shapes[childUid]) shapes[childUid].renderable = false;
            }
        }

        // Toggle Entity Panel
        let show = !isVisible;
        shapes[panel_uid].renderable = show;
        shapes[panel_uid].zIndex = 10000;
        AppData.actual_focus_uid_entity = show ? object_uid : null;

        // Figli del pannello entità
        for (const childUid of objects[panel_uid]['children']) {
            if (shapes[childUid]) {
                shapes[childUid].renderable = show;
                shapes[childUid].zIndex = 10001;
            }
        }

        // Reattività: Se c'è un elemento aperto, aggiorna il suo tasto Consuma
        if (AppData.actual_focus_uid_element) {
            let elPanelUid = AppData.actual_focus_uid_element + '_panel';
            if (objects[elPanelUid]) {
                for (const childUid of objects[elPanelUid]['children']) {
                    // Controllo se il figlio fa parte del bottone consuama
                    if (childUid.includes('_btn_consume')) {
                        if (shapes[childUid]) shapes[childUid].renderable = show;
                    }
                }
            }
        }
    }
    window['__name__']();
</script>
