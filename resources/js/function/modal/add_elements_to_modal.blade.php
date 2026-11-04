<script>
// ===== Generic modal content renderer factory =====
// Declares window.__createAddElementsToModalRenderer(modalUid, logTag) which
// returns a function(elements) that draws dynamic elements inside the modal
// content viewport and registers scroll metadata.
// Usage:
//   window['addElementsToModal_' + modalUid] = window.__createAddElementsToModalRenderer(modalUid, 'Timeline');
window.__createAddElementsToModalRenderer = function(modalUid, logTag) {
    const logPrefix = '[' + (logTag || 'Modal') + ']';

    // Rendering layer: test page uses mainLayer, the game frontend uses app.stage.
    const renderLayer = (typeof mainLayer !== 'undefined' && mainLayer)
        ? mainLayer
        : ((typeof app !== 'undefined' && app && app.stage) ? app.stage : null);

    return function(elements) {
        console.log(logPrefix, 'addElementsToModal called with:', elements ? elements.length : 0, 'elements');

        const viewportUid = modalUid + '_content_viewport';
        const viewportObject = objects[viewportUid];

        if (!viewportObject || !viewportObject.attributes) {
            console.error(logPrefix, 'Modal viewport not found:', viewportUid);
            return;
        }
        if (!renderLayer) {
            console.error(logPrefix, 'No render layer available (mainLayer/app.stage)');
            return;
        }
        if (!Array.isArray(elements) || elements.length === 0) {
            console.log(logPrefix, 'No elements to draw');
            return;
        }

        const contentX = viewportObject.attributes.scroll_viewport_left || 0;
        const contentY = viewportObject.attributes.scroll_viewport_top || 0;

        // Initialize scroll attributes if needed
        if (!viewportObject.attributes.scroll_child_uids) viewportObject.attributes.scroll_child_uids = [];
        if (!viewportObject.attributes.scroll_base_positions_x) viewportObject.attributes.scroll_base_positions_x = {};
        if (!viewportObject.attributes.scroll_base_positions_y) viewportObject.attributes.scroll_base_positions_y = {};
        if (!viewportObject.attributes.scroll_item_widths) viewportObject.attributes.scroll_item_widths = {};
        if (!viewportObject.attributes.scroll_item_heights) viewportObject.attributes.scroll_item_heights = {};
        if (!viewportObject.attributes.scroll_initial_renderables) viewportObject.attributes.scroll_initial_renderables = {};

        const trackElement = function(uid, x, y, w, h) {
            if (viewportObject.attributes.scroll_child_uids.indexOf(uid) === -1) {
                viewportObject.attributes.scroll_child_uids.push(uid);
            }
            viewportObject.attributes.scroll_base_positions_x[uid] = x;
            viewportObject.attributes.scroll_base_positions_y[uid] = y;
            viewportObject.attributes.scroll_item_widths[uid] = w;
            viewportObject.attributes.scroll_item_heights[uid] = h;
            viewportObject.attributes.scroll_initial_renderables[uid] = true;

            const itemRight = x + w;
            const itemBottom = y + h;
            if (viewportObject.attributes.scroll_content_left === undefined || x < viewportObject.attributes.scroll_content_left) viewportObject.attributes.scroll_content_left = x;
            if (viewportObject.attributes.scroll_content_right === undefined || itemRight > viewportObject.attributes.scroll_content_right) viewportObject.attributes.scroll_content_right = itemRight;
            if (viewportObject.attributes.scroll_content_top === undefined || y < viewportObject.attributes.scroll_content_top) viewportObject.attributes.scroll_content_top = y;
            if (viewportObject.attributes.scroll_content_bottom === undefined || itemBottom > viewportObject.attributes.scroll_content_bottom) viewportObject.attributes.scroll_content_bottom = itemBottom;
        };

        elements.forEach(function(element, index) {
            const uid = element.uid || (modalUid + '_custom_' + Date.now() + '_' + index);
            const x = contentX + (element.x || 0);
            const y = contentY + (element.y || 0);
            const color = (element.color !== null && element.color !== undefined) ? element.color : 0x000000;
            const zIndex = (element.attributes && element.attributes.z_index) || 20020;

            let drawItem, itemWidth, itemHeight, shape;

            if (element.type === 'square') {
                const size = element.size || 40;
                drawItem = { uid: uid, type: 'square', x: x, y: y, size: size, color: color, attributes: { renderable: true, z_index: zIndex } };
                itemWidth = itemHeight = size;
                const square = new Square(drawItem);
                square.build();
                square.render(app);
                shape = square.shape;
            } else if (element.type === 'circle') {
                const radius = element.radius || 20;
                drawItem = { uid: uid, type: 'circle', x: x, y: y, radius: radius, color: color, attributes: { renderable: true, z_index: zIndex } };
                itemWidth = itemHeight = radius * 2;
                const circle = new Circle(drawItem);
                circle.build();
                circle.render(app);
                shape = circle.shape;
            } else if (element.type === 'text') {
                const fontSize = element.fontSize || 16;
                drawItem = { uid: uid, type: 'text', x: x, y: y, text: element.text || '', color: color, fontSize: fontSize, fontFamily: element.fontFamily || 'Arial', attributes: { renderable: true, z_index: zIndex } };
                itemWidth = fontSize * 8;
                itemHeight = fontSize + 10;
                const textObj = new Text(drawItem);
                textObj.build();
                textObj.render(app);
                shape = textObj.shape;
            } else if (element.type === 'rectangle') {
                const width = element.width || 60;
                const height = element.height || 40;
                drawItem = { uid: uid, type: 'rectangle', x: x, y: y, width: width, height: height, color: color, attributes: { renderable: true, z_index: zIndex } };
                itemWidth = width;
                itemHeight = height;
                const rect = new Rectangle(drawItem);
                rect.build();
                rect.render(app);
                shape = rect.shape;
            } else if (element.type === 'rounded_rectangle') {
                const width = element.width || 60;
                const height = element.height || 40;
                const borderRadius = element.border_radius || 10;
                drawItem = { uid: uid, type: 'rounded_rectangle', x: x, y: y, width: width, height: height, color: color, border_radius: borderRadius, attributes: { renderable: true, z_index: zIndex } };
                itemWidth = width;
                itemHeight = height;
                const g = new PIXI.Graphics();
                g.beginFill(color);
                g.drawRoundedRect(x, y, width, height, borderRadius);
                g.endFill();
                g.renderable = true;
                g.zIndex = zIndex;
                renderLayer.addChild(g);
                shape = g;
            } else if (element.type === 'rounded_rectangle_outline') {
                const width = element.width || 60;
                const height = element.height || 40;
                const borderRadius = element.border_radius || 10;
                const borderWidth = element.border_width || 2;
                drawItem = { uid: uid, type: 'rounded_rectangle_outline', x: x, y: y, width: width, height: height, color: color, border_radius: borderRadius, border_width: borderWidth, attributes: { renderable: true, z_index: zIndex } };
                itemWidth = width;
                itemHeight = height;
                const g = new PIXI.Graphics();
                g.lineStyle(borderWidth, color);
                g.drawRoundedRect(x, y, width, height, borderRadius);
                g.renderable = true;
                g.zIndex = zIndex;
                renderLayer.addChild(g);
                shape = g;
            } else if (element.type === 'sprite') {
                const width = element.width || 100;
                const height = element.height || 100;
                const imageUrl = element.image_url || '';
                itemWidth = width;
                itemHeight = height;
                if (imageUrl) {
                    PIXI.Assets.load(imageUrl).then(function(texture) {
                        const sprite = new PIXI.Sprite(texture);
                        sprite.x = x;
                        sprite.y = y;
                        sprite.width = width;
                        sprite.height = height;
                        sprite.renderable = true;
                        sprite.zIndex = zIndex;
                        renderLayer.addChild(sprite);
                        shapes[uid] = sprite;
                    }).catch(function(err) {
                        console.error(logPrefix, 'Sprite load error:', imageUrl, err);
                    });
                    drawItem = { uid: uid, type: 'sprite', x: x, y: y, width: width, height: height, image_url: imageUrl, attributes: { renderable: true, z_index: zIndex } };
                    objects[uid] = drawItem;
                    trackElement(uid, x, y, itemWidth, itemHeight);
                    return;
                }
                const g = new PIXI.Graphics();
                g.beginFill(0xE0E0E0);
                g.drawRect(x, y, width, height);
                g.endFill();
                g.renderable = true;
                g.zIndex = zIndex;
                renderLayer.addChild(g);
                drawItem = { uid: uid, type: 'sprite', x: x, y: y, width: width, height: height, attributes: { renderable: true, z_index: zIndex } };
                shape = g;
            } else {
                console.warn(logPrefix, 'Unknown element type:', element.type);
                return;
            }

            objects[uid] = drawItem;
            if (shape) {
                shape.renderable = true;
                shapes[uid] = shape;
            }

            if (window.modalDynamicElements && window.modalDynamicElements[modalUid]) {
                window.modalDynamicElements[modalUid].push(uid);
            }

            trackElement(uid, x, y, itemWidth, itemHeight);
        });

        if (typeof window.refreshAllModalViewportMasks === 'function') {
            window.refreshAllModalViewportMasks();
        }
        if (app && app.stage) app.stage.sortChildren();
        console.log(logPrefix, 'Elements drawn into modal', modalUid);
    };
};
</script>
