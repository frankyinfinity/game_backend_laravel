<script>

    window['__name__'] = function() {

        let fields = __FIELDS__;
        let datas = getFormData(fields);

        status('Tentativo di login...');
        $.ajax({
            url: `${BACK_URL}/api/auth/login`,
            type: 'POST',
            data: datas,
            success: function (result) { 
                if(result.is_player) {

                    let newPlayerId = result.player.id;

                    status('Tentativo di pulizia login...');
                    $.ajax({
                        url: `${BACK_URL}/api/game/clear_login`,
                        type: 'POST',
                        data: {
                            player_id: playerId,
                            session_id: sessionId,
                            new_player_id: newPlayerId
                        },
                        success: function (result) { 
                            
                            let newSessionId = result.session_id;
                            
                            // Aggiorna variabili globali
                            if (window.setPlayerId) {
                                window.setPlayerId(newPlayerId);
                            } else {
                                playerId = newPlayerId;
                            }
                            sessionId = newSessionId;

                            // Cambia il canale Pusher PRIMA di chiamare la home
                            if (window.switchPlayerChannel) {
                                window.switchPlayerChannel(playerId);
                            }

                            status('Tentativo di generazione home...');
                            $.ajax({
                                url: `${BACK_URL}/api/game/home`,
                                type: 'POST',
                                data: {
                                    player_id: playerId,
                                },
                                success: function (result) { 
                                    console.log(result);
                                },
                                error: function (err) { status('Errore home'); console.error(err); }
                            });

                        },
                        error: function (err) { status('Errore home'); console.error(err); }
                    });

                    /*playerId = result.player.id;
                    sessionId = result.session_id;

                    status('Tentativo di generazione home...');
                     $.ajax({
                        url: `${BACK_URL}/api/game/home`,
                        type: 'POST',
                        data: {
                            player_id: playerId,
                            session_id: sessionId
                        },
                        success: function (result) { 
                            console.log(result);
                        },
                        error: function (err) { status('Errore home'); console.error(err); }
                    });*/

                }   
            },
            error: function (err) { status('Errore login'); console.error(err); }
        });

    }
    window['__name__']();

</script>
