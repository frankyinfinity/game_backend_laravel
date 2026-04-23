@extends('adminlte::page')

@section('title', 'Regole Elementi Chimici')

@section('content_header')@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="icon fas fa-check"></i>
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Regole Elementi Chimici</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list" data-url="{{ route('rule-chimical-elements.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <button type="button" class="btn btn-primary btn-block btn-sm" data-toggle="modal" data-target="#createRuleModal">
                            <i class="fa fa-plus"></i> Nuovo
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
                            <th>Elemento</th>
                            <th>Tipo</th>
                            <th>Min</th>
                            <th>Max</th>
                            <th>Default</th>
                            <th>Degradabile</th>
                            <th>Regola</th>
                            <th>Grafico</th>
                            <th>Clona</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Unica di Creazione --}}
<div class="modal fade" id="createRuleModal" tabindex="-1" role="dialog" aria-labelledby="createRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createRuleModalLabel"><i class="fas fa-plus-circle mr-2"></i> Nuova Regola Elemento Chimico</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('rule-chimical-elements.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <h6 class="text-bold mb-3"><i class="fas fa-info-circle mr-1"></i> Configurazione Base</h6>
                            <div class="form-group">
                                <label for="modal_element_type">Tipo Elemento</label>
                                <select class="form-control" id="modal_element_type" name="element_type" onchange="toggleModalElementSelects()">
                                    <option value="simple">Elemento Chimico Semplice</option>
                                    <option value="complex">Elemento Chimico Complesso</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_name">Nome Regola <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_name" name="name" required placeholder="Es: Regola Ossigeno">
                            </div>
                            <div class="form-group" id="modal_chimical_element_group">
                                <label for="modal_chimical_element_id">Elemento Chimico</label>
                                <select class="form-control" id="modal_chimical_element_id" name="chimical_element_id">
                                    <option value="">Seleziona Elemento Chimico</option>
                                    @foreach($chimicalElements as $ce)
                                        <option value="{{ $ce->id }}">{{ $ce->name }} ({{ $ce->symbol }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" id="modal_complex_chimical_element_group" style="display: none;">
                                <label for="modal_complex_chimical_element_id">Elemento Chimico Complesso</label>
                                <select class="form-control" id="modal_complex_chimical_element_id" name="complex_chimical_element_id">
                                    <option value="">Seleziona Elemento Chimico Complesso</option>
                                    @foreach($complexChimicalElements as $cce)
                                        <option value="{{ $cce->id }}">{{ $cce->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_type">Tipo Regola</label>
                                <select class="form-control" id="modal_type" name="type">
                                    <option value="entity">Entità</option>
                                    <option value="element" selected>Elemento</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_min">Min</label>
                                        <input type="number" class="form-control" id="modal_min" name="min" value="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_max">Max</label>
                                        <input type="number" class="form-control" id="modal_max" name="max" value="100" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="modal_default">Default</label>
                                        <input type="number" class="form-control" id="modal_default" name="default_value" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-bold mb-3"><i class="fas fa-chart-line mr-1"></i> Degradazione</h6>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="modal_degradable" name="degradable" value="1">
                                    <label class="custom-control-label" for="modal_degradable">Abilita Degradazione</label>
                                </div>
                            </div>
                            <div id="modal_degradation_fields" style="display: none;">
                                <div class="form-group mt-3">
                                    <label for="modal_qty_tick">Qtà per Tick</label>
                                    <input type="number" class="form-control" id="modal_qty_tick" name="quantity_tick_degradation" value="1" min="0">
                                    <small class="text-muted">Quantità persa ad ogni tick di gioco.</small>
                                </div>
                                <div class="form-group">
                                    <label for="modal_pct">Degrado %</label>
                                    <input type="number" class="form-control" id="modal_pct" name="percentage_degradation" value="100" min="0" max="100" step="0.01">
                                    <small class="text-muted">Probabilità di accadimento (0-100%).</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Crea Regola</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
    <script>
        $(document).ready(function () {

            $(document).on('click', '.btn_edit', function (e) {
                var url="{{ route('rule-chimical-elements.edit',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            $(document).on('click', '.btn_edit-rule', function (e) {
                var url="{{ route('rule-chimical-elements.edit',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            $(document).on('click', '.btn-replicate', function (e) {
                var url="{{ route('rule-chimical-elements.replicate',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            $(document).on('click', '.btn-graph', function (e) {
                var url="{{ route('rule-chimical-elements.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
            });

            window.toggleModalElementSelects = function() {
                var type = $('#modal_element_type').val();
                if (type === 'simple') {
                    $('#modal_chimical_element_group').show();
                    $('#modal_complex_chimical_element_group').hide();
                    $('#modal_chimical_element_id').attr('required', true);
                    $('#modal_complex_chimical_element_id').attr('required', false);
                } else {
                    $('#modal_chimical_element_group').hide();
                    $('#modal_complex_chimical_element_group').show();
                    $('#modal_chimical_element_id').attr('required', false);
                    $('#modal_complex_chimical_element_id').attr('required', true);
                }
            }

            $('#modal_degradable').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#modal_degradation_fields').slideDown();
                } else {
                    $('#modal_degradation_fields').slideUp();
                }
            });

            var table = $("#table_list").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('rule-chimical-elements.datatable') }}',
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
                    {data:"element", name:"element"},
                    {data:"type", name:"type"},
                    {data:"min", name:"min"},
                    {data:"max", name:"max"},
                    {data:"default_value", name:"default_value"},
                    {data:"degradable", name:"degradable"},
                    {data:"id", name:"id"},
                    {data:"id", name:"id"},
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
                            return '<span class="badge" style="background-color: '+row.color+'">'+data+'</span>';
                        },
                        targets:   3
                    },
                    {
                        render: function(data, type, row){
                            return data ? '<span class="badge badge-success">Sì</span>' : '<span class="badge badge-secondary">No</span>';
                        },
                        targets:   7
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-warning btn-sm btn-block btn_edit-rule" data-id="'+data+'" title="Modifica Regola"><i class="fa fa-cog"></i></button>';
                        },
                        targets:   8
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-info btn-sm btn-block btn-graph" data-id="'+data+'" title="Grafico Lineare"><i class="fa fa-chart-bar"></i></button>';
                        },
                        targets:   9
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-success btn-sm btn-block btn-replicate" data-id="'+data+'" title="Clona Regola"><i class="fa fa-copy"></i></button>';
                        },
                        targets:   10
                    },
                ],
            });

        });
    </script>
@stop
