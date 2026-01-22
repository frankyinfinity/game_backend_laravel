<script>

    // Initialize input_uid FIRST, before defining any functions that use it
    window.input_uid = object['uid'].split('_')[0];

    window['moveShapes_' + window.input_uid] = function(deltaY, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            shapes[window.input_uid + '_option_rect_' + id].y += deltaY;
            shapes[window.input_uid + '_option_border_' + id].y += deltaY;
            shapes[window.input_uid + '_option_text_' + id].y += deltaY;
        }
    };

    window['updateVisibility_' + window.input_uid] = function(currentStart, optionShowDisplay, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            let visible = idx >= currentStart && idx < currentStart + optionShowDisplay;
            shapes[window.input_uid + '_option_rect_' + id].renderable = visible;
            shapes[window.input_uid + '_option_border_' + id].renderable = visible;
            shapes[window.input_uid + '_option_text_' + id].renderable = visible;
        }
    }

    window['updateOptionColors_' + window.input_uid] = function(selectedOptionId, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            let optionText = shapes[window.input_uid + '_option_text_' + id];
            let optionBorder = shapes[window.input_uid + '_option_border_' + id];
            
            if (id === selectedOptionId) {
                if (optionText) {
                    optionText.tint = 0x0000FF; // Blue text
                    optionText.zIndex = 9999;
                }
                if (optionBorder) {
                    optionBorder.tint = 0x0000FF; // Blue border
                    optionBorder.zIndex = 9999;
                }
            } else {
                if (optionText) {
                    optionText.tint = 0x000000; // Black text
                    optionText.zIndex = 1;
                }
                if (optionBorder) {
                    optionBorder.tint = 0x000000; // Black border
                    optionBorder.zIndex = 1;
                }
            }
        }
    }







    window['__name__'] = function() {

        let objectBody = objects[window.input_uid+'_body_select'];
        let notActiveColor = objectBody.attributes.border_not_active_color;
        let activeColor = objectBody.attributes.border_active_color;

        // Deactivate all other inputs
        for (let key in objects) {
            if (key.endsWith('_body_select') && key !== window.input_uid + '_body_select') {
                let otherUid = key.split('_')[0];
                let otherObjectBody = objects[key];
                otherObjectBody.attributes.active = false;
                let otherShapeBorder = shapes[otherUid + '_border_select'];
                otherShapeBorder.tint = otherObjectBody.attributes.border_not_active_color;
                // Remove listener
                if (window['keydown_' + otherUid]) {
                    document.removeEventListener('keydown', window['keydown_' + otherUid]);
                    delete window['keydown_' + otherUid];
                }
            }
        }

        objectBody.attributes.active = !objectBody.attributes.active;
        let active = objectBody.attributes.active;

        let shapeBorder = shapes[window.input_uid+'_border_select'];
        shapeBorder.tint = active ? activeColor : notActiveColor;

        let shapeValueText = shapes[window.input_uid + '_box_icon_text'];
            shapeValueText.text = active ? '^' : 'V';

        let shapePanel = shapes[window.input_uid + '_panel_select'];
        shapePanel.renderable = active;

        let objectPanel = objects[window.input_uid + '_panel_select'];
        objectPanel.children.forEach(function(childUid) {
            let shapeChild = shapes[childUid];
            shapeChild.zIndex = active ? 1 : 0;
            shapeChild.renderable = active;
        });

        shapes[window.input_uid + '_scroll_up'].renderable = active;
        shapes[window.input_uid + '_scroll_up_text'].renderable = active;
        shapes[window.input_uid + '_scroll_down'].renderable = active;
        shapes[window.input_uid + '_scroll_down_text'].renderable = active;

        if (active) {
            window['updateVisibility_' + window.input_uid](objectBody.attributes.currentStart || 0, objectBody.attributes.optionShowDisplay, objectBody.attributes.optionIds, objectBody.attributes.totalOptions);
            window['updateOptionColors_' + window.input_uid](objectBody.attributes.selectedOptionId, objectBody.attributes.optionIds, objectBody.attributes.totalOptions);
        }


    }
    window['__name__']();

    window['scroll_up_' + window.input_uid] = function() {
        let objectBody = objects[window.input_uid + '_body_select'];
        let currentStart = objectBody.attributes.currentStart || 0;
        let optionShowDisplay = objectBody.attributes.optionShowDisplay;
        let totalOptions = objectBody.attributes.totalOptions;
        let heightOption = objectBody.attributes.heightOption;
        let optionIds = objectBody.attributes.optionIds;
        if (currentStart > 0) {
            currentStart--;
            objectBody.attributes.currentStart = currentStart;
            window['moveShapes_' + window.input_uid](heightOption, optionIds, totalOptions);
            window['updateVisibility_' + window.input_uid](currentStart, optionShowDisplay, optionIds, totalOptions);
        }
    };

    window['scroll_down_' + window.input_uid] = function() {
        let objectBody = objects[window.input_uid + '_body_select'];
        let currentStart = objectBody.attributes.currentStart || 0;
        let optionShowDisplay = objectBody.attributes.optionShowDisplay;
        let totalOptions = objectBody.attributes.totalOptions;
        let heightOption = objectBody.attributes.heightOption;
        let optionIds = objectBody.attributes.optionIds;
        if (currentStart + optionShowDisplay < totalOptions) {
            currentStart++;
            objectBody.attributes.currentStart = currentStart;
            window['moveShapes_' + window.input_uid](-heightOption, optionIds, totalOptions);
            window['updateVisibility_' + window.input_uid](currentStart, optionShowDisplay, optionIds, totalOptions);
        }
    };

</script>