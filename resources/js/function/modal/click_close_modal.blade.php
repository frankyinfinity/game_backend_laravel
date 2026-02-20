<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    const idsToHide = [
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
            idsToHide.push(uid);
        });
    }

    idsToHide.forEach(function(uid) {
        if (shapes[uid]) {
            shapes[uid].renderable = false;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = false;
        }
    });

    window['__modal_scroll_drag_' + modalUid] = null;
    window.__disableGlobalPan = false;
};

window['__name__']();

</script>
