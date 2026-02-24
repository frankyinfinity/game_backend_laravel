<script>
    window['__name__'] = function() {
        const entityUid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        if (!entityUid) {
            console.warn('Division click: no selected entity uid');
            return;
        }

        $.ajax({
            url: `${BACK_URL}/api/auth/game/entity/division`,
            type: 'POST',
            data: {
                entity_uid: entityUid
            },
            success: function(response) {
                console.log('Division response:', response);
            },
            error: function(err) {
                console.error('Division API error:', err);
            }
        });
    }
    window['__name__']();
</script>
