<script>

    window['__name__'] = function() {

        let input_uid = object['uid'].split('_')[0];

        let objectBody = objects[input_uid+'_body_input'];
        let notActiveColor = objectBody.attributes.border_not_active_color;
        let activeColor = objectBody.attributes.border_active_color;

        let validateAndFormat = function(uid, body) {
            if (body.attributes.type === 'number') {
                let val = parseInt(body.attributes.value);
                let min = body.attributes.min;
                let max = body.attributes.max;
                if (!isNaN(val)) {
                    if (min !== null && val < parseInt(min)) val = parseInt(min);
                    if (max !== null && val > parseInt(max)) val = parseInt(max);
                    body.attributes.value = val.toString();
                } else if (min !== null) {
                    body.attributes.value = min.toString();
                }
                if (shapes[uid + '_value_text']) {
                    shapes[uid + '_value_text'].text = body.attributes.value;
                }
            }
        };

        // Deactivate all other inputs
        for (let key in objects) {
            if (key.endsWith('_body_input') && key !== input_uid + '_body_input') {
                let otherUid = key.split('_')[0];
                let otherObjectBody = objects[key];
                if (otherObjectBody.attributes.active) {
                    validateAndFormat(otherUid, otherObjectBody);
                    otherObjectBody.attributes.active = false;
                    let otherShapeBorder = shapes[otherUid + '_border_input'];
                    if (otherShapeBorder) {
                        otherShapeBorder.tint = otherObjectBody.attributes.border_not_active_color;
                    }
                    if (window['keydown_' + otherUid]) {
                        document.removeEventListener('keydown', window['keydown_' + otherUid]);
                        delete window['keydown_' + otherUid];
                    }
                }
            }
        }

        objectBody.attributes.active = !objectBody.attributes.active;
        let active = objectBody.attributes.active;

        if (!active) {
            validateAndFormat(input_uid, objectBody);
        }

        let shapeBorder = shapes[input_uid+'_border_input'];
        shapeBorder.tint = active ? activeColor : notActiveColor;

        let shapeValueText = shapes[input_uid + '_value_text'];

        if (!objectBody.attributes.value) {
            objectBody.attributes.value = '';
        }

        if (active) {
            objectBody.attributes.value = shapeValueText.text || '';
            let type = objectBody.attributes.type || 'text';
            
            if (!window['keydown_' + input_uid]) {
                window['keydown_' + input_uid] = function(event) {
                    if (!objectBody.attributes.active) return;
                    
                    if (event.key.length === 1 && !event.ctrlKey && !event.altKey) {
                        if (type === 'number') {
                            if (!/^\d$/.test(event.key)) {
                                return;
                            }
                            let nextValue = objectBody.attributes.value + event.key;
                            let max = objectBody.attributes.max;
                            if (max !== null && parseInt(nextValue) > parseInt(max)) {
                                return;
                            }
                        }
                        objectBody.attributes.value += event.key;
                        shapeValueText.text = objectBody.attributes.value;
                    } else if (event.key === 'Backspace') {
                        objectBody.attributes.value = objectBody.attributes.value.slice(0, -1);
                        shapeValueText.text = objectBody.attributes.value;
                    } else if (event.key === 'Enter') {
                        objectBody.attributes.active = false;
                        shapeBorder.tint = notActiveColor;
                        validateAndFormat(input_uid, objectBody);
                        document.removeEventListener('keydown', window['keydown_' + input_uid]);
                        delete window['keydown_' + input_uid];
                    } else if (type === 'number') {
                        let min = objectBody.attributes.min;
                        let max = objectBody.attributes.max;
                        if (event.key === 'ArrowUp') {
                            let curr = parseInt(objectBody.attributes.value);
                            if (isNaN(curr)) {
                                curr = (min !== null) ? parseInt(min) : 0;
                            } else {
                                curr++;
                            }
                            if (max !== null && curr > parseInt(max)) curr = parseInt(max);
                            if (min !== null && curr < parseInt(min)) curr = parseInt(min);
                            objectBody.attributes.value = curr.toString();
                            shapeValueText.text = objectBody.attributes.value;
                        } else if (event.key === 'ArrowDown') {
                            let curr = parseInt(objectBody.attributes.value);
                            if (isNaN(curr)) {
                                curr = (min !== null) ? parseInt(min) : 0;
                            } else {
                                curr--;
                            }
                            if (min !== null && curr < parseInt(min) ) curr = parseInt(min);
                            if (max !== null && curr > parseInt(max) ) curr = parseInt(max);
                            objectBody.attributes.value = curr.toString();
                            shapeValueText.text = objectBody.attributes.value;
                        }
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