@extends('adminlte::page')

@section('title', 'Container')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Container</h4>
    </div>
    <div class="card-body">
        <div class="row">
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
                            <th>Utente</th>
                            <th>Birth Region</th>
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
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('containers.players.datatable') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {},
                },
                columns: [
                    {
                        searchable: false,
                        orderable: false,
                        data: null,
                        name: "checkbox",
                        defaultContent: "",
                        class: "disableEdit",
                    },
                    {data:"id", name:"id"},
                    {data:"user_name", name:"user_name"},
                    {data:"birth_region_name", name:"birth_region_name"},
                    {data:"id", name:"id"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function() {
                    jsgrid();
                },
                drawCallback: function() {
                    jsgrid();
                    $('#selAll').prop('checked', false);
                },
                columnDefs: [
                    {
                        render: function(data) {
                            return '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" id="sel-' + data.id + '" name="selected[]" value="' + data.id + '">' +
                                '</div>';
                        },
                        targets: 0
                    },
                    {
                        render: function(data) {
                            var url = "{{ route('containers.show', ['_id_']) }}";
                            url = url.replace('_id_', data);
                            return '<a href="' + url + '">' +
                                '<button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-eye"></i></button>' +
                                '</a>';
                        },
                        targets: 4
                    },
                ],
            });

        });
    </script>
@stop
