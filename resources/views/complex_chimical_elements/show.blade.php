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
                    <div class="col-md-4">
                        <select id="chimical_element_id" class="form-control form-control-sm">
                            <option value="">Seleziona Elemento Chimico</option>
                            @foreach($chimicalElements as $ce)
                                <option value="{{ $ce->id }}">{{ $ce->name }} ({{ $ce->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" id="quantity" class="form-control form-control-sm" placeholder="Quantità" min="1">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn_add_detail" class="btn btn-success btn-sm btn-block">
                            <i class="fa fa-plus"></i> Aggiungi
                        </button>
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

            $('#btn_add_detail').on('click', function() {
                var chimicalElementId = $('#chimical_element_id').val();
                var quantity = $('#quantity').val();

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
                        $('#chimical_element_id').val('');
                        $('#quantity').val('');
                        detailTable.ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Errore durante il salvataggio.');
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
