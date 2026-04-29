<div class="alert alert-info">
    Definisci la dimensione della griglia per il cervello dell'elemento (usata lato PIXI.js).
</div>
@php
    $existingNeuronItems = $element->brain && $element->brain->neurons
        ? $element->brain->neurons->map(function ($n) {
            return [
                'id' => (int) $n->id,
                'type' => $n->type,
                'grid_i' => (int) $n->grid_i,
                'grid_j' => (int) $n->grid_j,
                'radius' => $n->radius !== null ? (int) $n->radius : null,
                'target_type' => $n->target_type,
                'target_element_id' => $n->target_element_id !== null ? (int) $n->target_element_id : null,
                'gene_life_id' => $n->gene_life_id !== null ? (int) $n->gene_life_id : null,
                'gene_attack_id' => $n->gene_attack_id !== null ? (int) $n->gene_attack_id : null,
            ];
        })->values()->all()
        : [];
    $existingNeuronLinks = $element->brain && $element->brain->neurons
        ? $element->brain->neurons->flatMap(function ($n) {
            return $n->outgoingLinks->map(function ($l) {
                return [
                    'id' => (int) $l->id,
                    'from_neuron_id' => (int) $l->from_neuron_id,
                    'to_neuron_id' => (int) $l->to_neuron_id,
                    'condition' => $l->condition,
                ];
            });
        })->unique(function ($l) {
            return $l['from_neuron_id'] . '_' . $l['to_neuron_id'];
        })->values()->all()
        : [];
    $existingCircuits = $element->brain && $element->brain->circuits
        ? $element->brain->circuits->map(function ($c) {
            return [
                'id' => $c->id,
                'uid' => $c->uid,
                'state' => $c->state,
                'start_neuron_id' => $c->start_neuron_id,
                'neuron_ids' => $c->details->pluck('neuron_id')->toArray(),
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
<input type="hidden" id="neuron_links" name="neuron_links" value="{{ old('neuron_links', json_encode($existingNeuronLinks)) }}">
<input type="hidden" id="neuron_circuits" name="neuron_circuits" value="{{ json_encode($existingCircuits) }}">

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
                <div class="form-group" id="neuron_gene_life_group" style="display:none;">
                    <label for="neuron_gene_life_id">Gene Vita</label>
                    <select class="form-control" id="neuron_gene_life_id">
                        <option value="">-- Seleziona Gene Vita --</option>
                        @foreach(($brainGenes ?? collect()) as $brainGene)
                            <option value="{{ $brainGene->id }}">{{ $brainGene->name }} (#{{ $brainGene->id }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="neuron_gene_attack_group" style="display:none;">
                    <label for="neuron_gene_attack_id">Gene Attacco</label>
                    <select class="form-control" id="neuron_gene_attack_id">
                        <option value="">-- Seleziona Gene Attacco --</option>
                        @foreach(($brainGenes ?? collect()) as $brainGene)
                            <option value="{{ $brainGene->id }}">{{ $brainGene->name }} (#{{ $brainGene->id }})</option>
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
    const neuronLinksInput = document.getElementById('neuron_links');
    const neuronCircuitsInput = document.getElementById('neuron_circuits');
    const neuronTypeInput = document.getElementById('neuron_type');
    const neuronRadiusInput = document.getElementById('neuron_radius');
    const neuronRadiusGroup = document.getElementById('neuron_radius_group');
    const neuronTargetTypeElementInput = document.getElementById('neuron_target_type_element');
    const neuronTargetTypeEntityInput = document.getElementById('neuron_target_type_entity');
    const neuronTargetTypeGroup = document.getElementById('neuron_target_type_group');
    const neuronTargetElementGroup = document.getElementById('neuron_target_element_group');
    const neuronTargetElementIdInput = document.getElementById('neuron_target_element_id');
    const neuronGeneLifeGroup = document.getElementById('neuron_gene_life_group');
    const neuronGeneLifeIdInput = document.getElementById('neuron_gene_life_id');
    const neuronGeneAttackGroup = document.getElementById('neuron_gene_attack_group');
    const neuronGeneAttackIdInput = document.getElementById('neuron_gene_attack_id');
    const selectedCellLabel = document.getElementById('selected_cell_label');
    const saveNeuronBtn = document.getElementById('btn_save_neuron');
    const deleteNeuronBtn = document.getElementById('btn_delete_neuron');
    const neuronModalEl = document.getElementById('brainNeuronModal');
    if (!widthInput || !heightInput || !container || !neuronItemsInput || !neuronLinksInput || !neuronTypeInput || !neuronRadiusInput || !neuronRadiusGroup || !neuronTargetTypeElementInput || !neuronTargetTypeEntityInput || !neuronTargetTypeGroup || !neuronTargetElementGroup || !neuronTargetElementIdInput || !neuronGeneLifeGroup || !neuronGeneLifeIdInput || !neuronGeneAttackGroup || !neuronGeneAttackIdInput || !selectedCellLabel || !saveNeuronBtn || !deleteNeuronBtn || !neuronModalEl) {
        console.warn('One or more required elements for the brain tab are missing.');
        return;
    }

    if (typeof PIXI === 'undefined') {
        console.error('PIXI.js is not loaded.');
        return;
    }

    const fixedCellSize = 36;
    const typeDetection = @json(\App\Models\Neuron::TYPE_DETECTION);
    const typePath = @json(\App\Models\Neuron::TYPE_PATH);
    const typeAttack = @json(\App\Models\Neuron::TYPE_ATTACK);
    const typeMovement = @json(\App\Models\Neuron::TYPE_MOVEMENT);
    const typeStart = @json(\App\Models\Neuron::TYPE_START);
    const typeEnd = @json(\App\Models\Neuron::TYPE_END);
    const targetTypeElement = @json(\App\Models\Neuron::TARGET_TYPE_ELEMENT);
    const targetTypeEntity = @json(\App\Models\Neuron::TARGET_TYPE_ENTITY);
    const typeSymbols = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    const typeLabels = @json(\App\Models\Neuron::TYPE_LABELS);
    const targetTypeLabels = @json(\App\Models\Neuron::TARGET_TYPE_LABELS);
    const portDetectionSuccess = @json(\App\Models\NeuronLink::PORT_DETECTION_SUCCESS);
    const portDetectionFailure = @json(\App\Models\NeuronLink::PORT_DETECTION_FAILURE);
    const portTrigger = @json(\App\Models\NeuronLink::PORT_TRIGGER);
    const saveNeuronUrl = @json(route('elements.brain.neurons.save', $element));
    const deleteNeuronUrl = @json(route('elements.brain.neurons.delete', $element));
    const saveNeuronLinkUrl = @json(route('elements.brain.neuron-links.save', $element));
    const deleteNeuronLinkUrl = @json(route('elements.brain.neuron-links.delete', $element));
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    let app = null;
    let selectedCell = null;
    let neuronItems = [];
    let neuronLinks = [];
    let isLinkDragging = false;
    let isNeuronDragging = false;
    let draggedNeuronId = null;
    let dragStartCell = null;
    let dragStartMousePos = null;
    let currentDragPos = null;
    let tempLineGraphics = null;
    let fromNeuronId = null;
    let fromAnchorCondition = null;
    let currentNeuronId = null;
    let tooltipText = null;
    let tooltipBg = null;
    let neuronCircuits = [];

    try {
        const parsed = JSON.parse(neuronItemsInput.value || '[]');
        neuronItems = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        neuronItems = [];
    }
    neuronLinks = @json($existingNeuronLinks);
    if (!Array.isArray(neuronLinks)) {
        neuronLinks = [];
    }
    
    try {
        const parsedCircuits = JSON.parse(neuronCircuitsInput ? neuronCircuitsInput.value : '[]');
        neuronCircuits = Array.isArray(parsedCircuits) ? parsedCircuits : [];
    } catch (e) {
        neuronCircuits = [];
    }

    const circuitColors = [0x3b82f6, 0x8b5cf6, 0xec4899, 0x10b981, 0xf59e0b, 0xef4444, 0x06b6d4, 0x84cc16];

    function normalize(value) {
        const parsed = parseInt(value, 10);
        if (Number.isNaN(parsed) || parsed < 1) return 1;
        return parsed;
    }

    function neuronKey(i, j) {
        return `${i}_${j}`;
    }

    function updateNeuronHiddenInput() {
        neuronItemsInput.value = JSON.stringify(neuronItems);
    }

    function updateNeuronLinksHiddenInput() {
        neuronLinksInput.value = JSON.stringify(neuronLinks);
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

    function findNeuronById(id) {
        return neuronItems.find((item) => Number(item.id) === Number(id)) || null;
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
            const sx = x1 + (ux * drawn);
            const sy = y1 + (uy * drawn);
            const len = Math.min(dash, distance - drawn);
            const ex = sx + (ux * len);
            const ey = sy + (uy * len);
            graphics.moveTo(sx, sy);
            graphics.lineTo(ex, ey);
            drawn += dash + gap;
        }
    }

    function resolveLinkCondition(link, fromNeuron) {
        if (!fromNeuron) return null;
        if (fromNeuron.type === typeDetection) {
            if (!link || !link.condition) return portDetectionSuccess;
            if (link.condition === 'found' || link.condition === 'main' || link.condition === portDetectionSuccess) return portDetectionSuccess;
            if (link.condition === 'not_found' || link.condition === 'else' || link.condition === portDetectionFailure) return portDetectionFailure;
            return link.condition;
        }
        return portTrigger;
    }

    function getRightAnchorPoint(neuron, cellSize, condition, overrideTopLeft = null) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = overrideTopLeft ? overrideTopLeft.x : (Number(neuron.grid_j) * cellSize);
        const topLeftY = overrideTopLeft ? overrideTopLeft.y : (Number(neuron.grid_i) * cellSize);
        const baseX = topLeftX + cellSize;

        if (neuron.type === typeDetection) {
            const topY = topLeftY + (cellSize * 0.3);
            const bottomY = topLeftY + (cellSize * 0.7);
            const useBottom = condition === portDetectionFailure;
            return { x: baseX, y: useBottom ? bottomY : topY };
        }

        return { x: baseX, y: topLeftY + (cellSize / 2) };
    }

    function getLeftAnchorPoint(neuron, cellSize, overrideTopLeft = null) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = overrideTopLeft ? overrideTopLeft.x : (Number(neuron.grid_j) * cellSize);
        const topLeftY = overrideTopLeft ? overrideTopLeft.y : (Number(neuron.grid_i) * cellSize);
        return { x: topLeftX, y: topLeftY + (cellSize / 2) };
    }

    function toggleDetectionFieldsByType() {
        const isDetection = neuronTypeInput.value === typeDetection;
        const isMovement = neuronTypeInput.value === typeMovement;
        const isAttack = neuronTypeInput.value === typeAttack;
        neuronRadiusGroup.style.display = (isDetection || isMovement) ? '' : 'none';
        neuronTargetTypeGroup.style.display = isDetection ? '' : 'none';
        neuronGeneLifeGroup.style.display = isAttack ? '' : 'none';
        neuronGeneAttackGroup.style.display = isAttack ? '' : 'none';
        if (!isDetection) {
            neuronTargetElementGroup.style.display = 'none';
            return;
        }
        neuronTargetElementGroup.style.display = neuronTargetTypeElementInput.checked ? '' : 'none';
    }

    function openNeuronModal(i, j) {
        selectedCell = { i, j };
        selectedCellLabel.textContent = `(${i}, ${j})`;

        const existing = findNeuronAtCell(i, j);
        if (existing) {
            currentNeuronId = existing.id;
            const inferredTargetType = existing.target_type
                || (existing.target_element_id != null ? targetTypeElement : null)
                || targetTypeElement;

            neuronTypeInput.value = existing.type;
            neuronRadiusInput.value = existing.radius != null ? Number(existing.radius) : 1;
            neuronTargetTypeElementInput.checked = inferredTargetType === targetTypeElement;
            neuronTargetTypeEntityInput.checked = inferredTargetType === targetTypeEntity;
            neuronTargetElementIdInput.value = existing.target_element_id != null ? String(existing.target_element_id) : '';
            neuronGeneLifeIdInput.value = existing.gene_life_id != null ? String(existing.gene_life_id) : '';
            neuronGeneAttackIdInput.value = existing.gene_attack_id != null ? String(existing.gene_attack_id) : '';
            deleteNeuronBtn.style.display = '';
        } else {
            currentNeuronId = null;
            neuronTypeInput.value = typeDetection;
            neuronRadiusInput.value = 1;
            neuronTargetTypeElementInput.checked = true;
            neuronTargetTypeEntityInput.checked = false;
            neuronTargetElementIdInput.value = '';
            neuronGeneLifeIdInput.value = '';
            neuronGeneAttackIdInput.value = '';
            deleteNeuronBtn.style.display = 'none';
        }

        toggleDetectionFieldsByType();
        $(neuronModalEl).modal('show');
    }

    function drawNeuronLinks(layer, cellSize) {
        for (const link of neuronLinks) {
            const fromN = findNeuronById(link.from_neuron_id);
            const toN = findNeuronById(link.to_neuron_id);
            if (!fromN || !toN) continue;

            const linkCondition = resolveLinkCondition(link, fromN);
            let fromPoint = getRightAnchorPoint(fromN, cellSize, linkCondition);
            let toPoint = getLeftAnchorPoint(toN, cellSize);
            let x1 = fromPoint.x;
            let y1 = fromPoint.y;
            let x2 = toPoint.x;
            let y2 = toPoint.y;

            if (isNeuronDragging && currentDragPos && draggedNeuronId) {
                if (Number(fromN.id) === Number(draggedNeuronId)) {
                    const overridePoint = getRightAnchorPoint(
                        fromN,
                        cellSize,
                        linkCondition,
                        { x: currentDragPos.x, y: currentDragPos.y }
                    );
                    x1 = overridePoint.x;
                    y1 = overridePoint.y;
                }
                if (Number(toN.id) === Number(draggedNeuronId)) {
                    const overridePoint = getLeftAnchorPoint(
                        toN,
                        cellSize,
                        { x: currentDragPos.x, y: currentDragPos.y }
                    );
                    x2 = overridePoint.x;
                    y2 = overridePoint.y;
                }
            }

            let lineColor = 0x16a34a; // Green (main)
            if (linkCondition === portDetectionFailure) {
                lineColor = 0xf97316; // Orange (else)
            }
            const line = new PIXI.Graphics();
            line.lineStyle(3, lineColor, 1);
            line.moveTo(x1, y1);
            line.lineTo(x2, y2);
            layer.addChild(line);

            const mx = (x1 + x2) / 2;
            const my = (y1 + y2) / 2;

            const deleteBtn = new PIXI.Graphics();
            deleteBtn.beginFill(0xdc3545);
            deleteBtn.drawCircle(0, 0, 9);
            deleteBtn.endFill();
            
            deleteBtn.lineStyle(2, 0xffffff, 1);
            deleteBtn.moveTo(-4, -4);
            deleteBtn.lineTo(4, 4);
            deleteBtn.moveTo(4, -4);
            deleteBtn.lineTo(-4, 4);

            deleteBtn.x = mx;
            deleteBtn.y = my;
            deleteBtn.eventMode = 'static';
            deleteBtn.cursor = 'pointer';

            deleteBtn.on('pointerdown', async (e) => {
                e.stopPropagation();
                if (!confirm('Vuoi eliminare questo collegamento?')) return;
                try {
                    await requestDeleteNeuronLink({
                        from_neuron_id: Number(link.from_neuron_id),
                        to_neuron_id: Number(link.to_neuron_id),
                    });
                    neuronLinks = neuronLinks.filter((l) => !(Number(l.from_neuron_id) === Number(link.from_neuron_id) && Number(l.to_neuron_id) === Number(link.to_neuron_id)));
                    updateNeuronLinksHiddenInput();
                    renderGrid();
                } catch (error) {
                    alert(error.message || 'Errore durante la rimozione del collegamento');
                }
            });
            layer.addChild(deleteBtn);
        }
    }

    function drawNeuronSymbols(layer, cellSize) {
        for (const neuron of neuronItems) {
            let i = Number(neuron.grid_i);
            let j = Number(neuron.grid_j);
            const isDragging = Number(neuron.id) === Number(draggedNeuronId);

            if (isDragging && currentDragPos) {
                j = currentDragPos.x / cellSize;
                i = currentDragPos.y / cellSize;
            }

            const symbol = typeSymbols[neuron.type] || '?';
            
            const isSelectedFrom = Number(neuron.id) === Number(fromNeuronId);
            
            // Find circuits this neuron belongs to
            const belongsToCircuits = neuronCircuits.filter(c => c.neuron_ids && c.neuron_ids.includes(Number(neuron.id)));
            
            // Draw circuit indicators (concentric colored borders)
            belongsToCircuits.forEach((circuit, index) => {
                const colorIdx = neuronCircuits.indexOf(circuit) % circuitColors.length;
                const cColor = circuitColors[colorIdx];
                const offset = 3 + (index * 4);
                const cBorder = new PIXI.Graphics();
                cBorder.lineStyle(2, cColor, 0.8);
                cBorder.drawRect((j * cellSize) + 1 - offset, (i * cellSize) + 1 - offset, cellSize - 2 + (offset * 2), cellSize - 2 + (offset * 2));
                layer.addChild(cBorder);
            });

            const neuronBorder = new PIXI.Graphics();
            neuronBorder.lineStyle(2, isSelectedFrom ? 0xdc2626 : 0x111827, 1);
            neuronBorder.beginFill(0xFFFFFF, 1);
            neuronBorder.drawRect((j * cellSize) + 1, (i * cellSize) + 1, cellSize - 2, cellSize - 2);
            neuronBorder.endFill();

            neuronBorder.eventMode = 'static';
            neuronBorder.cursor = 'grab';
            neuronBorder.on('pointerdown', (e) => {
                e.stopPropagation();
                isNeuronDragging = true;
                draggedNeuronId = neuron.id;
                dragStartCell = { i: Number(neuron.grid_i), j: Number(neuron.grid_j) };
                
                dragStartMousePos = { x: e.global.x, y: e.global.y };
                app.view.style.cursor = 'grabbing';
                renderGrid(); // Redraw immediately to show ghost effect
            });
            neuronBorder.on('pointerover', () => {
                if (!tooltipText || !tooltipBg) return;
                const label = typeLabels[neuron.type] || neuron.type || 'Neurone';
                const lines = [label];
                lines.push(`Cella: (${Number(neuron.grid_i)}, ${Number(neuron.grid_j)})`);
                if (neuron.type === typeDetection) {
                    const targetLabel = targetTypeLabels[neuron.target_type] || '-';
                    lines.push(`Raggio: ${neuron.radius != null ? neuron.radius : '-'}`);
                    lines.push(`Target: ${targetLabel}`);
                    if (neuron.target_type === targetTypeElement) {
                        lines.push(`Id Element: ${neuron.target_element_id != null ? neuron.target_element_id : '-'}`);
                    }
                } else if (neuron.type === typeAttack) {
                    lines.push(`Gene Vita: ${neuron.gene_life_id != null ? neuron.gene_life_id : '-'}`);
                    lines.push(`Gene Attacco: ${neuron.gene_attack_id != null ? neuron.gene_attack_id : '-'}`);
                } else if (neuron.type === typeMovement) {
                    lines.push(`Raggio: ${neuron.radius != null ? neuron.radius : '-'}`);
                }

                tooltipText.text = lines.join('\n');
                tooltipText.visible = true;
                tooltipBg.visible = true;
                const paddingX = 6;
                const paddingY = 4;
                tooltipBg.clear();
                tooltipBg.lineStyle(1, 0x000000, 1);
                tooltipBg.beginFill(0xFFFFFF, 1);
                tooltipBg.drawRect(0, 0, tooltipText.width + (paddingX * 2), tooltipText.height + (paddingY * 2));
                tooltipBg.endFill();
                tooltipBg.x = tooltipText.x - paddingX;
                tooltipBg.y = tooltipText.y - paddingY;
            });
            neuronBorder.on('pointerout', () => {
                if (tooltipText) tooltipText.visible = false;
                if (tooltipBg) tooltipBg.visible = false;
            });
            neuronBorder.on('pointermove', (e) => {
                if (!tooltipText || !tooltipText.visible || !tooltipBg) return;
                const paddingX = 6;
                const paddingY = 4;
                const offsetX = 12;
                const offsetY = 12;
                const rect = app && app.view ? app.view.getBoundingClientRect() : null;
                const maxX = rect ? rect.width : (app ? app.renderer.width : Infinity);
                const maxY = rect ? rect.height : (app ? app.renderer.height : Infinity);
                const tooltipW = tooltipText.width + (paddingX * 2);
                const tooltipH = tooltipText.height + (paddingY * 2);

                let x = e.global.x + offsetX;
                let y = e.global.y + offsetY;
                if (x + tooltipW > maxX) x = Math.max(0, maxX - tooltipW);
                if (y + tooltipH > maxY) y = Math.max(0, maxY - tooltipH);

                tooltipText.x = x;
                tooltipText.y = y;
                tooltipBg.x = tooltipText.x - paddingX;
                tooltipBg.y = tooltipText.y - paddingY;
            });

            layer.addChild(neuronBorder);

            const text = new PIXI.Text(symbol, {
                fill: 0x1f2937,
                fontSize: Math.max(16, Math.floor(cellSize * 0.55)),
                fontWeight: 'bold',
                fontFamily: 'Consolas',
                align: 'center',
            });
            text.eventMode = 'none'; // Ensure clicks pass through to border
            text.alpha = 1;
            text.x = (j * cellSize) + (cellSize / 2) - (text.width / 2);
            text.y = (i * cellSize) + (cellSize / 2) - (text.height / 2);
            layer.addChild(text);

            if (neuron.type === typeStart) {
                const startCircuit = neuronCircuits.find(c => Number(c.start_neuron_id) === Number(neuron.id));
                if (startCircuit) {
                    const badge = new PIXI.Graphics();
                    const bColor = startCircuit.state === 'closed' ? 0x10b981 : 0xf59e0b; // Green/Orange
                    badge.beginFill(bColor);
                    badge.lineStyle(1, 0xffffff, 1);
                    badge.drawCircle((j * cellSize) + 8, (i * cellSize) + 8, 5);
                    badge.endFill();
                    layer.addChild(badge);
                }
            }

            const hasLeftAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeAttack || neuron.type === typeMovement || neuron.type === typeEnd;
            const hasRightAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeStart || neuron.type === typeAttack || neuron.type === typeMovement;

            if (hasLeftAnchor) {
                const leftAnchor = new PIXI.Graphics();
                leftAnchor.beginFill(0x16a34a);
                leftAnchor.lineStyle(2, 0xffffff, 1);
                leftAnchor.drawCircle((j * cellSize), (i * cellSize) + (cellSize / 2), 8);
                leftAnchor.endFill();
                leftAnchor.alpha = isDragging ? 0.5 : 1;
                leftAnchor.eventMode = 'static';
                leftAnchor.cursor = 'default';
                layer.addChild(leftAnchor);
            }

            if (hasRightAnchor) {
                const baseX = (j * cellSize) + cellSize;
                const baseY = i * cellSize;

                if (neuron.type === typeDetection) {
                    const topAnchorY = baseY + (cellSize * 0.3);
                    const bottomAnchorY = baseY + (cellSize * 0.7);
                    const anchorConfigs = [
                        { y: topAnchorY, condition: portDetectionSuccess, color: 0x16a34a },
                        { y: bottomAnchorY, condition: portDetectionFailure, color: 0xf97316 },
                    ];

                    for (const anchorConfig of anchorConfigs) {
                        const rightAnchor = new PIXI.Graphics();
                        rightAnchor.beginFill(anchorConfig.color);
                        rightAnchor.lineStyle(2, 0xffffff, 1);
                        rightAnchor.drawCircle(baseX, anchorConfig.y, 8);
                        rightAnchor.endFill();
                        rightAnchor.alpha = isDragging ? 0.5 : 1;
                        rightAnchor.eventMode = 'static';
                        rightAnchor.cursor = 'crosshair';

                        rightAnchor.on('pointerdown', (e) => {
                            e.stopPropagation();
                            fromNeuronId = neuron.id;
                            fromAnchorCondition = anchorConfig.condition;
                            isLinkDragging = true;
                            if (!tempLineGraphics) {
                                tempLineGraphics = new PIXI.Graphics();
                                layer.addChild(tempLineGraphics);
                            }
                        });
                        layer.addChild(rightAnchor);
                    }
                } else {
                    const rightAnchor = new PIXI.Graphics();
                    rightAnchor.beginFill(0x16a34a);
                    rightAnchor.lineStyle(2, 0xffffff, 1);
                    rightAnchor.drawCircle(baseX, baseY + (cellSize / 2), 8);
                    rightAnchor.endFill();
                    rightAnchor.alpha = isDragging ? 0.5 : 1;
                    rightAnchor.eventMode = 'static';
                    rightAnchor.cursor = 'crosshair';

                    rightAnchor.on('pointerdown', (e) => {
                        e.stopPropagation();
                        fromNeuronId = neuron.id;
                        fromAnchorCondition = portTrigger;
                        isLinkDragging = true;
                        if (!tempLineGraphics) {
                            tempLineGraphics = new PIXI.Graphics();
                            layer.addChild(tempLineGraphics);
                        }
                    });
                    layer.addChild(rightAnchor);
                }
            }
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
                antialias: true,
                backgroundAlpha: 1,
                backgroundColor: 0xffffff
            });
            container.innerHTML = '';
            container.appendChild(app.view);
            
            app.stage.eventMode = 'static';
            app.stage.hitArea = new PIXI.Rectangle(0, 0, canvasWidth, canvasHeight);

            app.stage.on('pointerdown', async (event) => {
                if (isLinkDragging || isNeuronDragging) return;
                
                const i = Math.floor(event.global.y / fixedCellSize);
                const j = Math.floor(event.global.x / fixedCellSize);
                const maxRows = normalize(heightInput.value || 5);
                const maxCols = normalize(widthInput.value || 5);
                if (i < 0 || j < 0 || i >= maxRows || j >= maxCols) return;

                openNeuronModal(i, j);
            });

        } else {
            app.renderer.resize(canvasWidth, canvasHeight);
            app.stage.removeChildren();
            tempLineGraphics = null;
        }

        // Draw background rectangle to ensure it's not transparent
        const bg = new PIXI.Graphics();
        bg.beginFill(0xFFFFFF);
        bg.drawRect(0, 0, canvasWidth, canvasHeight);
        bg.endFill();
        app.stage.addChild(bg);

        const lines = new PIXI.Graphics();
        lines.lineStyle(1, 0xdddddd, 1); // Lighter gray for better visibility on white
        for (let c = 0; c <= cols; c++) {
            const x = c * cellSize;
            drawDashedLine(lines, x, 0, x, canvasHeight);
        }
        for (let r = 0; r <= rows; r++) {
            const y = r * cellSize;
            drawDashedLine(lines, 0, y, canvasWidth, y);
        }
        app.stage.addChild(lines);

        const linksLayer = new PIXI.Container();
        app.stage.addChild(linksLayer);
        drawNeuronLinks(linksLayer, cellSize);

        const symbolsLayer = new PIXI.Container();
        app.stage.addChild(symbolsLayer);
        drawNeuronSymbols(symbolsLayer, cellSize);

        tooltipBg = new PIXI.Graphics();
        tooltipBg.visible = false;
        tooltipBg.zIndex = 19999;
        app.stage.addChild(tooltipBg);

        tooltipText = new PIXI.Text('', {
            fill: 0x000000,
            fontSize: 12,
            fontWeight: 'bold',
            fontFamily: 'Consolas',
            align: 'left',
        });
        tooltipText.visible = false;
        tooltipText.zIndex = 20000;
        app.stage.addChild(tooltipText);
        app.stage.sortChildren();
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
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante il salvataggio neurone');
        if (data.circuits) neuronCircuits = data.circuits;
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
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante la rimozione neurone');
        if (data.circuits) neuronCircuits = data.circuits;
    }

    async function requestSaveNeuronLink(payload) {
        const response = await fetch(saveNeuronLinkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante il salvataggio collegamento');
        if (data.circuits) neuronCircuits = data.circuits;
        return data.link;
    }

    async function requestDeleteNeuronLink(payload) {
        const response = await fetch(deleteNeuronLinkUrl, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Errore durante la rimozione collegamento');
        if (data.circuits) neuronCircuits = data.circuits;
    }

    widthInput.addEventListener('input', renderGrid);
    heightInput.addEventListener('input', renderGrid);
    neuronTypeInput.addEventListener('change', toggleDetectionFieldsByType);
    neuronTargetTypeElementInput.addEventListener('change', function () {
        if (neuronTargetTypeElementInput.checked) neuronTargetTypeEntityInput.checked = false;
        toggleDetectionFieldsByType();
    });
    neuronTargetTypeEntityInput.addEventListener('change', function () {
        if (neuronTargetTypeEntityInput.checked) neuronTargetTypeElementInput.checked = false;
        toggleDetectionFieldsByType();
    });

    saveNeuronBtn.addEventListener('click', async function () {
        if (!selectedCell) return;

        const type = neuronTypeInput.value;
        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        let radius = null;
        let targetType = null;
        let targetElementId = null;
        let geneLifeId = null;
        let geneAttackId = null;
        if (type === typeDetection) {
            radius = Math.max(1, normalize(neuronRadiusInput.value || 1));
            if (neuronTargetTypeEntityInput.checked) targetType = targetTypeEntity;
            else if (neuronTargetTypeElementInput.checked) targetType = targetTypeElement;
            if (targetType === targetTypeElement) {
                const parsed = parseInt(neuronTargetElementIdInput.value, 10);
                targetElementId = Number.isNaN(parsed) ? null : parsed;
            }
        } else if (type === typeMovement) {
            radius = Math.max(1, normalize(neuronRadiusInput.value || 1));
        } else if (type === typeAttack) {
            const parsedLife = parseInt(neuronGeneLifeIdInput.value, 10);
            const parsedAttack = parseInt(neuronGeneAttackIdInput.value, 10);
            geneLifeId = Number.isNaN(parsedLife) ? null : parsedLife;
            geneAttackId = Number.isNaN(parsedAttack) ? null : parsedAttack;
            if (geneLifeId == null || geneAttackId == null) {
                alert('Per il neurone Attacco devi selezionare Gene Vita e Gene Attacco');
                return;
            }
        }

        saveNeuronBtn.disabled = true;
        try {
            const savedNeuron = await requestSaveNeuron({
                id: currentNeuronId,
                brain_grid_width: normalize(widthInput.value || 5),
                brain_grid_height: normalize(heightInput.value || 5),
                type: type,
                grid_i: i,
                grid_j: j,
                radius: radius,
                target_type: targetType,
                target_element_id: targetElementId,
                gene_life_id: geneLifeId,
                gene_attack_id: geneAttackId,
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
        if (!selectedCell) return;
        const i = Number(selectedCell.i);
        const j = Number(selectedCell.j);
        const neuron = findNeuronAtCell(i, j);

        deleteNeuronBtn.disabled = true;
        try {
            await requestDeleteNeuron({ grid_i: i, grid_j: j });
            neuronItems = neuronItems.filter((item) => !(Number(item.grid_i) === i && Number(item.grid_j) === j));
            if (neuron) {
                neuronLinks = neuronLinks.filter((l) => Number(l.from_neuron_id) !== Number(neuron.id) && Number(l.to_neuron_id) !== Number(neuron.id));
            }
            updateNeuronHiddenInput();
            updateNeuronLinksHiddenInput();
            renderGrid();
            $(neuronModalEl).modal('hide');
        } catch (error) {
            alert(error.message || 'Errore durante la rimozione neurone');
        } finally {
            deleteNeuronBtn.disabled = false;
        }
    });

    const mainForm = container.closest('form');
    if (mainForm) {
        mainForm.addEventListener('submit', function () {
            updateNeuronHiddenInput();
            updateNeuronLinksHiddenInput();
        });
    }

    window.addEventListener('pointermove', (event) => {
        if (!app || !app.view) return;
        const rect = app.view.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        let hoveredNeuron = null;
        if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
            const i = Math.floor(y / fixedCellSize);
            const j = Math.floor(x / fixedCellSize);
            hoveredNeuron = findNeuronAtCell(i, j);
            app.view.style.cursor = (!isLinkDragging && hoveredNeuron) ? 'crosshair' : 'default';
        } else {
            app.view.style.cursor = 'default';
        }

        if (isLinkDragging && fromNeuronId) {
            const fromN = findNeuronById(fromNeuronId);
            if (!fromN) return;

            const resolvedCondition = resolveLinkCondition({ condition: fromAnchorCondition }, fromN);
            const startPoint = getRightAnchorPoint(fromN, fixedCellSize, resolvedCondition);
            const startX = startPoint.x;
            const startY = startPoint.y;

            if (!tempLineGraphics) {
                tempLineGraphics = new PIXI.Graphics();
                app.stage.addChild(tempLineGraphics);
            }
            
            tempLineGraphics.clear();
            
            let endX = x;
            let endY = y;
            let lineColor = 0x16a34a; // Green (main)
            if (resolvedCondition === portDetectionFailure) {
                lineColor = 0xf97316; // Orange (else)
            }
            let isOverValidTarget = false;

            if (hoveredNeuron && Number(hoveredNeuron.id) !== Number(fromNeuronId)) {
                const hasLeftAnchor = hoveredNeuron.type === typeDetection || hoveredNeuron.type === typePath || hoveredNeuron.type === typeAttack || hoveredNeuron.type === typeMovement || hoveredNeuron.type === typeEnd;
                if (hasLeftAnchor) {
                    isOverValidTarget = true;
                    lineColor = resolvedCondition === linkConditionElse ? 0xf59e0b : 0x22c55e; // Orange/Green (valid target)
                    // Snap to the left anchor of the target neuron
                    endX = Number(hoveredNeuron.grid_j) * fixedCellSize;
                    endY = (Number(hoveredNeuron.grid_i) * fixedCellSize) + (fixedCellSize / 2);
                }
            }

            if (isOverValidTarget) {
                // Draw a solid line when snapped
                tempLineGraphics.lineStyle(4, lineColor, 1);
                tempLineGraphics.moveTo(startX, startY);
                tempLineGraphics.lineTo(endX, endY);
                
                // Add a small pulse effect circle at target
                tempLineGraphics.beginFill(lineColor, 0.3);
                tempLineGraphics.drawCircle(endX, endY, 12);
                tempLineGraphics.endFill();
            } else {
                // Draw dashed line when searching
                tempLineGraphics.lineStyle(3, lineColor, 0.8);
                drawDashedLine(tempLineGraphics, startX, startY, endX, endY, 8, 8);
            }
        }

        if (isNeuronDragging && draggedNeuronId) {
            const dragN = findNeuronById(draggedNeuronId);
            if (!dragN) return;

            const targetI = Math.floor(y / fixedCellSize);
            const targetJ = Math.floor(x / fixedCellSize);
            
            // Move visuals: exact mouse snap minus center offset
            currentDragPos = { 
                x: x - (fixedCellSize / 2), 
                y: y - (fixedCellSize / 2) 
            };

            if (!tempLineGraphics) {
                tempLineGraphics = new PIXI.Graphics();
                app.stage.addChild(tempLineGraphics);
            }
            tempLineGraphics.clear();

            const maxRows = normalize(heightInput.value || 5);
            const maxCols = normalize(widthInput.value || 5);

            // Draw placement helper
            if (targetI >= 0 && targetJ >= 0 && targetI < maxRows && targetJ < maxCols) {
                const isOccupied = findNeuronAtCell(targetI, targetJ) && (targetI !== dragStartCell.i || targetJ !== dragStartCell.j);
                tempLineGraphics.lineStyle(2, isOccupied ? 0xdc3545 : 0x28a745, 1);
                tempLineGraphics.beginFill(isOccupied ? 0xdc3545 : 0x28a745, 0.15);
                tempLineGraphics.drawRect(targetJ * fixedCellSize + 2, targetI * fixedCellSize + 2, fixedCellSize - 4, fixedCellSize - 4);
                tempLineGraphics.endFill();
            }
            
            // Re-draw everything to move the neuron and its links live
            renderGrid();
        }
    });
    
    window.addEventListener('pointerup', async (event) => {
        const wasDraggingLink = isLinkDragging;
        const wasDraggingNeuron = isNeuronDragging;
        const oldId = draggedNeuronId;
        const startCell = dragStartCell;

        isLinkDragging = false;
        isNeuronDragging = false;
        currentDragPos = null;
        draggedNeuronId = null;
        dragStartCell = null;
        
        if (app && app.view) {
            app.view.style.cursor = 'default';
        }
        
        if (tempLineGraphics) {
            tempLineGraphics.clear();
        }

        if (!app || !app.view) return;
        const rect = app.view.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        // Handle dropping a neuron
        if (wasDraggingNeuron && oldId) {
            const targetI = Math.floor(y / fixedCellSize);
            const targetJ = Math.floor(x / fixedCellSize);
            const maxRows = normalize(heightInput.value || 5);
            const maxCols = normalize(widthInput.value || 5);
            
            const dist = dragStartMousePos ? Math.sqrt(Math.pow(x - dragStartMousePos.x, 2) + Math.pow(y - dragStartMousePos.y, 2)) : 0;
            const shifted = targetI !== startCell.i || targetJ !== startCell.j;

            if (shifted && targetI >= 0 && targetJ >= 0 && targetI < maxRows && targetJ < maxCols && !findNeuronAtCell(targetI, targetJ)) {
                const neuron = findNeuronById(oldId);
                if (neuron) {
                    try {
                        // Persist immediately by updating the existing record
                        const savedN = await requestSaveNeuron({
                            id: oldId, // Pass the ID to perform an update
                            grid_i: targetI,
                            grid_j: targetJ,
                            type: neuron.type,
                            radius: neuron.radius,
                            target_type: neuron.target_type,
                            target_element_id: neuron.target_element_id,
                            gene_life_id: neuron.gene_life_id,
                            gene_attack_id: neuron.gene_attack_id
                        });
                        
                        // ID remains the same, but we update the local grid coordinates
                        neuron.grid_i = targetI;
                        neuron.grid_j = targetJ;

                        updateNeuronHiddenInput();
                        updateNeuronLinksHiddenInput();
                    } catch (error) {
                        alert(error.message || 'Errore durante lo spostamento del neurone');
                    }
                }
            } else if (!shifted || dist < 5) {
                openNeuronModal(startCell.i, startCell.j);
            }
            dragStartMousePos = null;
            renderGrid();
            return;
        }
        // Handle dropping a link
        if (wasDraggingLink && fromNeuronId) {
            if (x >= 0 && x <= rect.width && y >= 0 && y <= rect.height) {
                const i = Math.floor(y / fixedCellSize);
                const j = Math.floor(x / fixedCellSize);
                const toNeuron = findNeuronAtCell(i, j);

                if (toNeuron && Number(toNeuron.id) !== Number(fromNeuronId)) {
                    const hasLeftAnchor = toNeuron.type === typeDetection || toNeuron.type === typePath || toNeuron.type === typeAttack || toNeuron.type === typeMovement || toNeuron.type === typeEnd;
                    if (hasLeftAnchor) {
                        try {
                            const fromNeuron = findNeuronById(fromNeuronId);
                            const payload = {
                                from_neuron_id: Number(fromNeuronId),
                                to_neuron_id: Number(toNeuron.id),
                                condition: fromAnchorCondition || (fromNeuron && fromNeuron.type === typeDetection ? portDetectionSuccess : portTrigger)
                            };
                            const savedLink = await requestSaveNeuronLink(payload);
                            const exists = neuronLinks.some((l) => Number(l.from_neuron_id) === Number(savedLink.from_neuron_id) && Number(l.to_neuron_id) === Number(savedLink.to_neuron_id));
                            if (!exists) neuronLinks.push(savedLink);
                            updateNeuronLinksHiddenInput();
                        } catch (error) {
                            alert(error.message || 'Errore durante il collegamento');
                        }
                    }
                }
            }
            fromNeuronId = null;
            fromAnchorCondition = null;
            renderGrid();
            return;
        }
    });

    toggleDetectionFieldsByType();
    updateNeuronLinksHiddenInput();
    renderGrid();
});
</script>
@endpush
