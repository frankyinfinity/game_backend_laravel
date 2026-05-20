@php
    $storageDir = \Illuminate\Support\Str::plural($modelType);
    $imagePath = 'storage/' . $storageDir . '/' . $model->image;
    $imageUrl = $model->image && \Storage::disk($storageDir)->exists($model->image) ? asset($imagePath) : null;
    $locked = $isLocked ?? false;
@endphp

<div id="anchors-editor-component-{{ $modelType }}">
    @if($locked)
    <div class="alert alert-warning shadow-sm mb-3" style="border-left: 4px solid #ffc107 !important;">
        <i class="fas fa-lock mr-2 text-warning"></i> Le ancore sono in <strong>sola visualizzazione</strong> poiché la configurazione è stata completata e bloccata.
    </div>
    @endif
    <div class="row">
        <!-- Canvas Editor Area -->
        <div class="col-lg-7 col-12">
            <div class="card card-outline card-secondary shadow-sm mb-3">
                <div class="card-header">
                    <span class="text-dark font-weight-bold">
                        <i class="fas fa-anchor mr-1"></i> Editor Ancore
                    </span>
                    <span class="text-muted small float-right mt-1">Clicca sull'immagine per aggiungere un'ancora</span>
                </div>
                <div class="card-body p-2 d-flex justify-content-center bg-light">
                    @if($imageUrl)
                    <div style="position:relative; display:inline-block; border: 2px solid #dee2e6; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background-color: #f4f6f9;">
                        <!-- Immagine scalata -->
                        <img id="anchors-image-{{ $modelType }}" 
                             src="{{ $imageUrl }}?v={{ time() }}" 
                             alt="Entity" 
                             style="width:512px; height:512px; image-rendering:pixelated; display:block;">
                        <!-- Canvas trasparente in overlay -->
                        <canvas id="anchors-canvas-{{ $modelType }}" 
                                width="512" height="512" 
                                style="position:absolute; top:0; left:0; cursor:{{ $locked ? 'not-allowed' : 'crosshair' }};"></canvas>
                    </div>
                    @else
                    <div class="alert alert-warning text-center w-100 m-3">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                        Immagine non trovata. Impossibile configurare le ancore.
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Elenco Ancore Area -->
        <div class="col-lg-5 col-12">
            <div class="card card-outline card-secondary shadow-sm">
                <div class="card-header">
                    <span class="text-dark font-weight-bold">
                        <i class="fas fa-list mr-1"></i> Ancore Create
                    </span>
                </div>
                <div class="card-body p-0">
                    <div id="anchors-loading-{{ $modelType }}" class="text-center py-4" style="display:none">
                        <div class="spinner-border text-secondary" role="status"></div>
                    </div>
                    <div id="anchors-empty-{{ $modelType }}" class="alert alert-light text-center m-3" style="display:none">
                        <i class="fas fa-anchor fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Nessuna ancora definita.<br>Clicca sul disegno a sinistra per aggiungerne una.</p>
                    </div>
                    <div id="anchors-list-wrapper-{{ $modelType }}">
                        <div class="table-responsive" style="max-height:440px; overflow-y:auto;">
                            <table class="table table-bordered table-hover table-sm mb-0">
                                <thead class="bg-light text-dark sticky-top">
                                    <tr>
                                        <th>Posizione (X, Y)</th>
                                        @if(!$locked)
                                        <th style="width: 100px;" class="text-center">Azioni</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="anchors-tbody-{{ $modelType }}">
                                    <!-- Loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-muted small px-3 py-2 bg-light border-top">
                    <i class="fas fa-info-circle mr-1"></i>
                    Le ancore hanno una dimensione fissa di 1x1 pixel sulla griglia nativa 32x32.<br>
                    Puoi cliccare su un'ancora esistente nell'editor per rimuoverla.
                </div>
            </div>
        </div>
    </div>
</div>

