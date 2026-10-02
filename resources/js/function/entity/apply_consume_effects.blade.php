<script>
    window['__name__'] = function () {
        // APPLY GENE EFFECTS - called from consume()
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/entity/apply_gene_effects',
            type: 'POST',
            data: {
                entity_uid: '__ENTITY_UID__',
                element_has_position_uid: '__ELEMENT_HAS_POSITION_UID__'
            }
        });
    }
    window['__name__']();
</script>