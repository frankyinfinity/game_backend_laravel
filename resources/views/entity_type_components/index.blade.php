@extends('adminlte::page')

@section('title', 'Tipologia Componente')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-dark font-weight-bold">Tipologia Componente</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row justify-content-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete shadow-sm" data-list="table_list" data-url="{{ route('entity-type-components.delete') }}">
                            <i class="fa fa-trash"></i> Elimina Selezionati
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('entity-type-components.create') }}">
                            <button type="button" class="btn btn-primary btn-block btn-sm shadow-sm"><i class="fa fa-plus"></i> Nuova Tipologia</button>                    
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-12">
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th class="column_with_checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" onClick="toggle(this, 'selected[]')">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Simbolo</th>
                                <th>Nome</th>
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
@stop

@section('js')
    <script> 
        $(document).ready(function () {

            $(document).on('click', '.btn_edit', function (e) {
                var url = "{{ route('entity-type-components.edit', ['_id_']) }}";
                url = url.replace('_id_', $(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: 10,
                ajax: {
                    type: 'POST',
                    url: '{{ route('entity-type-components.datatable') }}',
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
                    {data: "id", name: "id"},
                    {data: "symbol_display", name: "symbol_display", searchable: false, orderable: false},
                    {data: "name", name: "name"},
                    {data: "id", name: "id"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function(a, b) {
                    jsgrid();
                },
                "drawCallback": function(){
                    jsgrid();
                    $('#selAll').prop('checked', false);
                },
                columnDefs: [
                    {
                        render: function(data, type, row){
                            return '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" id="sel-'+row.id+'" name="selected[]" value="' + row.id + '">' +
                                '</div>';
                        },
                        targets:   0
                    },
                    {
                        render: function(data, type, row){
                            var editBtn = '<button type="button" class="btn btn-primary btn-sm btn_edit mr-1" data-id="' + data + '" data-toggle="tooltip" title="Modifica"><i class="fa fa-edit"></i></button>';
                            
                            var deleteForm = '<form action="{{ route('entity-type-components.destroy', ['_id_']) }}" method="POST" style="display:inline-block;" class="delete-form mr-1">' +
                                '@csrf' +
                                '@method("DELETE")' +
                                '<button type="submit" class="btn btn-danger btn-sm" data-toggle="tooltip" title="Elimina" onclick="return confirm(\'Sei sicuro di voler eliminare questa tipologia?\')"><i class="fa fa-trash"></i></button>' +
                                '</form>';
                            deleteForm = deleteForm.replace('_id_', data);

                            return '<div class="d-flex align-items-center">' + editBtn + deleteForm + '</div>';
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop
