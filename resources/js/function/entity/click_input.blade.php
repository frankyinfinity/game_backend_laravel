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

    }
    window['__name__']();

</script>
