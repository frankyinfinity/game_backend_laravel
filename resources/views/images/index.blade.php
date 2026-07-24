@extends('adminlte::page')

@section('title', 'Immagini Docker')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Immagini Docker</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('images.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <button type="button" class="btn btn-primary btn-block btn-sm" onclick="alert('Per buildare le immagini, esegui: php artisan docker:build')">
                            <i class="fa fa-hammer"></i> Build Immagini
                        </button>
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
                            <th>Immagine Docker</th>
                            <th>Tag</th>
                            <th>Versione</th>
                            <th>Stato</th>
                            <th>Creata</th>
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
                var url="{{ route('images.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            $(document).on('click', '.btn_activate', function (e) {
                var url="{{ route('images.activate',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                if(confirm('Sei sicuro di voler attivare questa versione?')) {
                    $.post(url, {'_token': $('meta[name="csrf-token"]').attr('content')})
                        .done(function() { table.ajax.reload(null, false); })
                        .fail(function() { alert('Errore durante l\'attivazione.'); });
                }
            });

            $(document).on('click', '.btn_deactivate', function (e) {
                var url="{{ route('images.deactivate',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                if(confirm('Sei sicuro di voler disattivare questa versione?')) {
                    $.post(url, {'_token': $('meta[name="csrf-token"]').attr('content')})
                        .done(function() { table.ajax.reload(null, false); })
                        .fail(function() { alert('Errore durante la disattivazione.'); });
                }
            });

            $(document).on('click', '.btn_download', function (e) {
                var url="{{ route('images.download',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('images.datatable') }}',
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
                    {data:"docker_image_name", name:"docker_image_name"},
                    {data:"docker_tag", name:"docker_tag"},
                    {data:"version", name:"version"},
                    {data:"is_active", name:"is_active"},
                    {data:"created_at", name:"created_at"},
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
                            if (data) {
                                return '<span class="badge badge-success">Attivo</span>';
                            }
                            return '<span class="badge badge-secondary">Inattivo</span>';
                        },
                        targets:   6
                    },
                    {
                        render: function(data, type, row){
                            if (data) {
                                return new Date(data).toLocaleDateString('it-IT') + ' ' + new Date(data).toLocaleTimeString('it-IT');
                            }
                            return '<span class="text-muted"><em>N/A</em></span>';
                        },
                        targets:   7
                    },
                    {
                        render: function(data, type, row){
                            var buttons = '<button type="button" class="btn btn-info btn-sm btn_show" data-id="'+data+'" title="Dettagli"><i class="fa fa-eye"></i></button>';
                            
                            if (row.is_active) {
                                buttons += ' <button type="button" class="btn btn-warning btn-sm btn_deactivate" data-id="'+data+'" title="Disattiva"><i class="fa fa-toggle-on"></i></button>';
                            } else {
                                buttons += ' <button type="button" class="btn btn-success btn-sm btn_activate" data-id="'+data+'" title="Attiva"><i class="fa fa-toggle-off"></i></button>';
                            }
                            
                            return buttons;
                        },
                        targets:   8
                    },
                ],
            });

        });
    </script>
@stop
