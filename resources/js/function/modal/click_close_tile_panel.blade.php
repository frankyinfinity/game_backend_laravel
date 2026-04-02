<script>
window['__name__'] = function() {
    var panelUid = 'tile_panel';
    var allUids = [
        panelUid + '_body',
        panelUid + '_header',
        panelUid + '_title',
        panelUid + '_close_button',
        panelUid + '_close_text'
    ];
    for (var ci = 0; ci < 10; ci++) {
        allUids.push(panelUid + '_content_' + ci);
    }

    for (var i = 0; i < allUids.length; i++) {
        var uid = allUids[i];
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = false;
        }
    }

    if (window.__tilePanelRefreshInterval) {
        clearInterval(window.__tilePanelRefreshInterval);
        window.__tilePanelRefreshInterval = null;
    }

    if (typeof AppData !== 'undefined') {
        if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
            AppData.open_modals = {};
        }
        AppData.open_modals[panelUid] = false;

        var borderUid = AppData.__selectedTileBorderUid;
        if (borderUid && typeof shapes !== 'undefined' && shapes[borderUid]) {
            if (shapes[borderUid].parent) {
                shapes[borderUid].parent.removeChild(shapes[borderUid]);
            }
            if (typeof shapes[borderUid].destroy === 'function') shapes[borderUid].destroy();
            delete shapes[borderUid];
            if (typeof objects !== 'undefined') delete objects[borderUid];
            AppData.__selectedTileBorderUid = null;
        }

        AppData.__tilePanelInfo = null;
    }
};
window['__name__']();
</script>
