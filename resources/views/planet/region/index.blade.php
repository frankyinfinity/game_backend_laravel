<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Regioni</h5>
    </div>
    <div class="card-body">
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
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th class="column_with_checkbox">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" onClick="toggle(this, 'selected[]')">
                                </div>
                            </th>
                            <th>Nome</th>
                            <th>Clima</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
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
                ajax: {
                    type: 'POST',
                    url: url_table,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {},
                },
                columns: [
                    {
                        searchable:     false,
                        orderable:      false,
                        data:           null,
                        name:           "checkbox",
                        defaultContent: "",
                        class:          "disableEdit",
                    },
                    {data:"name", name:"name"},
                    {data:"climate.name", name:"climate.name"},
                    {data:"id", name:"id"},
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
                            let editDataUrl = "{{ route('regions.edit', ['_id_']) }}";
                            editDataUrl = editDataUrl.replace('_id_', data);

                            let editMapUrl = "{{ route('regions.edit-map', ['_id_']) }}";
                            editMapUrl = editMapUrl.replace('_id_', data);

                            return '<div class="d-flex flex-column" style="gap: 4px;">' +
                                '<a href="' + editDataUrl + '">' +
                                    '<button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> Dati</button>' +
                                '</a>' +
                                '<a href="' + editMapUrl + '">' +
                                    '<button type="button" class="btn btn-info btn-block btn-sm"><i class="fa fa-map"></i> Mappa</button>' +
                                '</a>' +
                            '</div>';
                        },
                        targets:   3
                    },
                ],
            });

        });
    </script>
@stop
