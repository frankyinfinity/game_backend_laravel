<script>
    window['__name__'] = function() {

        // Logout button click handler
        // First call: close the current session
        status('Chiusura sessione in corso...');
        
        $.ajax({
            url: `${BACK_URL}/api/game/close`,
            type: 'POST',
            data: { player_id: playerId, session_id: sessionId },
            success: function (result) {
                status('Sessione chiusa. Pulizia schermo in corso...');
                
                // Second call: clear the screen before switching to player_1
                $.ajax({
                    url: `${BACK_URL}/api/game/clear`,
                    type: 'POST',
                    data: { player_id: playerId, session_id: sessionId },
                    success: function () {
                        
                        // Clear all local objects after screen clear
                        if (typeof shapes !== 'undefined') {
                            Object.keys(shapes).forEach(uid => {
                                let shape = shapes[uid];
                                if (shape) {
                                    if (typeof shape.clear === 'function') shape.clear();
                                    if (app && app.stage) app.stage.removeChild(shape);
                                    delete shapes[uid];
                                    delete objects[uid];
                                }
                            });
                            shapes = {};
                            objects = {};
                        }
                        
                        status('Schermo pulito. Ritorno al login...');
                        
                        // Switch to player_1
                        let oldSessionId = sessionId;
                        setPlayerId('1');
                        sessionId = 'init_session_id';
                        if (window.switchPlayerChannel) {
                            window.switchPlayerChannel(playerId);
                        }
                        
                        // Third call: redraw the login
                        $.ajax({
                            url: `${BACK_URL}/api/game/login`,
                            type: 'POST',
                            data: { 
                                player_id: playerId,
                                old_session_id: oldSessionId
                             },
                            success: function () {
                                status('Logout effettuato!');
                            },
                            error: function (err) {
                                status('Errore logout');
                                console.error(err);
                            }
                        });
                    },
                    error: function (err) {
                        status('Errore pulizia schermo');
                        console.error(err);
                    }
                });
            },
            error: function (err) {
                status('Errore logout');
                console.error(err);
            }
        });

    }
    window['__name__']();
</script>
