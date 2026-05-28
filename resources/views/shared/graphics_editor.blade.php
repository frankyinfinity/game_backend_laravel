@php
    $storageDir = \Illuminate\Support\Str::plural($modelType);
    $imagePath = 'storage/' . $storageDir . '/' . $model->id . '.png';
    $fileExists = file_exists(public_path($imagePath));
@endphp

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Editor 32x32</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btn-undo-{{$modelType}}" disabled>
                        <i class="fas fa-undo"></i> Annulla
                    </button>
                    <button type="button" class="btn btn-tool" id="btn-redo-{{$modelType}}" disabled>
                        <i class="fas fa-redo"></i> Rifai
                    </button>
                    <button type="button" class="btn btn-tool" id="btn-clear-canvas-{{$modelType}}">
                        <i class="fas fa-trash"></i> Pulisci
                    </button>
                </div>
            </div>
            <div class="card-body text-center" style="background-color: #f4f6f9;">
                <div id="pixel-editor-container-{{$modelType}}" style="display: inline-block; position: relative; border: 1px solid #ccc; line-height: 0;">
                    <canvas id="pixel-canvas-{{$modelType}}" width="512" height="512" style="image-rendering: pixelated; cursor: crosshair; display: block;"></canvas>
                    <canvas id="grid-canvas-{{$modelType}}" width="512" height="512" style="position: absolute; top: 0; left: 0; pointer-events: none; display: block;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-secondary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="graphics-editor-tabs-{{$modelType}}" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="graphics-tools-tab-{{$modelType}}" data-toggle="pill" href="#graphics-tools-{{$modelType}}" role="tab"
                            aria-controls="graphics-tools-{{$modelType}}" aria-selected="true">Strumenti</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="graphics-current-image-tab-{{$modelType}}" data-toggle="pill" href="#graphics-current-image-{{$modelType}}" role="tab"
                            aria-controls="graphics-current-image-{{$modelType}}" aria-selected="false">Immagine Attuale</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="graphics-ai-tab-{{$modelType}}" data-toggle="pill" href="#graphics-ai-{{$modelType}}" role="tab"
                            aria-controls="graphics-ai-{{$modelType}}" aria-selected="false">AI</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="graphics-editor-tabs-content-{{$modelType}}">
                    <div class="tab-pane fade show active" id="graphics-tools-{{$modelType}}" role="tabpanel" aria-labelledby="graphics-tools-tab-{{$modelType}}">
                        @if(isset($availableColors) && is_array($availableColors) && count($availableColors) > 0)
                            <div class="form-group" style="display: none;">
                                <input type="color" id="editor-color-{{$modelType}}" value="{{ $availableColors[0] }}">
                            </div>
                            <div class="form-group">
                                <label>Palette Colori (Vincolata) <i class="fas fa-lock text-warning" title="I colori sono limitati per questo tipo di grafica"></i></label>
                                <div class="d-flex flex-wrap">
                                    @foreach($availableColors as $color)
                                        <button type="button" class="btn btn-sm m-1" style="background-color: {{ $color }}; width: 30px; height: 30px;" data-color="{{ $color }}"></button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="form-group">
                                <label>Colore</label>
                                <input type="color" id="editor-color-{{$modelType}}" class="form-control" value="#000000" style="height: 40px;">
                            </div>

                            <div class="form-group">
                                <label>Palette Colori</label>
                                <div class="d-flex flex-wrap">
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #000000; width: 30px; height: 30px;" data-color="#000000"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #FFFFFF; width: 30px; height: 30px;" data-color="#FFFFFF"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #FF0000; width: 30px; height: 30px;" data-color="#FF0000"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #00FF00; width: 30px; height: 30px;" data-color="#00FF00"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #0000FF; width: 30px; height: 30px;" data-color="#0000FF"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #FFFF00; width: 30px; height: 30px;" data-color="#FFFF00"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #FF00FF; width: 30px; height: 30px;" data-color="#FF00FF"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #00FFFF; width: 30px; height: 30px;" data-color="#00FFFF"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #FFA500; width: 30px; height: 30px;" data-color="#FFA500"></button>
                                    <button type="button" class="btn btn-sm m-1" style="background-color: #800080; width: 30px; height: 30px;" data-color="#800080"></button>
                                </div>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Strumenti</label>
                            <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                                <label class="btn btn-primary active flex-fill">
                                    <input type="radio" name="editor-tool-{{$modelType}}" id="tool-pencil-{{$modelType}}" autocomplete="off" checked>
                                    <i class="fas fa-pencil-alt"></i> Matita
                                </label>
                                <label class="btn btn-primary flex-fill">
                                    <input type="radio" name="editor-tool-{{$modelType}}" id="tool-eraser-{{$modelType}}" autocomplete="off">
                                    <i class="fas fa-eraser"></i> Gomma
                                </label>
                                <label class="btn btn-primary flex-fill">
                                    <input type="radio" name="editor-tool-{{$modelType}}" id="tool-fill-{{$modelType}}" autocomplete="off">
                                    <i class="fas fa-fill-drip"></i> Riempimento
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="grid-toggle-{{$modelType}}" checked>
                                <label class="form-check-label" for="grid-toggle-{{$modelType}}">
                                    Mostra Griglia
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group text-center">
                            <label>Anteprima 32x32</label><br>
                            <div class="preview-box">
                                <canvas id="preview-canvas-{{$modelType}}" width="32" height="32" style="display: block;"></canvas>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="text-muted small">
                                <i class="fas fa-info-circle"></i> La grafica verrà salvata cliccando sul pulsante <b>Aggiorna</b> in fondo alla pagina.
                            </p>
                            <input type="hidden" name="image_base64" id="image_base64-{{$modelType}}">
                        </div>
                    </div>

                    <div class="tab-pane fade text-center" id="graphics-current-image-{{$modelType}}" role="tabpanel" aria-labelledby="graphics-current-image-tab-{{$modelType}}">
                        @if($fileExists)
                            <img id="current-image-{{$modelType}}" src="{{ asset($imagePath) }}?v={{ time() }}" style="width: 128px; height: 128px; image-rendering: pixelated; border: 1px solid #ccc;">
                            <br>
                            <button type="button" class="btn btn-sm btn-info mt-2" id="btn-load-current-{{$modelType}}">
                                <i class="fas fa-file-import"></i> Carica in Editor
                            </button>
                        @else
                            <p class="text-muted">Nessuna immagine salvata</p>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="graphics-ai-{{$modelType}}" role="tabpanel" aria-labelledby="graphics-ai-tab-{{$modelType}}">
                        <div class="form-group">
                            <label for="graphics-ai-prompt-{{$modelType}}">Prompt</label>
                            <textarea class="form-control" id="graphics-ai-prompt-{{$modelType}}" rows="5"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Invia
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #pixel-editor-container-{{$modelType}} {
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        background-image: linear-gradient(45deg, #e0e0e0 25%, transparent 25%),
                          linear-gradient(-45deg, #e0e0e0 25%, transparent 25%),
                          linear-gradient(45deg, transparent 75%, #e0e0e0 75%),
                          linear-gradient(-45deg, transparent 75%, #e0e0e0 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px 10px, 10px 0;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #dee2e6;
    }

    .btn-group-toggle .btn {
        transition: all 0.3s ease;
    }

    .preview-box {
        border: 4px solid #fff;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        display: inline-block;
        padding: 0;
        background: white;
        line-height: 0;
        border-radius: 4px;
        overflow: hidden;
    }

    #current-image-{{$modelType}} {
        transition: transform 0.3s ease;
    }

    #current-image-{{$modelType}}:hover {
        transform: scale(1.1);
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modelType = '{{$modelType}}';
    const canvasSize = 512;
    const pixelSize = 32;
    const cellSize = canvasSize / pixelSize;

    const canvas = document.getElementById('pixel-canvas-' + modelType);
    const ctx = canvas.getContext('2d');

    const gridCanvas = document.getElementById('grid-canvas-' + modelType);
    const gridCtx = gridCanvas.getContext('2d');

    const previewCanvas = document.getElementById('preview-canvas-' + modelType);
    const previewCtx = previewCanvas.getContext('2d');

    const colorPicker = document.getElementById('editor-color-' + modelType);
    const btnClear = document.getElementById('btn-clear-canvas-' + modelType);
    const btnLoadCurrent = document.getElementById('btn-load-current-' + modelType);
    const btnUndo = document.getElementById('btn-undo-' + modelType);
    const btnRedo = document.getElementById('btn-redo-' + modelType);
    const gridToggle = document.getElementById('grid-toggle-' + modelType);

    let isDrawing = false;
    let hasGraphicsChanges = false;
    let history = [];
    let historyIndex = -1;
    let gridVisible = true;

    // Initialize canvases
    function initCanvases() {
        ctx.clearRect(0, 0, canvasSize, canvasSize);
        drawGrid();
        updatePreview();
        saveState();
    }

    function saveState() {
        // Remove any history after current index
        history = history.slice(0, historyIndex + 1);
        // Add current state
        history.push(ctx.getImageData(0, 0, canvasSize, canvasSize));
        historyIndex++;
        // Limit history to 50 states
        if (history.length > 50) {
            history.shift();
            historyIndex--;
        }
        updateUndoRedoButtons();
    }

    function undo() {
        if (historyIndex > 0) {
            historyIndex--;
            ctx.putImageData(history[historyIndex], 0, 0);
            updatePreview();
            hasGraphicsChanges = true;
            updateUndoRedoButtons();
        }
    }

    function redo() {
        if (historyIndex < history.length - 1) {
            historyIndex++;
            ctx.putImageData(history[historyIndex], 0, 0);
            updatePreview();
            hasGraphicsChanges = true;
            updateUndoRedoButtons();
        }
    }

    function updateUndoRedoButtons() {
        btnUndo.disabled = historyIndex <= 0;
        btnRedo.disabled = historyIndex >= history.length - 1;
    }

    function drawGrid() {
        gridCtx.clearRect(0, 0, canvasSize, canvasSize);
        if (!gridVisible) return;

        gridCtx.strokeStyle = 'rgba(0, 0, 0, 0.1)';
        gridCtx.lineWidth = 0.5;

        for (let i = 0; i <= canvasSize; i += cellSize) {
            gridCtx.beginPath();
            gridCtx.moveTo(i, 0);
            gridCtx.lineTo(i, canvasSize);
            gridCtx.stroke();

            gridCtx.beginPath();
            gridCtx.moveTo(0, i);
            gridCtx.lineTo(canvasSize, i);
            gridCtx.stroke();
        }
    }

    function getPixelCoords(e) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        return {
            x: Math.floor(x / cellSize),
            y: Math.floor(y / cellSize)
        };
    }

    function drawPixel(x, y) {
        if (x < 0 || x >= pixelSize || y < 0 || y >= pixelSize) return;

        const tool = document.querySelector('input[name="editor-tool-' + modelType + '"]:checked').id;

        if (tool === 'tool-pencil-' + modelType) {
            ctx.fillStyle = colorPicker.value;
            ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
        } else if (tool === 'tool-eraser-' + modelType) {
            ctx.clearRect(x * cellSize, y * cellSize, cellSize, cellSize);
        }

        hasGraphicsChanges = true;
        updatePreview();
    }

    function fillAll(fillColor) {
        ctx.fillStyle = fillColor;
        ctx.fillRect(0, 0, canvasSize, canvasSize);
        hasGraphicsChanges = true;
        updatePreview();
    }

    function updatePreview() {
        previewCtx.clearRect(0, 0, pixelSize, pixelSize);
        previewCtx.drawImage(canvas, 0, 0, canvasSize, canvasSize, 0, 0, pixelSize, pixelSize);
    }

    canvas.addEventListener('mousedown', (e) => {
        const coords = getPixelCoords(e);
        const tool = document.querySelector('input[name="editor-tool-' + modelType + '"]:checked').id;

        if (tool === 'tool-fill-' + modelType) {
            fillAll(colorPicker.value);
            saveState();
        } else {
            isDrawing = true;
            drawPixel(coords.x, coords.y);
        }
    });

    window.addEventListener('mouseup', () => {
        if (isDrawing) {
            saveState();
        }
        isDrawing = false;
    });

    canvas.addEventListener('mousemove', (e) => {
        if (isDrawing) {
            const coords = getPixelCoords(e);
            drawPixel(coords.x, coords.y);
        }
    });

    btnUndo.addEventListener('click', undo);
    btnRedo.addEventListener('click', redo);

    btnClear.addEventListener('click', () => {
        if (confirm('Sei sicuro di voler pulire tutto?')) {
            ctx.clearRect(0, 0, canvasSize, canvasSize);
            hasGraphicsChanges = true;
            updatePreview();
            saveState();
        }
    });

    gridToggle.addEventListener('change', () => {
        gridVisible = gridToggle.checked;
        drawGrid();
    });

    // Palette color buttons
    document.querySelectorAll('button[data-color]').forEach(btn => {
        btn.addEventListener('click', () => {
            colorPicker.value = btn.dataset.color;
        });
    });

    if (btnLoadCurrent) {
        btnLoadCurrent.addEventListener('click', () => {
            const currentImg = document.getElementById('current-image-' + modelType);
            const img = new Image();
            // Use timestamp to avoid cache issues
            img.src = currentImg.src.split('?')[0] + '?t=' + new Date().getTime();
            img.crossOrigin = "Anonymous";
            img.onload = function() {
                // Clear the main canvas
                ctx.clearRect(0, 0, canvasSize, canvasSize);

                // Create a temporary 32x32 canvas to get clean pixels
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = 32;
                tempCanvas.height = 32;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.drawImage(img, 0, 0, 32, 32);

                // Get the image data
                const imageData = tempCtx.getImageData(0, 0, 32, 32);
                const data = imageData.data;

                // Redraw on the big canvas pixel by pixel to ensure sharpness
                for (let y = 0; y < 32; y++) {
                    for (let x = 0; x < 32; x++) {
                        const i = (y * 32 + x) * 4;
                        const r = data[i];
                        const g = data[i+1];
                        const b = data[i+2];
                        const a = data[i+3];

                        if (a > 0) {
                            ctx.fillStyle = `rgba(${r},${g},${b},${a/255})`;
                            ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
                        }
                    }
                }

                updatePreview();
                saveState();
                toastr.info('Immagine caricata nell\'editor');
            };
        });
    }

    // Populate hidden input before form submission
    const mainForm = canvas.closest('form');
    if (mainForm) {
        mainForm.addEventListener('submit', function() {
            const imageInput = document.getElementById('image_base64-' + modelType);
            if (!imageInput) {
                return;
            }

            // Avoid overwriting existing graphic when no change was made.
            if (hasGraphicsChanges) {
                imageInput.value = previewCanvas.toDataURL('image/png');
            } else {
                imageInput.value = '';
            }
        });
    }

    initCanvases();
});
</script>