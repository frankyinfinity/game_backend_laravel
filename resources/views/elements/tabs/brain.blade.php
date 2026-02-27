<div class="alert alert-info">
    Definisci la dimensione della griglia per il cervello dell'elemento (usata lato PIXI.js).
</div>
@php
    $existingNeuronItems = $element->brain && $element->brain->neurons
        ? $element->brain->neurons->map(function ($n) {
            return [
                'type' => $n->type,
                'grid_i' => (int) $n->grid_i,
                'grid_j' => (int) $n->grid_j,
                'radius' => $n->radius !== null ? (int) $n->radius : null,
                'target_type' => $n->target_type,
                'target_element_id' => $n->target_element_id !== null ? (int) $n->target_element_id : null,
                'target_entity_id' => $n->target_entity_id !== null ? (int) $n->target_entity_id : null,
            ];
        })->values()->all()
        : [];
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_width">Larghezza Griglia</label>
            <input type="number"
                   class="form-control @error('brain_grid_width') is-invalid @enderror"
                   id="brain_grid_width"
                   name="brain_grid_width"
                   min="1"
                   step="1"
                   value="{{ old('brain_grid_width', optional($element->brain)->grid_width ?? 5) }}"
                   placeholder="Es. 5">
            @error('brain_grid_width')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_height">Altezza Griglia</label>
            <input type="number"
                   class="form-control @error('brain_grid_height') is-invalid @enderror"
                   id="brain_grid_height"
                   name="brain_grid_height"
                   min="1"
                   step="1"
                   value="{{ old('brain_grid_height', optional($element->brain)->grid_height ?? 5) }}"
                   placeholder="Es. 5">
            @error('brain_grid_height')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </div>
</div>

<input type="hidden" id="neuron_items" name="neuron_items" value="{{ old('neuron_items', json_encode($existingNeuronItems)) }}">

