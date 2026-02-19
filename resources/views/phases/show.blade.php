@extends('adminlte::page')

@section('title', 'Dettagli Fase - ' . $phase->name)

@section('content_header')
<style>
    .link-anchor {
        transition: all 0.2s ease;
        border-radius: 50%;
    }
    .link-anchor:hover {
        transform: scale(1.3);
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.6);
    }
    .link-line {
        transition: stroke 0.2s ease, stroke-width 0.2s ease;
    }
    .link-line:hover {
        stroke: #dc3545;
        stroke-width: 4;
        cursor: pointer;
    }
    #links-canvas {
        pointer-events: none;
    }
    #links-canvas line {
        pointer-events: stroke;
    }
    /* Drag & Drop styles for targets */
    .target-item {
        transition: all 0.2s ease;
    }
    .target-item:active {
        cursor: grabbing;
    }
    .target-item.dragging {
        opacity: 0.5;
        border: 2px dashed #007bff;
        background-color: #e3f2fd;
    }
    .drop-zone {
        transition: all 0.2s ease;
    }
    .drop-zone.drag-over {
        background-color: #d4edda;
        border: 2px dashed #28a745;
    }
    .drop-zone.invalid-drop {
        background-color: #f8d7da;
        border: 2px dashed #dc3545;
    }
</style>
@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Dettagli Fase - {{ $phase->name }}</h5>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Nome: {{ $phase->name }}</h5>
                        <h6>Altezza: {{ $phase->height }}</h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm js-add-column d-flex align-items-center mr-2" style="min-height: 40px;">
                            <i class="fa fa-plus mr-2"></i> Nuova Fascia
                        </button>
                        <a href="{{ route('ages.phases.edit', [$age, $phase]) }}" class="btn btn-secondary btn-sm d-flex align-items-center" style="min-height: 40px;">
                            <i class="fa fa-edit mr-2"></i> Modifica Fase
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <h5 class="mb-3">Fasce</h5>
                <div class="d-flex gap-4 overflow-x-auto" id="columns-container" style="position: relative; min-height: 500px;">
                    <svg id="links-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 10;"></svg>
                    @foreach($phase->phaseColumns as $column)
                        @php
                            $columnIndex = $loop->index;
                        @endphp
                        <div class="column-card card" data-column-id="{{ $column->id }}" style="min-width: 300px;">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6>Fascia {{ $loop->index + 1 }}</h6>
                                <button type="button" class="btn btn-danger btn-sm js-delete-column" data-column-id="{{ $column->id }}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-column gap-2" id="buttons-container-{{ $column->id }}">
                                    @for($i = 0; $i < $phase->height; $i++)
                                        @php
                                            $target = $column->targets->where('slot', $i)->first();
                                        @endphp
                                        @if($target)
                                            <div class="border border-black p-3 target-item" data-target-id="{{ $target->id }}" data-column-id="{{ $column->id }}" data-slot="{{ $i }}" draggable="true" style="min-height: 80px; background-color: #f5f5f5; position: relative; cursor: grab;">
                                                <!-- Ancore di collegamento -->
                                                <div class="d-flex justify-content-between align-items-center" style="position: absolute; top: 50%; left: 0; right: 0; transform: translateY(-50%); pointer-events: none;">
                                                    @if($columnIndex == 0)
                                                        <div></div>
                                                        <div class="link-anchor right-anchor" data-target-id="{{ $target->id }}" data-column-index="{{ $columnIndex }}" data-slot="{{ $i }}" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>
                                                    @elseif($columnIndex == $phase->phaseColumns->count() - 1)
                                                        <div class="link-anchor left-anchor" data-target-id="{{ $target->id }}" data-column-index="{{ $columnIndex }}" data-slot="{{ $i }}" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>
                                                        <div></div>
                                                    @else
                                                        <div class="link-anchor left-anchor" data-target-id="{{ $target->id }}" data-column-index="{{ $columnIndex }}" data-slot="{{ $i }}" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>
                                                        <div class="link-anchor right-anchor" data-target-id="{{ $target->id }}" data-column-index="{{ $columnIndex }}" data-slot="{{ $i }}" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>
                                                    @endif
                                                </div>
                                                <h6 class="font-weight-bold">{{ $target->title }}</h6>
                                                @if($target->description)
                                                    <p class="text-sm">{{ $target->description }}</p>
                                                @endif
                                                <div class="d-flex gap-2 mt-2">
                                                    <button type="button" class="btn btn-info mr-2 btn-xs js-view-target-details" data-column-id="{{ $column->id }}" data-target-id="{{ $target->id }}" data-slot="{{ $i }}">
                                                        <i class="fa fa-info-circle"></i> Dettagli
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-xs js-delete-target" data-column-id="{{ $column->id }}" data-target-id="{{ $target->id }}" data-slot="{{ $i }}">
                                                        <i class="fa fa-trash"></i> Elimina
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <div class="border border-black d-flex align-items-center justify-content-center js-add-target drop-zone" style="width: 100%; height: 80px; cursor: pointer; font-size: 40px" data-column-id="{{ $column->id }}" data-slot="{{ $i }}">
                                                <span>+</span>
                                            </div>
                                        @endif
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('ages.phases.index', $age) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alle Fasi
        </a>
    </div>
</div>

