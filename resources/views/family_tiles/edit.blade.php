@extends('adminlte::page')

@section('title', 'Modifica Famiglia Tile')

@section('content_header')
<h1>Modifica Famiglia Tile</h1>
@stop

@section('content')
<div class="card card-primary card-outline card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="main-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-info-link" data-toggle="pill" href="#tab-info" role="tab"
                    aria-controls="tab-info" aria-selected="true">Informazioni Principali</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-limits-link" data-toggle="pill" href="#tab-limits" role="tab"
                    aria-controls="tab-limits" aria-selected="false">Limiti</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-diffusion-link" data-toggle="pill" href="#tab-diffusion" role="tab"
                    aria-controls="tab-diffusion" aria-selected="false">Diffusione</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="main-tabsContent">
            <!-- TAB INFORMAZIONI PRINCIPALI -->
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-link">
                <form action="{{ route('family-tiles.update', $familyTile) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $familyTile->name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Tipo</label>
                        <select class="form-control" id="type" name="type" required>
                            @foreach(App\Models\FamilyTile::getTypeLabels() as $value => $label)
                                <option value="{{ $value }}" {{ $familyTile->type == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
                </form>
            </div>

            <!-- TAB LIMITI -->
            <div class="tab-pane fade" id="tab-limits" role="tabpanel" aria-labelledby="tab-limits-link">
                <div class="alert alert-info">
                    <strong>Informazioni sui Limiti:</strong> In questa sezione puoi impostare i limiti di quantità per ogni elemento chimico e complesso su ciascun tipo di tile familiare. I limiti determinano la quantità massima che può essere distribuita o generata su un tile specifico. Un limite di 0 impedisce completamente la presenza dell'elemento su quel tile.
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#updateAllModal">Aggiorna Tutti</button>
                </div>
                <form action="{{ route('family-tiles.update-limits', $familyTile) }}" method="POST">
                    @csrf
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Elemento Chimico / Complesso</th>
                                    <th>Limite</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chimicalElements as $element)
                                    <tr>
                                        <td>{{ $element->name }} ({{ $element->symbol }})</td>
                                        <td>
                                            <input type="number" name="limits[ch{{ $element->id }}][limit_value]" value="{{ $element->limit_value }}" min="0" class="form-control">
                                            <input type="hidden" name="limits[ch{{ $element->id }}][chimical_element_id]" value="{{ $element->id }}">
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($complexChimicalElements as $element)
                                    <tr>
                                        <td>{{ $element->name }} (Complesso)</td>
                                        <td>
                                            <input type="number" name="limits[cc{{ $element->id }}][limit_value]" value="{{ $element->limit_value }}" min="0" class="form-control">
                                            <input type="hidden" name="limits[cc{{ $element->id }}][complex_chimical_element_id]" value="{{ $element->id }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Limiti</button>
                    <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
                </form>
            </div>

            <!-- TAB DIFFUSIONE -->
            <div class="tab-pane fade" id="tab-diffusion" role="tabpanel" aria-labelledby="tab-diffusion-link">
                <div class="alert alert-info">
                    <strong>Informazioni sulla Diffusione:</strong> In questa sezione puoi configurare la diffusione degli elementi chimici e complessi per questo tile familiare. Ogni regola definisce un intervallo iniziale (Da - A) per la quantità dell'elemento che potrà essere presente sul tile alla sua creazione. Ad esempio, se imposti Da=100 e A=120, il tile inizierà con una quantità compresa tra questi valori.
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createDiffusionModal">Aggiungi Diffusione</button>
                </div>
                <div class="table-responsive">
                    <table id="diffusions-table" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>Tipo Elemento</th>
                                <th>Nome Elemento</th>
                                <th>Intervallo</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Update All -->
<div class="modal fade" id="updateAllModal" tabindex="-1" role="dialog" aria-labelledby="updateAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateAllModalLabel">Aggiorna Tutti i Limiti</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateAllForm" action="{{ route('family-tiles.update-limits', $familyTile) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="all_limit_value">Valore Limite</label>
                        <input type="number" class="form-control" id="all_limit_value" name="all_limit_value" value="200" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Create/Edit Diffusion -->
<div class="modal fade" id="createDiffusionModal" tabindex="-1" role="dialog" aria-labelledby="createDiffusionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createDiffusionModalLabel">Aggiungi Diffusione</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="diffusionForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="element_type">Tipo Elemento</label>
                        <select class="form-control" id="element_type" name="element_type" required>
                            <option value="">Seleziona Tipo</option>
                            <option value="chimical">Elemento Chimico</option>
                            <option value="complex">Elemento Chimico Complesso</option>
                        </select>
                    </div>
                    <div class="form-group" id="chimical_element_group" style="display: none;">
                        <label for="chimical_element_id">Elemento Chimico</label>
                        <select class="form-control" id="chimical_element_id" name="chimical_element_id">
                            <option value="">Seleziona Elemento</option>
                            @foreach($chimicalElements as $element)
                                <option value="{{ $element->id }}">{{ $element->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="complex_element_group" style="display: none;">
                        <label for="complex_chimical_element_id">Elemento Chimico Complesso</label>
                        <select class="form-control" id="complex_chimical_element_id" name="complex_chimical_element_id">
                            <option value="">Seleziona Elemento</option>
                            @foreach($complexChimicalElements as $element)
                                <option value="{{ $element->id }}">{{ $element->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="range_group" style="display: none;">
                        <div class="form-row">
                            <div class="col">
                                <label for="from">Da</label>
                                <input type="number" class="form-control" id="from" name="from" min="0" required data-toggle="tooltip" title="Min: 0">
                            </div>
                            <div class="col">
                                <label for="to">A</label>
                                <input type="number" class="form-control" id="to" name="to" required data-toggle="tooltip" title="Min: Da + 1, Max: limite elemento">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Edit Diffusion -->
<div class="modal fade" id="editDiffusionModal" tabindex="-1" role="dialog" aria-labelledby="editDiffusionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDiffusionModalLabel">Modifica Diffusione</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDiffusionForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit_diffusion_id" name="diffusion_id">
                    <div class="form-group">
                        <label for="edit_element_type">Tipo Elemento</label>
                        <select class="form-control" id="edit_element_type" name="element_type" required>
                            <option value="">Seleziona Tipo</option>
                            <option value="chimical">Elemento Chimico</option>
                            <option value="complex">Elemento Chimico Complesso</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_chimical_element_group" style="display: none;">
                        <label for="edit_chimical_element_id">Elemento Chimico</label>
                        <select class="form-control" id="edit_chimical_element_id" name="chimical_element_id">
                            <option value="">Seleziona Elemento</option>
                            @foreach($chimicalElements as $element)
                                <option value="{{ $element->id }}">{{ $element->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" id="edit_complex_element_group" style="display: none;">
                        <label for="edit_complex_chimical_element_id">Elemento Chimico Complesso</label>
                        <select class="form-control" id="edit_complex_chimical_element_id" name="complex_chimical_element_id">
                            <option value="">Seleziona Elemento</option>
                            @foreach($complexChimicalElements as $element)
                                <option value="{{ $element->id }}">{{ $element->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="edit_range_group" style="display: none;">
                        <div class="form-row">
                            <div class="col">
                                <label for="edit_from">Da</label>
                                <input type="number" class="form-control" id="edit_from" name="from" min="0" required data-toggle="tooltip" title="Min: 0">
                            </div>
                            <div class="col">
                                <label for="edit_to">A</label>
                                <input type="number" class="form-control" id="edit_to" name="to" required data-toggle="tooltip" title="Min: Da + 1, Max: limite elemento">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
    </form>
</div>
</div>
</div>

@section('js')
<script>
$(document).ready(function() {
    // Initialize diffusions datatable
    $('#diffusions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("family-tiles.diffusions", $familyTile) }}',
        columns: [
            { data: 'element_type', name: 'element_type' },
            { data: 'element_name', name: 'element_name' },
            { data: 'range', name: 'range' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-primary edit-diffusion" data-id="${row.id}">Modifica</button>
                        <button class="btn btn-sm btn-danger delete-diffusion" data-id="${row.id}">Elimina</button>
                    `;
                }
            }
        ]
    });

    // Toggle element selects based on type
    $('#element_type').change(function() {
        const type = $(this).val();
        $('#chimical_element_group').toggle(type === 'chimical');
        $('#complex_element_group').toggle(type === 'complex');
        $('#chimical_element_id').prop('required', type === 'chimical').val('');
        $('#complex_chimical_element_id').prop('required', type === 'complex').val('');
        $('#range_group').hide();
        $('#from, #to').removeAttr('max min');
    });

    $('#edit_element_type').change(function() {
        const type = $(this).val();
        $('#edit_chimical_element_group').toggle(type === 'chimical');
        $('#edit_complex_element_group').toggle(type === 'complex');
        $('#edit_chimical_element_id').prop('required', type === 'chimical').val('');
        $('#edit_complex_chimical_element_id').prop('required', type === 'complex').val('');
        $('#edit_range_group').hide();
        $('#edit_from, #edit_to').removeAttr('max min');
    });

    // When element is selected, get limit and show from/to
    $('#chimical_element_id').change(function() {
        handleElementChange('chimical', $(this).val());
    });

    $('#complex_chimical_element_id').change(function() {
        handleElementChange('complex', $(this).val());
    });

    $('#edit_chimical_element_id').change(function() {
        handleElementChange('chimical', $(this).val(), true);
    });

    $('#edit_complex_chimical_element_id').change(function() {
        handleElementChange('complex', $(this).val(), true);
    });

    function handleElementChange(type, elementId, isEdit = false) {
        const prefix = isEdit ? 'edit_' : '';
        if (!elementId) {
            $('#' + prefix + 'from_group, #' + prefix + 'to_group').hide();
            return;
        }
        const url = `{{ route("family-tiles.element-limit", ["familyTile" => $familyTile->id, "elementId" => "__ELEMENTID__", "type" => "__TYPE__"]) }}`
            .replace("__ELEMENTID__", elementId)
            .replace("__TYPE__", type);
        $.get(url)
            .done(function(data) {
                const limit = data.limit;
                $('#' + prefix + 'to').attr('max', limit);
                $('#' + prefix + 'range_group').show();
                updateToMin(isEdit);
            });
    }

    // Update min for to when from changes
    $('#from').change(function() {
        updateToMin(false);
    });

    $('#edit_from').change(function() {
        updateToMin(true);
    });

    function updateToMin(isEdit) {
        const prefix = isEdit ? 'edit_' : '';
        const fromVal = parseInt($('#' + prefix + 'from').val()) || 0;
        $('#' + prefix + 'to').attr('min', fromVal + 1);
    }

    // Create diffusion
    $('#diffusionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("family-tiles.diffusions.store", $familyTile) }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#createDiffusionModal').modal('hide');
                    $('#diffusionForm')[0].reset();
                    $('#diffusions-table').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Errore: ' + xhr.responseJSON.message);
            }
        });
    });

    // Edit diffusion
    $(document).on('click', '.edit-diffusion', function() {
        const id = $(this).data('id');
        // Load diffusion data (you might need an endpoint to get single diffusion)
        // For simplicity, assume we have the data in the row
        const row = $(this).closest('tr');
        const data = $('#diffusions-table').DataTable().row(row).data();
        // Populate modal
        $('#edit_diffusion_id').val(data.id);
        const type = data.element_type === 'Elemento Chimico' ? 'chimical' : 'complex';
        $('#edit_element_type').val(type).trigger('change');
        if (type === 'chimical') {
            $('#edit_chimical_element_id').val(data.chimical_element_id).trigger('change');
        } else {
            $('#edit_complex_chimical_element_id').val(data.complex_chimical_element_id).trigger('change');
        }
        $('#edit_from').val(data.from);
        $('#edit_to').val(data.to);
        $('#editDiffusionModal').modal('show');
    });

    $('#editDiffusionForm').submit(function(e) {
        e.preventDefault();
        const id = $('#edit_diffusion_id').val();
        $.ajax({
            url: '{{ route("family-tiles.diffusions.update", [$familyTile, ":id"]) }}'.replace(':id', id),
            method: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#editDiffusionModal').modal('hide');
                    $('#diffusions-table').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Errore: ' + xhr.responseJSON.message);
            }
        });
    });

    // Delete diffusion
    $(document).on('click', '.delete-diffusion', function() {
        if (!confirm('Sei sicuro di voler eliminare questa diffusione?')) return;
        const id = $(this).data('id');
        $.ajax({
            url: '{{ route("family-tiles.diffusions.destroy", [$familyTile, ":id"]) }}'.replace(':id', id),
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#diffusions-table').DataTable().ajax.reload();
                }
            },
            error: function(xhr) {
                alert('Errore: ' + xhr.responseJSON.message);
            }
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
@endsection
@stop