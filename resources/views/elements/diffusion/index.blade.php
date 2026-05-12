@extends('adminlte::page')

@section('title', 'Diffusione Elementi')

@section('content_header')
<h1>Diffusione Elementi</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Diffusione Elementi</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Grafica</th>
                            <th>Nome</th>
                            <th>Tipologia</th>
                            <th>Caratteristica</th>
                            <th>Climi Validi</th>
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

            var table = $("#table_list").DataTable({
                order: [0, 'asc'],
                ajax: {
                    type: 'POST',
                    url: '{{ route("elements.datatable") }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                },
                columns: [
                    {data:"id", name:"id"},
                    {data:"graphics", name:"graphics", searchable: false, orderable: false},
                    {data:"name", name:"name"},
                    {data:"element_type_name", name:"element_type_name"},
                    {data:"characteristic", name:"characteristic"},
                    {data:"climates_list", name:"climates_list"},
                    {data:"id", name:"id"},
                ],
                columnDefs: [
                    {
                        render: function(data, type, row){
                            return '<a href="/elements/' + data + '/diffusion" class="btn btn-primary btn-block btn-sm"><i class="fa fa-eye"></i> Visualizza Diffusione</a>';
                        },
                        targets:   6
                    },
                ],
            });

        });
    </script>
@stop