@extends('adminlte::page')

@section('title', 'Modifica Componente Entity')

@section('content_header')@stop

@section('content')

    @if($entityComponent->isFinished())
        <div class="alert alert-info shadow-sm mb-4">
            <i class="fas fa-lock mr-2"></i> Questo componente è contrassegnato come <strong>Completato</strong>. Tutte le modifiche e l'editor di grafica sono bloccati.
        </div>
    @else
        <div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left: 4px solid #17a2b8 !important;">
            <div>
                <i class="fas fa-info-circle mr-2 text-info"></i> Questo componente è in stato <strong>Creato</strong>. Puoi modificare il nome e disegnare la grafica 32x32.
            </div>
            @if(!$entityComponent->image || !\Storage::disk('entity_components')->exists($entityComponent->image))
                <button type="button" class="btn btn-success btn-sm shadow-sm disabled-state-btn" data-toggle="tooltip" title="Disegna la grafica per poter completare il componente">
                    <i class="fas fa-check-circle"></i> Completa e Blocca
                </button>
            @else
                <form action="{{ route('entity-components.toggle-state', $entityComponent) }}" method="POST" class="m-0 js-confirm-complete">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm shadow-sm">
                        <i class="fas fa-check-circle"></i> Completa e Blocca
                    </button>
                </form>
            @endif
        </div>
    @endif

    <form action="{{ route('entity-components.update', $entityComponent) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs shadow-sm">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab" aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-graphics-link" data-toggle="pill" href="#tab-graphics" role="tab" aria-controls="tab-graphics" aria-selected="false">Grafica</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-genes-link" data-toggle="pill" href="#tab-genes" role="tab" aria-controls="tab-genes" aria-selected="false">Geni</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-rules-link" data-toggle="pill" href="#tab-rules" role="tab" aria-controls="tab-rules" aria-selected="false">Elementi Chimici</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    
                    <!-- TAB DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-link">
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label for="name" class="text-dark font-weight-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $entityComponent->name) }}" 
                                       {{ $entityComponent->isFinished() ? 'disabled' : 'required' }}>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group col-md-6 col-12">
                                <label for="entity_type_component_id" class="text-dark font-weight-bold">Tipologia Componente</label>
                                <select id="entity_type_component_id" name="entity_type_component_id" class="form-control select2" style="width: 100%;" {{ $entityComponent->isFinished() ? 'disabled' : '' }}>
                                    <option value="">Nessuna Tipologia...</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type->id }}" data-icon="{{ $type->symbol }}" {{ old('entity_type_component_id', $entityComponent->entity_type_component_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @if($entityComponent->isFinished())
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="card card-outline card-secondary shadow-sm text-center py-4">
                                        <div class="card-body">
                                            <h5 class="text-muted mb-3 font-weight-bold">Grafica Salvata (Sola Lettura)</h5>
                                            @if($entityComponent->image && \Storage::disk('entity_components')->exists($entityComponent->image))
                                                <img src="{{ asset('storage/entity_components/' . $entityComponent->image) }}?v={{ time() }}" style="width: 128px; height: 128px; image-rendering: pixelated; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px;">
                                            @else
                                                <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width: 128px; height: 128px; border-style: dashed !important;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                                <p class="text-muted mt-3 mb-0">Nessuna grafica disegnata.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            @include('shared.graphics_editor', ['modelType' => 'entity_component', 'model' => $entityComponent])
                        @endif
                    </div>

                    <!-- TAB GENI -->
                    <div class="tab-pane fade" id="tab-genes" role="tabpanel" aria-labelledby="tab-genes-link">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-dark font-weight-bold mb-0">Associazione Geni</h5>
                            @if(!$entityComponent->isFinished())
                                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-add-gene">
                                    <i class="fas fa-plus"></i> Aggiungi Gene
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table id="genes-table" class="table table-bordered table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Gene</th>
                                        <th>Key</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB ELEMENTI CHIMICI -->
                    <div class="tab-pane fade" id="tab-rules" role="tabpanel" aria-labelledby="tab-rules-link">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-dark font-weight-bold mb-0">Associazione Elementi Chimici (Regole)</h5>
                            @if(!$entityComponent->isFinished())
                                <button type="button" class="btn btn-sm btn-success shadow-sm" id="btn-add-rule">
                                    <i class="fas fa-plus"></i> Aggiungi Regola Elemento Chimico
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table id="rules-table" class="table table-bordered table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Regola</th>
                                        <th>Titolo</th>
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
            
            <div class="card-footer border-top pt-3">
                @if(!$entityComponent->isFinished())
                    <button type="submit" class="btn btn-primary shadow-sm mr-2">
                        <i class="fas fa-save"></i> Aggiorna
                    </button>
                @endif
                <a href="{{ route('entity-components.index') }}" class="btn btn-secondary shadow-sm">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </div>
    </form>

    <!-- MODALS FOR GENES CRUD -->
    <div class="modal fade" id="addGeneModal" tabindex="-1" role="dialog" aria-labelledby="addGeneModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="addGeneModalLabel">Aggiungi Associazione Gene</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addGeneForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="select-gene-id" class="font-weight-bold">Gene <span class="text-danger">*</span></label>
                            <select class="form-control" id="select-gene-id" name="gene_id" required>
                                <option value="">Seleziona un Gene...</option>
                            </select>
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

    <!-- MODAL FOR RULES CRUD -->
    <div class="modal fade" id="addRuleModal" tabindex="-1" role="dialog" aria-labelledby="addRuleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="addRuleModalLabel">Aggiungi Associazione Elemento Chimico (Regola)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="addRuleForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="select-rule-id" class="font-weight-bold">Regola Elemento Chimico (Tipo Entity) <span class="text-danger">*</span></label>
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
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $(document).on('click', '.disabled-state-btn', function(e) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
                } else {
                    alert('Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
                }
            });

            $(document).on('submit', '.js-confirm-complete', function(e) {
                if (!confirm("Sei sicuro di voler completare e bloccare questo componente? Questa azione è irreversibile.")) {
                    e.preventDefault();
                }
            });

            // Geni & Elementi Chimici DataTable and Ajax Handlers
            var isComponentFinished = @json($entityComponent->isFinished());
            
            var genesTable = $('#genes-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('entity-components.genes.datatable', $entityComponent) }}",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'gene_name', name: 'gene_name', orderable: false, searchable: false },
                    { data: 'gene_key', name: 'gene_key', orderable: false, searchable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            if (isComponentFinished) {
                                return '<span class="text-muted"><i class="fas fa-lock"></i> Bloccato</span>';
                            }
                            return '<button type="button" class="btn btn-xs btn-danger delete-gene-btn" data-id="' + row.id + '"><i class="fas fa-trash"></i> Elimina</button>';
                        }
                    }
                ]
            });

            var rulesTable = $('#rules-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('entity-components.rules.datatable', $entityComponent) }}",
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'rule_name', name: 'rule_name', orderable: false, searchable: false },
                    { data: 'rule_title', name: 'rule_title', orderable: false, searchable: false },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            if (isComponentFinished) {
                                return '<span class="text-muted"><i class="fas fa-lock"></i> Bloccato</span>';
                            }
                            return '<button type="button" class="btn btn-xs btn-danger delete-rule-btn" data-id="' + row.id + '"><i class="fas fa-trash"></i> Elimina</button>';
                        }
                    }
                ]
            });

            // Add Gene Modal populating and submit
            $('#btn-add-gene').click(function() {
                $.get("{{ route('entity-components.genes.available', $entityComponent) }}", function(data) {
                    var select = $('#select-gene-id');
                    select.empty().append('<option value="">Seleziona un Gene...</option>');
                    $.each(data, function(index, gene) {
                        select.append('<option value="' + gene.id + '">' + gene.name + ' (' + gene.key + ')</option>');
                    });
                    $('#addGeneModal').modal('show');
                });
            });

            $('#addGeneForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('entity-components.genes.store', $entityComponent) }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#addGeneModal').modal('hide');
                            genesTable.ajax.reload();
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Gene associato con successo.');
                            } else {
                                alert('Gene associato con successo.');
                            }
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore durante l\'operazione.';
                        alert('Errore: ' + msg);
                    }
                });
            });

            // Delete Gene
            $(document).on('click', '.delete-gene-btn', function() {
                if (!confirm('Sei sicuro di voler eliminare questa associazione gene?')) return;
                var id = $(this).data('id');
                var url = "{{ route('entity-components.genes.destroy', ':id') }}".replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            genesTable.ajax.reload();
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Associazione gene eliminata.');
                            } else {
                                alert('Associazione gene eliminata.');
                            }
                        }
                    },
                    error: function(xhr) {
                        alert('Errore durante l\'eliminazione.');
                    }
                });
            });

            // Add Rule Modal populating and submit
            $('#btn-add-rule').click(function() {
                $.get("{{ route('entity-components.rules.available', $entityComponent) }}", function(data) {
                    var select = $('#select-rule-id');
                    select.empty().append('<option value="">Seleziona una Regola...</option>');
                    $.each(data, function(index, rule) {
                        select.append('<option value="' + rule.id + '">' + rule.name + ' (' + rule.title + ')</option>');
                    });
                    $('#addRuleModal').modal('show');
                });
            });

            $('#addRuleForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('entity-components.rules.store', $entityComponent) }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            $('#addRuleModal').modal('hide');
                            rulesTable.ajax.reload();
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Regola elemento associata con successo.');
                            } else {
                                alert('Regola elemento associata con successo.');
                            }
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Errore durante l\'operazione.';
                        alert('Errore: ' + msg);
                    }
                });
            });

            // Delete Rule
            $(document).on('click', '.delete-rule-btn', function() {
                if (!confirm('Sei sicuro di voler eliminare questa associazione regola?')) return;
                var id = $(this).data('id');
                var url = "{{ route('entity-components.rules.destroy', ':id') }}".replace(':id', id);
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            rulesTable.ajax.reload();
                            if (typeof toastr !== 'undefined') {
                                toastr.success('Associazione regola eliminata.');
                            } else {
                                alert('Associazione regola eliminata.');
                            }
                        }
                    },
                    error: function(xhr) {
                        alert('Errore durante l\'eliminazione.');
                    }
                });
            });
            // Initialize select2 for component type
            function formatType(state) {
                if (!state.id) {
                    return state.text;
                }
                var icon = $(state.element).data('icon');
                if (!icon) return state.text;
                var $state = $(
                    '<span><i class="' + icon + ' fa-fw mr-2 text-dark"></i>' + state.text + '</span>'
                );
                return $state;
            }

            $('#entity_type_component_id').select2({
                templateResult: formatType,
                templateSelection: formatType,
                placeholder: "Seleziona una Tipologia...",
                allowClear: true
            });
        });
    </script>
@stop

@section('css')
    <style>
        .select2-container .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.25rem !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: normal !important;
            padding-left: 12px !important;
            color: #495057 !important;
            display: flex !important;
            align-items: center !important;
            width: 100% !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
    </style>
@stop
