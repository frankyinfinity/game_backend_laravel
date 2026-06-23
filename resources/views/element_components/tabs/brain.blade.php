@php
    use App\Helpers\NeuronTooltipHelper;
    $existingNeuronItems = $elementComponent->brain && $elementComponent->brain->neurons
        ? $elementComponent->brain->neurons->map(function ($n) {
            return [
                'id'                                   => (int) $n->id,
                'type'                                 => $n->type,
                'grid_i'                               => (int) $n->grid_i,
                'grid_j'                               => (int) $n->grid_j,
                'radius'                               => $n->radius !== null ? (int) $n->radius : null,
                'stop_before_target'                   => (bool) $n->stop_before_target,
                'target_type'                          => $n->target_type,
                'target_element_id'                    => $n->target_element_id !== null ? (int) $n->target_element_id : null,
                'chemical_element_id'                  => $n->chemical_element_id !== null ? (int) $n->chemical_element_id : null,
                'complex_chemical_element_id'          => $n->complex_chemical_element_id !== null ? (int) $n->complex_chemical_element_id : null,
                'gene_life_id'                         => $n->gene_life_id !== null ? (int) $n->gene_life_id : null,
                'gene_attack_id'                       => $n->gene_attack_id !== null ? (int) $n->gene_attack_id : null,
                'element_infomation_id'                => $n->element_infomation_id !== null ? (int) $n->element_infomation_id : null,
                'element_has_rule_chimical_element_id' => $n->element_has_rule_chimical_element_id !== null ? (int) $n->element_has_rule_chimical_element_id : null,
                'tooltip'                              => NeuronTooltipHelper::generateTextFromNeuron($n),
                'condition_orders'                     => $n->conditionOrders->map(function ($co) {
                    return [
                        'id'                              => $co->id,
                        'condition'                       => $co->condition,
                        'sort_order'                      => (int) $co->sort_order,
                        'color'                           => $co->color,
                        'rule_chimical_element_detail_id' => $co->rule_chimical_element_detail_id,
                    ];
                })->values()->all(),
            ];
        })->values()->all()
        : [];

    $existingNeuronLinks = $elementComponent->brain && $elementComponent->brain->neurons
        ? $elementComponent->brain->neurons->flatMap(function ($n) {
            return $n->outgoingLinks->map(function ($l) {
                return [
                    'id'                        => (int) $l->id,
                    'from_neuron_id'            => (int) $l->from_neuron_id,
                    'to_neuron_id'              => (int) $l->to_neuron_id,
                    'neuron_condition_order_id' => (int) $l->neuron_condition_order_id,
                    'condition'                 => $l->condition,
                    'color'                     => $l->color,
                ];
            });
        })->values()->all()
        : [];

    $existingCircuits = $elementComponent->brain && $elementComponent->brain->circuits
        ? $elementComponent->brain->circuits->map(function ($c) {
            return [
                'id'              => $c->id,
                'uid'             => $c->uid,
                'state'           => $c->state,
                'active'          => (bool) $c->active,
                'color'           => $c->color,
                'start_neuron_id' => $c->start_neuron_id,
                'neuron_ids'      => $c->details->pluck('neuron_id')->toArray(),
            ];
        })->values()->all()
        : [];
@endphp

@php $brainLocked = $elementComponent->isCompleted(); @endphp

@if($brainLocked)
<div class="alert alert-warning shadow-sm mb-3" style="border-left: 4px solid #ffc107 !important;">
    <i class="fas fa-lock mr-2 text-warning"></i> Il cervello è in <strong>sola visualizzazione</strong> poiché il componente è stato completato e bloccato.
</div>
@endif

<div class="row mb-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_width">Larghezza Griglia</label>
            <input type="number" class="form-control" id="brain_grid_width_ec" name="brain_grid_width"
                   min="1" step="1"
                   value="{{ old('brain_grid_width', optional($elementComponent->brain)->grid_width ?? 5) }}"
                   placeholder="Es. 5" {{ $brainLocked ? 'disabled' : '' }}>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="brain_grid_height">Altezza Griglia</label>
            <input type="number" class="form-control" id="brain_grid_height_ec" name="brain_grid_height"
                   min="1" step="1"
                   value="{{ old('brain_grid_height', optional($elementComponent->brain)->grid_height ?? 5) }}"
                   placeholder="Es. 5" {{ $brainLocked ? 'disabled' : '' }}>
        </div>
    </div>
</div>

<input type="hidden" id="neuron_items_ec"    value="{{ old('neuron_items',    json_encode($existingNeuronItems)) }}">
<input type="hidden" id="neuron_links_ec"    value="{{ old('neuron_links',    json_encode($existingNeuronLinks)) }}">
<input type="hidden" id="neuron_circuits_ec" value="{{ json_encode($existingCircuits) }}">

@if(!$brainLocked)
<div class="row mb-3">
    <div class="col-12">
        <button type="button" class="btn btn-primary" id="btn-save-brain-grid-ec">
            <i class="fas fa-save"></i> Aggiorna Griglia
        </button>
        <small class="text-muted ml-2">Salva le dimensioni della griglia. Neuroni e collegamenti vengono salvati automaticamente.</small>
    </div>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">Anteprima Griglia (PIXI.js)</h3>
                <div class="card-tools">
                    @include('elements.tabs.guide')
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 text-center">
                        <div id="brain-grid-pixi-ec" style="display:inline-block; border:1px solid #b0b0b0; border-radius:4px;"></div>
                    </div>
                    <div class="col-md-4">
                        <h5><i class="fas fa-network-wired"></i> Circuiti</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover border" id="circuits-table-ec">
                                <thead class="thead-light">
                                    <tr>
                                        <th>UID</th>
                                        <th class="text-center">Stato</th>
                                        <th class="text-center">Colore</th>
                                        <th class="text-right">Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="circuits-table-body-ec"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NEURON MODAL -->
