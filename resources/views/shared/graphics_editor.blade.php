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
                    {{--<li class="nav-item">
                        <a class="nav-link" id="graphics-ai-tab-{{$modelType}}" data-toggle="pill" href="#graphics-ai-{{$modelType}}" role="tab"
                            aria-controls="graphics-ai-{{$modelType}}" aria-selected="false">AI</a>
                    </li> --}}
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

                        <div class="form-group">
                            <label>Sposta Pixel</label>
                            <div class="direction-pad" id="direction-pad-{{$modelType}}">
                                <button type="button" class="btn-dir btn-dir-diag" data-dir="up-left" title="Su-Sinistra"><i class="fas fa-arrow-up"></i><i class="fas fa-arrow-left"></i></button>
                                <button type="button" class="btn-dir btn-dir-vert" data-dir="up" title="Su"><i class="fas fa-arrow-up"></i></button>
                                <button type="button" class="btn-dir btn-dir-diag" data-dir="up-right" title="Su-Destra"><i class="fas fa-arrow-up"></i><i class="fas fa-arrow-right"></i></button>
                                <button type="button" class="btn-dir btn-dir-horiz" data-dir="left" title="Sinistra"><i class="fas fa-arrow-left"></i></button>
                                <button type="button" class="btn-dir btn-dir-center" data-dir="center" title="Centro" disabled><i class="fas fa-arrows-alt"></i></button>
                                <button type="button" class="btn-dir btn-dir-horiz" data-dir="right" title="Destra"><i class="fas fa-arrow-right"></i></button>
                                <button type="button" class="btn-dir btn-dir-diag" data-dir="down-left" title="Giù-Sinistra"><i class="fas fa-arrow-down"></i><i class="fas fa-arrow-left"></i></button>
                                <button type="button" class="btn-dir btn-dir-vert" data-dir="down" title="Giù"><i class="fas fa-arrow-down"></i></button>
                                <button type="button" class="btn-dir btn-dir-diag" data-dir="down-right" title="Giù-Destra"><i class="fas fa-arrow-down"></i><i class="fas fa-arrow-right"></i></button>
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
                        <button type="button" class="btn btn-primary" id="btn-ai-generate-{{$modelType}}">
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

    .direction-pad {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 4px;
        max-width: 180px;
        margin: 0 auto;
    }

    .btn-dir {
        width: 50px;
        height: 50px;
        border: 1px solid #6c757d;
        background-color: #e9ecef;
        color: #495057;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
        font-size: 14px;
        padding: 0;
    }

    .btn-dir:hover:not(:disabled) {
        background-color: #6c757d;
        color: #fff;
        border-color: #6c757d;
    }

    .btn-dir:active:not(:disabled) {
        background-color: #495057;
        color: #fff;
        transform: scale(0.93);
    }

    .btn-dir:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .btn-dir i {
        font-size: 12px;
    }

    .btn-dir-diag i {
        font-size: 9px;
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
    const aiPrompt = document.getElementById('graphics-ai-prompt-' + modelType);
    const btnAiGenerate = document.getElementById('btn-ai-generate-' + modelType);

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

    function drawPixelMatrix(pixels) {
        ctx.clearRect(0, 0, canvasSize, canvasSize);

        pixels.forEach((row, y) => {
            row.forEach((color, x) => {
                if (color) {
                    ctx.fillStyle = color;
                    ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
                } else {
                    ctx.clearRect(x * cellSize, y * cellSize, cellSize, cellSize);
                }
            });
        });

        hasGraphicsChanges = true;
        updatePreview();
        saveState();
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

    if (btnAiGenerate) {
        function startAiRetryCountdown(seconds, originalHtml) {
            let remaining = Math.max(1, parseInt(seconds, 10));
            btnAiGenerate.disabled = true;
            btnAiGenerate.innerHTML = '<i class="fas fa-clock"></i> Riprova tra ' + remaining + 's';

            const interval = setInterval(() => {
                remaining--;

                if (remaining <= 0) {
                    clearInterval(interval);
                    btnAiGenerate.disabled = false;
                    btnAiGenerate.innerHTML = originalHtml;
                    return;
                }

                btnAiGenerate.innerHTML = '<i class="fas fa-clock"></i> Riprova tra ' + remaining + 's';
            }, 1000);
        }

        btnAiGenerate.addEventListener('click', async () => {
            const prompt = aiPrompt.value.trim();

            if (!prompt) {
                toastr.warning('Inserisci un prompt.');
                return;
            }

            const originalHtml = btnAiGenerate.innerHTML;
            btnAiGenerate.disabled = true;
            btnAiGenerate.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generazione...';

            try {
                const response = await fetch('{{ route('graphics-editor.ai-generate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        prompt: prompt,
                        model_type: modelType,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (response.status === 429) {
                        const retryAfter = data.details?.error?.metadata?.retry_after_seconds
                            || data.details?.error?.metadata?.headers?.['Retry-After'];

                        if (retryAfter) {
                            toastr.warning(data.message);
                            startAiRetryCountdown(retryAfter, originalHtml);
                            return;
                        }
                    }

                    throw new Error(data.message || 'Errore durante la generazione AI.');
                }

                drawPixelMatrix(data.pixels);
                toastr.success('Immagine generata sulla griglia.');
            } catch (error) {
                toastr.error(error.message);
            } finally {
                if (btnAiGenerate.innerHTML.includes('Generazione')) {
                    btnAiGenerate.disabled = false;
                    btnAiGenerate.innerHTML = originalHtml;
                }
            }
        });
    }

    // Direction pad: shift all pixels
    const directionPad = document.getElementById('direction-pad-' + modelType);
    if (directionPad) {
        directionPad.querySelectorAll('.btn-dir[data-dir]').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = btn.dataset.dir;
                if (dir === 'center') return;

                // Read current pixel data from the 32x32 grid
                const imageData = ctx.getImageData(0, 0, canvasSize, canvasSize);
                const data = imageData.data;

                // Build a 32x32 array of pixel colors by sampling the center of each cell (null = transparent)
                const pixels = [];
                for (let y = 0; y < pixelSize; y++) {
                    pixels[y] = [];
                    for (let x = 0; x < pixelSize; x++) {
                        // Sample from the center of each cell
                        const cx = x * cellSize + Math.floor(cellSize / 2);
                        const cy = y * cellSize + Math.floor(cellSize / 2);
                        const i = (cy * canvasSize + cx) * 4;
                        const r = data[i], g = data[i+1], b = data[i+2], a = data[i+3];
                        pixels[y][x] = (a > 0) ? { r, g, b, a } : null;
                    }
                }

                // Compute direction offsets
                let dx = 0, dy = 0;
                if (dir.includes('left'))  dx = -1;
                if (dir.includes('right')) dx = 1;
                if (dir.includes('up'))    dy = -1;
                if (dir.includes('down'))  dy = 1;

                // Create new shifted pixel array
                const newPixels = [];
                for (let y = 0; y < pixelSize; y++) {
                    newPixels[y] = [];
                    for (let x = 0; x < pixelSize; x++) {
                        const srcX = x - dx;
                        const srcY = y - dy;
                        if (srcX >= 0 && srcX < pixelSize && srcY >= 0 && srcY < pixelSize) {
                            newPixels[y][x] = pixels[srcY][srcX];
                        } else {
                            newPixels[y][x] = null;
                        }
                    }
                }

                // Redraw canvas with shifted pixels
                ctx.clearRect(0, 0, canvasSize, canvasSize);
                for (let y = 0; y < pixelSize; y++) {
                    for (let x = 0; x < pixelSize; x++) {
                        const px = newPixels[y][x];
                        if (px) {
                            ctx.fillStyle = `rgba(${px.r},${px.g},${px.b},${px.a / 255})`;
                            ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
                        }
                    }
                }

                hasGraphicsChanges = true;
                updatePreview();
                saveState();
            });
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