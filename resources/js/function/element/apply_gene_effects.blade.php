<script>
    window['__name__'] = function () {
        // APPLY ELEMENT GENE EFFECTS - called from consume
        $.ajax({
            url: window.BACK_URL + '/api/auth/game/element/apply_gene_effects',
            type: 'POST',
            data: {
                element_has_position_uid: '__ELEMENT_HAS_POSITION_UID__',
                target_element_has_position_uid: '__TARGET_ELEMENT_HAS_POSITION_UID__'
            }
        });
    }
    window['__name__']();
</script>
