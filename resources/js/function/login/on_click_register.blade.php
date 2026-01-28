<script>
    window['__name__'] = function() {

        status('Generazione registrazione...');
        $.ajax({
            url: `${BACK_URL}/api/game/register`,
            type: 'POST',
            data: { player_id: playerId },
            success: function () { status('Richiesta registrazione inviata!'); },
            error: function (err) { status('Errore registrazione'); console.error(err); }
        });

    }
    window['__name__']();
</script>
