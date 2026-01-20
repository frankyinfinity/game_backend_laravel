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

        let url = '__URL__';
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(datas)
        })
        .then(response => response.json())
        .then(data => console.log('Response:', data))
        .catch(error => console.error('Error:', error));

    }
    window['__name__']();

</script>