<script>
    window['__NAME__'] = function () {
        var updateItems = __UPDATE_ITEMS__;
        if (updateItems && updateItems.length > 0) {
            $.ajax({
                url: window.BACK_URL + '/api/auth/game/entity/information/update',
                type: 'POST',
                data: {
                    update_items: JSON.stringify(updateItems)
                }
            });
        }
    }
    window['__NAME__']();
</script>