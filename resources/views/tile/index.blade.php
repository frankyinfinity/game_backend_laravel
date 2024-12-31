@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Tile</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('tiles.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <a href="{{route('tiles.create')}}">
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
                            <th>Colore</th>
                            <th>Tipo</th>
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
                var url="{{ route('tiles.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('tiles.datatable') }}',
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
                    {data:"color", name:"color"},
                    {data:"type", name:"type"},
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
                            return '<div style="background-color: '+data+'; width: 25px; height: 25px"></div>';
                        },
                        targets:   2
                    },
                    {
                        render: function(data, type, row){
                            if(data.toString() === '{{App\Models\Tile::TYPE_SOLID}}') return 'Solido';
                            if(data.toString() === '{{App\Models\Tile::TYPE_LIQUID}}') return 'Liquido';
                            return '-';
                        },
                        targets:   3
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-primary btn-block btn-sm btn_edit" data-id="'+data+'"><i class="fa fa-edit"></i></button>';
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop