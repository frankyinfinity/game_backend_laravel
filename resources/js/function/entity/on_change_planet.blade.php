<script>
    window['__name__'] = function() {

        let x = '__x__';
        let y = '__y__';
        let width_input = '__width_input__';
        let height_input = '__height_input__';
        let name_input_uid = '__name_input_uid__';
        let email_input_uid = '__email_input_uid__';
        let password_input_uid = '__password_input_uid__';
        let name_specie_input_uid = '__name_specie_input_uid__';
        let tile_i_input_uid = '__tile_i_input_uid__';
        let tile_j_input_uid = '__tile_j_input_uid__';
        let planet_select_uid = '__planet_select_uid__';

        console.log('Planet changed to: ' + selectedText + ' (ID: ' + selectedId + ')');
        
        if (selectedId) {
            status('Caricamento regioni...');
            $.ajax({
                url: `${BACK_URL}/api/regions/${selectedId}`,
                type: 'POST',
                data: {
                    player_id: playerId,
                    session_id: sessionId,
                    x: x,
                    y: y,
                    width_input: width_input,
                    height_input: height_input,
                    name_input_uid: name_input_uid,
                    email_input_uid: email_input_uid,
                    password_input_uid: password_input_uid,
                    name_specie_input_uid: name_specie_input_uid,
                    tile_i_input_uid: tile_i_input_uid,
                    tile_j_input_uid: tile_j_input_uid,
                    planet_select_uid: planet_select_uid
                },
                success: function (data) {
                    status('Regioni caricate!');
                    console.log('Regions for planet ' + selectedId + ':', data.regions);
                },
                error: function (error) {
                    status('Errore caricamento regioni');
                    console.error('Error fetching regions:', error);
                }
            });
        }
    }
    window['__name__']();
</script>

