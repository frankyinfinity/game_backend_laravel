<div>
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-4"></div>
                    <div class="col-4">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('regions.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-4">
                        <a href="{{route('regions.create', [$planet->id])}}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-plus"></i> Nuovo</button>                    
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="table-responsive">
                    <table id="table_list" class="js-grid table table-bordered table-hover w-100" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th class="column_with_checkbox">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" onClick="toggle(this, 'selected[]')">
                                </div>
                            </th>
                            <th>Nome</th>
                            <th>Clima</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
</div>

@section('js')
    <script> 
        $(document).ready(function () {

            let url_table = "{{ route('regions.datatable',['_id_']) }}";
            url_table = url_table.replace('_id_', '{{$planet->id}}');

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                autoWidth: false,
                ajax: {
                    type: 'POST',
                    url: url_table,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                },
                columns: [
                    {
                        searchable:     false,
                        orderable:      false,
                        data:           null,
                        name:           "checkbox",
                        defaultContent: "",
                        class:          "disableEdit",
                        width: "5%"
                    },
                    {data:"name", name:"name", width: "25%"},
                    {data:"climate.name", name:"climate.name", width: "25%"},
                    {data:"state", name:"state", width: "15%"},
                    {data:"id", name:"id", width: "30%"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function(a, b) {
                    jsgrid();
                },
                "drawCallback":function(){
                    jsgrid();
                    $('#selAll').prop('checked',false);
                },
                columnDefs: [
                    {
                        render: function(data, type, row){
                            return '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" id="sel-'+data.id+'" name="selected[]" value="'+data.id+'">' +
                                '</div>';
                        },
                        targets:   0
                    },
                    {
                        render: function(data, type, row){
                            // State badge
                            let badge = '';
                            if (data == 0) badge = '<span class="badge badge-secondary">Creato</span>';
                            else if (data == 1) badge = '<span class="badge badge-info">Immagine Generata</span>';
                            else if (data == 2) badge = '<span class="badge badge-success">Completato</span>';
                            else badge = '<span class="badge badge-light">Sconosciuto</span>';
                            return badge;
                        },
                        targets:   3
                    },
                    {
                        render: function(data, type, row){
                            let editDataUrl = "{{ route('regions.show', ['_id_']) }}";
                            editDataUrl = editDataUrl.replace('_id_', data);

                            let html = '<div class="d-flex flex-column" style="gap: 4px;">' +
                                '<a href="' + editDataUrl + '">' +
                                    '<button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> Dati</button>' +
                                '</a>';

                            if (row.state == {{ \App\Models\Region::STATE_GENERATED }}) { // STATE_GENERATED
                                let editMapUrl = "{{ route('regions.edit-map', ['_id_']) }}";
                                editMapUrl = editMapUrl.replace('_id_', data);
                                html += '<a href="' + editMapUrl + '">' +
                                    '<button type="button" class="btn btn-info btn-block btn-sm"><i class="fa fa-map"></i> Mappa</button>' +
                                '</a>';
                            }

                            html += '</div>';
                            return html;
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop
