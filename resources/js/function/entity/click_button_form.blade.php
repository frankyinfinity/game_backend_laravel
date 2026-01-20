<script>

    window['__name__'] = function() {

        console.log('click button form');

        let fields = __FIELDS__;

        console.log('Fields:', fields);

        let datas = {};
        for (let key in fields) {
            
            let fieldName = key.substring(6);
            let fieldUid = fields[key];

            let fieldShapes = shapes[fieldUid];
            let fieldValue = fieldShapes.text;
            datas[fieldName] = fieldValue;

        }
        console.log('Datas:', datas);

    }
    window['__name__']();

</script>