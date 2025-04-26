@extends('adminlte::page')

@section('title', 'Pianeti')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Giocatori</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th>Giocatore</th>
                            <th>Pianeta</th>
                            <th>Regione</th>
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
                var url="{{ route('players.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('players.datatable') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {},
                },
                columns: [
                    {data:"user.name", name:"user.name"},
                    {data:"birth_planet.name", name:"birth_planet.name"},
                    {data:"birth_region.name", name:"birth_region.name"},
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
                            return '<div class="mt-1 mb-1">' +
                                data+
                                '</div>';
                        },
                        targets:   0
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-primary btn-block btn-sm btn_edit" data-id="'+data+'"><i class="fa fa-eye"></i></button>';
                        },
                        targets:   3
                    },
                ],
            });

        });
    </script>
@stop