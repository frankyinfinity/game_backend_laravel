<script>

    window['__name__'] = function() {

        let action = '__action__';
        let entity_uid = '__uid__';
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
                entity_uid: entity_uid,
                action: action,
            },
            success: function(result) {
                // ...
            },
        });

    }
    window['__name__']();

</script>
