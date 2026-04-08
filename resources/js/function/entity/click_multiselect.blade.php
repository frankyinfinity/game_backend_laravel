<script>

    window.input_uid = object['uid'].replace('_body_multiselect', '');

    window['moveShapes_' + window.input_uid] = function(deltaY, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            let rect = shapes[window.input_uid + '_option_rect_' + id];
            let border = shapes[window.input_uid + '_option_border_' + id];
            let text = shapes[window.input_uid + '_option_text_' + id];
            let checkbox = shapes[window.input_uid + '_checkbox_' + id];
            let checkboxBorder = shapes[window.input_uid + '_checkbox_border_' + id];
            if (rect) rect.y += deltaY;
            if (border) border.y += deltaY;
            if (text) text.y += deltaY;
            if (checkbox) checkbox.y += deltaY;
            if (checkboxBorder) checkboxBorder.y += deltaY;
        }
    };

    window['updateVisibility_' + window.input_uid] = function(currentStart, optionShowDisplay, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            let visible = idx >= currentStart && idx < currentStart + optionShowDisplay;
            let rect = shapes[window.input_uid + '_option_rect_' + id];
            let border = shapes[window.input_uid + '_option_border_' + id];
            let text = shapes[window.input_uid + '_option_text_' + id];
            let checkbox = shapes[window.input_uid + '_checkbox_' + id];
            let checkboxBorder = shapes[window.input_uid + '_checkbox_border_' + id];
            if (rect) rect.renderable = visible;
            if (border) border.renderable = visible;
            if (text) text.renderable = visible;
            if (checkbox) checkbox.renderable = visible;
            if (checkboxBorder) checkboxBorder.renderable = visible;
        }
    }

    window['updateOptionColors_' + window.input_uid] = function(selectedOptionIds, optionIds, totalOptions) {
        for (let idx = 0; idx < totalOptions; idx++) {
            let id = optionIds[idx];
            let optionText = shapes[window.input_uid + '_option_text_' + id];
            let optionBorder = shapes[window.input_uid + '_option_border_' + id];
            let checkbox = shapes[window.input_uid + '_checkbox_' + id];
            let checkboxBorder = shapes[window.input_uid + '_checkbox_border_' + id];
            
            let isSelected = selectedOptionIds.includes(id);
            
            if (isSelected) {
                if (optionText) {
                    optionText.tint = 0x0000FF;
                    optionText.zIndex = 11002;
                }
                if (optionBorder) {
                    optionBorder.tint = 0x0000FF;
                    optionBorder.zIndex = 11002;
                }
                if (checkbox) {
                    checkbox.tint = 0x0000FF;
                }
            } else {
                if (optionText) {
                    optionText.tint = 0x000000;
                    optionText.zIndex = 11001;
                }
                if (optionBorder) {
                    optionBorder.tint = 0x000000;
                    optionBorder.zIndex = 11001;
                }
                if (checkbox) {
                    checkbox.tint = 0xFFFFFF;
                }
            }
        }
    }

    window['__name__'] = function() {

        let objectBody = objects[window.input_uid+'_body_multiselect'];
        let notActiveColor = objectBody.attributes.border_not_active_color;
        let activeColor = objectBody.attributes.border_active_color;

        for (let key in objects) {
            if (key.endsWith('_body_multiselect') && key !== window.input_uid + '_body_multiselect') {
                let otherUid = key.replace('_body_multiselect', '');
                let otherObjectBody = objects[key];
                otherObjectBody.attributes.active = false;
                let otherShapeBorder = shapes[otherUid + '_border_multiselect'];
                if (otherShapeBorder) {
                    otherShapeBorder.tint = otherObjectBody.attributes.border_not_active_color;
                }
                if (window['keydown_' + otherUid]) {
                    document.removeEventListener('keydown', window['keydown_' + otherUid]);
                    delete window['keydown_' + otherUid];
                }
            }
        }

        objectBody.attributes.active = !objectBody.attributes.active;
        let active = objectBody.attributes.active;

        let shapeBorder = shapes[window.input_uid+'_border_multiselect'];
        if (shapeBorder) {
            shapeBorder.tint = active ? activeColor : notActiveColor;
        }

        let shapeValueText = shapes[window.input_uid + '_box_icon_text'];
            shapeValueText.text = active ? '^' : 'V';

        let shapePanel = shapes[window.input_uid + '_panel_multiselect'];
        shapePanel.renderable = active;
        if (active) {
            shapePanel.zIndex = 11000;
        } else {
            shapePanel.zIndex = 0;
        }

        let objectPanel = objects[window.input_uid + '_panel_multiselect'];
        objectPanel.children.forEach(function(childUid) {
            let shapeChild = shapes[childUid];
            if (!shapeChild) return;
            if (active) {
                shapeChild.zIndex = 11001;
            } else {
                shapeChild.zIndex = 0;
            }
            shapeChild.renderable = active;
        });

        let scrollIds = [
            window.input_uid + '_scroll_up',
            window.input_uid + '_scroll_up_text',
            window.input_uid + '_scroll_down',
            window.input_uid + '_scroll_down_text',
            window.input_uid + '_scrollbar_strip',
            window.input_uid + '_scrollbar_border'
        ];

        scrollIds.forEach(function(id) {
            let shapeScroll = shapes[id];
            if (shapeScroll) {
                shapeScroll.renderable = active;
                if (active) {
                    if (id.includes('_text')) {
                        shapeScroll.zIndex = 12001;
                    } else if (id.includes('_scroll_')) {
                        shapeScroll.zIndex = 12000;
                    } else if (id.includes('_scrollbar_strip') || id.includes('_scrollbar_border')) {
                        shapeScroll.zIndex = 11005;
                    } else {
                        shapeScroll.zIndex = 11005; 
                    }
                } else {
                    shapeScroll.zIndex = 0;
                }
            }
        });

        let optionIds = objectBody.attributes.optionIds || [];
        optionIds.forEach(function(id) {
            let checkbox = shapes[window.input_uid + '_checkbox_' + id];
            let checkboxBorder = shapes[window.input_uid + '_checkbox_border_' + id];
            if (checkbox) {
                checkbox.renderable = active;
                checkbox.zIndex = active ? 11001 : 0;
            }
            if (checkboxBorder) {
                checkboxBorder.renderable = active;
                checkboxBorder.zIndex = active ? 11001 : 0;
            }
        });

        if (active) {
            let selectedOptionIds = objectBody.attributes.selectedOptionIds || [];
            window['updateVisibility_' + window.input_uid](objectBody.attributes.currentStart || 0, objectBody.attributes.optionShowDisplay, objectBody.attributes.optionIds, objectBody.attributes.totalOptions);
            window['updateOptionColors_' + window.input_uid](selectedOptionIds, objectBody.attributes.optionIds, objectBody.attributes.totalOptions);
        }

    }
    window['__name__']();

    window['scroll_up_' + window.input_uid] = function() {
        let objectBody = objects[window.input_uid + '_body_multiselect'];
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
        let objectBody = objects[window.input_uid + '_body_multiselect'];
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