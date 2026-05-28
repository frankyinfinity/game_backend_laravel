@extends('adminlte::page')

@section('title', 'Geni')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Geni</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Immagine</th>
                            <th>Nome</th>
                            <th>Key</th>
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

            $(document).on('click', '.btn_show', function (e) {
                var url="{{ route('genes.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('genes.datatable') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {},
                },
                columns: [
                    {data:"id", name:"id"},
                    {data:"image_url", name:"image"},
                    {data:"name", name:"name"},
                    {data:"key", name:"key"},
                    {data:"id", name:"id"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function(a, b) {
                    jsgrid();
                },
                "drawCallback":function(){
                    jsgrid();
                },
                columnDefs: [
                    {
                        render: function(data, type, row){
                            if (data) {
                                return '<img src="'+data+'" alt="Immagine" style="max-height: 40px; max-width: 40px; border-radius: 4px;">';
                            }
                            return '<span class="text-muted"><em>Nessuna</em></span>';
                        },
                        targets:   1
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-info btn-block btn-sm btn_show" data-id="'+data+'"><i class="fa fa-eye"></i> Dettaglio</button>';
                        },
                        targets:   4
                    },
                ],
            });

        });
    </script>
@stop