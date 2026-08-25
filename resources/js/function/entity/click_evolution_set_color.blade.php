<script>
    window['__name__'] = function() {
        const zoneId = '__zone_id__';
        const color = '__color__';
        const entityUid = '__entity_uid__';
        const modalUid = entityUid + '_evolution_modal';
        const colorRectUid = modalUid + '_zone_' + zoneId + '_color';

        // Cambia il colore del rettangolo nella modal
        if (shapes[colorRectUid]) {
            shapes[colorRectUid].color = color;
            if (objects[colorRectUid] && objects[colorRectUid].attributes) {
                objects[colorRectUid].attributes.color = color;
            }
            if (typeof redrawShapeFromObject === 'function') {
                redrawShapeFromObject(colorRectUid);
            }
            if (app && app.stage) {
                app.stage.sortChildren();
            }
        }
    }
    window['__name__']();
</script>