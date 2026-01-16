<script>

    window['__name__'] = function() {

        let input_uid = object['uid'].split('_')[0];

        let objectBody = objects[input_uid+'_body_input'];
        let notActiveColor = objectBody.attributes.border_not_active_color;
        let activeColor = objectBody.attributes.border_active_color;

        objectBody.attributes.active = !objectBody.attributes.active;
        let active = objectBody.attributes.active;

        let shapeBorder = shapes[input_uid+'_border_input'];
        shapeBorder.tint = active ? activeColor : notActiveColor;

        let shapeValueText = shapes[input_uid + '_value_text'];

        if (!objectBody.attributes.value) {
            objectBody.attributes.value = '';
        }

        if (active) {
            objectBody.attributes.value = shapeValueText.text || '';
            if (!window['keydown_' + input_uid]) {
                window['keydown_' + input_uid] = function(event) {
                    if (!objectBody.attributes.active) return;
                    if (event.key.length === 1 && !event.ctrlKey && !event.altKey) {
                        objectBody.attributes.value += event.key;
                        shapeValueText.text = objectBody.attributes.value;
                    } else if (event.key === 'Backspace') {
                        objectBody.attributes.value = objectBody.attributes.value.slice(0, -1);
                        shapeValueText.text = objectBody.attributes.value;
                    }
                    event.preventDefault();
                };
                document.addEventListener('keydown', window['keydown_' + input_uid]);
            }
        } else {
            if (window['keydown_' + input_uid]) {
                document.removeEventListener('keydown', window['keydown_' + input_uid]);
                delete window['keydown_' + input_uid];
            }
        }

    }
    window['__name__']();

</script>