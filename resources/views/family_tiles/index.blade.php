@extends('adminlte::page')

@section('title', 'Famiglie Tile')

@section('content_header')
<h1>Famiglie Tile</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Famiglie Tile</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('family-tiles.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <a href="{{route('family-tiles.create')}}">
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
                            <th>ID</th>
                            <th>Nome</th>
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
                var url="{{ route('family-tiles.edit',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                ajax: {
                    type: 'POST',
                    url: '{{ route('family-tiles.datatable') }}',
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
                    },
                    {data:"id", name:"id"},
                    {data:"name", name:"name"},
                    {data:"type_label", name:"type_label"},
                    {data:"id", name:"id"},
                ],
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
                            return '<button type="button" class="btn btn-primary btn-block btn-sm btn_edit" data-id="'+data+'"><i class="fa fa-edit"></i></button>';
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop