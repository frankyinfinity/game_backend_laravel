@extends('adminlte::page')

@section('title', 'Corpo Entity')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-dark font-weight-bold">Corpo Entity</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row justify-content-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete shadow-sm" data-list="table_list" data-url="{{ route('entity-bodies.delete') }}">
                            <i class="fa fa-trash"></i> Elimina Selezionati
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('entity-bodies.create') }}">
                            <button type="button" class="btn btn-primary btn-block btn-sm shadow-sm"><i class="fa fa-plus"></i> Nuovo Corpo</button>                    
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
                                <th>Immagine</th>
                                <th>Nome</th>
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
</div>
@stop

@section('js')
    <script> 
        $(document).ready(function () {

            $(document).on('click', '.btn_edit', function (e) {
                var url = "{{ route('entity-bodies.edit', ['_id_']) }}";
                url = url.replace('_id_', $(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: 10,
                ajax: {
                    type: 'POST',
                    url: '{{ route('entity-bodies.datatable') }}',
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
                    {data: "image_display", name: "image_display", searchable: false, orderable: false},
                    {data: "name", name: "name"},
                    {data: "state_display", name: "state_display"},
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
                            // If component is finished, we disable the checkbox
                            var disabledAttr = row.state == 1 ? 'disabled title="I corpi completati non possono essere eliminati"' : '';
                            return '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" id="sel-'+row.id+'" name="selected[]" value="' + row.id + '" ' + disabledAttr + '>' +
                                '</div>';
                        },
                        targets:   0
                    },
                    {
                        render: function(data, type, row){
                            var editBtn = '<button type="button" class="btn btn-primary btn-sm btn_edit mr-1" data-id="' + data + '" data-toggle="tooltip" title="Modifica"><i class="fa fa-edit"></i></button>';
                            
                            var toggleBtn = '';
                            if (row.state == 0) {
                                if (!row.image) {
                                    toggleBtn = '<button type="button" class="btn btn-success btn-sm disabled-state-btn mr-1" data-toggle="tooltip" title="Disegna la grafica per poter completare"><i class="fas fa-check-circle" style="opacity: 0.5;"></i></button>';
                                } else {
                                    toggleBtn = '<form action="{{ route('entity-bodies.toggle-state') }}" method="POST" style="display:inline-block;" class="state-form js-confirm-complete mr-1">' +
                                        '@csrf' +
                                        '<input type="hidden" name="id" value="' + data + '">' +
                                        '<button type="submit" class="btn btn-success btn-sm" data-toggle="tooltip" title="Completa e blocca"><i class="fas fa-check-circle"></i></button>' +
                                        '</form>';
                                }
                            }
                            
                            return '<div class="d-flex align-items-center">' + editBtn + toggleBtn + '</div>';
                        },
                        targets:   5
                    },
                ],
            });

            $(document).on('click', '.disabled-state-btn', function(e) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Disegna l\'immagine per poter completare il corpo.');
                } else {
                    alert('Disegna l\'immagine per poter completare il corpo.');
                }
            });

            $(document).on('submit', '.js-confirm-complete', function(e) {
                if(!confirm('Sei sicuro di voler completare questo corpo? Non sarà più modificabile.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
@stop
