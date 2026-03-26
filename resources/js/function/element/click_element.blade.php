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
                if (shapes[childUid]) {
                    if (childUid.includes('_btn_consume')) {
                        let canConsume = (show && AppData.actual_focus_uid_entity !== null && AppData.actual_focus_uid_entity !== undefined);
                        childRenderable = canConsume;
                        shapes[childUid].renderable = canConsume;
                        shapes[childUid].zIndex = zIndex + 1;
                    } else if (childUid.includes('_btn_attack')) {
                        let canAttack = (show && AppData.actual_focus_uid_entity !== null && AppData.actual_focus_uid_entity !== undefined);
                        childRenderable = canAttack;
                        shapes[childUid].renderable = canAttack;
                        shapes[childUid].zIndex = zIndex + 1;
                    } else {
                        shapes[childUid].renderable = show;
                        shapes[childUid].zIndex = zIndex;
                    }
                }

                if (objects[childUid]) {
                    objects[childUid].attributes = objects[childUid].attributes || {};
                    objects[childUid].attributes.renderable = childRenderable;
                }

                if (objects[childUid] && Array.isArray(objects[childUid]['children']) && objects[childUid]['children'].length > 0) {
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
        shapes[panel_uid].zIndex = 10000;
        if (objects[panel_uid]) {
            objects[panel_uid].attributes = objects[panel_uid].attributes || {};
            objects[panel_uid].attributes.renderable = show;
        }
        AppData.actual_focus_uid_element = show ? object_uid : null;

        // Toggle all descendants too, so nested structures remain visible
        setChildrenVisibility(panel_uid, show, 10001);
    }
    window['__name__']();
</script>
