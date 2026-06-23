@extends('adminlte::page')

@section('title', 'Modifica Componente Element')

@section('plugins.Select2', true)

@section('content_header')@stop

@section('content')

    @if($elementComponent->isCompleted())
        <div class="alert alert-success shadow-sm mb-4" style="border-left:4px solid #28a745 !important;background-color:#f3fdf6;color:#155724;">
            <i class="fas fa-check-double mr-2 text-success"></i> Componente in stato <strong>Completato</strong>. Tutto bloccato definitivamente.
        </div>
    @elseif($elementComponent->isFinishDraw())
        <div class="alert alert-info border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #17a2b8 !important;background-color:#f3fafd;color:#117a8b;">
            <div><i class="fas fa-lock mr-2 text-info"></i> Componente con <strong>Disegno Terminato</strong>. Modifica e grafica bloccati. Configura le ancore.</div>
            <form action="{{ route('element-components.toggle-state', $elementComponent) }}" method="POST" class="m-0 js-confirm-complete">
                @csrf
                <input type="hidden" name="state" value="{{ \App\Models\ElementComponent::STATE_COMPLETED }}">
                <button type="submit" class="btn btn-primary btn-sm shadow-sm" onclick="return confirm('Bloccare definitivamente le ancore?');">
                    <i class="fas fa-check-double"></i> Termina Ancore e Blocca
                </button>
            </form>
        </div>
    @else
        <div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #ffc107 !important;">
            <div><i class="fas fa-info-circle mr-2 text-warning"></i> Stato <strong>Creato</strong>. Puoi modificare e disegnare la grafica 32x32.</div>
            @if(!$elementComponent->image || !\Storage::disk('element_components')->exists($elementComponent->image))
                <button type="button" class="btn btn-success btn-sm shadow-sm disabled-state-btn">
                    <i class="fas fa-check-circle"></i> Termina Disegno e Blocca
                </button>
            @else
                <form action="{{ route('element-components.toggle-state', $elementComponent) }}" method="POST" class="m-0 js-confirm-complete">
                    @csrf
                    <input type="hidden" name="state" value="{{ \App\Models\ElementComponent::STATE_FINISH_DRAW }}">
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" onclick="return confirm('Terminare il disegno e bloccarlo?');">
                        <i class="fas fa-check-circle"></i> Termina Disegno e Blocca
                    </button>
                </form>
            @endif
        </div>
    @endif

    <form action="{{ route('element-components.update', $elementComponent) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card card-primary card-outline card-tabs shadow-sm">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="pill" href="#tab-general" role="tab">Dati Generali</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-graphics" role="tab">Grafica</a></li>
                    @if($elementComponent->isInteractive())
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-genes" role="tab">Geni</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-rules" role="tab">Elementi Chimici</a></li>
                    @endif
                    @if($elementComponent->isConsumable())
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-consumption" role="tab">Effetti Consumo</a></li>
                    @endif
                    @if($elementComponent->isInteractive() && ($elementComponent->isFinishDraw() || $elementComponent->isCompleted()))
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-brain" role="tab">Cervello</a></li>
                    @endif
                    @if($elementComponent->isFinishDraw() || $elementComponent->isCompleted())
                        <li class="nav-item"><a class="nav-link" data-toggle="pill" href="#tab-ancore" role="tab">Ancore</a></li>
                    @endif
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">

                    <!-- DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label for="name" class="text-dark font-weight-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                    value="{{ old('name', $elementComponent->name) }}" {{ $elementComponent->isFinishDraw() ? 'disabled' : 'required' }}>
                                @error('name')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="form-group col-md-6 col-12">
                                <label for="characteristic" class="text-dark font-weight-bold">Caratteristica <span class="text-danger">*</span></label>
                                <select id="characteristic" name="characteristic" class="form-control @error('characteristic') is-invalid @enderror"
                                    {{ $elementComponent->isFinishDraw() ? 'disabled' : 'required' }}>
                                    @foreach(\App\Models\ElementComponent::CHARACTERISTIC_TYPES as $value => $label)
                                        <option value="{{ $value }}" {{ old('characteristic', $elementComponent->characteristic) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('characteristic')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                            </div>

                            <div class="form-group col-md-6 col-12">
                                <label for="element_type_component_id" class="text-dark font-weight-bold">Tipologia Componente</label>
                                <select id="element_type_component_id" name="element_type_component_id" class="form-control select2" style="width:100%;" {{ $elementComponent->isFinishDraw() ? 'disabled' : '' }}>
                                    <option value="">Nessuna Tipologia...</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" data-icon="{{ $type->symbol }}" {{ old('element_type_component_id', $elementComponent->element_type_component_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- GRAFICA -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel">
                        @if($elementComponent->isFinishDraw())
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="card card-outline card-secondary shadow-sm text-center py-4">
                                        <div class="card-body">
                                            <h5 class="text-muted mb-3 font-weight-bold">Grafica Salvata (Sola Lettura)</h5>
                                            @if($elementComponent->image && \Storage::disk('element_components')->exists($elementComponent->image))
                                                <img src="{{ asset('storage/element_components/' . $elementComponent->image) }}?v={{ time() }}" style="width:128px;height:128px;image-rendering:pixelated;border:4px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.1);border-radius:8px;">
                                            @else
                                                <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width:128px;height:128px;border-style:dashed !important;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            @include('shared.graphics_editor', ['modelType' => 'element_component', 'model' => $elementComponent])
                        @endif
                    </div>

                    <!-- GENI -->
                    @if($elementComponent->isInteractive())
                    <div class="tab-pane fade" id="tab-genes" role="tabpanel">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-dark font-weight-bold mb-0">Associazione Geni</h5>
                            @if(!$elementComponent->isFinishDraw())
                                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-add-gene"><i class="fas fa-plus"></i> Aggiungi Gene</button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table id="genes-table" class="table table-bordered table-hover w-100">
                                <thead><tr><th>ID</th><th>Nome Gene</th><th>Key</th><th>Valore</th><th>Azioni</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ELEMENTI CHIMICI -->
                    <div class="tab-pane fade" id="tab-rules" role="tabpanel">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-dark font-weight-bold mb-0">Associazione Elementi Chimici (Regole)</h5>
                            @if(!$elementComponent->isFinishDraw())
                                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-add-rule"><i class="fas fa-plus"></i> Aggiungi Regola</button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table id="rules-table" class="table table-bordered table-hover w-100">
                                <thead><tr><th>ID</th><th>Nome Regola</th><th>Titolo</th><th>Azioni</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($elementComponent->isConsumable())
                    <!-- EFFETTI CONSUMO -->
                    <div class="tab-pane fade" id="tab-consumption" role="tabpanel">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-dark font-weight-bold mb-0">Effetti Consumo (Geni modificati)</h5>
                            @if(!$elementComponent->isFinishDraw())
                                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-add-consumption"><i class="fas fa-plus"></i> Aggiungi Effetto</button>
                            @endif
                        </div>
                        <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Definisci quali geni vengono modificati quando questo componente viene consumato.</div>
                        <div class="table-responsive">
                            <table id="consumption-table" class="table table-bordered table-hover w-100">
                                <thead><tr><th>ID</th><th>Nome Gene</th><th>Key</th><th>Effetto</th><th>Azioni</th></tr></thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($elementComponent->isInteractive() && ($elementComponent->isFinishDraw() || $elementComponent->isCompleted()))
                    <!-- CERVELLO -->
                    <div class="tab-pane fade" id="tab-brain" role="tabpanel">
                        @include('element_components.tabs.brain')
                    </div>
                    @endif
                    @if($elementComponent->isFinishDraw() || $elementComponent->isCompleted())
                    <!-- ANCORE -->
                    <div class="tab-pane fade" id="tab-ancore" role="tabpanel">
                        @include('shared.anchors_editor', ['modelType' => 'element_component', 'model' => $elementComponent, 'isLocked' => $elementComponent->isCompleted(), 'anchorRoute' => '/element-anchors'])
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer border-top pt-3">
                @if(!$elementComponent->isFinishDraw())
                    <button type="submit" class="btn btn-primary shadow-sm mr-2"><i class="fas fa-save"></i> Aggiorna</button>
                @endif
                <a href="{{ route('element-components.index') }}" class="btn btn-secondary shadow-sm"><i class="fas fa-times"></i> Annulla</a>
            </div>
        </div>
    </form>

    <!-- MODAL GENE -->
    <div class="modal fade" id="addGeneModal" tabindex="-1" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Aggiungi Associazione Gene</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="addGeneForm">@csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Gene <span class="text-danger">*</span></label>
                        <select class="form-control" id="select-gene-id" name="gene_id" required>
                            <option value="">Seleziona un Gene...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Valore</label>
                        <input type="number" class="form-control" id="gene-value" name="value" placeholder="Inserisci un valore intero">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </form>
        </div></div>
    </div>

    <!-- MODAL RULE -->
    <div class="modal fade" id="addRuleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Aggiungi Regola Elemento Chimico</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="addRuleForm">@csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Regola <span class="text-danger">*</span></label>
                        <select class="form-control" id="select-rule-id" name="rule_chimical_element_id" required>
                            <option value="">Seleziona una Regola...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </form>
        </div></div>
    </div>

    <!-- MODAL CONSUMPTION -->
    <div class="modal fade" id="addConsumptionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">Aggiungi Effetto Consumo</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="addConsumptionForm">@csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Gene <span class="text-danger">*</span></label>
                        <select class="form-control" id="select-consumption-gene-id" name="gene_id" required>
                            <option value="">Seleziona un Gene...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Effetto (valore numerico) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="consumption-effect-value" name="effect" required placeholder="Es. 10 o -5">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Salva</button>
                </div>
            </form>
        </div></div>
    </div>
@stop

@section('css')
<style>
    .select2-container .select2-selection--single { height:38px !important; border:1px solid #ced4da !important; border-radius:0.25rem !important; display:flex !important; align-items:center !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height:normal !important; padding-left:12px !important; color:#495057 !important; display:flex !important; align-items:center !important; width:100% !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height:36px !important; display:flex !important; align-items:center !important; justify-content:center !important; }
</style>
@stop

@section('js')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();

        $(document).on('click', '.disabled-state-btn', function (e) {
            e.preventDefault();
            typeof toastr !== 'undefined' ? toastr.warning('Genera prima la grafica del componente.') : alert('Genera prima la grafica del componente.');
        });
        $(document).on('submit', '.js-confirm-complete', function (e) {
            if (!confirm("Terminare il disegno e bloccare? Irreversibile.")) e.preventDefault();
        });

        var isFinishDraw = @json($elementComponent->isFinishDraw());

        var genesTable = $('#genes-table').DataTable({
            processing: true, serverSide: true,
            ajax: { url: "{{ route('element-components.genes.datatable', $elementComponent) }}", type: 'POST', data: { _token: '{{ csrf_token() }}' } },
            columns: [
                { data: 'id' }, { data: 'gene_name', orderable: false, searchable: false },
                { data: 'gene_key', orderable: false, searchable: false }, { data: 'value', orderable: false, searchable: false },
                { data: null, orderable: false, searchable: false, render: function (d, t, row) {
                    return isFinishDraw ? '<span class="text-muted"><i class="fas fa-lock"></i> Bloccato</span>'
                        : '<button type="button" class="btn btn-xs btn-danger delete-gene-btn" data-id="' + row.id + '"><i class="fas fa-trash"></i> Elimina</button>';
                }}
            ]
        });

        var rulesTable = $('#rules-table').DataTable({
            processing: true, serverSide: true,
            ajax: { url: "{{ route('element-components.rules.datatable', $elementComponent) }}", type: 'POST', data: { _token: '{{ csrf_token() }}' } },
            columns: [
                { data: 'id' }, { data: 'rule_name', orderable: false, searchable: false },
                { data: 'rule_title', orderable: false, searchable: false },
                { data: null, orderable: false, searchable: false, render: function (d, t, row) {
                    return isFinishDraw ? '<span class="text-muted"><i class="fas fa-lock"></i> Bloccato</span>'
                        : '<button type="button" class="btn btn-xs btn-danger delete-rule-btn" data-id="' + row.id + '"><i class="fas fa-trash"></i> Elimina</button>';
                }}
            ]
        });

        $('#btn-add-gene').click(function () {
            $.get("{{ route('element-components.genes.available', $elementComponent) }}", function (data) {
                var s = $('#select-gene-id').empty().append('<option value="">Seleziona un Gene...</option>');
                $.each(data, function (i, g) { s.append('<option value="' + g.id + '">' + g.name + ' (' + g.key + ')</option>'); });
                $('#addGeneModal').modal('show');
            });
        });

        $('#addGeneForm').submit(function (e) {
            e.preventDefault();
            $.ajax({ url: "{{ route('element-components.genes.store', $elementComponent) }}", type: 'POST', data: $(this).serialize(),
                success: function (r) { if (r.success) { $('#addGeneModal').modal('hide'); genesTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Gene associato.'); } },
                error: function (xhr) { alert('Errore: ' + (xhr.responseJSON?.message || 'sconosciuto')); }
            });
        });

        $(document).on('click', '.delete-gene-btn', function () {
            if (!confirm('Eliminare associazione gene?')) return;
            var url = "{{ route('element-components.genes.destroy', ':id') }}".replace(':id', $(this).data('id'));
            $.ajax({ url: url, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                success: function (r) { if (r.success) { genesTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Eliminato.'); } }
            });
        });

        $('#btn-add-rule').click(function () {
            $.get("{{ route('element-components.rules.available', $elementComponent) }}", function (data) {
                var s = $('#select-rule-id').empty().append('<option value="">Seleziona una Regola...</option>');
                $.each(data, function (i, r) { s.append('<option value="' + r.id + '">' + r.name + ' (' + r.title + ')</option>'); });
                $('#addRuleModal').modal('show');
            });
        });

        $('#addRuleForm').submit(function (e) {
            e.preventDefault();
            $.ajax({ url: "{{ route('element-components.rules.store', $elementComponent) }}", type: 'POST', data: $(this).serialize(),
                success: function (r) { if (r.success) { $('#addRuleModal').modal('hide'); rulesTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Regola associata.'); } },
                error: function (xhr) { alert('Errore: ' + (xhr.responseJSON?.message || 'sconosciuto')); }
            });
        });

        $(document).on('click', '.delete-rule-btn', function () {
            if (!confirm('Eliminare associazione regola?')) return;
            var url = "{{ route('element-components.rules.destroy', ':id') }}".replace(':id', $(this).data('id'));
            $.ajax({ url: url, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                success: function (r) { if (r.success) { rulesTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Eliminato.'); } }
            });
        });

        function formatType(state) {
            if (!state.id) return state.text;
            var icon = $(state.element).data('icon');
            if (!icon) return state.text;
            return $('<span><i class="' + icon + ' fa-fw mr-2 text-dark"></i>' + state.text + '</span>');
        }
        $('#element_type_component_id').select2({ templateResult: formatType, templateSelection: formatType, placeholder: "Seleziona una Tipologia...", allowClear: true });

        // ── CONSUMPTION EFFECTS ───────────────────────────────────────────────
        @if($elementComponent->isConsumable())
        var consumptionTable = $('#consumption-table').DataTable({
            processing: true, serverSide: true,
            ajax: { url: "{{ route('element-components.consumption.datatable', $elementComponent) }}", type: 'POST', data: { _token: '{{ csrf_token() }}' } },
            columns: [
                { data: 'id' }, { data: 'gene_name', orderable: false, searchable: false },
                { data: 'gene_key', orderable: false, searchable: false }, { data: 'effect', orderable: false, searchable: false },
                { data: null, orderable: false, searchable: false, render: function (d, t, row) {
                    return isFinishDraw ? '<span class="text-muted"><i class="fas fa-lock"></i> Bloccato</span>'
                        : '<button type="button" class="btn btn-xs btn-danger delete-consumption-btn" data-id="' + row.id + '"><i class="fas fa-trash"></i> Elimina</button>';
                }}
            ]
        });

        $('#btn-add-consumption').click(function () {
            $.get("{{ route('element-components.consumption.available', $elementComponent) }}", function (data) {
                var s = $('#select-consumption-gene-id').empty().append('<option value="">Seleziona un Gene...</option>');
                $.each(data, function (i, g) { s.append('<option value="' + g.id + '">' + g.name + ' (' + g.key + ')</option>'); });
                $('#consumption-effect-value').val('');
                $('#addConsumptionModal').modal('show');
            });
        });

        $('#addConsumptionForm').submit(function (e) {
            e.preventDefault();
            $.ajax({ url: "{{ route('element-components.consumption.store', $elementComponent) }}", type: 'POST', data: $(this).serialize(),
                success: function (r) { if (r.success) { $('#addConsumptionModal').modal('hide'); consumptionTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Effetto aggiunto.'); } },
                error: function (xhr) { alert('Errore: ' + (xhr.responseJSON?.message || 'sconosciuto')); }
            });
        });

        $(document).on('click', '.delete-consumption-btn', function () {
            if (!confirm('Eliminare effetto consumo?')) return;
            var url = "{{ route('element-components.consumption.destroy', ':id') }}".replace(':id', $(this).data('id'));
            $.ajax({ url: url, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                success: function (r) { if (r.success) { consumptionTable.ajax.reload(); typeof toastr !== 'undefined' && toastr.success('Eliminato.'); } }
            });
        });
        @endif
    });
</script>
@stop
