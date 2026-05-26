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

    // Reset entity body grid and clear zone borders if the modal has them
    var resetGridFn = window['resetEntityBodyGrid_' + modalUid];
    if (typeof resetGridFn === 'function') {
        resetGridFn();
    }

    // Hide zone panel if the modal has it
    var panel = shapes[modalUid + '_zone_panel'];
    var colorSquare = shapes[modalUid + '_zone_color_square'];
    var nameText = shapes[modalUid + '_zone_name_text'];
    var closeButton = shapes[modalUid + '_zone_close_button'];
    var closeText = shapes[modalUid + '_zone_close_text'];
    var borderTop = shapes[modalUid + '_zone_border_top'];
    var borderBottom = shapes[modalUid + '_zone_border_bottom'];
    var borderLeft = shapes[modalUid + '_zone_border_left'];
    var borderRight = shapes[modalUid + '_zone_border_right'];
    if (panel) panel.renderable = false;
    if (colorSquare) colorSquare.renderable = false;
    if (nameText) nameText.renderable = false;
    if (closeButton) closeButton.renderable = false;
    if (closeText) closeText.renderable = false;
    if (borderTop) borderTop.renderable = false;
    if (borderBottom) borderBottom.renderable = false;
    if (borderLeft) borderLeft.renderable = false;
    if (borderRight) borderRight.renderable = false;

    // Hide sliders if the modal has them
    var sliderSuffixes = ['slider_red', 'slider_green', 'slider_blue'];
    var sliderParts = ['knob', 'track_bg', 'track_fill', 'title', 'min', 'max'];
    sliderSuffixes.forEach(function(suffix) {
        sliderParts.forEach(function(part) {
            var uid = modalUid + '_' + suffix + '_' + part;
            if (shapes[uid]) shapes[uid].renderable = false;
        });
    });

    // Hide direction buttons if the modal has them
    var dirButtons = ['dir_up', 'dir_down', 'dir_left', 'dir_right'];
    dirButtons.forEach(function(dir) {
        var buttonUid = modalUid + '_' + dir;
        var textUid = modalUid + '_' + dir + '_text';
        if (shapes[buttonUid]) shapes[buttonUid].renderable = false;
        if (shapes[textUid]) shapes[textUid].renderable = false;
    });

    // Hide direction title and container
    var dirTitle = shapes[modalUid + '_dir_title'];
    if (dirTitle) dirTitle.renderable = false;
    var dirContainer = shapes[modalUid + '_dir_container'];
    if (dirContainer) dirContainer.renderable = false;

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
