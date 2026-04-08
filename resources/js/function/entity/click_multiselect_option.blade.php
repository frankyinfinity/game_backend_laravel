<script>

    window.input_uid = object['uid'].substring(0, object['uid'].lastIndexOf('_option_rect_'));

    window['__name__'] = function() {

        let optionId = object['attributes']['optionId'];
        let optionText = object['attributes']['optionText'];
        
        let objectBody = objects[window.input_uid + '_body_multiselect'];
        let selectedOptionIds = objectBody.attributes.selectedOptionIds || [];
        
        let index = selectedOptionIds.indexOf(optionId);
        let isSelected = index > -1;
        
        if (isSelected) {
            selectedOptionIds.splice(index, 1);
        } else {
            selectedOptionIds.push(optionId);
        }
        
        objectBody.attributes.selectedOptionIds = selectedOptionIds;
        
        let checkbox = shapes[window.input_uid + '_checkbox_' + optionId];
        let checkboxBorder = shapes[window.input_uid + '_checkbox_border_' + optionId];
        if (checkbox) {
            checkbox.tint = isSelected ? 0xFFFFFF : 0x0000FF;
        }
        if (checkboxBorder) {
            checkboxBorder.tint = isSelected ? 0x000000 : 0x0000FF;
        }
        
        let optionTextShape = shapes[window.input_uid + '_option_text_' + optionId];
        let optionBorderShape = shapes[window.input_uid + '_option_border_' + optionId];
        
        if (isSelected) {
            if (optionTextShape) {
                optionTextShape.tint = 0x000000;
                optionTextShape.zIndex = 11001;
            }
            if (optionBorderShape) {
                optionBorderShape.tint = 0x000000;
                optionBorderShape.zIndex = 11001;
            }
        } else {
            if (optionTextShape) {
                optionTextShape.tint = 0x0000FF;
                optionTextShape.zIndex = 11002;
            }
            if (optionBorderShape) {
                optionBorderShape.tint = 0x0000FF;
                optionBorderShape.zIndex = 11002;
            }
        }
        
        let valueText = shapes[window.input_uid + '_value_text'];
        if (valueText) {
            if (selectedOptionIds.length === 0) {
                valueText.text = '';
            } else {
                valueText.text = selectedOptionIds.length + ' selezionati';
            }
        }
        
        let valueIdText = shapes[window.input_uid + '_value_ids'];
        if (valueIdText) {
            valueIdText.text = selectedOptionIds.join(',');
        }
        
        if (objectBody.attributes.onChangeJs) {
            try {
                let onChangeJs = objectBody.attributes.onChangeJs;
                let selectedIds = selectedOptionIds;
                let selectedTextsArray = [];
                selectedOptionIds.forEach(function(id) {
                    let optRect = objects[window.input_uid + '_option_rect_' + id];
                    if (optRect && optRect.attributes && optRect.attributes.optionText) {
                        selectedTextsArray.push(optRect.attributes.optionText);
                    }
                });
                eval(onChangeJs);
            } catch (e) {
                console.error("Error executing onChangeJs for multiselect:", e);
            }
        }
        
    }
    window['__name__']();

</script>