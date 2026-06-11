<script>
    window['__name__'] = function() {
        console.log('click button form');
        let fields = __FIELDS__;
        let datas = getFormData(fields);
        console.log(datas);
    }
    window['__name__']();
</script>
