@extends('adminlte::page')

@section('title', 'Fasi - ' . $age->name)

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Fasi - {{ $age->name }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('ages.phases.delete', $age) }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <a href="{{ route('ages.phases.create', $age) }}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-plus"></i> Nuova</button>                    
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
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Altezza</th>
                            <th>Ordinamento</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('ages.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alle Ere
        </a>
    </div>
</div>
@stop

@section('js')
    <script> 
        $(document).ready(function () {

            $(document).on('click', '.btn_edit', function (e) {
                var url="{{ route('ages.phases.edit', ['age' => $age->id, 'phase' => '_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            $(document).on('click', '.btn_move_up', function (e) {
                e.preventDefault();
                var url="{{ route('ages.phases.move-up', ['age' => $age->id, 'phase' => '_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $(document).on('click', '.btn_move_down', function (e) {
                e.preventDefault();
                var url="{{ route('ages.phases.move-down', ['age' => $age->id, 'phase' => '_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        table.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            var table = $("#table_list").DataTable({
                order: [4, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('ages.phases.datatable', $age) }}',
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
                    {data:"id", name:"id"},
                    {data:"name", name:"name"},
                    {data:"height", name:"height"},
                    {data:"order", name:"order"},
                    {data:"id", name:"id"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function(a, b) {
                    jsgrid();
                },
                "drawCallback":function(){
                    jsgrid();
                    $('#selAll').prop('checked',false);
                    
                    // Get reference to current table instance
                    var api = this.api();
                    
                    // Get all order values
                    var orderValues = [];
                    api.rows().every(function(rowIdx, tableLoop, rowLoop) {
                        var data = this.data();
                        orderValues.push(data.order);
                    });
                    
                    // Hide buttons based on order
                    api.rows().every(function(rowIdx, tableLoop, rowLoop) {
                        var data = this.data();
                        var cell = api.cell(rowIdx, 4).node();
                        
                        if (data.order === Math.min.apply(Math, orderValues)) {
                            // First item - only show down button
                            $(cell).find('.btn_move_up').hide();
                            $(cell).find('.btn_move_down').show();
                        } else if (data.order === Math.max.apply(Math, orderValues)) {
                            // Last item - only show up button
                            $(cell).find('.btn_move_up').show();
                            $(cell).find('.btn_move_down').hide();
                        } else {
                            // Middle items - show both buttons
                            $(cell).find('.btn_move_up').show();
                            $(cell).find('.btn_move_down').show();
                        }
                    });
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
                            var detailsUrl = "{{ route('ages.phases.show', ['age' => $age->id, 'phase' => '_id_']) }}";
                            detailsUrl = detailsUrl.replace('_id_', data);
                            var editUrl = "{{ route('ages.phases.edit', ['age' => $age->id, 'phase' => '_id_']) }}";
                            editUrl = editUrl.replace('_id_', data);
                            return '<div class="d-flex flex-column gap-1">' +
                                '<a href="' + detailsUrl + '" class="btn btn-info btn-block btn-sm"><i class="fa fa-eye"></i> Dettagli</a>' +
                                '<button type="button" class="btn btn-primary btn-block btn-sm btn_edit" data-id="'+data+'"><i class="fa fa-edit"></i> Modifica</button>' +
                                '</div>';
                        },
                        targets:   5
                    },
                    {
                        render: function(data, type, row, meta){
                            return '<div class="d-flex gap-1">' +
                                '<button type="button" class="btn btn-primary mr-2 btn-sm btn_move_up" data-id="'+row.id+'"><i class="fa fa-arrow-up"></i></button>' +
                                '<button type="button" class="btn btn-danger btn-sm btn_move_down" data-id="'+row.id+'"><i class="fa fa-arrow-down"></i></button>' +
                                '</div>';
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop
