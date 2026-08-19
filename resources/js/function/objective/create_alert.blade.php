<script>
    window['__name__'] = function () {
        var targets = __TARGETS_JSON__;
        for (var i = 0; i < targets.length; i++) {
            $.ajax({
                url: window.BACK_URL + '/api/game/alert',
                type: 'POST',
                data: {
                    player_id: __PLAYER_ID__,
                    title: 'Obiettivo completato!',
                    body: targets[i].title,
                    type: 'success'
                }
            });
        }
    }
    window['__name__']();
</script>