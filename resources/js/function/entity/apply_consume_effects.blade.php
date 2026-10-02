<script>
    window['__name__'] = function () {
        // APPLY CONSUME EFFECTS - applica i rewards del bersaglio ai geni dell'entity e
        // assegna al player proprietario dell'entity i scores del bersaglio.
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/entity/apply_consume_effects',
            type: 'POST',
            data: {
                entity_uid: '__ENTITY_UID__',
                element_has_position_uid: '__ELEMENT_HAS_POSITION_UID__'
            }
        });
    }
    window['__name__']();
</script>