<script>
    window['__name__'] = function () {
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/player_values/reset',
            type: 'POST',
            data: {
                player_id: __PLAYER_ID__,
                reset_action: '__RESET_ACTION__'
            }
        });
    }
    window['__name__']();
</script>
