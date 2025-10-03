<script>

    window['__name__'] = function() {

        let actual_focus_uid_entity = AppData.actual_focus_uid_entity ?? null;
        if(actual_focus_uid_entity !== null) {

            let uid = '__uid__';
            let color = '__color__';

            let shape = shapes[uid];
            shape.tint = color;

        }

    }
    window['__name__']();

</script>