<div class="modal fade" id="brainNeuronModal-ec" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configura Neurone</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 border-right">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-cog"></i> Configurazione Neurone</h6>
                        <div class="mb-2 text-muted">Cella selezionata: <strong id="selected_cell_label_ec">-</strong></div>
                        <div class="form-group">
                            <label>Tipologia</label>
                            <select class="form-control" id="neuron_type_ec">
                                @foreach(\App\Models\Neuron::TYPE_LABELS as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_radius_group_ec">
                            <label>Raggio (in celle)</label>
                            <input type="number" class="form-control" id="neuron_radius_ec" min="1" step="1" value="1">
                        </div>
                        <div class="form-group" id="neuron_stop_before_target_group_ec" style="display:none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="neuron_stop_before_target_ec" value="1">
                                <label class="form-check-label" for="neuron_stop_before_target_ec">Stop movimento prima del target</label>
                            </div>
                        </div>
                        <div class="form-group" id="neuron_target_type_group_ec">
                            <label>Target da individuare</label>
                            <select class="form-control" id="neuron_target_type_ec">
                                <option value="">-- Seleziona Target --</option>
                                <option value="element">Element</option>
                                <option value="entity">Entity</option>
                                <option value="chemical_element">Elemento Chimico</option>
                                <option value="complex_chemical_element">Elemento Chimico Complesso</option>
                            </select>
                        </div>
                        <div class="form-group" id="neuron_target_element_group_ec">
                            <label>Seleziona Element</label>
                            <select class="form-control" id="neuron_target_element_id_ec">
                                <option value="">-- Seleziona --</option>
                                @foreach(($brainTargetElements ?? collect()) as $te)
                                    <option value="{{ $te->id }}">{{ $te->name }} (#{{ $te->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_gene_life_group_ec" style="display:none;">
                            <label>Gene Vita</label>
                            <select class="form-control" id="neuron_gene_life_id_ec">
                                <option value="">-- Seleziona Gene Vita --</option>
                                @foreach(($brainGenes ?? collect()) as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} (#{{ $g->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_gene_attack_group_ec" style="display:none;">
                            <label>Gene Attacco</label>
                            <select class="form-control" id="neuron_gene_attack_id_ec">
                                <option value="">-- Seleziona Gene Attacco --</option>
                                @foreach(($brainGenes ?? collect()) as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} (#{{ $g->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_element_infomation_group_ec" style="display:none;">
                            <label>Gene</label>
                            <select class="form-control" id="neuron_element_infomation_id_ec">
                                <option value="">-- Seleziona Gene --</option>
                                @foreach(($informationGenes ?? collect()) as $ig)
                                    <option value="{{ $ig->id }}">{{ $ig->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_rule_chimical_element_group_ec" style="display:none;">
                            <label>Regola Elemento Chimico</label>
                            <select class="form-control" id="neuron_element_has_rule_chimical_element_id_ec">
                                <option value="">-- Seleziona Regola --</option>
                                @foreach(($allRuleChimicalElements ?? collect()) as $rule)
                                    <option value="{{ $rule->id }}">{{ $rule->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_chemical_element_group_ec" style="display:none;">
                            <label>Elemento Chimico</label>
                            <select class="form-control" id="neuron_chemical_element_id_ec">
                                <option value="">-- Seleziona --</option>
                                @foreach(($brainChimicalElements ?? collect()) as $e)
                                    <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group" id="neuron_complex_chemical_element_group_ec" style="display:none;">
                            <label>Elemento Chimico Complesso</label>
                            <select class="form-control" id="neuron_complex_chemical_element_id_ec">
                                <option value="">-- Seleziona --</option>
                                @foreach(($brainComplexChimicalElements ?? collect()) as $e)
                                    <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold mb-3"><i class="fas fa-link"></i> Collegamenti in uscita</h6>
                        <div id="neuron-links-container-ec" style="max-height:400px; overflow-y:auto;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger mr-auto" id="btn_delete_neuron_ec">Rimuovi</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btn_save_neuron_ec">Salva neurone</button>
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
    // ── DOM refs ──────────────────────────────────────────────────────────────
    const widthInput  = document.getElementById('brain_grid_width_ec');
    const heightInput = document.getElementById('brain_grid_height_ec');
    const container   = document.getElementById('brain-grid-pixi-ec');
    const neuronItemsInput   = document.getElementById('neuron_items_ec');
    const neuronLinksInput   = document.getElementById('neuron_links_ec');
    const neuronCircuitsInput= document.getElementById('neuron_circuits_ec');
    const neuronTypeInput    = document.getElementById('neuron_type_ec');
    const neuronRadiusInput  = document.getElementById('neuron_radius_ec');
    const neuronRadiusGroup  = document.getElementById('neuron_radius_group_ec');
    const neuronStopGroup    = document.getElementById('neuron_stop_before_target_group_ec');
    const neuronStopInput    = document.getElementById('neuron_stop_before_target_ec');
    const neuronTargetTypeInput  = document.getElementById('neuron_target_type_ec');
    const neuronTargetTypeGroup  = document.getElementById('neuron_target_type_group_ec');
    const neuronTargetElemGroup  = document.getElementById('neuron_target_element_group_ec');
    const neuronTargetElemInput  = document.getElementById('neuron_target_element_id_ec');
    const neuronGeneLifeGroup    = document.getElementById('neuron_gene_life_group_ec');
    const neuronGeneLifeInput    = document.getElementById('neuron_gene_life_id_ec');
    const neuronGeneAttackGroup  = document.getElementById('neuron_gene_attack_group_ec');
    const neuronGeneAttackInput  = document.getElementById('neuron_gene_attack_id_ec');
    const neuronInfoGroup        = document.getElementById('neuron_element_infomation_group_ec');
    const neuronInfoInput        = document.getElementById('neuron_element_infomation_id_ec');
    const neuronRuleGroup        = document.getElementById('neuron_rule_chimical_element_group_ec');
    const neuronRuleInput        = document.getElementById('neuron_element_has_rule_chimical_element_id_ec');
    const neuronChemGroup        = document.getElementById('neuron_chemical_element_group_ec');
    const neuronChemInput        = document.getElementById('neuron_chemical_element_id_ec');
    const neuronComplexGroup     = document.getElementById('neuron_complex_chemical_element_group_ec');
    const neuronComplexInput     = document.getElementById('neuron_complex_chemical_element_id_ec');
    const selectedCellLabel = document.getElementById('selected_cell_label_ec');
    const saveNeuronBtn  = document.getElementById('btn_save_neuron_ec');
    const deleteNeuronBtn= document.getElementById('btn_delete_neuron_ec');
    const neuronModalEl  = document.getElementById('brainNeuronModal-ec');
    const circuitsTableBody = document.getElementById('circuits-table-body-ec');

    if (!widthInput || !heightInput || !container || !neuronItemsInput || !saveNeuronBtn || !deleteNeuronBtn || !neuronModalEl) {
        console.warn('Brain tab (EC): required elements missing.');
        return;
    }
    if (typeof PIXI === 'undefined') { console.error('PIXI.js not loaded'); return; }

    const brainLocked = {{ $brainLocked ? 'true' : 'false' }};

    // ── Constants ─────────────────────────────────────────────────────────────
    const fixedCellSize = 40;
    const typeDetection        = @json(\App\Models\Neuron::TYPE_DETECTION);
    const typePath             = @json(\App\Models\Neuron::TYPE_PATH);
    const typeAttack           = @json(\App\Models\Neuron::TYPE_ATTACK);
    const typeMovement         = @json(\App\Models\Neuron::TYPE_MOVEMENT);
    const typeStart            = @json(\App\Models\Neuron::TYPE_START);
    const typeEnd              = @json(\App\Models\Neuron::TYPE_END);
    const typeReadChimical     = @json(\App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT);
    const typeReadGene         = @json(\App\Models\Neuron::TYPE_READ_GENE);
    const typeMaxValueGene     = @json(\App\Models\Neuron::TYPE_MAX_VALUE_GENE);
    const typeConsume          = @json(\App\Models\Neuron::TYPE_CONSUME);
    const MAX_GENE_YES         = @json(\App\Models\Neuron::MAX_VALUE_GENE_YES);
    const MAX_GENE_NO          = @json(\App\Models\Neuron::MAX_VALUE_GENE_NO);
    const targetTypeElement    = @json(\App\Models\Neuron::TARGET_TYPE_ELEMENT);
    const targetTypeEntity     = @json(\App\Models\Neuron::TARGET_TYPE_ENTITY);
    const targetTypeChemical   = @json(\App\Models\Neuron::TARGET_TYPE_CHEMICAL_ELEMENT);
    const targetTypeComplex    = @json(\App\Models\Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT);
    const typeSymbols          = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    const typeLabels           = @json(\App\Models\Neuron::TYPE_LABELS);
    const portSuccess          = @json(\App\Models\NeuronLink::PORT_DETECTION_SUCCESS);
    const portFailure          = @json(\App\Models\NeuronLink::PORT_DETECTION_FAILURE);
    const portTrigger          = @json(\App\Models\NeuronLink::PORT_TRIGGER);
    const defaultChimical      = @json(\App\Models\NeuronLink::DEFAULT_CHIMICAL_ELEMENT);
    const portColors           = @json(\App\Models\NeuronLink::PORT_COLORS);
    const allRules             = @json($allRuleChimicalElements ?? []);
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {getAttribute:()=>''}).getAttribute('content');

    const saveNeuronUrl    = @json(route('element-components.brain.neurons.save', $elementComponent));
    const moveNeuronUrl    = @json(route('element-components.brain.neurons.move', [$elementComponent, ':neuron']));
    const deleteNeuronUrl  = @json(route('element-components.brain.neurons.delete', $elementComponent));
    const saveLinkUrl      = @json(route('element-components.brain.neuron-links.save', $elementComponent));
    const deleteLinkUrl    = @json(route('element-components.brain.neuron-links.delete', $elementComponent));
    const condOrdersBaseUrl = '/element-components/{{ $elementComponent->id }}/brain/neurons/';
    const toggleCircuitUrl = '/element-components/{{ $elementComponent->id }}/brain/circuits/';

    // ── State ─────────────────────────────────────────────────────────────────
    let app = null, selectedCell = null, currentNeuronId = null;
    let neuronItems = [], neuronLinks = [], neuronCircuits = [];
    let tooltipText = null, tooltipBg = null;
    let draggedNeuron = null, draggedLayer = null;
    let dragOrigI = null, dragOrigJ = null, dragOffX = 0, dragOffY = 0, dragStarted = false;
    let highlightedCircuitId = null;

    try { const p = JSON.parse(neuronItemsInput.value || '[]'); neuronItems = Array.isArray(p) ? p : []; } catch(e) { neuronItems = []; }
    try { const p = JSON.parse(neuronLinksInput.value  || '[]'); neuronLinks  = Array.isArray(p) ? p : []; } catch(e) { neuronLinks  = []; }
    try { const p = JSON.parse(neuronCircuitsInput ? neuronCircuitsInput.value : '[]'); neuronCircuits = Array.isArray(p) ? p : []; } catch(e) { neuronCircuits = []; }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function normalize(v) { const n = parseInt(v, 10); return (isNaN(n) || n < 1) ? 1 : n; }
    function nKey(i, j) { return `${i}_${j}`; }
    function findAtCell(i, j) { return neuronItems.find(n => nKey(+n.grid_i, +n.grid_j) === nKey(i, j)) || null; }
    function findById(id)      { return neuronItems.find(n => +n.id === +id) || null; }

    function getConditionColor(type, ruleId, condition) {
        if (type === typeReadChimical) {
            const rule = allRules.find(r => +r.id === +ruleId);
            if (rule && rule.details) {
                const d = rule.details.find(d => `[${d.min}/${d.max}]` === condition);
                if (d && d.color) return d.color;
            }
            if (condition === defaultChimical) return '#6b7280';
        }
        if (type === typeMaxValueGene) {
            return condition === MAX_GENE_YES ? '#16A34A' : '#DC2626';
        }
        const c = portColors[condition] || portColors[portTrigger];
        return '#' + (c ? c.toString(16).padStart(6,'0') : '000000');
    }

    function getOutputConditionsDetailed(neuron) {
        if (neuron.type === typeDetection) return [{condition: portSuccess, rule_detail_id: null}, {condition: portFailure, rule_detail_id: null}];
        if (neuron.type === typeReadChimical) {
            const rule = allRules.find(r => +r.id === +neuron.element_has_rule_chimical_element_id);
            if (rule && rule.details) {
                const conds = rule.details.map(d => ({condition:`[${d.min}/${d.max}]`, rule_detail_id: d.id}));
                conds.push({condition: defaultChimical, rule_detail_id: null});
                return conds;
            }
            return [{condition: defaultChimical, rule_detail_id: null}];
        }
        if (neuron.type === typeMaxValueGene) return [{condition: MAX_GENE_YES, rule_detail_id: null}, {condition: MAX_GENE_NO, rule_detail_id: null}];
        return [{condition: portTrigger, rule_detail_id: null}];
    }

    function getRightAnchor(neuron, cellSize, condition) {
        const baseX = (+neuron.grid_j + 1) * cellSize;
        const topY  =  +neuron.grid_i * cellSize;
        let orders = neuron.condition_orders || [];
        if (!orders.length) orders = getOutputConditionsDetailed(neuron).map((c,i)=>({condition:c.condition,sort_order:i}));
        const sorted = [...orders].sort((a,b)=>a.sort_order - b.sort_order);
        const idx = sorted.findIndex(o => o.condition === condition);
        const cnt = sorted.length;
        if (cnt > 0 && idx !== -1) return {x: baseX, y: topY + (cellSize / (cnt+1)) * (idx+1)};
        return {x: baseX, y: topY + cellSize/2};
    }
    function getLeftAnchor(neuron, cellSize) {
        return {x: +neuron.grid_j * cellSize, y: +neuron.grid_i * cellSize + cellSize/2};
    }

    function updateNeuronInput() { neuronItemsInput.value = JSON.stringify(neuronItems); }
    function updateLinksInput()  { neuronLinksInput.value  = JSON.stringify(neuronLinks);  }

    // ── Toggle modal fields ───────────────────────────────────────────────────
    function toggleFields() {
        const t = neuronTypeInput.value;
        const isDet = t === typeDetection, isMove = t === typeMovement, isAtk = t === typeAttack;
        const isPath= t === typePath, isReadChem = t === typeReadChimical;
        const isReadGene = t === typeReadGene, isMaxGene = t === typeMaxValueGene;
        neuronRadiusGroup.style.display           = (isDet || isMove) ? '' : 'none';
        neuronStopGroup.style.display             = isPath ? '' : 'none';
        neuronTargetTypeGroup.style.display       = isDet  ? '' : 'none';
        neuronGeneLifeGroup.style.display         = isAtk  ? '' : 'none';
        neuronGeneAttackGroup.style.display       = isAtk  ? '' : 'none';
        neuronInfoGroup.style.display             = (isReadGene || isMaxGene) ? '' : 'none';
        neuronRuleGroup.style.display             = isReadChem ? '' : 'none';
        neuronChemGroup.style.display             = 'none';
        neuronComplexGroup.style.display          = 'none';
        neuronTargetElemGroup.style.display       = 'none';
        if (!isDet) return;
        const tt = neuronTargetTypeInput.value;
        neuronTargetElemGroup.style.display  = tt === targetTypeElement  ? '' : 'none';
        neuronChemGroup.style.display        = tt === targetTypeChemical ? '' : 'none';
        neuronComplexGroup.style.display     = tt === targetTypeComplex  ? '' : 'none';
    }

    // ── Circuits table ────────────────────────────────────────────────────────
    function renderCircuitsTable() {
        if (!circuitsTableBody) return;
        if ($.fn.DataTable.isDataTable('#circuits-table-ec')) $('#circuits-table-ec').DataTable().destroy();
        circuitsTableBody.innerHTML = '';
        neuronCircuits.forEach(circuit => {
            const color = circuit.color || '#aaaaaa';
            const tr = document.createElement('tr');
            tr.dataset.circuitId = circuit.id;
            const actionsHtml = brainLocked
                ? ''
                : `<button type="button" class="btn btn-xs ${circuit.active?'btn-success':'btn-secondary'} btn-toggle-circuit-ec" data-id="${circuit.id}">${circuit.active?'<i class="fas fa-check-circle"></i> Attivo':'<i class="fas fa-times-circle"></i> Disattivo'}</button>
                   <button type="button" class="btn btn-xs btn-danger ml-1 btn-delete-circuit-ec" data-id="${circuit.id}" title="Elimina"><i class="fas fa-trash"></i></button>`;
            tr.innerHTML = `
                <td title="${circuit.uid}"><small class="text-monospace">${circuit.uid.substring(0,8)}...</small></td>
                <td class="text-center"><span class="badge ${circuit.state==='closed'?'badge-success':'badge-warning'}">${circuit.state}</span></td>
                <td class="text-center"><div style="width:20px;height:20px;background:${color};border-radius:4px;margin:0 auto;border:1px solid #ccc;"></div></td>
                <td class="text-right">${actionsHtml}</td>`;
            circuitsTableBody.appendChild(tr);
        });
        $('#circuits-table-ec').DataTable({paging:false,searching:false,info:false,ordering:true,autoWidth:false,destroy:true,language:{emptyTable:'Nessun circuito'}});
    }

    $(document).on('mouseenter','#circuits-table-body-ec tr', function(){ highlightedCircuitId=$(this).data('circuit-id'); renderGrid(); });
    $(document).on('mouseleave','#circuits-table-body-ec tr', function(){ highlightedCircuitId=null; renderGrid(); });
    $(document).on('mouseleave','#circuits-table-ec', function(){ highlightedCircuitId=null; renderGrid(); });
    $(document).on('click','.btn-toggle-circuit-ec', function(e){ e.stopPropagation(); toggleCircuit($(this).data('id')); });
    $(document).on('click','.btn-delete-circuit-ec', function(e){ e.stopPropagation(); if(confirm('Eliminare circuito?')) deleteCircuit($(this).data('id')); });

    async function toggleCircuit(id) {
        try {
            const r = await fetch(toggleCircuitUrl + id + '/toggle-active', {method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}});
            const d = await r.json();
            if (d.success) { neuronCircuits = d.circuits; renderGrid(); renderCircuitsTable(); }
        } catch(e) { console.error(e); }
    }

    async function deleteCircuit(id) {
        try {
            const r = await fetch(toggleCircuitUrl + id, {method:'DELETE', headers:{'X-CSRF-TOKEN':csrfToken,'Accept':'application/json'}});
            const d = await r.json();
            if (d.success) {
                neuronItems = d.neurons || neuronItems;
                neuronLinks = d.links  || neuronLinks;
                neuronCircuits = d.circuits;
                updateNeuronInput(); updateLinksInput();
                renderGrid(); renderCircuitsTable();
            }
        } catch(e) { console.error(e); }
    }

    // ── Links tab ─────────────────────────────────────────────────────────────
    function populateLinksTab(neuronId) {
        const neuron = findById(neuronId);
        if (!neuron) return;
        const cont = document.getElementById('neuron-links-container-ec');
        cont.innerHTML = '';
        let orders = neuron.condition_orders || [];
        if (!orders.length) {
            const conds = getOutputConditionsDetailed(neuron);
            orders = conds.map((c,i)=>({condition:c.condition, sort_order:i, color:getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, c.condition), rule_chimical_element_detail_id:c.rule_detail_id}));
            neuron.condition_orders = orders;
        }
        const sorted = [...orders].sort((a,b)=>a.sort_order-b.sort_order);
        sorted.forEach((ord, i) => {
            const cond  = ord.condition;
            const color = ord.color || getConditionColor(neuron.type, neuron.element_has_rule_chimical_element_id, cond);
            const link  = neuronLinks.find(l => +l.from_neuron_id === +neuronId && l.condition === cond);
            const div = document.createElement('div');
            div.className = 'form-group mb-3 border-bottom pb-2';
            const lc = document.createElement('div');
            lc.className = 'd-flex align-items-center mb-1';
            lc.innerHTML = `<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:${color};margin-right:10px;"></span>`;
            const lbl = document.createElement('label');
            lbl.className = 'mb-0 mr-auto'; lbl.style.fontWeight = 'bold';
            lbl.textContent = cond === portTrigger ? 'Trigger' : (cond === portSuccess ? 'Success' : (cond === portFailure ? 'Failure' : cond));
            lc.appendChild(lbl);
            const bg = document.createElement('div'); bg.className = 'btn-group ml-2';
            const bU = document.createElement('button'); bU.type='button'; bU.className='btn btn-xs btn-outline-secondary'; bU.innerHTML='<i class="fas fa-arrow-up"></i>'; bU.disabled=(i===0); bU.onclick=e=>{e.preventDefault();moveCondition(neuronId,i,-1);};
            const bD = document.createElement('button'); bD.type='button'; bD.className='btn btn-xs btn-outline-secondary'; bD.innerHTML='<i class="fas fa-arrow-down"></i>'; bD.disabled=(i===sorted.length-1); bD.onclick=e=>{e.preventDefault();moveCondition(neuronId,i,1);};
            bg.appendChild(bU); bg.appendChild(bD); lc.appendChild(bg);
            div.appendChild(lc);
            const sel = document.createElement('select'); sel.className='form-control link-target-ec'; sel.dataset.condition=cond; sel.dataset.ruleDetailId=ord.rule_chimical_element_detail_id||'';
            const def = document.createElement('option'); def.value=''; def.textContent='-- Nessun collegamento --'; sel.appendChild(def);
            neuronItems.forEach(n => { if (+n.id===+neuronId) return; const o=document.createElement('option'); o.value=n.id; o.textContent=`#${n.id} (${n.grid_i},${n.grid_j}) - ${typeLabels[n.type]||n.type}`; if(link && +link.to_neuron_id===+n.id) o.selected=true; sel.appendChild(o); });
            div.appendChild(sel);
            cont.appendChild(div);
        });
        const sb = document.createElement('button'); sb.type='button'; sb.id='btn_save_links_ec'; sb.className='btn btn-primary mt-3'; sb.textContent='Salva Collegamenti';
        sb.onclick = () => saveLinks(neuronId);
        cont.appendChild(sb);
    }

    function moveCondition(neuronId, index, dir) {
        const neuron = findById(neuronId);
        if (!neuron) return;
        const cont = document.getElementById('neuron-links-container-ec');
        const selects = Array.from(cont.querySelectorAll('.link-target-ec'));
        const data = selects.map((s,i) => ({ name:s.dataset.condition, targetId:s.value, sort_order:i, color:(neuron.condition_orders||[]).find(o=>o.condition===s.dataset.condition)?.color||null, rule_chimical_element_detail_id:s.dataset.ruleDetailId||null }));
        const ti = index + dir;
        if (ti < 0 || ti >= data.length) return;
        [data[index], data[ti]] = [data[ti], data[index]];
        data.forEach((d,i)=>d.sort_order=i);
        neuron.condition_orders = data.map(d=>({condition:d.name, sort_order:d.sort_order, color:d.color, rule_chimical_element_detail_id:d.rule_chimical_element_detail_id}));
        populateLinksTab(neuronId);
        renderGrid();
    }

    async function saveLinks(neuronId) {
        const cont = document.getElementById('neuron-links-container-ec');
        const selects = Array.from(cont.querySelectorAll('.link-target-ec'));
        const sb = document.getElementById('btn_save_links_ec');
        const srcNeuron = findById(neuronId);
        if (!srcNeuron) return;
        if (sb) sb.disabled = true;
        try {
            const orders = [];
            for (let i = 0; i < selects.length; i++) {
                const sel = selects[i];
                const cond = sel.dataset.condition;
                const ruleDetailId = sel.dataset.ruleDetailId ? +sel.dataset.ruleDetailId : null;
                const targetId = sel.value ? +sel.value : null;
                const existingLink = neuronLinks.find(l => +l.from_neuron_id===+neuronId && l.condition===cond);
                orders.push({condition:cond, sort_order:i, rule_chimical_element_detail_id:ruleDetailId});
                if (targetId) {
                    if (existingLink) {
                        if (+existingLink.to_neuron_id !== targetId) {
                            await reqDeleteLink({from_neuron_id:+neuronId, to_neuron_id:+existingLink.to_neuron_id, condition:cond});
                            neuronLinks = neuronLinks.filter(l=>l!==existingLink);
                            const saved = await reqSaveLink({from_neuron_id:+neuronId, to_neuron_id:targetId, condition:cond, color:existingLink.color});
                            if (saved) neuronLinks.push(saved);
                        }
                    } else {
                        const saved = await reqSaveLink({from_neuron_id:+neuronId, to_neuron_id:targetId, condition:cond, color:getConditionColor(srcNeuron.type, srcNeuron.element_has_rule_chimical_element_id, cond)});
                        if (saved) neuronLinks.push(saved);
                    }
                } else if (existingLink) {
                    await reqDeleteLink({from_neuron_id:+neuronId, to_neuron_id:+existingLink.to_neuron_id, condition:cond});
                    neuronLinks = neuronLinks.filter(l=>l!==existingLink);
                }
            }
            await reqSaveConditionOrders(neuronId, orders);
            updateLinksInput(); renderGrid(); renderCircuitsTable();
            $(neuronModalEl).modal('hide');
        } catch(e) { alert(e.message||'Errore salvataggio collegamenti'); }
        finally { if(sb) sb.disabled=false; }
    }

    // ── PIXI render ───────────────────────────────────────────────────────────
    function drawDashedLine(g, x1, y1, x2, y2, dash=6, gap=4) {
        const dx=x2-x1, dy=y2-y1, dist=Math.sqrt(dx*dx+dy*dy); if(!dist)return;
        const ux=dx/dist, uy=dy/dist; let drawn=0;
        while(drawn<dist){ const sx=x1+ux*drawn, sy=y1+uy*drawn, len=Math.min(dash,dist-drawn), ex=sx+ux*len, ey=sy+uy*len; g.moveTo(sx,sy); g.lineTo(ex,ey); drawn+=dash+gap; }
    }

    function drawNeuronLinks(layer, cs) {
        for (const link of neuronLinks) {
            const fn = findById(link.from_neuron_id), tn = findById(link.to_neuron_id);
            if (!fn || !tn) continue;
            const cond = link.condition;
            const fp = getRightAnchor(fn, cs, cond), tp = getLeftAnchor(tn, cs);
            const ord = (fn.condition_orders||[]).find(o=>o.condition===cond);
            const cStr = (ord && ord.color) ? ord.color : getConditionColor(fn.type, fn.element_has_rule_chimical_element_id, cond);
            let lineColor = cStr.startsWith('#') ? parseInt(cStr.replace('#','0x'),16) : +cStr;
            if (cond === defaultChimical) lineColor = 0x6b7280;
            const line = new PIXI.Graphics();
            line.lineStyle(3, lineColor, 1); line.moveTo(fp.x, fp.y); line.lineTo(tp.x, tp.y);
            layer.addChild(line);
            const mx=(fp.x+tp.x)/2, my=(fp.y+tp.y)/2;
            const btn = new PIXI.Graphics();
            btn.beginFill(0xdc3545); btn.lineStyle(2,0xffffff,1); btn.drawCircle(0,0,9); btn.endFill();
            btn.moveTo(-4,-4); btn.lineTo(4,4); btn.moveTo(4,-4); btn.lineTo(-4,4);
            btn.x=mx; btn.y=my; btn.eventMode='static'; btn.cursor='pointer'; btn.isInteractiveElement=true;
            if (brainLocked) { btn.visible = false; }
            btn.on('pointerdown', async e => {
                e.stopPropagation();
                if (!confirm('Eliminare collegamento?')) return;
                try { await reqDeleteLink({from_neuron_id:+link.from_neuron_id, to_neuron_id:+link.to_neuron_id, condition:cond}); neuronLinks=neuronLinks.filter(l=>!(+l.from_neuron_id===+link.from_neuron_id&&+l.to_neuron_id===+link.to_neuron_id&&l.condition===cond)); updateLinksInput(); renderGrid(); renderCircuitsTable(); } catch(err){alert(err.message||'Errore');}
            });
            layer.addChild(btn);
        }
    }

    function drawNeuronSymbols(layer, cs) {
        for (const neuron of neuronItems) {
            const i=+neuron.grid_i, j=+neuron.grid_j;
            const belongs = neuronCircuits.filter(c=>c.neuron_ids&&c.neuron_ids.includes(+neuron.id));
            belongs.forEach((c,idx)=>{ const cc=parseInt((c.color||'#cccccc').replace('#','0x'),16); const off=3+idx*4; const b=new PIXI.Graphics(); b.lineStyle(2,cc,0.8); b.drawRect(j*cs+1-off,i*cs+1-off,cs-2+off*2,cs-2+off*2); layer.addChild(b); });
            const inactive = belongs.length>0 && belongs.every(c=>!c.active);
            const highlighted = highlightedCircuitId && belongs.some(c=>c.id===highlightedCircuitId);
            const nb = new PIXI.Graphics();
            if (highlighted) nb.lineStyle(6,0x3b82f6,1); else nb.lineStyle(2,0x111827,1);
            nb.beginFill(inactive?0xd1d5db:0xFFFFFF,1);
            nb.drawRect(j*cs+1,i*cs+1,cs-2,cs-2); nb.endFill();
            nb.eventMode='static'; nb.cursor=brainLocked?'default':'grab'; nb.isInteractiveElement=true;
            nb.on('pointerdown', e=>{ if(brainLocked)return; e.stopPropagation(); if(e.button===0){ draggedNeuron=nb; draggedLayer=layer; dragOrigI=i; dragOrigJ=j; dragOffX=e.global.x-(j*cs+1); dragOffY=e.global.y-(i*cs+1); dragStarted=false; nb.cursor='grabbing'; nb.alpha=0.7; } });
            nb.on('pointerover', ()=>{ if(!tooltipText||!tooltipBg)return; tooltipText.text=neuron.tooltip||'Neurone'; tooltipText.visible=true; tooltipBg.visible=true; const px=6,py=4; tooltipBg.clear(); tooltipBg.lineStyle(1,0,1); tooltipBg.beginFill(0xFFFFFF,1); tooltipBg.drawRect(0,0,tooltipText.width+px*2,tooltipText.height+py*2); tooltipBg.endFill(); tooltipBg.x=tooltipText.x-px; tooltipBg.y=tooltipText.y-py; });
            nb.on('pointerout', ()=>{ if(tooltipText)tooltipText.visible=false; if(tooltipBg)tooltipBg.visible=false; });
            nb.on('pointermove', e=>{ if(!tooltipText||!tooltipText.visible||!tooltipBg)return; const ox=12,oy=12,px=6,py=4; const tw=tooltipText.width+px*2,th=tooltipText.height+py*2; const mX=app?app.renderer.width:Infinity,mY=app?app.renderer.height:Infinity; let x=e.global.x+ox,y=e.global.y+oy; if(x+tw>mX)x=Math.max(0,mX-tw); if(y+th>mY)y=Math.max(0,mY-th); tooltipText.x=x; tooltipText.y=y; tooltipBg.x=x-px; tooltipBg.y=y-py; });
            layer.addChild(nb);
            const txt = new PIXI.Text(typeSymbols[neuron.type]||'?',{fill:0x1f2937,fontSize:Math.max(16,Math.floor(cs*0.55)),fontWeight:'bold',fontFamily:'Consolas',align:'center'});
            txt.eventMode='none'; txt.x=j*cs+cs/2-txt.width/2; txt.y=i*cs+cs/2-txt.height/2; layer.addChild(txt);
            if (neuron.type===typeStart) { const sc=neuronCircuits.find(c=>+c.start_neuron_id===+neuron.id); if(sc){const bd=new PIXI.Graphics();bd.beginFill(sc.state==='closed'?0x10b981:0xf59e0b);bd.drawCircle(j*cs+8,i*cs+8,5);bd.endFill();layer.addChild(bd);} }
            const needsLeft = [typeDetection,typePath,typeAttack,typeMovement,typeEnd,typeReadChimical,typeReadGene,typeMaxValueGene,typeConsume].includes(neuron.type);
            if (needsLeft) { const lp=getLeftAnchor(neuron,cs); const la=new PIXI.Graphics(); la.beginFill(0x16a34a); la.lineStyle(2,0xffffff,1); la.drawCircle(lp.x,lp.y,8); la.endFill(); la.eventMode='none'; layer.addChild(la); }
            const hasRight = [typeDetection,typePath,typeStart,typeAttack,typeMovement,typeReadChimical,typeReadGene,typeMaxValueGene,typeConsume].includes(neuron.type);
            if (hasRight) {
                let orders = neuron.condition_orders||[];
                if (!orders.length) orders = getOutputConditionsDetailed(neuron).map((c,idx)=>({condition:c.condition,sort_order:idx,color:getConditionColor(neuron.type,neuron.element_has_rule_chimical_element_id,c.condition),rule_chimical_element_detail_id:c.rule_detail_id}));
                [...orders].sort((a,b)=>a.sort_order-b.sort_order).forEach(ord=>{
                    const ap=getRightAnchor(neuron,cs,ord.condition);
                    const cs2=(neuron.type===typeReadChimical)?1:2, r=(neuron.type===typeReadChimical)?10:8, xOff=(neuron.type===typeReadChimical)?2:0;
                    const cStr=(ord&&ord.color)?ord.color:getConditionColor(neuron.type,neuron.element_has_rule_chimical_element_id,ord.condition);
                    let ci=cStr.startsWith('#')?parseInt(cStr.replace('#','0x'),16):+cStr;
                    const a=new PIXI.Graphics(); a.beginFill(ci); a.lineStyle(cs2,0xffffff,1); a.drawCircle(ap.x+xOff,ap.y,r); a.endFill(); a.eventMode='none'; layer.addChild(a);
                });
            }
        }
    }

    function renderGrid() {
        const cols=normalize(widthInput.value||5), rows=normalize(heightInput.value||5), cs=fixedCellSize;
        const cw=cols*cs, ch=rows*cs;
        neuronItems=neuronItems.filter(n=>{const i=+n.grid_i,j=+n.grid_j;return i>=0&&j>=0&&i<rows&&j<cols;}); updateNeuronInput();
        if (!app) {
            app=new PIXI.Application({width:cw,height:ch,antialias:true,backgroundAlpha:1,backgroundColor:0xffffff});
            container.innerHTML=''; container.appendChild(app.view);
            app.stage.eventMode='static'; app.stage.hitArea=new PIXI.Rectangle(0,0,cw,ch);
            app.stage.on('pointerdown', e=>{ if(brainLocked||draggedNeuron)return; const i=Math.floor(e.global.y/cs),j=Math.floor(e.global.x/cs); const mr=normalize(heightInput.value||5),mc=normalize(widthInput.value||5); if(i<0||j<0||i>=mr||j>=mc)return; openNeuronModal(i,j); });
            app.view.addEventListener('pointermove', e=>{ if(!draggedNeuron)return; const rect=app.view.getBoundingClientRect(); const x=e.clientX-rect.left,y=e.clientY-rect.top; const dx=Math.abs(x-dragOffX-(dragOrigJ*cs+1)),dy=Math.abs(y-dragOffY-(dragOrigI*cs+1)); if(dx>3||dy>3)dragStarted=true; if(!dragStarted)return; draggedNeuron.x=x-dragOffX-(dragOrigJ*cs+1); draggedNeuron.y=y-dragOffY-(dragOrigI*cs+1); });
            app.view.addEventListener('pointerup', async e=>{ if(!draggedNeuron)return; draggedNeuron.cursor='grab'; draggedNeuron.alpha=1; if(!dragStarted){const tn=draggedNeuron,ti=dragOrigI,tj=dragOrigJ;draggedNeuron=null;draggedLayer=null;openNeuronModal(ti,tj);return;} const neuron=findAtCell(dragOrigI,dragOrigJ); if(neuron){const nj=Math.floor((draggedNeuron.x+dragOrigJ*cs+1+cs/2)/cs),ni=Math.floor((draggedNeuron.y+dragOrigI*cs+1+cs/2)/cs); const mr=normalize(heightInput.value||5),mc=normalize(widthInput.value||5); const ci=Math.max(0,Math.min(ni,mr-1)),cj=Math.max(0,Math.min(nj,mc-1)); if(ci!==dragOrigI||cj!==dragOrigJ){const occ=findAtCell(ci,cj); if(!occ||+occ.id===+neuron.id){try{const un=await reqMoveNeuron(neuron.id,ci,cj);neuronItems=neuronItems.filter(n=>+n.id!==+neuron.id);neuronItems.push(un);updateNeuronInput();}catch(err){alert(err.message||'Errore spostamento');}}} } draggedNeuron=null;draggedLayer=null;dragStarted=false;renderGrid();renderCircuitsTable(); });
        } else { app.renderer.resize(cw,ch); app.stage.removeChildren(); }
        const bg=new PIXI.Graphics(); bg.beginFill(0xFFFFFF); bg.drawRect(0,0,cw,ch); bg.endFill(); app.stage.addChild(bg);
        const lines=new PIXI.Graphics(); lines.lineStyle(1,0xdddddd,1);
        for(let c=0;c<=cols;c++) drawDashedLine(lines,c*cs,0,c*cs,ch);
        for(let r=0;r<=rows;r++) drawDashedLine(lines,0,r*cs,cw,r*cs);
        app.stage.addChild(lines);
        const ll=new PIXI.Container(); app.stage.addChild(ll); drawNeuronLinks(ll,cs);
        const sl=new PIXI.Container(); app.stage.addChild(sl); drawNeuronSymbols(sl,cs);
        tooltipBg=new PIXI.Graphics(); tooltipBg.visible=false; tooltipBg.zIndex=19999; app.stage.addChild(tooltipBg);
        tooltipText=new PIXI.Text('',{fill:0,fontSize:12,fontWeight:'bold',fontFamily:'Consolas',align:'left'}); tooltipText.visible=false; tooltipText.zIndex=20000; app.stage.addChild(tooltipText);
        app.stage.sortChildren();
    }

    // ── Modal open ────────────────────────────────────────────────────────────
    function openNeuronModal(i, j) {
        if (brainLocked) return;
        selectedCell = {i, j};
        selectedCellLabel.textContent = `(${i}, ${j})`;
        const existing = findAtCell(i, j);
        if (existing) {
            currentNeuronId = existing.id;
            neuronTypeInput.value     = existing.type;
            neuronRadiusInput.value   = existing.radius != null ? +existing.radius : 1;
            neuronStopInput.checked   = !!existing.stop_before_target;
            neuronGeneLifeInput.value   = existing.gene_life_id   != null ? String(existing.gene_life_id)   : '';
            neuronGeneAttackInput.value = existing.gene_attack_id  != null ? String(existing.gene_attack_id)  : '';
            neuronInfoInput.value       = existing.element_infomation_id != null ? String(existing.element_infomation_id) : '';
            neuronRuleInput.value       = existing.element_has_rule_chimical_element_id != null ? String(existing.element_has_rule_chimical_element_id) : '';
            neuronChemInput.value       = existing.chemical_element_id != null ? String(existing.chemical_element_id) : '';
            neuronComplexInput.value    = existing.complex_chemical_element_id != null ? String(existing.complex_chemical_element_id) : '';
            deleteNeuronBtn.style.display = '';
            toggleFields();
            populateLinksTab(existing.id);
        } else {
            currentNeuronId = null;
            neuronTypeInput.value = typeDetection;
            neuronRadiusInput.value = 1; neuronStopInput.checked = false;
            neuronTargetTypeInput.value = targetTypeElement;
            neuronTargetElemInput.value = neuronChemInput.value = neuronComplexInput.value = '';
            neuronGeneLifeInput.value = neuronGeneAttackInput.value = neuronInfoInput.value = neuronRuleInput.value = '';
            deleteNeuronBtn.style.display = 'none';
            document.getElementById('neuron-links-container-ec').innerHTML = '';
        }
        toggleFields();
        $(neuronModalEl).modal('show');
        $(neuronModalEl).one('shown.bs.modal', function() {
            if (currentNeuronId) {
                const ex = findById(currentNeuronId);
                if (ex) {
                    const tt = ex.target_type || (ex.target_element_id!=null?targetTypeElement:null) || (ex.chemical_element_id!=null?targetTypeChemical:null) || (ex.complex_chemical_element_id!=null?targetTypeComplex:null) || targetTypeElement;
                    neuronTargetTypeInput.value = tt;
                    toggleFields();
                    if (tt===targetTypeElement)  neuronTargetElemInput.value  = ex.target_element_id!=null?String(ex.target_element_id):'';
                    if (tt===targetTypeChemical) neuronChemInput.value         = ex.chemical_element_id!=null?String(ex.chemical_element_id):'';
                    if (tt===targetTypeComplex)  neuronComplexInput.value      = ex.complex_chemical_element_id!=null?String(ex.complex_chemical_element_id):'';
                }
            }
        });
    }

    // ── API calls ─────────────────────────────────────────────────────────────
    async function reqSaveNeuron(payload) {
        const r = await fetch(saveNeuronUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(payload)});
        const d = await r.json();
        if (!r.ok||!d.success) throw new Error(d.message||'Errore salvataggio neurone');
        if (d.circuits) neuronCircuits=d.circuits;
        return d.neuron;
    }
    async function reqDeleteNeuron(payload) {
        const r = await fetch(deleteNeuronUrl, {method:'DELETE',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(payload)});
        const d = await r.json();
        if (!r.ok||!d.success) throw new Error(d.message||'Errore rimozione neurone');
        if (d.circuits) neuronCircuits=d.circuits;
    }
    async function reqMoveNeuron(neuronId, gridI, gridJ) {
        const url = moveNeuronUrl.replace(':neuron', neuronId);
        const r = await fetch(url, {method:'PATCH',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify({grid_i:gridI,grid_j:gridJ})});
        const d = await r.json();
        if (!r.ok||!d.success) throw new Error(d.message||'Errore spostamento');
        if (d.circuits) neuronCircuits=d.circuits;
        return d.neuron;
    }
    async function reqSaveLink(payload) {
        const r = await fetch(saveLinkUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(payload)});
        const d = await r.json();
        if (!r.ok||!d.success) throw new Error(d.message||'Errore salvataggio link');
        if (d.circuits) neuronCircuits=d.circuits;
        return d.link;
    }
    async function reqDeleteLink(payload) {
        const r = await fetch(deleteLinkUrl, {method:'DELETE',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify(payload)});
        const d = await r.json();
        if (!r.ok||!d.success) throw new Error(d.message||'Errore rimozione link');
        if (d.circuits) neuronCircuits=d.circuits;
    }
    async function reqSaveConditionOrders(neuronId, orders) {
        const r = await fetch(condOrdersBaseUrl + neuronId + '/condition-orders', {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},body:JSON.stringify({orders})});
        const d = await r.json();
        const n = findById(neuronId);
        if (n && d.orders) n.condition_orders = d.orders.map(o=>({id:o.id,condition:o.condition,sort_order:o.sort_order,color:o.color,rule_chimical_element_detail_id:o.rule_chimical_element_detail_id}));
        return d;
    }

    // ── Button handlers ───────────────────────────────────────────────────────
    saveNeuronBtn.addEventListener('click', async function() {
        if (!selectedCell) return;
        const t = neuronTypeInput.value, i = +selectedCell.i, j = +selectedCell.j;
        let radius=null, targetType=null, targetElementId=null, chemicalElementId=null, complexChemicalElementId=null;
        let geneLifeId=null, geneAttackId=null, elementInfomationId=null, elementHasRuleChimicalElementId=null;
        if (t===typeDetection) { radius=Math.max(1,normalize(neuronRadiusInput.value||1)); targetType=neuronTargetTypeInput.value; if(targetType===targetTypeElement) targetElementId=parseInt(neuronTargetElemInput.value)||null; else if(targetType===targetTypeChemical) chemicalElementId=parseInt(neuronChemInput.value)||null; else if(targetType===targetTypeComplex) complexChemicalElementId=parseInt(neuronComplexInput.value)||null; }
        else if (t===typeMovement) { radius=Math.max(1,normalize(neuronRadiusInput.value||1)); }
        else if (t===typeAttack) { geneLifeId=parseInt(neuronGeneLifeInput.value)||null; geneAttackId=parseInt(neuronGeneAttackInput.value)||null; if(!geneLifeId||!geneAttackId){alert('Seleziona Gene Vita e Gene Attacco');return;} }
        else if (t===typeReadGene||t===typeMaxValueGene) { elementInfomationId=parseInt(neuronInfoInput.value)||null; if(!elementInfomationId){alert('Seleziona un Gene');return;} }
        else if (t===typeReadChimical) { elementHasRuleChimicalElementId=parseInt(neuronRuleInput.value)||null; if(!elementHasRuleChimicalElementId){alert('Seleziona una Regola');return;} }
        saveNeuronBtn.disabled=true;
        try {
            const saved = await reqSaveNeuron({id:currentNeuronId, brain_grid_width:normalize(widthInput.value||5), brain_grid_height:normalize(heightInput.value||5), type:t, grid_i:i, grid_j:j, radius, stop_before_target:neuronStopInput.checked, target_type:targetType, target_element_id:targetElementId, chemical_element_id:chemicalElementId, complex_chemical_element_id:complexChemicalElementId, gene_life_id:geneLifeId, gene_attack_id:geneAttackId, element_infomation_id:elementInfomationId, element_has_rule_chimical_element_id:elementHasRuleChimicalElementId});
            neuronItems=neuronItems.filter(n=>!(+n.grid_i===i&&+n.grid_j===j)); neuronItems.push(saved); updateNeuronInput(); renderGrid(); $(neuronModalEl).modal('hide');
        } catch(e) { alert(e.message||'Errore'); } finally { saveNeuronBtn.disabled=false; }
    });

    deleteNeuronBtn.addEventListener('click', async function() {
        if (!selectedCell) return;
        const i=+selectedCell.i, j=+selectedCell.j;
        const neuron = findAtCell(i,j);
        deleteNeuronBtn.disabled=true;
        try {
            await reqDeleteNeuron({grid_i:i, grid_j:j});
            neuronItems=neuronItems.filter(n=>!(+n.grid_i===i&&+n.grid_j===j));
            if(neuron) neuronLinks=neuronLinks.filter(l=>+l.from_neuron_id!==+neuron.id&&+l.to_neuron_id!==+neuron.id);
            updateNeuronInput(); updateLinksInput(); renderGrid(); $(neuronModalEl).modal('hide');
        } catch(e){ alert(e.message||'Errore'); } finally{ deleteNeuronBtn.disabled=false; }
    });

    neuronTypeInput.addEventListener('change', toggleFields);
    neuronTargetTypeInput.addEventListener('change', toggleFields);
    if (!brainLocked) {
        widthInput.addEventListener('input', renderGrid);
        heightInput.addEventListener('input', renderGrid);
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    toggleFields();
    updateLinksInput();
    renderGrid();
    renderCircuitsTable();

    // ── Save grid button ──────────────────────────────────────────────────────
    const saveGridBtn = document.getElementById('btn-save-brain-grid-ec');
    if (saveGridBtn) {
        saveGridBtn.addEventListener('click', async function() {
            saveGridBtn.disabled = true;
            try {
                const r = await fetch(@json(route('element-components.brain.grid.save', $elementComponent)), {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrfToken},
                    body: JSON.stringify({
                        brain_grid_width: normalize(widthInput.value || 5),
                        brain_grid_height: normalize(heightInput.value || 5)
                    })
                });
                const d = await r.json();
                if (d.success) {
                    if (typeof toastr !== 'undefined') toastr.success('Griglia salvata.');
                    else alert('Griglia salvata.');
                } else {
                    if (typeof toastr !== 'undefined') toastr.error(d.message || 'Errore');
                    else alert(d.message || 'Errore');
                }
            } catch(e) { alert('Errore: ' + e.message); }
            finally { saveGridBtn.disabled = false; }
        });
    }
});
</script>
@endpush

@push('css')
<style>
    #circuits-table-body-ec tr { cursor: pointer; }
    #circuits-table-body-ec tr:hover td { background-color: #f2f2f2 !important; }
</style>
@endpush