<div class="row mb-3">
    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Salva
        </button>
        <a href="{{ route('elements.index') }}" class="btn btn-secondary">
            <i class="fas fa-times"></i> Annulla
        </a>
        <small class="text-muted ml-2">Ricordati di salvare per mantenere le modifiche.</small>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Anteprima Griglia (PIXI.js)</h3>
            </div>
            <div class="card-body">
                <div id="brain-grid-pixi" style="display:inline-block; border:1px solid #b0b0b0; border-radius:4px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="brainNeuronModal" tabindex="-1" role="dialog" aria-labelledby="brainNeuronModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brainNeuronModalLabel">Configura Neurone</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2 text-muted">
                    Cella selezionata: <strong id="selected_cell_label">-</strong>
                </div>
                <div class="form-group">
                    <label for="neuron_type">Tipologia</label>
                    <select class="form-control" id="neuron_type">
                        @foreach(\App\Models\Neuron::TYPE_LABELS as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="neuron_radius_group">
                    <label for="neuron_radius">Raggio (in celle)</label>
                    <input type="number" class="form-control" id="neuron_radius" min="1" step="1" value="1">
                </div>
                <div class="form-group" id="neuron_target_type_group">
                    <label>Target da individuare</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="neuron_target_type_element">
                        <label class="form-check-label" for="neuron_target_type_element">Element</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="neuron_target_type_entity">
                        <label class="form-check-label" for="neuron_target_type_entity">Entity</label>
                    </div>
                </div>
                <div class="form-group" id="neuron_target_element_group">
                    <label for="neuron_target_element_id">Seleziona Element</label>
                    <select class="form-control" id="neuron_target_element_id">
                        <option value="">-- Seleziona --</option>
                        @foreach(($brainTargetElements ?? collect()) as $targetElement)
                            <option value="{{ $targetElement->id }}">{{ $targetElement->name }} (#{{ $targetElement->id }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" id="btn_delete_neuron">Rimuovi</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btn_save_neuron">Salva neurone</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('js')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    @endpush
@endonce

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const widthInput = document.getElementById('brain_grid_width');
    const heightInput = document.getElementById('brain_grid_height');
    const container = document.getElementById('brain-grid-pixi');
    const neuronItemsInput = document.getElementById('neuron_items');
    const neuronTypeInput = document.getElementById('neuron_type');
    const neuronRadiusInput = document.getElementById('neuron_radius');
    const neuronRadiusGroup = document.getElementById('neuron_radius_group');
    const neuronTargetTypeElementInput = document.getElementById('neuron_target_type_element');
    const neuronTargetTypeEntityInput = document.getElementById('neuron_target_type_entity');
    const neuronTargetTypeGroup = document.getElementById('neuron_target_type_group');
    const neuronTargetElementGroup = document.getElementById('neuron_target_element_group');
    const neuronTargetElementIdInput = document.getElementById('neuron_target_element_id');
    const neuronTargetEntityGroup = null;
    const neuronTargetEntityIdInput = null;
    const selectedCellLabel = document.getElementById('selected_cell_label');
    const saveNeuronBtn = document.getElementById('btn_save_neuron');
    const deleteNeuronBtn = document.getElementById('btn_delete_neuron');
    const neuronModalEl = document.getElementById('brainNeuronModal');
    if (!widthInput || !heightInput || !container || !neuronItemsInput || !neuronTypeInput || !neuronRadiusInput || !neuronRadiusGroup || !neuronTargetTypeElementInput || !neuronTargetTypeEntityInput || !neuronTargetTypeGroup || !neuronTargetElementGroup || !neuronTargetElementIdInput || !selectedCellLabel || !saveNeuronBtn || !deleteNeuronBtn || !neuronModalEl || typeof PIXI === 'undefined') {
        return;
    }

    const fixedCellSize = 36;
    const typeDetection = @json(\App\Models\Neuron::TYPE_DETECTION);
    const typePath = @json(\App\Models\Neuron::TYPE_PATH);
    const targetTypeElement = @json(\App\Models\Neuron::TARGET_TYPE_ELEMENT);
    const targetTypeEntity = @json(\App\Models\Neuron::TARGET_TYPE_ENTITY);
    const typeSymbols = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    const saveNeuronUrl = @json(route('elements.brain.neurons.save', $element));
    const deleteNeuronUrl = @json(route('elements.brain.neurons.delete', $element));
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    let app = null;
    let selectedCell = null;
    let neuronItems = [];

    try {
        const parsed = JSON.parse(neuronItemsInput.value || '[]');
        neuronItems = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        neuronItems = [];
    }

    function normalize(value) {
        const parsed = parseInt(value, 10);
        if (Number.isNaN(parsed) || parsed < 1) return 1;
        return parsed;
    }

    function drawDashedLine(graphics, x1, y1, x2, y2, dash = 6, gap = 4) {
        const dx = x2 - x1;
        const dy = y2 - y1;
        const distance = Math.sqrt((dx * dx) + (dy * dy));
        if (distance === 0) return;

        const ux = dx / distance;
        const uy = dy / distance;
        let drawn = 0;

        while (drawn < distance) {
            const startX = x1 + (ux * drawn);
            const startY = y1 + (uy * drawn);
            const dashLength = Math.min(dash, distance - drawn);
            const endX = startX + (ux * dashLength);
            const endY = startY + (uy * dashLength);

            graphics.moveTo(startX, startY);
            graphics.lineTo(endX, endY);

            drawn += dash + gap;
        }
    }

    function neuronKey(i, j) {
        return `${i}_${j}`;
    }

    function updateNeuronHiddenInput() {
        neuronItemsInput.value = JSON.stringify(neuronItems);
    }

    function filterOutOfBoundsNeurons(rows, cols) {
        neuronItems = neuronItems.filter((item) => {
            const i = Number(item.grid_i);
            const j = Number(item.grid_j);
            return i >= 0 && j >= 0 && i < rows && j < cols;
        });
    }

    function findNeuronAtCell(i, j) {
        const key = neuronKey(i, j);
        return neuronItems.find((item) => neuronKey(Number(item.grid_i), Number(item.grid_j)) === key) || null;
    }

    function toggleDetectionFieldsByType() {
        const isDetection = neuronTypeInput.value === typeDetection;
        neuronRadiusGroup.style.display = isDetection ? '' : 'none';
        neuronTargetTypeGroup.style.display = isDetection ? '' : 'none';
        if (!isDetection) {
            neuronTargetElementGroup.style.display = 'none';
            return;
        }

        if (neuronTargetTypeEntityInput.checked) {
            neuronTargetElementGroup.style.display = 'none';
            return;
        }

        if (neuronTargetTypeElementInput.checked) {
            neuronTargetElementGroup.style.display = '';
        } else {
            neuronTargetElementGroup.style.display = 'none';
        }
    }

    function openNeuronModal(i, j) {
        selectedCell = { i, j };
        selectedCellLabel.textContent = `(${i}, ${j})`;

        const existing = findNeuronAtCell(i, j);
        const resetCreateFields = function () {
            neuronTypeInput.value = typeDetection;
            neuronRadiusInput.value = 1;
            neuronTargetTypeElementInput.checked = true;
            neuronTargetTypeEntityInput.checked = false;
            neuronTargetElementIdInput.value = '';
        };

        if (existing) {
            const inferredTargetType = existing.target_type
                || (existing.target_element_id != null ? targetTypeElement : null)
                || (existing.target_entity_id != null ? targetTypeEntity : null)
                || targetTypeElement;
            const inferredElementTargetId = existing.target_element_id != null
                ? String(existing.target_element_id)
                : (inferredTargetType === targetTypeElement && existing.target_entity_id != null ? String(existing.target_entity_id) : '');

            neuronTypeInput.value = existing.type;
            neuronRadiusInput.value = existing.radius != null ? Number(existing.radius) : 1;
            neuronTargetTypeElementInput.checked = inferredTargetType === targetTypeElement;
            neuronTargetTypeEntityInput.checked = inferredTargetType === targetTypeEntity;
            if (inferredElementTargetId !== '' && neuronTargetElementIdInput.querySelector(`option[value="${inferredElementTargetId}"]`)) {
                neuronTargetElementIdInput.value = inferredElementTargetId;
            } else {
                neuronTargetElementIdInput.value = '';
            }
            deleteNeuronBtn.style.display = '';
        } else {
            resetCreateFields();
            deleteNeuronBtn.style.display = 'none';
        }

        toggleDetectionFieldsByType();
        $(neuronModalEl).modal('show');
    }

    function drawNeuronSymbols(layer, cellSize) {
        for (const neuron of neuronItems) {
            const i = Number(neuron.grid_i);
            const j = Number(neuron.grid_j);
            const symbol = typeSymbols[neuron.type] || '?';

            const neuronBorder = new PIXI.Graphics();
            neuronBorder.lineStyle(2, 0x111827, 1);
            neuronBorder.beginFill(0xFFFFFF, 0.001);
            neuronBorder.drawRect(
                (j * cellSize) + 1,
                (i * cellSize) + 1,
                cellSize - 2,
                cellSize - 2
            );
            neuronBorder.endFill();
            layer.addChild(neuronBorder);

            const text = new PIXI.Text(symbol, {
                fill: 0x1f2937,
                fontSize: Math.max(16, Math.floor(cellSize * 0.55)),
                fontWeight: 'bold',
                fontFamily: 'Consolas',
                align: 'center',
            });
            text.x = (j * cellSize) + (cellSize / 2) - (text.width / 2);
            text.y = (i * cellSize) + (cellSize / 2) - (text.height / 2);
            layer.addChild(text);
        }
    }

    function renderGrid() {
        const cols = normalize(widthInput.value || 5);
        const rows = normalize(heightInput.value || 5);
        const cellSize = fixedCellSize;
        const canvasWidth = cols * cellSize;
        const canvasHeight = rows * cellSize;
        filterOutOfBoundsNeurons(rows, cols);
        updateNeuronHiddenInput();

        if (!app) {
            app = new PIXI.Application({
                width: canvasWidth,
                height: canvasHeight,
                antialias: false,
                backgroundAlpha: 0
            });
            container.innerHTML = '';
            container.appendChild(app.view);
            app.view.style.cursor = 'pointer';
            app.view.addEventListener('click', function (event) {
                const rect = app.view.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const i = Math.floor(y / fixedCellSize);
                const j = Math.floor(x / fixedCellSize);

                const maxRows = normalize(heightInput.value || 5);
                const maxCols = normalize(widthInput.value || 5);
                if (i < 0 || j < 0 || i >= maxRows || j >= maxCols) {
                    return;
                }

                openNeuronModal(i, j);
            });
        } else {
            app.renderer.resize(canvasWidth, canvasHeight);
            app.stage.removeChildren();
        }

        const bg = new PIXI.Graphics();
        bg.beginFill(0xFFFFFF);
        bg.drawRect(0, 0, canvasWidth, canvasHeight);
        bg.endFill();
        app.stage.addChild(bg);

        const lines = new PIXI.Graphics();
        lines.lineStyle(1, 0x555555, 1);

        for (let c = 0; c <= cols; c++) {
            const x = c * cellSize;
            drawDashedLine(lines, x, 0, x, canvasHeight);
        }

        for (let r = 0; r <= rows; r++) {
            const y = r * cellSize;
            drawDashedLine(lines, 0, y, canvasWidth, y);
        }

        app.stage.addChild(lines);
        drawNeuronSymbols(app.stage, cellSize);
    }

    async function requestSaveNeuron(payload) {
        const response = await fetch(saveNeuronUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Errore durante il salvataggio neurone');
        }
        return data.neuron;
    }

    async function requestDeleteNeuron(payload) {
        const response = await fetch(deleteNeuronUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Errore durante la rimozione neurone');
        }
    }

    widthInput.addEventListener('input', renderGrid);
    heightInput.addEventListener('input', renderGrid);
    neuronTypeInput.addEventListener('change', toggleDetectionFieldsByType);
    neuronTargetTypeElementInput.addEventListener('change', function () {
        if (neuronTargetTypeElementInput.checked) {
            neuronTargetTypeEntityInput.checked = false;
        }
        toggleDetectionFieldsByType();
    });
    neuronTargetTypeEntityInput.addEventListener('change', function () {
        if (neuronTargetTypeEntityInput.checked) {
            neuronTargetTypeElementInput.checked = false;
        }
        toggleDetectionFieldsByType();
    });
    saveNeuronBtn.addEventListener('click', async function () {
        if (!selectedCell) {
            return;
        }

        const type = neuronTypeInput.value;
        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        let radius = null;
        let targetType = null;
        let targetElementId = null;
        let targetEntityId = null;
        if (type === typeDetection) {
            radius = Math.max(1, normalize(neuronRadiusInput.value || 1));
            if (neuronTargetTypeEntityInput.checked) {
                targetType = targetTypeEntity;
            } else if (neuronTargetTypeElementInput.checked) {
                targetType = targetTypeElement;
            }
            if (targetType === targetTypeElement) {
                const parsed = parseInt(neuronTargetElementIdInput.value, 10);
                targetElementId = Number.isNaN(parsed) ? null : parsed;
            }
        }

        saveNeuronBtn.disabled = true;
        try {
            const savedNeuron = await requestSaveNeuron({
                brain_grid_width: normalize(widthInput.value || 5),
                brain_grid_height: normalize(heightInput.value || 5),
                type: type,
                grid_i: i,
                grid_j: j,
                radius: radius,
                target_type: targetType,
                target_element_id: targetElementId,
                target_entity_id: targetEntityId,
            });

            neuronItems = neuronItems.filter((item) => !(Number(item.grid_i) === i && Number(item.grid_j) === j));
            neuronItems.push(savedNeuron);
            updateNeuronHiddenInput();
            renderGrid();
            $(neuronModalEl).modal('hide');
        } catch (error) {
            alert(error.message || 'Errore durante il salvataggio neurone');
        } finally {
            saveNeuronBtn.disabled = false;
        }
    });
    deleteNeuronBtn.addEventListener('click', async function () {
        if (!selectedCell) {
            return;
        }

        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        deleteNeuronBtn.disabled = true;
        try {
            await requestDeleteNeuron({
                grid_i: i,
                grid_j: j,
            });

            neuronItems = neuronItems.filter((item) => !(Number(item.grid_i) === i && Number(item.grid_j) === j));
            updateNeuronHiddenInput();
            renderGrid();
            $(neuronModalEl).modal('hide');
        } catch (error) {
            alert(error.message || 'Errore durante la rimozione neurone');
        } finally {
            deleteNeuronBtn.disabled = false;
        }
    });

    toggleDetectionFieldsByType();
    renderGrid();
});
</script>
@endpush
