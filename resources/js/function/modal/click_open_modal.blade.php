<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    const idsToShow = [
        modalUid + '_body',
        modalUid + '_header',
        modalUid + '_title',
        modalUid + '_close_button',
        modalUid + '_close_text',
        modalUid + '_content_viewport',
    ];

    const viewportObject = objects[modalUid + '_content_viewport'];
    if (viewportObject && viewportObject.attributes && Array.isArray(viewportObject.attributes.scroll_child_uids)) {
        viewportObject.attributes.scroll_child_uids.forEach(function(uid) {
            idsToShow.push(uid);
        });
    }

    idsToShow.forEach(function(uid) {
        if (shapes[uid]) {
            shapes[uid].renderable = true;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = true;
        }
    });

    window.__disableGlobalPan = true;
};

window['__name__']();

</script>