<!-- Modal per creazione obiettivo -->
<div class="modal fade" id="createTargetModal" tabindex="-1" role="dialog" aria-labelledby="createTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTargetModalLabel">Crea Nuovo Obiettivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createTargetForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="targetTitle">Titolo *</label>
                        <input type="text" class="form-control" id="targetTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="targetDescription">Descrizione</label>
                        <textarea class="form-control" id="targetDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Obiettivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per dettagli obiettivo -->
<div class="modal fade" id="viewTargetDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewTargetDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTargetDetailsModalLabel">Dettagli Obiettivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="targetDetailsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cost-tab" data-toggle="tab" href="#cost" role="tab" aria-controls="cost" aria-selected="false">Costo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="reward-tab" data-toggle="tab" href="#reward" role="tab" aria-controls="reward" aria-selected="false">Ricompensa</a>
                    </li>
                </ul>
                <div class="tab-content" id="targetDetailsTabsContent">
                    <!-- Tab Dati Generali -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form id="updateTargetForm">
                            @csrf
                            @method('PUT')
                            <div class="form-group mt-3">
                                <label for="updateTargetTitle">Titolo *</label>
                                <input type="text" class="form-control" id="updateTargetTitle" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="updateTargetDescription">Descrizione</label>
                                <textarea class="form-control" id="updateTargetDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-primary">Aggiorna Obiettivo</button>
                            </div>
                        </form>
                    </div>
                    <!-- Tab Costo -->
                    <div class="tab-pane fade" id="cost" role="tabpanel" aria-labelledby="cost-tab">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Elenco Costi</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addCostButton">
                                <i class="fa fa-plus"></i> Aggiungi Costo
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="targetHasScoresTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Score</th>
                                        <th>Valore</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="targetHasScoresTableBody">
                                    <!-- Dati caricate via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Tab Ricompensa -->
                    <div class="tab-pane fade" id="reward" role="tabpanel" aria-labelledby="reward-tab">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Codice PHP Ricompensa</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="saveRewardButton">
                                <i class="fa fa-save"></i> Salva
                            </button>
                        </div>
                        <div class="form-group">
                            <!-- Monaco Editor Container -->
                            <div id="rewardMonacoEditor" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
                            <textarea class="d-none" id="rewardEditor" name="reward"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per aggiungere/modificare costo -->
<div class="modal fade" id="addEditCostModal" tabindex="-1" role="dialog" aria-labelledby="addEditCostModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditCostModalLabel">Aggiungi Costo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addEditCostForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="scoreSelect">Score *</label>
                        <select class="form-control" id="scoreSelect" name="score_id" required>
                            <!-- Options caricate via JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="costValue">Valore *</label>
                        <input type="number" class="form-control" id="costValue" name="value" required>
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
@stop

