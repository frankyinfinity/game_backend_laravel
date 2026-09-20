// Temporary harness: reproduce open -> close(X) -> reopen(click) flow for entity/element panels.
// Run with: node __tmp_reopen_check.cjs
const fs = require('fs');
const path = require('path');

function loadScript(relFile, name) {
    return fs.readFileSync(path.join(__dirname, relFile), 'utf8')
        .replace(/<\/?script>/g, '')
        .replace(/__PANEL_UID__/g, name === 'close' ? '__PANEL_UID__' : '__PANEL_UID__');
}

// Generic scene builder
function buildScene(kind, rootUid) {
    const shapes = {};
    const objects = {};
    const mkShape = (uid) => { shapes[uid] = { renderable: true, zIndex: 0 }; };
    const mkObject = (uid, children, attrs) => {
        objects[uid] = Object.assign({ uid: uid, children: children || [], attributes: attrs || { renderable: true } });
    };

    const panelUid = rootUid + '_panel';
    // entity/element image (click target)
    mkObject(rootUid, [], { renderable: true, ws_port: 9001, is_interactive: kind === 'element' });
    mkShape(rootUid);

    // panel with direct children incl. one grandchild depth and the close button
    const direct = [rootUid + '_text1', rootUid + '_text2', rootUid + '_sub', panelUid + '_close_button', panelUid + '_close_text'];
    mkObject(panelUid, direct, { renderable: true, z_index: 10000 });
    mkShape(panelUid);
    direct.forEach((uid) => {
        const extra = uid === rootUid + '_sub' ? [rootUid + '_sub_child'] : [];
        mkObject(uid, extra, { renderable: true, z_index: 10001 });
        mkShape(uid);
        extra.forEach((g) => { mkObject(g, [], { renderable: true }); mkShape(g); });
    });
    return { shapes: shapes, objects: objects, panelUid: panelUid };
}

function makeGlobals(scene, focusEntity, focusElement) {
    const g = {};
    g.window = g;
    g.shapes = scene.shapes;
    g.objects = scene.objects;
    g.app = { stage: { sortChildren() {} } };
    g.AppData = {
        actual_focus_uid_entity: focusEntity,
        actual_focus_uid_element: focusElement,
        _genePollingIntervals: {},
        _chimicalPollingIntervals: {}
    };
    g.redrawShapeFromObject = () => {};
    g.setInterval = () => 1;
    g.clearInterval = () => {};
    g.setTimeout = (fn) => 1;
    g.WebSocket = class { constructor() { this.readyState = 1; } send() {} addEventListener() {} };
    g.WebSocket.OPEN = 1; g.WebSocket.CONNECTING = 0; g.WebSocket.CLOSED = 2; g.WebSocket.CLOSING = 3;
    return g;
}

function runInteractive(code, ctx, clickObject) {
    // mimic frontend: (function (object, shape, shapes, objects, AppData, event) { eval(script) })(...)
    const names = ['object', 'shape', 'shapes', 'objects', 'AppData', 'app', 'window', 'playerId', 'sessionId', 'BACK_URL'];
    const vals = [clickObject, { renderable: true }, ctx.shapes, ctx.objects, ctx.AppData, ctx.app, ctx.window, 1, 's1', 'http://x'];
    const fn = new Function(...names, 'redrawShapeFromObject', 'setInterval', 'clearInterval', 'setTimeout', 'WebSocket', 'console', code);
    fn(...vals, ctx.redrawShapeFromObject, ctx.setInterval, ctx.clearInterval, ctx.setTimeout, ctx.WebSocket, console);
}

let failures = 0;
function check(cond, msg) {
    console.log((cond ? 'PASS  ' : 'FAIL  ') + msg);
    if (!cond) failures++;
}


// ---------------------------------------------------------------- entity flow
(function () {
    const rootUid = 'ent_1';
    const scene = buildScene('entity', rootUid);
    const g = makeGlobals(scene, null, null);
    const ctx = {
        shapes: g.shapes, objects: g.objects, AppData: g.AppData, app: g.app,
        redrawShapeFromObject: g.redrawShapeFromObject, setInterval: g.setInterval,
        clearInterval: g.clearInterval, setTimeout: g.setTimeout, WebSocket: g.WebSocket, window: g
    };

    // 1) open: click entity image
    runInteractive(loadScript('resources/js/function/entity/click_entity.blade.php'), ctx, g.objects[rootUid]);
    check(scene.shapes[scene.panelUid].renderable === true, '[entity] panel open');

    // 2) close with X
    const closeCode = loadScript('resources/js/function/entity/click_close_entity_panel.blade.php')
        .replace(/__PANEL_UID__/g, scene.panelUid);
    runInteractive(closeCode, ctx, g.objects[scene.panelUid + '_close_button']);
    check(scene.shapes[scene.panelUid].renderable === false, '[entity] panel closed via X');
    check(scene.shapes[rootUid + '_sub_child'].renderable === false, '[entity] grandchild hidden via X');
    check(g.AppData.actual_focus_uid_entity === null, '[entity] focus cleared via X');

    // 3) reopen: click entity image again
    runInteractive(loadScript('resources/js/function/entity/click_entity.blade.php'), ctx, g.objects[rootUid]);
    check(scene.shapes[scene.panelUid].renderable === true, '[entity] panel reopened by click');
    check(scene.shapes[rootUid + '_text1'].renderable === true, '[entity] direct child visible after reopen');
    check(scene.shapes[rootUid + '_sub'].renderable === true, '[entity] sub child visible after reopen');
    check(scene.shapes[rootUid + '_sub_child'].renderable === false, '[entity] KNOWN ISSUE: grandchild stays hidden after reopen');
    check(g.AppData.actual_focus_uid_entity === rootUid, '[entity] focus set again');
})();

// --------------------------------------------------------------- element flow
(function () {
    const rootUid = 'element_7_2_3';
    const scene = buildScene('element', rootUid);
    const g = makeGlobals(scene, rootUid, null);
    const ctx = {
        shapes: g.shapes, objects: g.objects, AppData: g.AppData, app: g.app,
        redrawShapeFromObject: g.redrawShapeFromObject, setInterval: g.setInterval,
        clearInterval: g.clearInterval, setTimeout: g.setTimeout, WebSocket: g.WebSocket, window: g
    };

    runInteractive(loadScript('resources/js/function/element/click_element.blade.php'), ctx, g.objects[rootUid]);
    check(scene.shapes[scene.panelUid].renderable === true, '[element] panel open');

    const closeCode = loadScript('resources/js/function/element/click_close_element_panel.blade.php')
        .replace(/__PANEL_UID__/g, scene.panelUid);
    runInteractive(closeCode, ctx, g.objects[scene.panelUid + '_close_button']);
    check(scene.shapes[scene.panelUid].renderable === false, '[element] panel closed via X');
    check(g.AppData.actual_focus_uid_element === null, '[element] focus cleared via X');

    runInteractive(loadScript('resources/js/function/element/click_element.blade.php'), ctx, g.objects[rootUid]);
    check(scene.shapes[scene.panelUid].renderable === true, '[element] panel reopened by click');
    check(scene.shapes[rootUid + '_text1'].renderable === true, '[element] direct child visible after reopen');
    check(scene.shapes[rootUid + '_sub_child'].renderable === false, '[element] KNOWN ISSUE: grandchild stays hidden after reopen');
    check(g.AppData.actual_focus_uid_element === rootUid, '[element] focus set again');
})();

console.log(failures === 0 ? '\nALL EXPECTATIONS MET' : '\nSOME CHECKS FAILED');
process.exit(failures === 0 ? 0 : 1);