@if($imageUrl)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modelType = '{{ get_class($model) }}';
    const modelId = {{ $model->id }};
    const canvasId = 'anchors-canvas-{{ $modelType }}';
    
    const canvas = document.getElementById(canvasId);
    if (!canvas) return; // fail-safe
    
    const ctx = canvas.getContext('2d');
    
    // Costanti per il rendering
    const CANVAS_SIZE = 512;
    const GRID_SIZE = 32;
    const CELL_SIZE = CANVAS_SIZE / GRID_SIZE; // 16 pixels
    
    // Variabili di stato
    let anchorsList = [];
    let hoveredCell = null;
    const isLocked = {{ $locked ? 'true' : 'false' }};
    
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Funzione principale per caricare le ancore
    function loadAnchors() {
        document.getElementById('anchors-loading-{{ $modelType }}').style.display = 'block';
        document.getElementById('anchors-empty-{{ $modelType }}').style.display = 'none';
        document.getElementById('anchors-list-wrapper-{{ $modelType }}').style.display = 'none';
        
        fetch(`/entity-anchors?type=${encodeURIComponent(modelType)}&id=${modelId}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            anchorsList = data;
            renderAnchorsList();
            drawCanvas();
            
            document.getElementById('anchors-loading-{{ $modelType }}').style.display = 'none';
            if (anchorsList.length === 0) {
                document.getElementById('anchors-empty-{{ $modelType }}').style.display = 'block';
            } else {
                document.getElementById('anchors-list-wrapper-{{ $modelType }}').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Errore nel caricamento delle ancore:', error);
            if (typeof toastr !== 'undefined') toastr.error('Impossibile caricare le ancore.');
        });
    }
    
    // Disegna la lista HTML
    function renderAnchorsList() {
        const tbody = document.getElementById('anchors-tbody-{{ $modelType }}');
        tbody.innerHTML = '';
        
        anchorsList.forEach(anchor => {
            const tr = document.createElement('tr');
            
            // Colonna Posizione
            const tdPos = document.createElement('td');
            tdPos.className = 'align-middle font-weight-bold';
            tdPos.innerHTML = `<span class="badge badge-light border px-2 py-1"><i class="fas fa-crosshairs text-secondary mr-1"></i> X: ${anchor.x}, Y: ${anchor.y}</span>`;
            
            tr.appendChild(tdPos);

            if (!isLocked) {
                // Colonna Azioni
                const tdActions = document.createElement('td');
                tdActions.className = 'text-center align-middle';
                const btnDel = document.createElement('button');
                btnDel.className = 'btn btn-xs btn-danger shadow-sm';
                btnDel.innerHTML = '<i class="fas fa-trash"></i> Elimina';
                btnDel.type = 'button';
                btnDel.onclick = function() { deleteAnchor(anchor.id); };
                
                tdActions.appendChild(btnDel);
                tr.appendChild(tdActions);
            }
            
            tbody.appendChild(tr);
        });
    }
    
    // Aggiungi nuova ancora
    function addAnchor(x, y) {
        fetch('/entity-anchors', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                type: modelType,
                id: modelId,
                x: x,
                y: y
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof toastr !== 'undefined') toastr.success('Ancora aggiunta con successo.');
                loadAnchors();
            } else {
                if (typeof toastr !== 'undefined') toastr.error(data.message || 'Errore durante il salvataggio.');
                else alert(data.message || 'Errore');
            }
        })
        .catch(error => {
            console.error('Errore durante l\'aggiunta:', error);
            if (typeof toastr !== 'undefined') toastr.error('Si è verificato un errore.');
        });
    }
    
    // Elimina ancora
    function deleteAnchor(anchorId) {
        if (!confirm('Vuoi eliminare questa ancora?')) return;
        
        fetch(`/entity-anchors/${anchorId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof toastr !== 'undefined') toastr.success('Ancora rimossa.');
                loadAnchors();
            } else {
                if (typeof toastr !== 'undefined') toastr.error('Errore durante l\'eliminazione.');
            }
        })
        .catch(error => {
            console.error('Errore durante l\'eliminazione:', error);
            if (typeof toastr !== 'undefined') toastr.error('Si è verificato un errore.');
        });
    }
    
    // Funzioni di Disegno Canvas
    function drawCanvas() {
        ctx.clearRect(0, 0, CANVAS_SIZE, CANVAS_SIZE);
        
        // Disegna tutte le ancore salvate
        anchorsList.forEach(anchor => {
            drawSquare(anchor.x, anchor.y, 'rgba(220, 53, 69, 0.5)', '#dc3545', 2);
        });
        
        // Disegna l'hover state (anteprima)
        if (hoveredCell !== null) {
            // Controlla se c'è già un'ancora qui
            const exists = anchorsList.find(a => a.x === hoveredCell.x && a.y === hoveredCell.y);
            
            if (exists) {
                // Se esiste, mostra l'hover rosso (per indicare l'eliminazione)
                drawSquare(hoveredCell.x, hoveredCell.y, 'rgba(220, 53, 69, 0.8)', '#fff', 2, [2, 2]);
            } else {
                // Se non esiste, mostra l'hover azzurro (per indicare la creazione)
                drawSquare(hoveredCell.x, hoveredCell.y, 'rgba(0, 123, 255, 0.3)', '#007bff', 2, [4, 4]);
            }
        }
    }
    
    function drawSquare(x, y, fillStyle, strokeStyle, lineWidth, lineDash = []) {
        const px = x * CELL_SIZE;
        const py = y * CELL_SIZE;
        
        ctx.fillStyle = fillStyle;
        ctx.fillRect(px, py, CELL_SIZE, CELL_SIZE);
        
        ctx.strokeStyle = strokeStyle;
        ctx.lineWidth = lineWidth;
        ctx.setLineDash(lineDash);
        
        // Offset for stroke to draw inside the pixel bounds
        const offset = lineWidth / 2;
        ctx.strokeRect(px + offset, py + offset, CELL_SIZE - lineWidth, CELL_SIZE - lineWidth);
        
        ctx.setLineDash([]); // reset line dash
    }
    
    // Funzioni helper per la cella
    function getCellCoords(e) {
        const rect = canvas.getBoundingClientRect();
        // Calculate the scale in case the CSS dimensions differ from canvas physical dimensions
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        
        const px = (e.clientX - rect.left) * scaleX;
        const py = (e.clientY - rect.top) * scaleY;
        
        return {
            x: Math.floor(px / CELL_SIZE),
            y: Math.floor(py / CELL_SIZE)
        };
    }
    
    // Event Listeners Canvas
    canvas.addEventListener('mousemove', function(e) {
        if (isLocked) return;
        const coords = getCellCoords(e);
        
        // Evita redraw inutili se non si cambia cella
        if (hoveredCell === null || hoveredCell.x !== coords.x || hoveredCell.y !== coords.y) {
            hoveredCell = coords;
            drawCanvas();
        }
    });
    
    canvas.addEventListener('mouseout', function() {
        if (isLocked) return;
        hoveredCell = null;
        drawCanvas();
    });
    
    canvas.addEventListener('click', function(e) {
        if (isLocked) return;
        const coords = getCellCoords(e);
        
        // Controlla se esiste già un'ancora in questa cella
        const existingAnchor = anchorsList.find(a => a.x === coords.x && a.y === coords.y);
        
        if (existingAnchor) {
            deleteAnchor(existingAnchor.id);
        } else {
            addAnchor(coords.x, coords.y);
        }
    });
    
    // Inizializza al caricamento
    loadAnchors();
});
</script>
@endif
