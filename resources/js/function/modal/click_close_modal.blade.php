<script>

window['__name__'] = function() {
    const modalUid = '__MODAL_UID__';
    if (typeof AppData !== 'undefined') {
        if (!AppData.open_modals || typeof AppData.open_modals !== 'object') {
            AppData.open_modals = {};
        }
        AppData.open_modals[modalUid] = false;
    }
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

    if (typeof modalUid === 'string' && modalUid.startsWith('objective_modal_')) {
        let resolvedPlayerId = null;
        if (typeof playerId !== 'undefined') {
            resolvedPlayerId = playerId;
        } else if (typeof window !== 'undefined' && typeof window.playerId !== 'undefined') {
            resolvedPlayerId = window.playerId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.player_id !== 'undefined') {
            resolvedPlayerId = AppData.player_id;
        }

        let resolvedSessionId = null;
        if (typeof sessionId !== 'undefined') {
            resolvedSessionId = sessionId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.session_id !== 'undefined') {
            resolvedSessionId = AppData.session_id;
        }

        if (resolvedPlayerId) {
            const requestData = {
                player_id: resolvedPlayerId,
                modal_uid: modalUid,
                renderable: false
            };
            if (resolvedSessionId) {
                requestData.session_id = resolvedSessionId;
            }
            $.ajax({
                url: `${BACK_URL}/api/auth/game/objective/modal_visibility`,
                type: 'POST',
                data: requestData
            });
        }
    }
};

window['__name__']();

</script>
