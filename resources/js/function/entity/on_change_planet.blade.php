<script>
    window['__name__'] = function() {
        console.log('Planet changed to: ' + selectedText + ' (ID: ' + selectedId + ')');
        
        if (selectedId) {
            fetch('/api/regions/' + selectedId)
                .catch(error => console.error('Error fetching regions:', error));
        }
    }
    window['__name__']();
</script>
