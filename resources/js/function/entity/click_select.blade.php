<script>

    window['__name__'] = function() {

        let input_uid = object['uid'].split('_')[0];

        let objectBody = objects[input_uid+'_body_select'];
        let notActiveColor = objectBody.attributes.border_not_active_color;
        let activeColor = objectBody.attributes.border_active_color;

        // Deactivate all other inputs
        for (let key in objects) {
            if (key.endsWith('_body_select') && key !== input_uid + '_body_select') {
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

        let shapeBorder = shapes[input_uid+'_border_select'];
        shapeBorder.tint = active ? activeColor : notActiveColor;

        let shapeValueText = shapes[input_uid + '_box_icon_text'];
        shapeValueText.text = active ? '∧' : 'V';

        let shapePanel = shapes[input_uid + '_panel_select'];
        shapePanel.renderable = active;

    }
    window['__name__']();

</script>