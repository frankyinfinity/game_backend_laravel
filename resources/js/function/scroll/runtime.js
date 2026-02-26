(function () {
    window.getScrollExcludedUids = function (appData) {
        if (appData && Array.isArray(appData.scroll_excluded_uids)) {
            return appData.scroll_excluded_uids;
        }
        return ['map_nav_up', 'map_nav_left', 'map_nav_down', 'map_nav_right'];
    };

    window.getObjectScrollGroup = function (obj) {
        return (obj && obj.attributes && obj.attributes.scroll_group) ? obj.attributes.scroll_group : null;
    };

    window.ensureScrollGroupsState = function (appData) {
        if (!appData.scroll_groups || typeof appData.scroll_groups !== 'object') {
            appData.scroll_groups = {};
        }
        return appData.scroll_groups;
    };

    window.ensureScrollGroupState = function (appData, groupName) {
        if (!groupName) return null;
        const groups = window.ensureScrollGroupsState(appData);
        if (!groups[groupName]) {
            groups[groupName] = { offset_x: 0, offset_y: 0 };
        }
        return groups[groupName];
    };

    window.getScrollGroupOffset = function (appData, groupName) {
        const groupState = window.ensureScrollGroupState(appData, groupName);
        if (!groupState) return { x: 0, y: 0 };
        return {
            x: parseInt(groupState.offset_x || 0, 10),
            y: parseInt(groupState.offset_y || 0, 10)
        };
    };

    window.incrementScrollGroupOffset = function (appData, groupName, dx, dy) {
        const groupState = window.ensureScrollGroupState(appData, groupName);
        if (!groupState) return;
        groupState.offset_x = parseInt(groupState.offset_x || 0, 10) + parseInt(dx || 0, 10);
        groupState.offset_y = parseInt(groupState.offset_y || 0, 10) + parseInt(dy || 0, 10);
    };

    window.isScrollExcludedUid = function (uid, excludedUids) {
        const blockedUids = Array.isArray(excludedUids) ? excludedUids : [];
        return uid.indexOf('appbar') === 0
            || uid.indexOf('modal_') === 0
            || uid.indexOf('_score_') !== -1
            || blockedUids.includes(uid);
    };

    window.isInScrollGroup = function (uid, obj, groupName, excludedUids) {
        if (!uid || !obj || !groupName || window.isScrollExcludedUid(uid, excludedUids)) return false;
        return window.getObjectScrollGroup(obj) === groupName;
    };

    window.applyScrollGroupOffsetToObject = function (obj, appData, groupName) {
        const targetGroup = groupName || window.getObjectScrollGroup(obj);
        if (!targetGroup) return obj;
        const offset = window.getScrollGroupOffset(appData, targetGroup);
        const offsetX = offset.x;
        const offsetY = offset.y;
        if (!offsetX && !offsetY) return obj;

        if (obj.type === 'multi_line' && Array.isArray(obj.points)) {
            obj.points = obj.points.map((point) => ({
                x: point.x + offsetX,
                y: point.y + offsetY
            }));
            return obj;
        }

        if (typeof obj.x === 'number') obj.x += offsetX;
        if (typeof obj.y === 'number') obj.y += offsetY;
        return obj;
    };

    window.ensureScrollViewportMask = function (app, state, clipStartY) {
        if (!app || !app.stage || !app.renderer) return;
        if (!state.scrollViewportMask) {
            state.scrollViewportMask = new PIXI.Graphics();
            app.stage.addChild(state.scrollViewportMask);
        }
        state.scrollViewportMask.clear();
        state.scrollViewportMask.beginFill(0xFFFFFF);
        state.scrollViewportMask.drawRect(0, clipStartY, app.renderer.width, Math.max(0, app.renderer.height - clipStartY));
        state.scrollViewportMask.endFill();
        state.scrollViewportMask.renderable = false;
        state.scrollViewportMask.zIndex = -999999;
    };

    window.refreshScrollClipping = function (app, state, objects, shapes, appData, excludedUids, clipStartY) {
        window.ensureScrollViewportMask(app, state, clipStartY);
        if (!state.scrollViewportMask) return;

        Object.keys(objects).forEach((uid) => {
            const obj = objects[uid];
            if (!obj) return;
            const shape = shapes[uid];
            if (!shape) return;
            const groupName = window.getObjectScrollGroup(obj);
            shape.mask = window.isInScrollGroup(uid, obj, groupName, excludedUids) ? state.scrollViewportMask : null;
        });
    };
})();
