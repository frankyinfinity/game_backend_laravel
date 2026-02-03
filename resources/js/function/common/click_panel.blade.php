<script>

    window['__name__'] = function() {

        let object_uid = object['uid'];
        let panel_uid = object_uid + '_panel';
        
        if (!shapes[panel_uid]) {
            console.warn('Panel not found for UID: ' + panel_uid);
            return;
        }

        let renderable = shapes[panel_uid].renderable;

        //Close all panels
        const objectPanels = Object.entries(objects).filter(([key, _]) => key.endsWith('_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
        for (const [key, objectPanel] of Object.entries(objectPanels)) {

            let shapePanel = shapes[key];
            shapePanel.renderable = false;

            let children = objectPanel['children'];
            for (const [key, childUid] of Object.entries(children)) {
                let shape = shapes[childUid];
                if (shape) {
                    shape.renderable = false;
                }
            }

        }

        //Toggle current Panel
        let shapePanel = shapes[panel_uid];
        shapePanel.renderable = !renderable;
        shapePanel.zIndex = 10000;

        let actual_focus_uid = null;
        if(shapePanel.renderable) {
            actual_focus_uid = object_uid;
        }
        AppData.actual_focus_uid = actual_focus_uid;

        //Open/Close Panel Children
        let panelChildren = objects[panel_uid]['children'];
        for (const [key, childUid] of Object.entries(panelChildren)) {
            let shape = shapes[childUid];
            if (shape) {
                shape.renderable = !renderable;
                shape.zIndex = 10000;
            }
        }

    }
    window['__name__']();

</script>
