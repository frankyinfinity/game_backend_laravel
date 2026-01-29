<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Editor 32x32</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btn-clear-canvas">
                        <i class="fas fa-trash"></i> Pulisci
                    </button>
                </div>
            </div>
            <div class="card-body text-center" style="background-color: #f4f6f9;">
                <div id="pixel-editor-container" style="display: inline-block; position: relative; border: 1px solid #ccc; line-height: 0;">
                    <canvas id="pixel-canvas" width="512" height="512" style="image-rendering: pixelated; cursor: crosshair; display: block;"></canvas>
                    <canvas id="grid-canvas" width="512" height="512" style="position: absolute; top: 0; left: 0; pointer-events: none; display: block;"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Strumenti</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Colore</label>
                    <input type="color" id="editor-color" class="form-control" value="#000000" style="height: 40px;">
                </div>
                
                <div class="form-group">
                    <label>Strumenti</label>
                    <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                        <label class="btn btn-primary active flex-fill">
                            <input type="radio" name="editor-tool" id="tool-pencil" autocomplete="off" checked> 
                            <i class="fas fa-pencil-alt"></i> Matita
                        </label>
                        <label class="btn btn-primary flex-fill">
                            <input type="radio" name="editor-tool" id="tool-eraser" autocomplete="off"> 
                            <i class="fas fa-eraser"></i> Gomma
                        </label>
                    </div>
                </div>

                <hr>

                <div class="form-group text-center">
                    <label>Anteprima 32x32</label><br>
                    <div class="preview-box">
                        <canvas id="preview-canvas" width="32" height="32" style="display: block;"></canvas>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> La grafica verrà salvata cliccando sul pulsante <b>Aggiorna</b> in fondo alla pagina.
                    </p>
                    <input type="hidden" name="image_base64" id="image_base64">
                </div>
            </div>
        </div>

        <div class="card card-outline card-info mt-3">
             <div class="card-header">
                <h3 class="card-title">Immagine Attuale</h3>
            </div>
            <div class="card-body text-center">
                @php
                    $imagePath = 'storage/elements/' . $element->id . '.png';
                    $fileExists = file_exists(public_path($imagePath));
                @endphp
                @if($fileExists)
                    <img id="current-image" src="{{ asset($imagePath) }}?v={{ time() }}" style="width: 128px; height: 128px; image-rendering: pixelated; border: 1px solid #ccc;">
                    <br>
                    <button type="button" class="btn btn-sm btn-info mt-2" id="btn-load-current">
                        <i class="fas fa-file-import"></i> Carica in Editor
                    </button>
                @else
                    <p class="text-muted">Nessuna immagine salvata</p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    #pixel-editor-container {
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

    #current-image {
        transition: transform 0.3s ease;
    }

    #current-image:hover {
        transform: scale(1.1);
    }

    .card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvasSize = 512;
    const pixelSize = 32;
    const cellSize = canvasSize / pixelSize;
    
    const canvas = document.getElementById('pixel-canvas');
    const ctx = canvas.getContext('2d');
    
    const gridCanvas = document.getElementById('grid-canvas');
    const gridCtx = gridCanvas.getContext('2d');
    
    const previewCanvas = document.getElementById('preview-canvas');
    const previewCtx = previewCanvas.getContext('2d');
    
    const colorPicker = document.getElementById('editor-color');
    const btnClear = document.getElementById('btn-clear-canvas');
    const btnSave = document.getElementById('btn-save-graphics');
    const btnLoadCurrent = document.getElementById('btn-load-current');
    
    let isDrawing = false;
    
    // Initialize canvases
    function initCanvases() {
        ctx.clearRect(0, 0, canvasSize, canvasSize);
        drawGrid();
        updatePreview();
    }
    
    function drawGrid() {
        gridCtx.clearRect(0, 0, canvasSize, canvasSize);
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
        
        const tool = document.querySelector('input[name="editor-tool"]:checked').id;
        
        if (tool === 'tool-pencil') {
            ctx.fillStyle = colorPicker.value;
            ctx.fillRect(x * cellSize, y * cellSize, cellSize, cellSize);
        } else {
            ctx.clearRect(x * cellSize, y * cellSize, cellSize, cellSize);
        }
        
        updatePreview();
    }
    
    function updatePreview() {
        previewCtx.clearRect(0, 0, pixelSize, pixelSize);
        previewCtx.drawImage(canvas, 0, 0, canvasSize, canvasSize, 0, 0, pixelSize, pixelSize);
    }
    
    canvas.addEventListener('mousedown', (e) => {
        isDrawing = true;
        const coords = getPixelCoords(e);
        drawPixel(coords.x, coords.y);
    });
    
    window.addEventListener('mouseup', () => {
        isDrawing = false;
    });
    
    canvas.addEventListener('mousemove', (e) => {
        if (isDrawing) {
            const coords = getPixelCoords(e);
            drawPixel(coords.x, coords.y);
        }
    });
    
    btnClear.addEventListener('click', () => {
        if (confirm('Sei sicuro di voler pulire tutto?')) {
            ctx.clearRect(0, 0, canvasSize, canvasSize);
            updatePreview();
        }
    });

    if (btnLoadCurrent) {
        btnLoadCurrent.addEventListener('click', () => {
            const currentImg = document.getElementById('current-image');
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
                toastr.info('Immagine caricata nell\'editor');
            };
        });
    }
    
    // Populate hidden input before form submission
    const mainForm = canvas.closest('form');
    if (mainForm) {
        mainForm.addEventListener('submit', function() {
            const dataUrl = previewCanvas.toDataURL('image/png');
            document.getElementById('image_base64').value = dataUrl;
        });
    }
    
    initCanvases();
});
</script>
