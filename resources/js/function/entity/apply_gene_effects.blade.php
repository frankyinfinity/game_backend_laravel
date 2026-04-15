<script>
    window['__name__'] = function () {
        // APPLY GENE EFFECTS - called from consume()
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/entity/apply_gene_effects',
            type: 'POST',
            data: {
                entity_uid: '__ENTITY_UID__',
                element_uid: '__ELEMENT_UID__',
                element_id: '__ELEMENT_ID__'
            }
        });
    }
    window['__name__']();
</script>