<script>
    window['__name__'] = function() {
        let entity_uid = AppData.actual_focus_uid_entity;
        let element_uid = AppData.actual_focus_uid_element;
        
        // playerId is global in the frontend
        if (typeof playerId === 'undefined') {
            console.error('playerId global variable not found');
            return;
        }

        console.log('Action: Consume');
        console.log('Entity UID (Source):', entity_uid);
        console.log('Element UID (Target):', element_uid);
        
        if (!entity_uid || !element_uid) {
            console.warn('Missing focus: Entity or Element panel might be closed.');
            return;
        }

        $.ajax({
            url: `${BACK_URL}/api/auth/game/entity/consume`,
            type: 'POST',
            data: {
                player_id: playerId,
                entity_uid: entity_uid,
                element_uid: element_uid
            },
            success: function (result) {
                console.log('Consume API Success:', result);
            },
            error: function (err) {
                console.error('Consume API Error:', err);
            }
        });
    }
    window['__name__']();
</script>
