<script>
    window['__name__'] = function() {

        let fields = __FIELDS__;
        let datas = getFormData(fields);

        console.log('Registering with data:', datas);
        status('Tentativo di registrazione...');

        $.ajax({
            url: `${BACK_URL}/api/auth/register`,
            type: 'POST',
            data: datas,
            success: function (result) { 
                if(result.success) {
                    status('Registrazione completata!');
                    alert('Registrazione avvenuta con successo! Verrai riportato al login.');
                    
                    // Trigger the same logic as the "Back to Login" button
                    status('Ritorno al login...');
                    $.ajax({
                        url: `${BACK_URL}/api/game/login`,
                        type: 'POST',
                        data: { player_id: playerId },
                        success: function () { status('Pagina login caricata!'); },
                        error: function (err) { status('Errore caricamento login'); console.error(err); }
                    });
                } else {
                    status('Errore: ' + (result.message || 'Verifica i dati'));
                }
            },
            error: function (err) { 
                status('Errore registrazione'); 
                console.error(err);
                if(err.responseJSON && err.responseJSON.message) {
                    alert('Errore: ' + err.responseJSON.message);
                }
            }
        });

    }
    window['__name__']();
</script>

