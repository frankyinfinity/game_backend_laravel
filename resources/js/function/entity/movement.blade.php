<script>

    window['__name__'] = function() {

        let action = '__action__';
        let entity_uid = '__uid__';
        let player_id = '__player_id__';
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
                action: action,
                entity_uid: entity_uid,
                player_id: player_id,
            },
            success: function(result) {
                // ...
            },
        });

    }
    window['__name__']();

</script>
