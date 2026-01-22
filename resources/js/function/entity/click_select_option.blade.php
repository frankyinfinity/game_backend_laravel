<script>

    // Initialize input_uid from the clicked option
    window.input_uid = object['uid'].split('_')[0];

    window['__name__'] = function() {

        let optionId = object['attributes']['optionId'];
        let optionText = object['attributes']['optionText'];
        
        let objectBody = objects[window.input_uid + '_body_select'];
        
        // Update selected option in body attributes
        let previousSelectedId = objectBody.attributes.selectedOptionId;
        objectBody.attributes.selectedOptionId = optionId;
        objectBody.attributes.selectedOptionText = optionText;
        
        // Update value text
        let shapeValueText = shapes[window.input_uid + '_value_text'];
        shapeValueText.text = optionText;
        
        // Reset previous selected option color (if any)
        if (previousSelectedId !== null) {
            let prevOptionText = shapes[window.input_uid + '_option_text_' + previousSelectedId];
            let prevOptionBorder = shapes[window.input_uid + '_option_border_' + previousSelectedId];
            if (prevOptionText) {
                prevOptionText.tint = 0x000000; // Black text
            }
            if (prevOptionBorder) {
                prevOptionBorder.tint = 0x000000; // Black border
            }
        }
        
        // Set current selected option text and border color to blue
        let currentOptionText = shapes[window.input_uid + '_option_text_' + optionId];
        let currentOptionBorder = shapes[window.input_uid + '_option_border_' + optionId];
        
        if (currentOptionText) {
            currentOptionText.tint = 0x0000FF; // Blue text
        }
        if (currentOptionBorder) {
            currentOptionBorder.tint = 0x0000FF; // Blue border
        }







        
        // Close the select panel
        objectBody.attributes.active = false;
        
        let shapeBorder = shapes[window.input_uid + '_border_select'];
        shapeBorder.tint = objectBody.attributes.border_not_active_color;
        
        let shapeBoxIconText = shapes[window.input_uid + '_box_icon_text'];
        shapeBoxIconText.text = 'V';
        
        let shapePanel = shapes[window.input_uid + '_panel_select'];
        shapePanel.renderable = false;
        
        let objectPanel = objects[window.input_uid + '_panel_select'];
        objectPanel.children.forEach(function(childUid) {
            let shapeChild = shapes[childUid];
            shapeChild.zIndex = 0;
            shapeChild.renderable = false;
        });
        
        shapes[window.input_uid + '_scroll_up'].renderable = false;
        shapes[window.input_uid + '_scroll_up_text'].renderable = false;
        shapes[window.input_uid + '_scroll_down'].renderable = false;
        shapes[window.input_uid + '_scroll_down_text'].renderable = false;
        
    }
    window['__name__']();

</script>
