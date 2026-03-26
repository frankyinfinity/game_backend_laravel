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
        }
        AppData.actual_focus_uid_element = show ? object_uid : null;

        // Toggle all descendants too, so nested structures remain visible
        setChildrenVisibility(panel_uid, show, 10001);
        if (app && app.stage) {
            app.stage.sortChildren();
        }
    }
    window['__name__']();
</script>
