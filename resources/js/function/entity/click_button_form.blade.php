<script>

    window['__name__'] = function() {

        console.log('click button form');

        let fields = __FIELDS__;
        let datas = getFormData(fields);

        let url = '__URL__';
        if (url.startsWith('/') && typeof BACK_URL !== 'undefined') {
            url = BACK_URL + url;
        }

        let csrfToken = '';
        let csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            csrfToken = csrfMeta.getAttribute('content');
        }

        status('Invio dati...');
        $.ajax({
            url: url,
            type: 'POST',
            data: JSON.stringify(datas),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (data) {
                status('Dati inviati con successo!');
                console.log('Response:', data);
            },
            error: function (error) {
                status('Errore durante l\'invio');
                console.error('Error:', error);
            }
        });

    }
    window['__name__']();

</script>