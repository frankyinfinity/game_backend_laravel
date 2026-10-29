<script>
window.addElementsToModal = function(modalUid, elements) {
    console.log('add_elements_to_modal called with:', { modalUid, elements });
    
    const viewportUid = modalUid + '_content_viewport';
    const viewportObject = objects[viewportUid];
    const viewportShape = shapes[viewportUid];
    
    console.log('Viewport UID:', viewportUid);
    console.log('Viewport object:', viewportObject);
    console.log('Viewport shape:', viewportShape);
    
    if (!viewportObject || !viewportObject.attributes) {
        console.error('Modal viewport not found or has no attributes');
        return;
    }
    
    const contentX = viewportObject.attributes.scroll_viewport_left || 0;
    const contentY = viewportObject.attributes.scroll_viewport_top || 0;
    const contentWidth = viewportObject.width || 0;
    const contentHeight = viewportObject.height || 0;
    
    console.log('Content bounds:', { contentX, contentY, contentWidth, contentHeight });
    console.log('mainLayer:', mainLayer);
    
    if (!mainLayer) {
        console.error('mainLayer is null!');
        return;
    }
    
    // Initialize scroll attributes if needed
    if (!viewportObject.attributes.scroll_child_uids) {
        viewportObject.attributes.scroll_child_uids = [];
    }
    if (!viewportObject.attributes.scroll_base_positions_x) {
        viewportObject.attributes.scroll_base_positions_x = {};
    }
    if (!viewportObject.attributes.scroll_base_positions_y) {
        viewportObject.attributes.scroll_base_positions_y = {};
    }
    if (!viewportObject.attributes.scroll_item_widths) {
        viewportObject.attributes.scroll_item_widths = {};
    }
    if (!viewportObject.attributes.scroll_item_heights) {
        viewportObject.attributes.scroll_item_heights = {};
    }
    if (!viewportObject.attributes.scroll_initial_renderables) {
        viewportObject.attributes.scroll_initial_renderables = {};
    }
    
    // Process each element
    elements.forEach(function(element, index) {
        const uid = modalUid + '_custom_' + Date.now() + '_' + index;
        const x = contentX + (element.x || 0);
        const y = contentY + (element.y || 0);
        const color = element.color || 0x000000;
        
        console.log('Creating element:', { type: element.type, uid, x, y, color });
        
        let drawItem;
        let itemWidth, itemHeight;
        let shape;
        
        if (element.type === 'square') {
            const size = element.size || 40;
            drawItem = {
                uid: uid,
                type: 'square',
                x: x,
                y: y,
                size: size,
                color: color,
                attributes: { renderable: true, z_index: 20020 }
            };
            itemWidth = size;
            itemHeight = size;
            
            shape = new PIXI.Sprite(PIXI.Texture.WHITE);
            shape.width = size;
            shape.height = size;
            shape.tint = color;
            shape.x = x;
            shape.y = y;
        } else if (element.type === 'circle') {
            const radius = element.radius || 20;
            drawItem = {
                uid: uid,
                type: 'circle',
                x: x,
                y: y,
                radius: radius,
                color: color,
                attributes: { renderable: true, z_index: 20020 }
            };
            itemWidth = radius * 2;
            itemHeight = radius * 2;
            
            shape = new PIXI.Graphics();
            shape.beginFill(color);
            shape.drawCircle(0, 0, radius);
            shape.endFill();
            shape.x = x;
            shape.y = y;
        } else if (element.type === 'text') {
            const text = element.text || 'Test';
            const fontSize = element.fontSize || 16;
            const fontFamily = element.fontFamily || 'Arial';
            drawItem = {
                uid: uid,
                type: 'text',
                x: x,
                y: y,
                text: text,
                color: color,
                fontSize: fontSize,
                fontFamily: fontFamily,
                attributes: { renderable: true, z_index: 20020 }
            };
            itemWidth = 80;
            itemHeight = 30;
            
            shape = new PIXI.Text(text, {
                fontFamily: fontFamily,
                fontSize: fontSize,
                fill: '#' + color.toString(16).padStart(6, '0')
            });
            shape.x = x;
            shape.y = y;
        } else if (element.type === 'rectangle') {
            const width = element.width || 60;
            const height = element.height || 40;
            drawItem = {
                uid: uid,
                type: 'rectangle',
                x: x,
                y: y,
                width: width,
                height: height,
                color: color,
                attributes: { renderable: true, z_index: 20020 }
            };
            itemWidth = width;
            itemHeight = height;
            
            shape = new PIXI.Graphics();
            shape.beginFill(color);
            shape.drawRect(0, 0, width, height);
            shape.endFill();
            shape.x = x;
            shape.y = y;
        } else {
            console.warn('Unknown element type:', element.type);
            return;
        }
        
        // Add to objects and shapes
        objects[uid] = drawItem;
        shape.renderable = true;
        shape.zIndex = drawItem.attributes.z_index;
        mainLayer.addChild(shape);
        shapes[uid] = shape;
        
        console.log('Shape created and added:', uid);
        
        // Update scroll attributes
        viewportObject.attributes.scroll_child_uids.push(uid);
        viewportObject.attributes.scroll_base_positions_x[uid] = x;
        viewportObject.attributes.scroll_base_positions_y[uid] = y;
        viewportObject.attributes.scroll_item_widths[uid] = itemWidth;
        viewportObject.attributes.scroll_item_heights[uid] = itemHeight;
        viewportObject.attributes.scroll_initial_renderables[uid] = true;
        
        // Update content bounds
        const itemLeft = x;
        const itemRight = x + itemWidth;
        const itemTop = y;
        const itemBottom = y + itemHeight;
        
        if (viewportObject.attributes.scroll_content_left === undefined || itemLeft < viewportObject.attributes.scroll_content_left) {
            viewportObject.attributes.scroll_content_left = itemLeft;
        }
        if (viewportObject.attributes.scroll_content_right === undefined || itemRight > viewportObject.attributes.scroll_content_right) {
            viewportObject.attributes.scroll_content_right = itemRight;
        }
        if (viewportObject.attributes.scroll_content_top === undefined || itemTop < viewportObject.attributes.scroll_content_top) {
            viewportObject.attributes.scroll_content_top = itemTop;
        }
        if (viewportObject.attributes.scroll_content_bottom === undefined || itemBottom > viewportObject.attributes.scroll_content_bottom) {
            viewportObject.attributes.scroll_content_bottom = itemBottom;
        }
    });
    
    console.log('Elements added. Refreshing masks...');
    
    // Refresh modal viewport masks
    if (typeof window.refreshAllModalViewportMasks === 'function') {
        window.refreshAllModalViewportMasks();
    }
    
    console.log('Done adding elements to modal');
};
</script>
