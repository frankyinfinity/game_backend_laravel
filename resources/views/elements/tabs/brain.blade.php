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
                'element_has_rule_chimical_element_id' => $n->element_has_rule_chimical_element_id !== null ? (int) $n->element_has_rule_chimical_element_id : null,
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
                    'color' => $l->color,
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="brainNeuronModalLabel">Configura Neurone</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="neuronModalTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="edit-tab" data-toggle="tab" href="#edit" role="tab" aria-controls="edit" aria-selected="true">Modifica Neurone</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="links-tab" data-toggle="tab" href="#links" role="tab" aria-controls="links" aria-selected="false">Collegamenti</a>
                    </li>
                </ul>
                <div class="tab-content" id="neuronModalTabContent">
                    <div class="tab-pane fade show active" id="edit" role="tabpanel" aria-labelledby="edit-tab">
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
                <div class="form-group" id="neuron_rule_chimical_element_group" style="display:none;">
                    <label for="neuron_element_has_rule_chimical_element_id">Regola Elemento Chimico</label>
                    <select class="form-control" id="neuron_element_has_rule_chimical_element_id">
                        <option value="">-- Seleziona Regola --</option>
                        @foreach(($allRuleChimicalElements ?? collect()) as $rule)
                            <option value="{{ $rule->id }}">{{ $rule->title }}</option>
                        @endforeach
                    </select>
                </div>
                    </div>
                    <div class="tab-pane fade" id="links" role="tabpanel" aria-labelledby="links-tab">
                        <div id="neuron-links-container">
                            <!-- Links will be populated here -->
                        </div>
                    </div>
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
    const neuronRuleChimicalElementGroup = document.getElementById('neuron_rule_chimical_element_group');
    const neuronRuleChimicalElementIdInput = document.getElementById('neuron_element_has_rule_chimical_element_id');
    const selectedCellLabel = document.getElementById('selected_cell_label');
    const saveNeuronBtn = document.getElementById('btn_save_neuron');
    const deleteNeuronBtn = document.getElementById('btn_delete_neuron');
    const neuronModalEl = document.getElementById('brainNeuronModal');
    if (!widthInput || !heightInput || !container || !neuronItemsInput || !neuronLinksInput || !neuronTypeInput || !neuronRadiusInput || !neuronRadiusGroup || !neuronTargetTypeElementInput || !neuronTargetTypeEntityInput || !neuronTargetTypeGroup || !neuronTargetElementGroup || !neuronTargetElementIdInput || !neuronGeneLifeGroup || !neuronGeneLifeIdInput || !neuronGeneAttackGroup || !neuronGeneAttackIdInput || !neuronRuleChimicalElementGroup || !neuronRuleChimicalElementIdInput || !selectedCellLabel || !saveNeuronBtn || !deleteNeuronBtn || !neuronModalEl) {
        console.warn('One or more required elements for the brain tab are missing.');
        return;
    }

    if (typeof PIXI === 'undefined') {
        console.error('PIXI.js is not loaded.');
        return;
    }

     const fixedCellSize = 40;
    const typeDetection = @json(\App\Models\Neuron::TYPE_DETECTION);
    const typePath = @json(\App\Models\Neuron::TYPE_PATH);
    const typeAttack = @json(\App\Models\Neuron::TYPE_ATTACK);
    const typeMovement = @json(\App\Models\Neuron::TYPE_MOVEMENT);
    const typeStart = @json(\App\Models\Neuron::TYPE_START);
    const typeEnd = @json(\App\Models\Neuron::TYPE_END);
    const typeReadChimicalElement = @json(\App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT);
    const targetTypeElement = @json(\App\Models\Neuron::TARGET_TYPE_ELEMENT);
    const targetTypeEntity = @json(\App\Models\Neuron::TARGET_TYPE_ENTITY);
    const typeSymbols = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    const typeLabels = @json(\App\Models\Neuron::TYPE_LABELS);
    const targetTypeLabels = @json(\App\Models\Neuron::TARGET_TYPE_LABELS);
    const portDetectionSuccess = @json(\App\Models\NeuronLink::PORT_DETECTION_SUCCESS);
    const portDetectionFailure = @json(\App\Models\NeuronLink::PORT_DETECTION_FAILURE);
     const portTrigger = @json(\App\Models\NeuronLink::PORT_TRIGGER);
     const DEFAULT_CHIMICAL_ELEMENT = @json(\App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT);
     const portColors = @json(\App\Models\NeuronLink::PORT_COLORS);
    const saveNeuronUrl = @json(route('elements.brain.neurons.save', $element));
    const deleteNeuronUrl = @json(route('elements.brain.neurons.delete', $element));
    const saveNeuronLinkUrl = @json(route('elements.brain.neuron-links.save', $element));
    const deleteNeuronLinkUrl = @json(route('elements.brain.neuron-links.delete', $element));
    const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
    const allRuleChimicalElements = @json($allRuleChimicalElements);

    let app = null;
    let selectedCell = null;
    let neuronItems = [];
    let neuronLinks = [];
    let currentNeuronId = null;
    let tooltipText = null;
    let tooltipBg = null;
    let neuronCircuits = [];
    let draggedNeuron = null;
    let draggedNeuronLayer = null;
    let draggedNeuronOriginalI = null;
    let draggedNeuronOriginalJ = null;
    let draggedOffsetX = 0;
    let draggedOffsetY = 0;
    let dragStarted = false;

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

    function populateLinksTab(neuronId) {
        const neuron = findNeuronById(neuronId);
        if (!neuron) return;

        const container = document.getElementById('neuron-links-container');
        container.innerHTML = '';

        const outputConditions = getOutputConditions(neuron);

        for (let i = 0; i < outputConditions.length; i++) {
            const condition = outputConditions[i];
            const link = neuronLinks.find(l => l.from_neuron_id === neuronId && l.condition === condition);

            const div = document.createElement('div');
            div.className = 'form-group';

            const label = document.createElement('label');
            label.textContent = condition;
            div.appendChild(label);

            const select = document.createElement('select');
            select.className = 'form-control link-target';
            select.setAttribute('data-condition', condition);

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Nessun collegamento --';
            select.appendChild(defaultOption);

            for (let j = 0; j < neuronItems.length; j++) {
                const n = neuronItems[j];
                if (n.id === neuronId) continue;

                const option = document.createElement('option');
                option.value = n.id;
                option.textContent = `#${n.id} (${n.grid_i},${n.grid_j}) - ${typeLabels[n.type] || n.type}`;
                if (link && link.to_neuron_id == n.id) {
                    option.selected = true;
                }
                select.appendChild(option);
            }

            div.appendChild(select);
            container.appendChild(div);
        }

        const saveBtn = document.createElement('button');
        saveBtn.type = 'button';
        saveBtn.id = 'btn_save_links';
        saveBtn.className = 'btn btn-primary mt-3';
        saveBtn.textContent = 'Salva Collegamenti';
        saveBtn.onclick = function() { saveLinks(neuronId); };
        container.appendChild(saveBtn);
    }

    function getOutputConditions(neuron) {
        if (neuron.type === typeDetection) {
            return [portDetectionSuccess, portDetectionFailure];
        } else if (neuron.type === typeReadChimicalElement) {
            const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(neuron.element_has_rule_chimical_element_id));
            if (rule && rule.details) {
                const conditions = rule.details.map(d => `[${d.min}/${d.max}]`);
                conditions.push(DEFAULT_CHIMICAL_ELEMENT);
                return conditions;
            }
            return [DEFAULT_CHIMICAL_ELEMENT];
        } else {
            return [portTrigger];
        }
    }

    function saveLinks(neuronId) {
        const selects = document.querySelectorAll('.link-target');
        const operations = [];

        // Find source neuron to determine default color for new links
        const sourceNeuron = neuronItems.find(n => n.id === neuronId);

        selects.forEach(select => {
            const condition = select.dataset.condition;
            const targetId = select.value ? Number(select.value) : null;
            const existingLink = neuronLinks.find(l => l.from_neuron_id == neuronId && l.condition === condition);

            if (targetId) {
                if (existingLink) {
                    if (existingLink.to_neuron_id !== targetId) {
                        // Update existing link
                        operations.push(
                            requestSaveNeuronLink({
                                from_neuron_id: neuronId,
                                to_neuron_id: targetId,
                                condition: condition,
                                color: existingLink.color
                            }).then(response => {
                                if (response && response.link) {
                                    Object.assign(existingLink, response.link);
                                } else {
                                    existingLink.to_neuron_id = targetId;
                                }
                            })
                        );
                    }
                } else {
                    // Create new link
                    let linkColor = null;
                    if (sourceNeuron && sourceNeuron.type === typeReadChimicalElement) {
                        const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(sourceNeuron.element_has_rule_chimical_element_id));
                        if (rule && rule.details) {
                            const detail = rule.details.find(d => `[${d.min}/${d.max}]` === condition);
                            if (detail && detail.color) linkColor = detail.color;
                        }
                        if (condition === DEFAULT_CHIMICAL_ELEMENT) linkColor = '#6b7280';
                    } else if (condition === portDetectionFailure) {
                        linkColor = '#' + portColors[portDetectionFailure].toString(16).padStart(6, '0');
                    } else {
                        linkColor = '#' + portColors[portTrigger].toString(16).padStart(6, '0');
                    }

                    operations.push(
                        requestSaveNeuronLink({
                            from_neuron_id: neuronId,
                            to_neuron_id: targetId,
                            condition: condition,
                            color: linkColor
                        }).then(response => {
                            if (response && response.link) {
                                neuronLinks.push(response.link);
                            } else {
                                neuronLinks.push({
                                    from_neuron_id: neuronId,
                                    to_neuron_id: targetId,
                                    condition: condition,
                                    color: linkColor,
                                });
                            }
                        })
                    );
                }
            } else {
                if (existingLink) {
                    operations.push(
                        requestDeleteNeuronLink({
                            from_neuron_id: neuronId,
                            to_neuron_id: existingLink.to_neuron_id
                        }).then(() => {
                            neuronLinks = neuronLinks.filter(l => l !== existingLink);
                        })
                    );
                }
            }
        });

        const saveBtn = document.getElementById('btn_save_links');
        if (saveBtn) saveBtn.disabled = true;

        Promise.all(operations)
            .then(() => {
                updateNeuronLinksHiddenInput();
                renderGrid();
                $(neuronModalEl).modal('hide');
            })
            .catch(error => {
                alert(error.message || 'Errore durante il salvataggio dei collegamenti');
            })
            .finally(() => {
                if (saveBtn) saveBtn.disabled = false;
            });
    }

    function getRightAnchorPoint(neuron, cellSize, condition) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = (Number(neuron.grid_j) * cellSize);
        const topLeftY = (Number(neuron.grid_i) * cellSize);
        const baseX = topLeftX + cellSize;

        if (neuron.type === typeDetection) {
            const topY = topLeftY + (cellSize * 0.3);
            const bottomY = topLeftY + (cellSize * 0.7);
            const useBottom = condition === portDetectionFailure;
            return { x: baseX, y: useBottom ? bottomY : topY };
        }

        if (neuron.type === typeReadChimicalElement) {
            if (condition === DEFAULT_CHIMICAL_ELEMENT) {
                return { x: baseX, y: topLeftY + cellSize };
            }
            const ruleId = neuron.element_has_rule_chimical_element_id;
            const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(ruleId));
            if (rule && rule.details && rule.details.length > 0) {
                const details = rule.details;
                const count = details.length;
                const index = details.findIndex(d => `[${d.min}/${d.max}]` === condition);
                if (index !== -1) {
                    const step = cellSize / (count + 1);
                    return { x: baseX + 2, y: topLeftY + (step * (index + 1)) };
                }
            }
        }

        return { x: baseX, y: topLeftY + (cellSize / 2) };
    }

    function getLeftAnchorPoint(neuron, cellSize) {
        if (!neuron) return { x: 0, y: 0 };
        const topLeftX = (Number(neuron.grid_j) * cellSize);
        const topLeftY = (Number(neuron.grid_i) * cellSize);
        return { x: topLeftX, y: topLeftY + (cellSize / 2) };
    }

    function toggleDetectionFieldsByType() {
        const isDetection = neuronTypeInput.value === typeDetection;
        const isMovement = neuronTypeInput.value === typeMovement;
        const isAttack = neuronTypeInput.value === typeAttack;
        const isReadChimicalElement = neuronTypeInput.value === typeReadChimicalElement;
        neuronRadiusGroup.style.display = (isDetection || isMovement) ? '' : 'none';
        neuronTargetTypeGroup.style.display = isDetection ? '' : 'none';
        neuronGeneLifeGroup.style.display = isAttack ? '' : 'none';
        neuronGeneAttackGroup.style.display = isAttack ? '' : 'none';
        neuronRuleChimicalElementGroup.style.display = isReadChimicalElement ? '' : 'none';
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
            neuronRuleChimicalElementIdInput.value = existing.element_has_rule_chimical_element_id != null ? String(existing.element_has_rule_chimical_element_id) : '';
            deleteNeuronBtn.style.display = '';

            // Populate links tab
            populateLinksTab(existing.id);
        } else {
            currentNeuronId = null;
            neuronTypeInput.value = typeDetection;
            neuronRadiusInput.value = 1;
            neuronTargetTypeElementInput.checked = true;
            neuronTargetTypeEntityInput.checked = false;
            neuronTargetElementIdInput.value = '';
            neuronGeneLifeIdInput.value = '';
            neuronGeneAttackIdInput.value = '';
            neuronRuleChimicalElementIdInput.value = '';
            deleteNeuronBtn.style.display = 'none';

            // Clear links tab
            document.getElementById('neuron-links-container').innerHTML = '';
        }

        toggleDetectionFieldsByType();
        $(neuronModalEl).modal('show');
    }

    function drawNeuronLinks(layer, cellSize) {
        for (const link of neuronLinks) {
            const fromN = findNeuronById(link.from_neuron_id);
            const toN = findNeuronById(link.to_neuron_id);
            if (!fromN || !toN) continue;

            const linkCondition = link.condition;
            const fromPoint = getRightAnchorPoint(fromN, cellSize, linkCondition);
            const toPoint = getLeftAnchorPoint(toN, cellSize);

            let lineColor = portColors[portTrigger];
            if (link.color) {
                lineColor = link.color.startsWith('#') ? parseInt(link.color.replace('#', '0x'), 16) : Number(link.color);
            } else if (linkCondition === portDetectionFailure) {
                lineColor = portColors[portDetectionFailure];
            } else if (fromN.type === typeReadChimicalElement) {
                const ruleId = fromN.element_has_rule_chimical_element_id;
                const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(ruleId));
                if (rule && rule.details) {
                    const detail = rule.details.find(d => `[${d.min}/${d.max}]` === linkCondition);
                    if (detail && detail.color) {
                        lineColor = parseInt(detail.color.replace('#', '0x'), 16);
                    }
                }
                // If condition is 'default_chimical_element', use gray
                if (linkCondition === 'default_chimical_element') {
                    lineColor = 0x6b7280; // Gray
                }
            }
            const line = new PIXI.Graphics();
            line.lineStyle(3, lineColor, 1);
            line.moveTo(fromPoint.x, fromPoint.y);
            line.lineTo(toPoint.x, toPoint.y);
            layer.addChild(line);

            const mx = (fromPoint.x + toPoint.x) / 2;
            const my = (fromPoint.y + toPoint.y) / 2;

            const deleteBtn = new PIXI.Graphics();
            deleteBtn.beginFill(0xdc3545);
            deleteBtn.lineStyle(2, 0xffffff, 1);
            deleteBtn.drawCircle(0, 0, 9);
            deleteBtn.endFill();

            // Draw white X
            deleteBtn.moveTo(-4, -4);
            deleteBtn.lineTo(4, 4);
            deleteBtn.moveTo(4, -4);
            deleteBtn.lineTo(-4, 4);

            deleteBtn.x = mx;
            deleteBtn.y = my;
            deleteBtn.eventMode = 'static';
            deleteBtn.cursor = 'pointer';
            deleteBtn.isInteractiveElement = true;

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
            const i = Number(neuron.grid_i);
            const j = Number(neuron.grid_j);

            const symbol = typeSymbols[neuron.type] || '?';

            // Circuit borders
            const belongsToCircuits = neuronCircuits.filter(c => c.neuron_ids && c.neuron_ids.includes(Number(neuron.id)));
            belongsToCircuits.forEach((circuit, index) => {
                const colorIdx = neuronCircuits.indexOf(circuit) % circuitColors.length;
                const cColor = circuitColors[colorIdx];
                const offset = 3 + (index * 4);
                const cBorder = new PIXI.Graphics();
                cBorder.lineStyle(2, cColor, 0.8);
                cBorder.drawRect((j * cellSize) + 1 - offset, (i * cellSize) + 1 - offset, cellSize - 2 + (offset * 2), cellSize - 2 + (offset * 2));
                layer.addChild(cBorder);
            });

             // Neuron cell border (clickable and draggable)
            const neuronBorder = new PIXI.Graphics();
            neuronBorder.lineStyle(2, 0x111827, 1);
            neuronBorder.beginFill(0xFFFFFF, 1);
            neuronBorder.drawRect((j * cellSize) + 1, (i * cellSize) + 1, cellSize - 2, cellSize - 2);
            neuronBorder.endFill();
            neuronBorder.eventMode = 'static';
            neuronBorder.cursor = 'grab';
            neuronBorder.isInteractiveElement = true;

            neuronBorder.on('pointerdown', (e) => {
                e.stopPropagation();
                if (e.button === 0) {
                    draggedNeuron = neuronBorder;
                    draggedNeuronLayer = layer;
                    draggedNeuronOriginalI = i;
                    draggedNeuronOriginalJ = j;
                    const rect = app.view.getBoundingClientRect();
                    draggedOffsetX = e.global.x - ((j * cellSize) + 1);
                    draggedOffsetY = e.global.y - ((i * cellSize) + 1);
                    dragStarted = false;
                    neuronBorder.cursor = 'grabbing';
                    neuronBorder.alpha = 0.7;
                }
            });
            // Tooltip
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
                } else if (neuron.type === typeReadChimicalElement) {
                    const ruleId = neuron.element_has_rule_chimical_element_id;
                    const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(ruleId));
                    lines.push(`Regola: ${rule ? rule.title : '-'}`);
                }
                tooltipText.text = lines.join('\n');
                tooltipText.visible = true;
                tooltipBg.visible = true;
                const paddingX = 6, paddingY = 4;
                tooltipBg.clear();
                tooltipBg.lineStyle(1, 0x000000, 1);
                tooltipBg.beginFill(0xFFFFFF, 1);
                tooltipBg.drawRect(0, 0, tooltipText.width + paddingX*2, tooltipText.height + paddingY*2);
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
                const paddingX = 6, paddingY = 4;
                const offsetX = 12, offsetY = 12;
                const rect = app && app.view ? app.view.getBoundingClientRect() : null;
                const maxX = rect ? rect.width : (app ? app.renderer.width : Infinity);
                const maxY = rect ? rect.height : (app ? app.renderer.height : Infinity);
                const tooltipW = tooltipText.width + paddingX*2;
                const tooltipH = tooltipText.height + paddingY*2;
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

            // Neuron symbol text
            const text = new PIXI.Text(symbol, {
                fill: 0x1f2937,
                fontSize: Math.max(16, Math.floor(cellSize * 0.55)),
                fontWeight: 'bold',
                fontFamily: 'Consolas',
                align: 'center',
            });
            text.eventMode = 'none';
            text.x = (j * cellSize) + (cellSize / 2) - (text.width / 2);
            text.y = (i * cellSize) + (cellSize / 2) - (text.height / 2);
            layer.addChild(text);

            // Start circuit badge
            if (neuron.type === typeStart) {
                const startCircuit = neuronCircuits.find(c => Number(c.start_neuron_id) === Number(neuron.id));
                if (startCircuit) {
                    const badge = new PIXI.Graphics();
                    const bColor = startCircuit.state === 'closed' ? 0x10b981 : 0xf59e0b;
                    badge.beginFill(bColor);
                    badge.lineStyle(1, 0xffffff, 1);
                    badge.drawCircle((j * cellSize) + 8, (i * cellSize) + 8, 5);
                    badge.endFill();
                    layer.addChild(badge);
                }
            }

            // Anchors (static visualization)
            const hasLeftAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeAttack || neuron.type === typeMovement || neuron.type === typeEnd || neuron.type === typeReadChimicalElement;
            const hasRightAnchor = neuron.type === typeDetection || neuron.type === typePath || neuron.type === typeStart || neuron.type === typeAttack || neuron.type === typeMovement || neuron.type === typeReadChimicalElement;

            if (hasLeftAnchor) {
                const leftAnchor = new PIXI.Graphics();
                leftAnchor.beginFill(0x16a34a);
                leftAnchor.lineStyle(2, 0xffffff, 1);
                leftAnchor.drawCircle((j * cellSize), (i * cellSize) + (cellSize / 2), 8);
                leftAnchor.endFill();
                leftAnchor.eventMode = 'none';
                layer.addChild(leftAnchor);
            }

            if (hasRightAnchor) {
                const baseX = (j * cellSize) + cellSize;
                const baseY = i * cellSize;

                if (neuron.type === typeDetection) {
                    const topY = baseY + (cellSize * 0.3);
                    const bottomY = baseY + (cellSize * 0.7);
                    const anchors = [
                        { y: topY, color: portColors[portDetectionSuccess] },
                        { y: bottomY, color: portColors[portDetectionFailure] },
                    ];
                    anchors.forEach(ac => {
                        const a = new PIXI.Graphics();
                        a.beginFill(ac.color);
                        a.lineStyle(2, 0xffffff, 1);
                        a.drawCircle(baseX, ac.y, 8);
                        a.endFill();
                        a.eventMode = 'none';
                        layer.addChild(a);
                    });
                } else if (neuron.type === typeReadChimicalElement) {
                    const ruleId = neuron.element_has_rule_chimical_element_id;
                    const rule = allRuleChimicalElements.find(r => Number(r.id) === Number(ruleId));
                    const dynamicRadius = 10;
                    if (rule && rule.details && rule.details.length > 0) {
                        const details = rule.details;
                        const count = details.length;
                        const step = cellSize / (count + 1);
                        details.forEach((detail, idx) => {
                            const anchorY = baseY + (step * (idx + 1));
                            const color = parseInt(detail.color.replace('#', '0x'), 16);
                            const a = new PIXI.Graphics();
                            a.beginFill(color);
                            a.lineStyle(1, 0xffffff, 1);
                            a.drawCircle(baseX + 2, anchorY, dynamicRadius);
                            a.endFill();
                            a.eventMode = 'none';
                            layer.addChild(a);
                        });
                    }
                    // Default gray anchor
                    const defaultAnchorY = baseY + cellSize;
                    const defaultAnchor = new PIXI.Graphics();
                    defaultAnchor.beginFill(0x6b7280);
                    defaultAnchor.lineStyle(1, 0xffffff, 1);
                    defaultAnchor.drawCircle(baseX + 2, defaultAnchorY, dynamicRadius);
                    defaultAnchor.endFill();
                    defaultAnchor.eventMode = 'none';
                    layer.addChild(defaultAnchor);
                } else {
                    const a = new PIXI.Graphics();
                    a.beginFill(portColors[portTrigger]);
                    a.lineStyle(2, 0xffffff, 1);
                    a.drawCircle(baseX, baseY + (cellSize / 2), 8);
                    a.endFill();
                    a.eventMode = 'none';
                    layer.addChild(a);
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

            app.stage.on('pointerdown', (event) => {
                if (draggedNeuron) return;
                const i = Math.floor(event.global.y / fixedCellSize);
                const j = Math.floor(event.global.x / fixedCellSize);
                const maxRows = normalize(heightInput.value || 5);
                const maxCols = normalize(widthInput.value || 5);
                if (i < 0 || j < 0 || i >= maxRows || j >= maxCols) return;
                openNeuronModal(i, j);
            });

            // Native browser events for reliable drag
            app.view.addEventListener('pointermove', (e) => {
                if (!draggedNeuron) return;
                const rect = app.view.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const dx = Math.abs(x - draggedOffsetX - ((draggedNeuronOriginalJ * fixedCellSize) + 1));
                const dy = Math.abs(y - draggedOffsetY - ((draggedNeuronOriginalI * fixedCellSize) + 1));
                if (dx > 3 || dy > 3) dragStarted = true;
                if (!dragStarted) return;
                draggedNeuron.x = x - draggedOffsetX - ((draggedNeuronOriginalJ * fixedCellSize) + 1);
                draggedNeuron.y = y - draggedOffsetY - ((draggedNeuronOriginalI * fixedCellSize) + 1);
                if (draggedNeuronLayer) draggedNeuronLayer.sortChildren();
            });

            app.view.addEventListener('pointerup', async (e) => {
                if (!draggedNeuron) return;
                draggedNeuron.cursor = 'grab';
                draggedNeuron.alpha = 1;
                if (!dragStarted) {
                    const tempNeuron = draggedNeuron;
                    const tempI = draggedNeuronOriginalI;
                    const tempJ = draggedNeuronOriginalJ;
                    draggedNeuron = null;
                    draggedNeuronLayer = null;
                    openNeuronModal(tempI, tempJ);
                    return;
                }
                const neuron = findNeuronAtCell(draggedNeuronOriginalI, draggedNeuronOriginalJ);
                if (neuron) {
                    const newJ = Math.floor((draggedNeuron.x + (draggedNeuronOriginalJ * fixedCellSize) + 1 + (fixedCellSize / 2)) / fixedCellSize);
                    const newI = Math.floor((draggedNeuron.y + (draggedNeuronOriginalI * fixedCellSize) + 1 + (fixedCellSize / 2)) / fixedCellSize);
                    const maxRows = normalize(heightInput.value || 5);
                    const maxCols = normalize(widthInput.value || 5);
                    const clampedI = Math.max(0, Math.min(newI, maxRows - 1));
                    const clampedJ = Math.max(0, Math.min(newJ, maxCols - 1));
                    if (clampedI !== draggedNeuronOriginalI || clampedJ !== draggedNeuronOriginalJ) {
                        const targetOccupied = findNeuronAtCell(clampedI, clampedJ);
                        if (!targetOccupied || Number(targetOccupied.id) === Number(neuron.id)) {
                            try {
                                const updatedNeuron = await requestSaveNeuron({
                                    id: neuron.id,
                                    brain_grid_width: normalize(widthInput.value || 5),
                                    brain_grid_height: normalize(heightInput.value || 5),
                                    type: neuron.type,
                                    grid_i: clampedI,
                                    grid_j: clampedJ,
                                    radius: neuron.radius,
                                    target_type: neuron.target_type,
                                    target_element_id: neuron.target_element_id,
                                    gene_life_id: neuron.gene_life_id,
                                    gene_attack_id: neuron.gene_attack_id,
                                    element_has_rule_chimical_element_id: neuron.element_has_rule_chimical_element_id,
                                });
                                neuronItems = neuronItems.filter(item => Number(item.id) !== Number(neuron.id));
                                neuronItems.push(updatedNeuron);
                                updateNeuronHiddenInput();
                            } catch (error) {
                                alert(error.message || 'Errore durante lo spostamento del neurone');
                            }
                        } else {
                            alert('La cella di destinazione è già occupata');
                        }
                    }
                }
                draggedNeuron = null;
                draggedNeuronLayer = null;
                dragStarted = false;
                renderGrid();
            });

        } else {
            app.renderer.resize(canvasWidth, canvasHeight);
            app.stage.removeChildren();
        }

        // Draw background rectangle to ensure it's not transparent
        const bg = new PIXI.Graphics();
        bg.beginFill(0xFFFFFF);
        bg.drawRect(0, 0, canvasWidth, canvasHeight);
        bg.endFill();
        app.stage.addChild(bg);

        const lines = new PIXI.Graphics();
        lines.lineStyle(1, 0xdddddd, 1);
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
        let elementHasRuleChimicalElementId = null;
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
        } else if (type === typeReadChimicalElement) {
            const parsedRule = parseInt(neuronRuleChimicalElementIdInput.value, 10);
            elementHasRuleChimicalElementId = Number.isNaN(parsedRule) ? null : parsedRule;
            if (elementHasRuleChimicalElementId == null) {
                alert('Per il neurone Lettura Elemento Chimico devi selezionare una Regola');
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
                element_has_rule_chimical_element_id: elementHasRuleChimicalElementId,
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

    // Pointer move handler removed - cursor handled by neuron events
    
    toggleDetectionFieldsByType();
    updateNeuronLinksHiddenInput();
    renderGrid();
});
</script>
@endpush
