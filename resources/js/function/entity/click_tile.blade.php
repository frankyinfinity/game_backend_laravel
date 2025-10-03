<script>

    window['__name__'] = function() {

        let actual_focus_uid_entity = AppData.actual_focus_uid_entity ?? null;
        if(actual_focus_uid_entity !== null) {

            let i = '__i__';
            let j = '__j__';

            let url = '__url__';
            let token = AppData.csrfToken;

            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                xhrFields: {
                    withCredentials: true
                },
                data: {
                    entity_uid: actual_focus_uid_entity,
                    target_i: i,
                    target_j: j,
                },
                success: function(result) {
                    // ...
                },
            });

        }

    }
    window['__name__']();

</script>
