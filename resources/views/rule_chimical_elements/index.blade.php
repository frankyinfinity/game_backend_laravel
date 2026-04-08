@extends('adminlte::page')

@section('title', 'Regole Elementi Chimici')

@section('content_header')@stop

@section('content')
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
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa fa-plus"></i> Nuovo
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createChimicalRuleModal">
                                    <i class="fa fa-atom"></i> Elemento Chimico
                                </a>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createComplexChimicalRuleModal">
                                    <i class="fa fa-cubes"></i> Elemento Complesso
                                </a>
                            </div>
                        </div>
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
                            <th>Regola</th>
                            <th>Grafico</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createChimicalRuleModal" tabindex="-1" role="dialog" aria-labelledby="createChimicalRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createChimicalRuleModalLabel">Nuova Regola - Elemento Chimico</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('rule-chimical-elements.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="chimical_element_id_modal">Elemento Chimico <span class="text-danger">*</span></label>
                        <select class="form-control" id="chimical_element_id_modal" name="chimical_element_id" required>
                            <option value="">Seleziona Elemento Chimico</option>
                            @foreach($chimicalElements as $ce)
                                <option value="{{ $ce->id }}">{{ $ce->name }} ({{ $ce->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="min">Min <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="min" name="min" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="max">Max <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="max" name="max" value="0" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createComplexChimicalRuleModal" tabindex="-1" role="dialog" aria-labelledby="createComplexChimicalRuleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createComplexChimicalRuleModalLabel">Nuova Regola - Elemento Chimico Complesso</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('rule-chimical-elements.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="complex_chimical_element_id_modal">Elemento Chimico Complesso <span class="text-danger">*</span></label>
                        <select class="form-control" id="complex_chimical_element_id_modal" name="complex_chimical_element_id" required>
                            <option value="">Seleziona Elemento Chimico Complesso</option>
                            @foreach($complexChimicalElements as $cce)
                                <option value="{{ $cce->id }}">{{ $cce->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="min">Min <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="min" name="min" value="0" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="max">Max <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="max" name="max" value="0" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
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

            $(document).on('click', '.btn-graph', function (e) {
                var url="{{ route('rule-chimical-elements.show',['_id_']) }}";
                url = url.replace('_id_',$(this).data('id'));
                window.location.href = url;
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
                            return '<button type="button" class="btn btn-warning btn-sm btn-block btn_edit-rule" data-id="'+data+'" title="Modifica Regola"><i class="fa fa-cog"></i></button>';
                        },
                        targets:   6
                    },
                    {
                        render: function(data, type, row){
                            return '<button type="button" class="btn btn-info btn-sm btn-block btn-graph" data-id="'+data+'" title="Grafico Lineare"><i class="fa fa-chart-bar"></i></button>';
                        },
                        targets:   7
                    },
                ],
            });

        });
    </script>
@stop
