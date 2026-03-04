<script>
    window['__name__'] = function () {
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/player_values/reset',
            type: 'POST',
            data: {
                player_id: __PLAYER_ID__,
                reset_token: '__RESET_TOKEN__'
            }
        });
    }
    window['__name__']();
</script>
