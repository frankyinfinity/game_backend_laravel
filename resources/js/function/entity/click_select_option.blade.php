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

        // Update value ID
        let shapeValueId = shapes[window.input_uid + '_value_id'];
        if (shapeValueId) {
            shapeValueId.text = optionId;
        }
        
        // Reset previous selected option color (if any)
        if (previousSelectedId !== null) {
            let prevOptionText = shapes[window.input_uid + '_option_text_' + previousSelectedId];
            let prevOptionBorder = shapes[window.input_uid + '_option_border_' + previousSelectedId];
            if (prevOptionText) {
                prevOptionText.tint = 0x000000; // Black text
                prevOptionText.zIndex = 1;
            }
            if (prevOptionBorder) {
                prevOptionBorder.tint = 0x000000; // Black border
                prevOptionBorder.zIndex = 1;
            }
        }
        
        // Set current selected option text and border color to blue
        let currentOptionText = shapes[window.input_uid + '_option_text_' + optionId];
        let currentOptionBorder = shapes[window.input_uid + '_option_border_' + optionId];
        
        if (currentOptionText) {
            currentOptionText.tint = 0x0000FF; // Blue text
            currentOptionText.zIndex = 9999;
        }
        if (currentOptionBorder) {
            currentOptionBorder.tint = 0x0000FF; // Blue border
            currentOptionBorder.zIndex = 9999;
        }
        
        // Execute custom onChange JS if provided
        if (objectBody.attributes.onChangeJs) {
            try {
                let onChangeJs = objectBody.attributes.onChangeJs;
                let selectedId = optionId;
                let selectedText = optionText;
                eval(onChangeJs);
            } catch (e) {
                console.error("Error executing onChangeJs for select:", e);
            }
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
            if (shapeChild) {
                shapeChild.zIndex = 0;
                shapeChild.renderable = false;
            }
        });
        
        // Hide scroll components (some are top-level)
        let manualHide = [
            '_scroll_up', '_scroll_up_text', 
            '_scroll_down', '_scroll_down_text',
            '_scrollbar_strip', '_scrollbar_border'
        ];
        manualHide.forEach(suffix => {
            let s = shapes[window.input_uid + suffix];
            if (s) s.renderable = false;
        });
        
    }
    window['__name__']();

</script>
