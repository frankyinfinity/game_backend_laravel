/**
 * addElementsToModal_ - Add elements to modal viewport
 * Assign to: window['addElementsToModal_' + modalUid]
 * Call: addElementsToModal_(modalUid, elements)
 */
window.addElementsToModal_ = function(modalUid, elements) {
    'use strict';
    const viewportUid = modalUid + '_content_viewport';
    const viewport = window[viewportUid];
    if (!viewport || !viewport.attributes) return;
    
    const contentX = viewport.attributes.scroll_viewport_left || 0;
    const contentY = viewport.attributes.scroll_viewport_top || 0;
    
    if (!window.mainLayer) {
        console.error('mainLayer is null');
        return;
    }
    
    // Initialize scroll attributes
    if (!viewport.attributes.scroll_child_uids) viewport.attributes.scroll_child_uids = [];
    if (!viewport.attributes.scroll_base_positions_x) viewport.attributes.scroll_base_positions_x = {};
    if (!viewport.attributes.scroll_base_positions_y) viewport.attributes.scroll_base_positions_y = {};
    if (!viewport.attributes.scroll_item_widths) viewport.attributes.scroll_item_widths = {};
    if (!viewport.attributes.scroll_item_heights) viewport.attributes.scroll_item_heights = {};
    if (!viewport.attributes.scroll_initial_renderables) viewport.attributes.scroll_initial_renderables = {};
    
    elements.forEach(function(el, i) {
        const uid = modalUid + '_custom_' + Date.now() + '_' + i;
        const x = contentX + (el.x || 0);
        const y = contentY + (el.y || 0);
        const color = el.color || 0x000000;
        
        let drawItem, itemWidth, itemHeight;
        
        if (el.type === 'square') {
            drawItem = { uid, type: 'square', x, y, size: el.size || 40, color, attributes: { renderable: true, z_index: 20020 } };
            itemWidth = itemHeight = el.size || 40;
            const Square = window.Square;
            if (Square) { const sq = new Square(drawItem); sq.build(); sq.render(window.mainLayer); }
        } else if (el.type === 'circle') {
            drawItem = { uid, type: 'circle', x, y, radius: el.radius || 20, color, attributes: { renderable: true, z_index: 20020 } };
            itemWidth = itemHeight = (el.radius || 20) * 2;
            const Circle = window.Circle;
            if (Circle) { const c = new Circle(drawItem); c.build(); c.render(window.mainLayer); }
        } else if (el.type === 'text') {
            drawItem = { uid, type: 'text', text: el.text || '', fontSize: el.fontSize || 16, fontFamily: el.fontFamily || 'Outfit', color, attributes: { renderable: true, z_index: 20020 } };
            itemWidth = (el.fontSize || 16) * 8;
            itemHeight = (el.fontSize || 16) + 10;
            const Text = window.Text;
            if (Text) { const t = new Text(drawItem); t.build(); }
        } else {
            drawItem = { uid, type: el.type || 'rectangle', x, y, width: el.width || 50, height: el.height || 50, color, attributes: { renderable: true, z_index: 20020 } };
            itemWidth = el.width || 50;
            itemHeight = el.height || 50;
        }
        
        if (viewport.attributes.scroll_child_uids) viewport.attributes.scroll_child_uids.push(uid);
        viewport.attributes.scroll_base_positions_x[uid] = x;
        viewport.attributes.scroll_base_positions_y[uid] = y;
        viewport.attributes.scroll_item_widths[uid] = itemWidth;
        viewport.attributes.scroll_item_heights[uid] = itemHeight;
    });
    
    if (typeof window.refreshModalViewport === 'function') window.refreshModalViewport(modalUid);
    console.log('addElementsToModal_ done for:', modalUid, 'elements:', elements.length);
};