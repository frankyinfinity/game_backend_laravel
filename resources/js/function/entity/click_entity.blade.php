<script>

    window['__name__'] = function() {

        let entity_uid = object['uid'];
        let renderable = shapes[entity_uid+'_panel'].renderable;

        //Close all
        const objectPanels = Object.entries(objects).filter(([key, _]) => key.endsWith('_panel')).reduce((obj, [key, value]) => {obj[key] = value;return obj;}, {});
        for (const [key, objectPanel] of Object.entries(objectPanels)) {

            let shapePanel = shapes[key];
            shapePanel.renderable = false;

            let children = objectPanel['children'];
            for (const [key, childUid] of Object.entries(children)) {
                let shape = shapes[childUid];
                shape.renderable = false;
            }

        }

        //Open Panel
        let shapePanel = shapes[entity_uid+'_panel'];
        shapePanel.renderable = !renderable;
        shapePanel.zIndex = 10000;

        //Open Panel (Children)
        let panelChildren = objects[entity_uid+'_panel']['children'];
        for (const [key, childUid] of Object.entries(panelChildren)) {
            let shape = shapes[childUid];
            shape.renderable = !renderable;
            shape.zIndex = 10000;
        }

    }
    window['__name__']();

</script>