@section('js')
    <!-- Monaco Editor -->
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
    <script>
        // Variabile globale per l'editor Monaco
        var monacoEditor = null;
        
        // Inizializza Monaco Editor
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            // Registra il linguaggio PHP se non già presente
            monaco.languages.register({ id: 'php' });
            
            // Valore iniziale dell'editor
            var initialValue = '<' + '?php\n// Scrivi qui il codice PHP per la ricompensa\n';
            
            // Crea l'editor
            monacoEditor = monaco.editor.create(document.getElementById('rewardMonacoEditor'), {
                value: initialValue,
                language: 'php',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
                fontSize: 14,
                lineNumbers: 'on',
                roundedSelection: false,
                scrollBeyondLastLine: false,
                readOnly: false,
                wordWrap: 'on',
                folding: true,
                tabSize: 4,
                insertSpaces: true,
                formatOnPaste: true,
                formatOnType: true
            });
            
            // Sincronizza il contenuto con il textarea nascosto
            monacoEditor.onDidChangeModelContent(function () {
                document.getElementById('rewardEditor').value = monacoEditor.getValue();
            });
        });
    </script>
    <script>
        $(document).ready(function () {
            // Variabili per tracciare la colonna e lo slot selezionato
            var selectedColumnId;
            var selectedSlot;
            
            // Variabili per tracciare il collegamento tra obiettivi
            var selectedFromTargetId;
            var selectedFromColumnIndex;
            var selectedFromSlot;
            var isDragging = false;
            var startX, startY, endX, endY;
            var tempLine = null;
            var tempLineId = 'temp-drag-line';
            
            // Funzione per ottenere le coordinate relative al container
            function getRelativeCoordinates(element) {
                var containerRect = $('#columns-container')[0].getBoundingClientRect();
                var elementRect = element[0].getBoundingClientRect();
                return {
                    x: elementRect.left + elementRect.width / 2 - containerRect.left,
                    y: elementRect.top + elementRect.height / 2 - containerRect.top
                };
            }
            
            // Funzione per disegnare una linea SVG
            function drawLine(x1, y1, x2, y2, isTemporary, fromTargetId, toTargetId) {
                var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', x1);
                line.setAttribute('y1', y1);
                line.setAttribute('x2', x2);
                line.setAttribute('y2', y2);
                
                if (isTemporary) {
                    line.setAttribute('id', tempLineId);
                    line.setAttribute('stroke', '#28a745');
                    line.setAttribute('stroke-width', 3);
                    line.setAttribute('stroke-dasharray', '5,5');
                    line.setAttribute('stroke-linecap', 'round');
                    line.setAttribute('pointer-events', 'none');
                } else {
                    line.setAttribute('stroke', '#007bff');
                    line.setAttribute('stroke-width', 3);
                    line.setAttribute('data-from-target-id', fromTargetId);
                    line.setAttribute('data-to-target-id', toTargetId);
                    line.setAttribute('pointer-events', 'stroke');
                    line.setAttribute('cursor', 'pointer');
                    line.setAttribute('class', 'link-line');
                }
                
                $('#links-canvas').append(line);
                return line;
            }
            
            // Funzione per rimuovere la linea temporanea
            function removeTempLine() {
                $('#' + tempLineId).remove();
                tempLine = null;
            }
            
            // Funzione per evidenziare le ancore valide
            function highlightValidAnchors(fromColumnIndex) {
                $('.link-anchor').each(function() {
                    var anchorColumnIndex = $(this).data('column-index');
                    var anchorTargetId = $(this).data('target-id');
                    var isLeftAnchor = $(this).hasClass('left-anchor');
                    
                    // Reset stile
                    $(this).css({
                        'background-color': '#007bff',
                        'transform': 'scale(1)',
                        'transition': 'all 0.2s ease'
                    });
                    
                    // Evidenzia solo ancore sinistre in una qualsiasi fascia successiva
                    if (isLeftAnchor && anchorColumnIndex > fromColumnIndex && anchorTargetId) {
                        $(this).css({
                            'background-color': '#28a745',
                            'transform': 'scale(1.3)',
                            'box-shadow': '0 0 8px rgba(40, 167, 69, 0.6)'
                        });
                    }
                });
            }
            
            // Funzione per resettare lo stile delle ancore
            function resetAnchorsStyle() {
                $('.link-anchor').css({
                    'background-color': '#007bff',
                    'transform': 'scale(1)',
                    'box-shadow': 'none'
                });
            }
            
            // Funzione per caricare i collegamenti esistenti
            function loadExistingLinks() {
                var phaseLinksUrl = "{{ route('ages.phases.target-links.index', [$age, $phase]) }}";
                $.ajax({
                    url: phaseLinksUrl,
                    type: 'GET',
                    success: function(response) {
                        var links = response.links;
                        links.forEach(function(link) {
                            // Trova le ancore di partenza (right-anchor) e arrivo (left-anchor)
                            var fromAnchor = $('.right-anchor[data-target-id="' + link.from_target_id + '"]');
                            var toAnchor = $('.left-anchor[data-target-id="' + link.to_target_id + '"]');
                            
                            if (fromAnchor.length > 0 && toAnchor.length > 0) {
                                var fromCoords = getRelativeCoordinates(fromAnchor);
                                var toCoords = getRelativeCoordinates(toAnchor);
                                
                                drawLine(fromCoords.x, fromCoords.y, toCoords.x, toCoords.y, false, link.from_target_id, link.to_target_id);
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
            
            // Carica i collegamenti esistenti al caricamento della pagina
            loadExistingLinks();

            // Add column button click
            $(document).on('click', '.js-add-column', function (e) {
                e.preventDefault();
                var url = "{{ route('ages.phases.columns.store', [$age, $phase]) }}";
                var uid = 'column-' + Date.now(); // Genera un UID casuale

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        uid: uid
                    },
                    success: function(response) {
                        // Aggiorna la pagina per visualizzare la nuova colonna
                        location.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            // Delete column button click
            $(document).on('click', '.js-delete-column', function (e) {
                e.preventDefault();
                var columnId = $(this).data('column-id');
                var url = "{{ route('ages.phases.columns.destroy', [$age, $phase, '_column_']) }}";
                url = url.replace('_column_', columnId);

                if (confirm('Sei sicuro di voler eliminare questa fascia?')) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Rimuovi la colonna dalla pagina
                            $('div[data-column-id="' + columnId + '"]').remove();
                            
                            // Riallinea i numeri delle fasce
                            $('#columns-container .column-card').each(function(index, element) {
                                $(element).find('.card-header h6').text('Fascia ' + (index + 1));
                            });
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });

            // Add target button click (clic su container +)
            $(document).on('click', '.js-add-target', function (e) {
                e.preventDefault();
                selectedColumnId = $(this).data('column-id');
                selectedSlot = $(this).data('slot');
                
                // Recupera il numero della fascia
                var columnIndex = $('#columns-container .column-card').index($('div[data-column-id="' + selectedColumnId + '"]'));
                var columnNumber = columnIndex + 1;
                
                // Resetta il form
                $('#createTargetForm')[0].reset();
                
                // Aggiorna il titolo della modal con la fascia e lo slot
                $('#createTargetModalLabel').text('Crea Nuovo Obiettivo - Fascia ' + columnNumber + ', Slot ' + (selectedSlot + 1));
                
                // Mostra la modal
                $('#createTargetModal').modal('show');
            });

            // Create target form submission
            $(document).on('submit', '#createTargetForm', function (e) {
                e.preventDefault();
                
                var url = "{{ route('ages.phases.columns.targets.store', [$age, $phase, '_column_']) }}";
                url = url.replace('_column_', selectedColumnId);
                
                var formData = $(this).serializeArray();
                formData.push({name: 'slot', value: selectedSlot});

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    success: function(response) {
                        // Chiudi la modal
                        $('#createTargetModal').modal('hide');
                        
                        // Recupera il numero della fascia
                        var columnIndex = $('#columns-container .column-card').index($('div[data-column-id="' + selectedColumnId + '"]'));
                        
                        // Crea l'HTML per il nuovo obiettivo
                        var targetHtml = '<div class="border border-black p-3" style="min-height: 80px; background-color: #f5f5f5; position: relative;">' +
                            // Ancore di collegamento
                            '<div class="d-flex justify-content-between align-items-center" style="position: absolute; top: 50%; left: 0; right: 0; transform: translateY(-50%); pointer-events: none;">';
                        
                        if (columnIndex == 0) {
                            targetHtml += '<div></div>' +
                                '<div class="link-anchor right-anchor" data-target-id="' + response.target.id + '" data-column-index="' + columnIndex + '" data-slot="' + selectedSlot + '" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>';
                        } else if (columnIndex == $('#columns-container .column-card').length - 1) {
                            targetHtml += '<div class="link-anchor left-anchor" data-target-id="' + response.target.id + '" data-column-index="' + columnIndex + '" data-slot="' + selectedSlot + '" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>' +
                                '<div></div>';
                        } else {
                            targetHtml += '<div class="link-anchor left-anchor" data-target-id="' + response.target.id + '" data-column-index="' + columnIndex + '" data-slot="' + selectedSlot + '" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>' +
                                '<div class="link-anchor right-anchor" data-target-id="' + response.target.id + '" data-column-index="' + columnIndex + '" data-slot="' + selectedSlot + '" style="width: 12px; height: 12px; background-color: blue; cursor: pointer; pointer-events: auto;"></div>';
                        }
                        
                        targetHtml += '</div>' +
                            '<h6 class="font-weight-bold">' + response.target.title + '</h6>';
                        
                        if (response.target.description) {
                            targetHtml += '<p class="text-sm">' + response.target.description + '</p>';
                        }
                        
                        targetHtml += '<div class="d-flex gap-2 mt-2">' +
                            '<button type="button" class="btn btn-info mr-2 btn-xs js-view-target-details" data-column-id="' + selectedColumnId + '" data-target-id="' + response.target.id + '" data-slot="' + selectedSlot + '">' +
                                '<i class="fa fa-info-circle"></i> Dettagli' +
                            '</button>' +
                            '<button type="button" class="btn btn-danger btn-xs js-delete-target" data-column-id="' + selectedColumnId + '" data-target-id="' + response.target.id + '" data-slot="' + selectedSlot + '">' +
                                '<i class="fa fa-trash"></i> Elimina' +
                            '</button>' +
                        '</div>' +
                        '</div>';
                        
                        // Sostituisce il container + con l'obiettivo
                        $('#buttons-container-' + selectedColumnId + ' .js-add-target[data-slot="' + selectedSlot + '"]').replaceWith(targetHtml);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });

            // View target details button click
            var selectedTargetId;
            var selectedTargetColumnId;
            
            $(document).on('click', '.js-view-target-details', function (e) {
                e.preventDefault();
                selectedTargetId = $(this).data('target-id');
                selectedTargetColumnId = $(this).data('column-id');
                
                // Recupera i dettagli dell'obiettivo
                var showUrl = "{{ route('ages.phases.columns.targets.show', [$age, $phase, '_column_', '_target_']) }}";
                showUrl = showUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                
                $.ajax({
                    url: showUrl,
                    type: 'GET',
                    success: function(response) {
                        // Popola il form di modifica
                        $('#updateTargetTitle').val(response.target.title);
                        $('#updateTargetDescription').val(response.target.description);
                        
                        // Aggiorna il contenuto di Monaco Editor
                        if (monacoEditor) {
                            var rewardValue = response.target.reward || '<' + '?php\n// Scrivi qui il codice PHP per la ricompensa\n';
                            monacoEditor.setValue(rewardValue);
                        }
                        
                        // Recupera i target_has_scores
                        var targetHasScoresUrl = "{{ route('ages.phases.columns.targets.target-has-scores.index', [$age, $phase, '_column_', '_target_']) }}";
                        targetHasScoresUrl = targetHasScoresUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                        
                        $.ajax({
                            url: targetHasScoresUrl,
                            type: 'GET',
                            success: function(response) {
                                // Memorizza i target_has_scores
                                var targetHasScores = response.target_has_scores;
                                
                                // Popola la tabella dei costi
                                populateTargetHasScoresTable(targetHasScores);
                                
                                // Recupera i scores per il select
                                $.ajax({
                                    url: "{{ route('scores.index') }}",
                                    type: 'GET',
                                    success: function(response) {
                                        // Popola il select dei scores
                                        populateScoreSelect(response, targetHasScores);
                                    },
                                    error: function(xhr) {
                                        console.error(xhr.responseText);
                                    }
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                        
                        // Recupera i scores per il select
                        $.ajax({
                            url: "{{ route('scores.index') }}",
                            type: 'GET',
                            success: function(response) {
                                // Popola il select dei scores
                                populateScoreSelect(response, targetHasScores);
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                        
                        // Mostra la modal
                        $('#viewTargetDetailsModal').modal('show');
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });
            
            // Save reward button click
            $(document).on('click', '#saveRewardButton', function (e) {
                e.preventDefault();
                
                var updateUrl = "{{ route('ages.phases.columns.targets.update', [$age, $phase, '_column_', '_target_']) }}";
                updateUrl = updateUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                
                // Ottieni il valore da Monaco Editor
                var reward = monacoEditor ? monacoEditor.getValue() : $('#rewardEditor').val();
                
                var formData = new FormData();
                formData.append('reward', reward);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('_method', 'PUT');
                
                $.ajax({
                    url: updateUrl,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        // Mostra un messaggio di successo
                        alert('Ricompensa salvata con successo');
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('Errore nel salvataggio: ' + xhr.responseText);
                    }
                });
            });
            
            // Update target form submission
            $(document).on('submit', '#updateTargetForm', function (e) {
                e.preventDefault();
                
                var updateUrl = "{{ route('ages.phases.columns.targets.update', [$age, $phase, '_column_', '_target_']) }}";
                updateUrl = updateUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: updateUrl,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    success: function(response) {
                        // Aggiorna l'obiettivo nella pagina
                        $('#buttons-container-' + selectedTargetColumnId + ' .js-view-target-details[data-target-id="' + selectedTargetId + '"]').closest('.border').find('h6').text(response.target.title);
                        var descriptionElement = $('#buttons-container-' + selectedTargetColumnId + ' .js-view-target-details[data-target-id="' + selectedTargetId + '"]').closest('.border').find('p');
                        if (response.target.description) {
                            if (descriptionElement.length === 0) {
                                $('#buttons-container-' + selectedTargetColumnId + ' .js-view-target-details[data-target-id="' + selectedTargetId + '"]').closest('.border').find('h6').after('<p class="text-sm">' + response.target.description + '</p>');
                            } else {
                                descriptionElement.text(response.target.description);
                            }
                        } else {
                            descriptionElement.remove();
                        }
                        
                        // Chiudi la modal
                        $('#viewTargetDetailsModal').modal('hide');
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });
            
            // Add cost button click
            $(document).on('click', '#addCostButton', function (e) {
                e.preventDefault();
                $('#addEditCostModalLabel').text('Aggiungi Costo');
                $('#addEditCostForm')[0].reset();
                $('#addEditCostForm').attr('data-method', 'POST');
                $('#addEditCostModal').modal('show');
            });
            
            // Add/Edit cost form submission
            $(document).on('submit', '#addEditCostForm', function (e) {
                e.preventDefault();
                
                var url;
                var method;
                
                if ($(this).attr('data-method') === 'POST') {
                    url = "{{ route('ages.phases.columns.targets.target-has-scores.store', [$age, $phase, '_column_', '_target_']) }}";
                    url = url.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                    method = 'POST';
                } else {
                    url = "{{ route('ages.phases.columns.targets.target-has-scores.update', [$age, $phase, '_column_', '_target_', '_targetHasScore_']) }}";
                    url = url.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId).replace('_targetHasScore_', $(this).attr('data-target-has-score-id'));
                    method = 'PUT';
                }
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    success: function(response) {
                        // Aggiorna la tabella dei costi
                        var targetHasScoresUrl = "{{ route('ages.phases.columns.targets.target-has-scores.index', [$age, $phase, '_column_', '_target_']) }}";
                        targetHasScoresUrl = targetHasScoresUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                        
                        $.ajax({
                            url: targetHasScoresUrl,
                            type: 'GET',
                            success: function(response) {
                                populateTargetHasScoresTable(response.target_has_scores);
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                        
                        // Chiudi la modal
                        $('#addEditCostModal').modal('hide');
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            });
            
            // Edit cost button click
            $(document).on('click', '.js-edit-cost', function (e) {
                e.preventDefault();
                var targetHasScoreId = $(this).data('target-has-score-id');
                var scoreId = $(this).data('score-id');
                var value = $(this).data('value');
                
                $('#addEditCostModalLabel').text('Modifica Costo');
                $('#scoreSelect').val(scoreId);
                $('#costValue').val(value);
                $('#addEditCostForm').attr('data-method', 'PUT');
                $('#addEditCostForm').attr('data-target-has-score-id', targetHasScoreId);
                $('#addEditCostModal').modal('show');
            });
            
            // Delete cost button click
            $(document).on('click', '.js-delete-cost', function (e) {
                e.preventDefault();
                var targetHasScoreId = $(this).data('target-has-score-id');
                
                if (confirm('Sei sicuro di voler eliminare questo costo?')) {
                    var deleteUrl = "{{ route('ages.phases.columns.targets.target-has-scores.destroy', [$age, $phase, '_column_', '_target_', '_targetHasScore_']) }}";
                    deleteUrl = deleteUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId).replace('_targetHasScore_', targetHasScoreId);
                    
                    $.ajax({
                        url: deleteUrl,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Aggiorna la tabella dei costi
                            var targetHasScoresUrl = "{{ route('ages.phases.columns.targets.target-has-scores.index', [$age, $phase, '_column_', '_target_']) }}";
                            targetHasScoresUrl = targetHasScoresUrl.replace('_column_', selectedTargetColumnId).replace('_target_', selectedTargetId);
                            
                            $.ajax({
                                url: targetHasScoresUrl,
                                type: 'GET',
                                success: function(response) {
                                    populateTargetHasScoresTable(response.target_has_scores);
                                },
                                error: function(xhr) {
                                    console.error(xhr.responseText);
                                }
                            });
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
            
            // Link anchor mousedown - Inizia il drag
            $(document).on('mousedown', '.link-anchor', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                var targetId = $(this).data('target-id');
                var columnIndex = $(this).data('column-index');
                var slot = $(this).data('slot');
                
                // Se l'ancora non ha un target id (obiettivo vuoto), ignoriamo
                if (!targetId) {
                    return;
                }
                
                // Verifica che sia un'ancora destra (per partire verso destra)
                if (!$(this).hasClass('right-anchor')) {
                    return;
                }
                
                // Inizia il drag
                isDragging = true;
                selectedFromTargetId = targetId;
                selectedFromColumnIndex = columnIndex;
                selectedFromSlot = slot;
                
                // Ottiene le coordinate iniziali
                var coords = getRelativeCoordinates($(this));
                startX = coords.x;
                startY = coords.y;
                
                // Evidenzia l'ancora di partenza
                $(this).css({
                    'background-color': '#dc3545',
                    'transform': 'scale(1.5)',
                    'box-shadow': '0 0 10px rgba(220, 53, 69, 0.8)'
                });
                
                // Evidenzia le ancore valide (fasce successive)
                highlightValidAnchors(columnIndex);
                
                // Crea la linea temporanea
                drawLine(startX, startY, startX, startY, true);
                
                // Cambia il cursore
                $('body').css('cursor', 'crosshair');
            });
            
            // Mouse move - Aggiorna la linea durante il drag
            $(document).on('mousemove', function (e) {
                if (!isDragging) return;
                
                // Ottiene le coordinate correnti
                var containerRect = $('#columns-container')[0].getBoundingClientRect();
                endX = e.clientX - containerRect.left;
                endY = e.clientY - containerRect.top;
                
                // Aggiorna la linea temporanea
                $('#' + tempLineId).attr({
                    'x2': endX,
                    'y2': endY
                });
                
                // Verifica se siamo sopra un'ancora valida
                var hoveredAnchor = null;
                $('.link-anchor').each(function() {
                    if (!$(this).data('target-id')) return;
                    if (!$(this).hasClass('left-anchor')) return;
                    if ($(this).data('column-index') <= selectedFromColumnIndex) return;
                    
                    var anchorCoords = getRelativeCoordinates($(this));
                    var distance = Math.sqrt(Math.pow(endX - anchorCoords.x, 2) + Math.pow(endY - anchorCoords.y, 2));
                    
                    if (distance < 20) {
                        hoveredAnchor = $(this);
                    }
                });
                
                // Aggiorna lo stile dell'ancora sotto il mouse
                $('.link-anchor').each(function() {
                    if ($(this).hasClass('left-anchor') && $(this).data('column-index') > selectedFromColumnIndex) {
                        if ($(this).is(hoveredAnchor)) {
                            $(this).css({
                                'background-color': '#ffc107',
                                'transform': 'scale(1.5)',
                                'box-shadow': '0 0 12px rgba(255, 193, 7, 0.8)'
                            });
                        } else {
                            $(this).css({
                                'background-color': '#28a745',
                                'transform': 'scale(1.3)',
                                'box-shadow': '0 0 8px rgba(40, 167, 69, 0.6)'
                            });
                        }
                    }
                });
            });
            
            // Mouse up - Termina il drag
            $(document).on('mouseup', function (e) {
                if (!isDragging) return;
                
                // Termina il drag
                isDragging = false;
                
                // Reset del cursore
                $('body').css('cursor', 'default');
                
                // Ottiene le coordinate finali
                var containerRect = $('#columns-container')[0].getBoundingClientRect();
                endX = e.clientX - containerRect.left;
                endY = e.clientY - containerRect.top;
                
                // Trova l'ancora di arrivo (deve essere left-anchor in una fascia successiva)
                var targetAnchor = null;
                $('.left-anchor').each(function() {
                    if (!$(this).data('target-id')) return;
                    
                    var anchorCoords = getRelativeCoordinates($(this));
                    var distance = Math.sqrt(Math.pow(endX - anchorCoords.x, 2) + Math.pow(endY - anchorCoords.y, 2));
                    
                    if (distance < 20) {
                        var toColumnIndex = $(this).data('column-index');
                        // Verifica che sia in una fascia successiva
                        if (toColumnIndex > selectedFromColumnIndex) {
                            targetAnchor = $(this);
                        }
                    }
                });
                
                // Se è stata trovata un'ancora di arrivo valida
                if (targetAnchor && targetAnchor.data('target-id') !== selectedFromTargetId) {
                    var toTargetId = targetAnchor.data('target-id');
                    
                    // Verifica se esiste già un collegamento tra questi target
                    var existingLink = $('#links-canvas line[data-from-target-id="' + selectedFromTargetId + '"][data-to-target-id="' + toTargetId + '"]');
                    if (existingLink.length > 0) {
                        // Collegamento già esistente, mostra messaggio
                        alert('Questo collegamento esiste già.');
                        removeTempLine();
                        resetAnchorsStyle();
                        selectedFromTargetId = null;
                        selectedFromColumnIndex = null;
                        selectedFromSlot = null;
                        return;
                    }
                    
                    // Crea il collegamento sul database
                    var url = "{{ route('ages.phases.columns.targets.target-links.store', [$age, $phase, '_column_', '_target_']) }}";
                    var fromTarget = $('.right-anchor[data-target-id="' + selectedFromTargetId + '"]');
                    var fromColumnId = fromTarget.closest('.column-card').data('column-id');
                    url = url.replace('_column_', fromColumnId).replace('_target_', selectedFromTargetId);
                    
                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: {
                            from_target_id: selectedFromTargetId,
                            to_target_id: toTargetId
                        },
                        success: function(response) {
                            // Rimuovi la linea temporanea
                            removeTempLine();
                            
                            // Crea la linea permanente
                            var targetCoords = getRelativeCoordinates(targetAnchor);
                            drawLine(startX, startY, targetCoords.x, targetCoords.y, false, selectedFromTargetId, toTargetId);
                            
                            // Reset della selezione e stile
                            resetAnchorsStyle();
                            selectedFromTargetId = null;
                            selectedFromColumnIndex = null;
                            selectedFromSlot = null;
                            
                            // Mostra messaggio di successo
                            console.log('Collegamento creato con successo');
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                            removeTempLine();
                            resetAnchorsStyle();
                            selectedFromTargetId = null;
                            selectedFromColumnIndex = null;
                            selectedFromSlot = null;
                            
                            // Mostra errore
                            var errorMsg = 'Errore nella creazione del collegamento';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }
                            alert(errorMsg);
                        }
                    });
                } else {
                    // Nessuna ancora valida trovata, annulla
                    removeTempLine();
                    resetAnchorsStyle();
                    selectedFromTargetId = null;
                    selectedFromColumnIndex = null;
                    selectedFromSlot = null;
                }
            });
            
            // Funzione per popolare la tabella dei costi
            function populateTargetHasScoresTable(targetHasScores) {
                var tbody = $('#targetHasScoresTableBody');
                tbody.empty();
                
                targetHasScores.forEach(function(targetHasScore) {
                    var tr = $('<tr>');
                    tr.append('<td>' + targetHasScore.id + '</td>');
                    tr.append('<td>' + targetHasScore.score.name + '</td>');
                    tr.append('<td>' + targetHasScore.value + '</td>');
                    tr.append('<td>' +
                        '<button type="button" class="btn btn-info btn-xs js-edit-cost" data-target-has-score-id="' + targetHasScore.id + '" data-score-id="' + targetHasScore.score_id + '" data-value="' + targetHasScore.value + '">' +
                            '<i class="fa fa-edit"></i> Modifica' +
                        '</button>' +
                        '<button type="button" class="btn btn-danger btn-xs ml-1 js-delete-cost" data-target-has-score-id="' + targetHasScore.id + '">' +
                            '<i class="fa fa-trash"></i> Elimina' +
                        '</button>' +
                    '</td>');
                    tbody.append(tr);
                });
            }
            
            // Funzione per popolare il select dei scores
            function populateScoreSelect(scores, existingTargetHasScores) {
                var select = $('#scoreSelect');
                select.empty();
                
                // Crea un array con gli score_id già presenti
                var existingScoreIds = [];
                existingTargetHasScores.forEach(function(targetHasScore) {
                    existingScoreIds.push(targetHasScore.score_id);
                });
                
                // Popola il select con i scores non già presenti
                scores.forEach(function(score) {
                    if (!existingScoreIds.includes(score.id)) {
                        var option = $('<option>');
                        option.attr('value', score.id);
                        option.text(score.name);
                        select.append(option);
                    }
                });
                
                // Se non ci sono scores disponibili, disabilita il select
                if (select.children().length === 0) {
                    select.attr('disabled', 'disabled');
                    select.append('<option value="">Nessun score disponibile</option>');
                } else {
                    select.removeAttr('disabled');
                }
            }
            
            // Delete target button click
            $(document).on('click', '.js-delete-target', function (e) {
                e.preventDefault();
                var columnId = $(this).data('column-id');
                var targetId = $(this).data('target-id');
                var slot = $(this).data('slot');
                
                var url = "{{ route('ages.phases.columns.targets.destroy', [$age, $phase, '_column_', '_target_']) }}";
                url = url.replace('_column_', columnId).replace('_target_', targetId);

                if (confirm('Sei sicuro di voler eliminare questo obiettivo? Anche i collegamenti associati verranno rimossi.')) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // Rimuove i collegamenti relativi all'obiettivo dal canvas
                            $('#links-canvas line[data-from-target-id="' + targetId + '"], #links-canvas line[data-to-target-id="' + targetId + '"]').remove();
                            
                            // Rimuove l'obiettivo dalla pagina
                            var targetElement = $('#buttons-container-' + columnId + ' .js-view-target-details[data-target-id="' + targetId + '"]').closest('.border');
                            var columnIndex = $('#columns-container .column-card').index($('div[data-column-id="' + columnId + '"]'));
                            
                            var addTargetHtml = '<div class="border border-black d-flex align-items-center justify-content-center js-add-target" style="width: 100%; height: 80px; cursor: pointer; font-size: 40px" data-column-id="' + columnId + '" data-slot="' + slot + '"><span>+</span></div>';
                            targetElement.replaceWith(addTargetHtml);
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
            
            // Click su una linea per eliminarla
            $(document).on('click', '#links-canvas line', function (e) {
                e.stopPropagation();
                
                var fromTargetId = $(this).data('from-target-id');
                var toTargetId = $(this).data('to-target-id');
                
                if (!fromTargetId || !toTargetId) return;
                
                if (confirm('Vuoi eliminare questo collegamento?')) {
                    // Trova il link da eliminare
                    var linkElement = $(this);
                    
                    // Prima elimina dal database
                    // Ottieni il target di partenza e la sua colonna
                    var fromAnchor = $('.right-anchor[data-target-id="' + fromTargetId + '"]');
                    var fromColumnId = fromAnchor.closest('.column-card').data('column-id');
                    
                    // Cerca il link nel database
                    var findLinkUrl = "{{ route('ages.phases.columns.targets.target-links.index', [$age, $phase, '_column_', '_target_']) }}";
                    findLinkUrl = findLinkUrl.replace('_column_', fromColumnId).replace('_target_', fromTargetId);
                    
                    $.ajax({
                        url: findLinkUrl,
                        type: 'GET',
                        success: function(response) {
                            var links = response.links;
                            var linkToDelete = links.find(function(link) {
                                return link.from_target_id == fromTargetId && link.to_target_id == toTargetId;
                            });
                            
                            if (linkToDelete) {
                                // Elimina il link
                                var deleteUrl = "{{ route('ages.phases.columns.targets.target-links.destroy', [$age, $phase, '_column_', '_target_', '_link_']) }}";
                                deleteUrl = deleteUrl.replace('_column_', fromColumnId).replace('_target_', fromTargetId).replace('_link_', linkToDelete.id);
                                
                                $.ajax({
                                    url: deleteUrl,
                                    type: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    success: function(response) {
                                        // Rimuovi la linea dal canvas
                                        linkElement.remove();
                                        console.log('Collegamento eliminato con successo');
                                    },
                                    error: function(xhr) {
                                        console.error(xhr.responseText);
                                        alert('Errore durante l\'eliminazione del collegamento');
                                    }
                                });
                            }
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                        }
                    });
                }
            });
            
            // Rende le linee cliccabili
            $(document).ready(function() {
                $('#links-canvas line').css('pointer-events', 'auto').css('cursor', 'pointer');
            });
            
            // Aggiorna le linee quando la finestra viene ridimensionata
            $(window).on('resize', function() {
                // Rimuovi tutte le linee esistenti
                $('#links-canvas line').remove();
                // Ricarica i collegamenti
                loadExistingLinks();
            });
            
            // ==========================================
            // DRAG & DROP PER CAMBIARE SLOT DEI TARGET
            // ==========================================
            
            var draggedTarget = null;
            var draggedTargetId = null;
            var draggedFromColumnId = null;
            var draggedFromSlot = null;
            
            // Drag start - Inizia il trascinamento
            $(document).on('dragstart', '.target-item', function(e) {
                draggedTarget = $(this);
                draggedTargetId = $(this).data('target-id');
                draggedFromColumnId = $(this).data('column-id');
                draggedFromSlot = $(this).data('slot');
                
                $(this).addClass('dragging');
                
                // Imposta i dati del drag
                e.originalEvent.dataTransfer.setData('text/plain', draggedTargetId);
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                
                // Nascondi le linee durante il drag
                $('#links-canvas').css('opacity', '0.3');
            });
            
            // Drag end - Termina il trascinamento
            $(document).on('dragend', '.target-item', function(e) {
                $(this).removeClass('dragging');
                $('.drop-zone').removeClass('drag-over invalid-drop');
                
                // Ripristina le linee
                $('#links-canvas').css('opacity', '1');
                
                draggedTarget = null;
                draggedTargetId = null;
                draggedFromColumnId = null;
                draggedFromSlot = null;
            });
            
            // Drag over - Quando si trascina sopra una drop zone
            $(document).on('dragover', '.drop-zone', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                
                var targetColumnId = $(this).data('column-id');
                
                // Evidenzia solo se è nella stessa colonna
                if (targetColumnId == draggedFromColumnId) {
                    $(this).addClass('drag-over');
                    $(this).removeClass('invalid-drop');
                } else {
                    $(this).addClass('invalid-drop');
                }
            });
            
            // Drag leave - Quando si esce dalla drop zone
            $(document).on('dragleave', '.drop-zone', function(e) {
                $(this).removeClass('drag-over invalid-drop');
            });
            
            // Drop - Rilascio del target
            $(document).on('drop', '.drop-zone', function(e) {
                e.preventDefault();
                
                var targetColumnId = $(this).data('column-id');
                var targetSlot = $(this).data('slot');
                
                $(this).removeClass('drag-over invalid-drop');
                
                // Verifica che sia nella stessa colonna
                if (targetColumnId != draggedFromColumnId) {
                    alert('Non puoi spostare obiettivi tra colonne diverse.');
                    return;
                }
                
                // Aggiorna lo slot nel database
                var updateUrl = "{{ route('ages.phases.columns.targets.update', [$age, $phase, '_column_', '_target_']) }}";
                updateUrl = updateUrl.replace('_column_', draggedFromColumnId).replace('_target_', draggedTargetId);
                
                $.ajax({
                    url: updateUrl,
                    type: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        slot: targetSlot
                    },
                    success: function(response) {
                        // Ricarica la pagina per aggiornare le posizioni
                        location.reload();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert('Errore durante lo spostamento dell\'obiettivo.');
                    }
                });
            });
            
            // Previeni il drop sui target esistenti (non permettere sovrascrittura)
            $(document).on('dragover', '.target-item', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'none';
            });
        });
    </script>
@stop
