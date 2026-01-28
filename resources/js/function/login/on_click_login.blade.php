<script>
    window['__name__'] = function() {

        status('Generazione login...');
        $.ajax({
            url: `${BACK_URL}/api/game/login`,
            type: 'POST',
            data: { player_id: playerId },
            success: function () { status('Richiesta login inviata!'); },
            error: function (err) { status('Errore login'); console.error(err); }
        });

    }
    window['__name__']();
</script>
