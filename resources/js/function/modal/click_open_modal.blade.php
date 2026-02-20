<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    const viewportUid = modalUid + '_content_viewport';
    const idsToAlwaysShow = [
        modalUid + '_body',
        modalUid + '_header',
        modalUid + '_title',
        modalUid + '_close_button',
        modalUid + '_close_text',
        viewportUid,
    ];

    idsToAlwaysShow.forEach(function(uid) {
        if (shapes[uid]) {
            shapes[uid].renderable = true;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = true;
        }
    });

    const viewportObject = objects[viewportUid];
    if (viewportObject && viewportObject.attributes && Array.isArray(viewportObject.attributes.scroll_child_uids)) {
        const initialRenderables = (viewportObject.attributes.scroll_initial_renderables && typeof viewportObject.attributes.scroll_initial_renderables === 'object')
            ? viewportObject.attributes.scroll_initial_renderables
            : {};

        viewportObject.attributes.scroll_child_uids.forEach(function(uid) {
            const shouldShow = initialRenderables[uid] === undefined ? true : !!initialRenderables[uid];
            if (shapes[uid]) {
                shapes[uid].renderable = shouldShow;
            }
            if (objects[uid] && objects[uid].attributes) {
                objects[uid].attributes.renderable = shouldShow;
            }
        });
    }

    if (typeof window.refreshAllModalViewportMasks === 'function') {
        window.refreshAllModalViewportMasks();
    }

    window.__disableGlobalPan = true;
};

window['__name__']();

</script>
