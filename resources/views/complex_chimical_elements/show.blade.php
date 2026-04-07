@extends('adminlte::page')

@section('title', 'Elemento Chimico Complesso: ' . $complexChimicalElement->name)

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">{{ $complexChimicalElement->name }} ({{ $complexChimicalElement->symbol }})</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <a href="{{ route('complex-chimical-elements.edit', $complexChimicalElement) }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-edit"></i> Modifica
                </a>
                <a href="{{ route('complex-chimical-elements.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Indietro
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5>Dettagli Elementi Chimici</h5>
                <hr>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal_add_chimical">
                            <i class="fa fa-plus"></i> Aggiungi Elemento Chimico
                        </button>
                        <button type="button" class="btn btn-info btn-sm ml-2" data-toggle="modal" data-target="#modal_add_complex">
                            <i class="fa fa-plus"></i> Aggiungi Elemento Chimico Complesso
                        </button>
                    </div>
                </div>

                <!-- Modal A: Simple Element -->
                <div class="modal fade" id="modal_add_chimical" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Aggiungi Elemento Chimico</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Elemento Chimico</label>
                                    <select id="modal_chimical_element_id" class="form-control">
                                        <option value="">Seleziona Elemento Chimico</option>
                                        @foreach($chimicalElements as $ce)
                                            <option value="{{ $ce->id }}">{{ $ce->name }} ({{ $ce->symbol }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Quantità</label>
                                    <input type="number" id="modal_chimical_quantity" class="form-control" placeholder="Quantità" min="1">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                                <button type="button" id="btn_save_chimical" class="btn btn-success">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal B: Complex Element -->
                <div class="modal fade" id="modal_add_complex" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Aggiungi Elemento Chimico Complesso</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Elemento Chimico Complesso</label>
                                    <select id="modal_complex_chimical_element_id" class="form-control">
                                        <option value="">Seleziona Elemento Complesso</option>
                                        @foreach($allComplexChimicalElements as $cce)
                                            <option value="{{ $cce->id }}">{{ $cce->name }} ({{ $cce->symbol }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Quantità</label>
                                    <input type="number" id="modal_complex_quantity" class="form-control" placeholder="Quantità" min="1">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                                <button type="button" id="btn_save_complex" class="btn btn-info">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="material-datatables">
                    <table id="detail_table" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead>
                        <tr>
                            <th class="column_with_checkbox">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" onClick="toggle(this, 'selected[]')">
                                </div>
                            </th>
                            <th>ID</th>
                            <th>Elemento Chimico</th>
                            <th>Simbolo</th>
                            <th>Quantità</th>
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

            var detailTable = $("#detail_table").DataTable({
                order: [1, 'asc'],
                pageLength: -1,
                ajax: {
                    type: 'POST',
                    url: '{{ route('complex-chimical-elements.details.datatable', $complexChimicalElement) }}',
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
                    {data: "id", name: "id"},
                    {data: "chimical_element_name", name: "chimical_element_name"},
                    {data: "chimical_element_symbol", name: "chimical_element_symbol"},
                    {data: "quantity", name: "quantity"},
                    {data: "id", name: "id"},
                ],
                sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
                initComplete: function(a, b) {
                    jsgrid();
                },
                "drawCallback": function() {
                    jsgrid();
                    $('#selAll').prop('checked', false);
                },
                columnDefs: [
                    {
                        render: function(data, type, row) {
                            return '<div class="form-check">' +
                                '<input class="form-check-input" type="checkbox" id="sel-' + data.id + '" name="selected[]" value="' + data.id + '">' +
                                '</div>';
                        },
                        targets: 0
                    },
                    {
                        render: function(data, type, row) {
                            return '<button type="button" class="btn btn-danger btn-block btn-sm btn_delete_detail" data-id="' + data + '"><i class="fa fa-trash"></i></button>';
                        },
                        targets: 5
                    },
                ],
            });

            $('#btn_save_chimical').on('click', function() {
                var chimicalElementId = $('#modal_chimical_element_id').val();
                var quantity = $('#modal_chimical_quantity').val();

                if (!chimicalElementId) {
                    alert('Seleziona un elemento chimico.');
                    return;
                }
                if (!quantity || quantity < 1) {
                    alert('Inserisci una quantità valida.');
                    return;
                }

                $.ajax({
                    url: '{{ route('complex-chimical-elements.details.store', $complexChimicalElement) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        chimical_element_id: chimicalElementId,
                        quantity: quantity
                    },
                    success: function(response) {
                        $('#modal_chimical_element_id').val('');
                        $('#modal_chimical_quantity').val('');
                        $('#modal_add_chimical').modal('hide');
                        detailTable.ajax.reload();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.message || 'Errore durante il salvataggio.');
                    }
                });
            });

            $('#btn_save_complex').on('click', function() {
                var complexChimicalElementId = $('#modal_complex_chimical_element_id').val();
                var quantity = $('#modal_complex_quantity').val();

                if (!complexChimicalElementId) {
                    alert('Seleziona un elemento chimico complesso.');
                    return;
                }
                if (!quantity || quantity < 1) {
                    alert('Inserisci una quantità valida.');
                    return;
                }

                $.ajax({
                    url: '{{ route('complex-chimical-elements.details.store', $complexChimicalElement) }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        complex_chimical_element_id: complexChimicalElementId,
                        quantity: quantity
                    },
                    success: function(response) {
                        $('#modal_complex_chimical_element_id').val('');
                        $('#modal_complex_quantity').val('');
                        $('#modal_add_complex').modal('hide');
                        detailTable.ajax.reload();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.message || 'Errore durante il salvataggio.');
                    }
                });
            });

            $(document).on('click', '.btn_delete_detail', function() {
                var id = $(this).data('id');
                if (!confirm('Sei sicuro di voler eliminare questo dettaglio?')) return;

                $.ajax({
                    url: '{{ route('complex-chimical-elements.details.destroy', [$complexChimicalElement, '_id_']) }}'.replace('_id_', id),
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        detailTable.ajax.reload();
                    },
                    error: function() {
                        alert('Errore durante l\'eliminazione.');
                    }
                });
            });

        });
    </script>
@stop
