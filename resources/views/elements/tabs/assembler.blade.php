<div class="row">
    <!-- LEFT: Body Selection + Main Grid -->
    <div class="col-lg-5 col-12">
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header"><span class="font-weight-bold"><i class="fas fa-child mr-1"></i> Corpo Element</span></div>
            <div class="card-body p-2">
                <div class="form-group mb-2">
                    <select class="form-control" id="asm-body-select">
                        <option value="">-- Seleziona un Corpo --</option>
                    </select>
                </div>
                <div class="text-center">
                    <canvas id="asm-main-canvas" width="512" height="512"
                        style="border:2px solid #dee2e6;border-radius:4px;image-rendering:pixelated;cursor:crosshair;background:#fff;"></canvas>
                </div>
                <!-- Direction buttons -->
                <div class="d-flex justify-content-center align-items-center mt-2 mb-2">
                    <div class="text-center">
                        <div>
                            <button type="button" class="btn btn-dark btn-sm" id="asm-move-up" disabled><i class="fas fa-arrow-up"></i></button>
                        </div>
                        <div class="mt-1">
                            <button type="button" class="btn btn-dark btn-sm mr-1" id="asm-move-left" disabled><i class="fas fa-arrow-left"></i></button>
                            <button type="button" class="btn btn-dark btn-sm mr-1" id="asm-move-down" disabled><i class="fas fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-dark btn-sm" id="asm-move-right" disabled><i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
                <!-- Zone color panel -->
                <div id="asm-zone-panel" class="card mt-2 p-2" style="display:none;">
                    <div class="d-flex align-items-center mb-2">
                        <span id="asm-zone-color-swatch" style="width:20px;height:20px;border:1px solid #000;border-radius:3px;margin-right:8px;"></span>
                        <strong id="asm-zone-name-label">Zona</strong>
                        <button type="button" class="btn btn-xs btn-secondary ml-auto" id="asm-zone-close"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="form-group mb-1"><label class="small mb-0">R</label><input type="range" class="form-control-range" id="asm-zone-r" min="0" max="255" value="0"></div>
                    <div class="form-group mb-1"><label class="small mb-0">G</label><input type="range" class="form-control-range" id="asm-zone-g" min="0" max="255" value="0"></div>
                    <div class="form-group mb-1"><label class="small mb-0">B</label><input type="range" class="form-control-range" id="asm-zone-b" min="0" max="255" value="0"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Components -->
    <div class="col-lg-7 col-12">
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold"><i class="fas fa-puzzle-piece mr-1"></i> Componenti</span>
                <button type="button" class="btn btn-sm btn-success" id="asm-add-component-btn" disabled><i class="fas fa-plus"></i> Aggiungi Componente</button>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive mb-3">
                    <table id="asm-added-components-table" class="table table-bordered table-hover table-sm w-100">
                        <thead class="bg-light"><tr><th>Nome</th><th>Body Anchor</th><th>Comp Anchor</th><th>Offset</th><th style="width:60px">Info</th>@if(!$element->isFinishAssembler())<th style="width:60px"></th>@endif</tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="asm-link-area" style="display:none;">
                    <div class="alert alert-info py-2 mb-2"><i class="fas fa-link mr-1"></i> Collega: clicca un'ancora blu del corpo (sinistra) e una del componente (destra).</div>
                    <div class="row">
                        <div class="col-6 text-center">
                            <h6 class="font-weight-bold">Corpo</h6>
                            <canvas id="asm-link-body-canvas" width="256" height="256" style="border:1px solid #ccc;image-rendering:pixelated;cursor:crosshair;background:#fff;"></canvas>
                        </div>
                        <div class="col-6 text-center">
                            <h6 class="font-weight-bold">Componente</h6>
                            <canvas id="asm-link-comp-canvas" width="256" height="256" style="border:1px solid #ccc;image-rendering:pixelated;cursor:crosshair;background:#fff;"></canvas>
                        </div>
                    </div>
                    <div class="mt-2 text-center">
                        <span class="mr-3">Corpo: <strong id="asm-link-body-anchor">-</strong></span>
                        <span class="mr-3">Componente: <strong id="asm-link-comp-anchor">-</strong></span>
                        <button type="button" class="btn btn-primary btn-sm" id="asm-link-confirm" disabled>Conferma</button>
                        <button type="button" class="btn btn-secondary btn-sm" id="asm-link-cancel">Annulla</button>
                    </div>
                </div>
            </div>
        </div>

        @if($element->isFinishAssembler())
        <!-- Card riepilogo dati aggregati -->
        <div class="card card-outline card-info shadow-sm mb-3">
            <div class="card-header">
                <span class="font-weight-bold">
                    @if($element->isConsumable())
                    <i class="fas fa-utensils mr-1"></i> Effetti Consumo
                    @else
                    <i class="fas fa-dna mr-1"></i> Geni e Elementi Chimici
                    @endif
                </span>
            </div>
            <div class="card-body p-2">
                @if($element->isConsumable())
                <table class="table table-bordered table-hover table-sm w-100 mb-0">
                    <thead class="bg-light"><tr><th>Gene</th><th>Effetto</th></tr></thead>
                    <tbody>
                        @forelse($element->genes as $gene)
                        <tr><td>{{ $gene->name }}</td><td>{{ $gene->pivot->effect >= 0 ? '+' : '' }}{{ $gene->pivot->effect }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-muted text-center">Nessun effetto consumo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @else
                <h6 class="font-weight-bold mb-2"><i class="fas fa-dna mr-1 text-info"></i> Geni</h6>
                <table class="table table-bordered table-hover table-sm w-100 mb-3">
                    <thead class="bg-light"><tr><th>Gene</th><th>Effetto</th></tr></thead>
                    <tbody>
                        @forelse($element->genes as $gene)
                        <tr><td>{{ $gene->name }}</td><td>{{ $gene->pivot->effect >= 0 ? '+' : '' }}{{ $gene->pivot->effect }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-muted text-center">Nessun gene.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <h6 class="font-weight-bold mb-2"><i class="fas fa-flask mr-1 text-warning"></i> Elementi Chimici</h6>
                <table class="table table-bordered table-hover table-sm w-100 mb-0">
                    <thead class="bg-light"><tr><th>Nome</th><th>Titolo</th></tr></thead>
                    <tbody>
                        @forelse($element->ruleChimicalElements as $rule)
                        <tr><td>{{ $rule->name }}</td><td>{{ $rule->title }}</td></tr>
                        @empty
                        <tr><td colspan="2" class="text-muted text-center">Nessuna regola.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<div class="form-group mt-3">
    <label class="font-weight-bold">JSON Assemblaggio (output)</label>
    <input type="hidden" id="asm-json-output" name="assembler_json" value="">
</div>

@if(!$element->isFinishAssembler())
<div class="mt-3 border-top pt-3">
    <button type="button" class="btn btn-success shadow-sm" id="asm-finish-btn" disabled onclick="submitFinishAssembler();">
        <i class="fas fa-check-circle mr-1"></i> Termina Assemblaggio e Blocca
    </button>
    <small class="text-muted ml-2">Seleziona un corpo e almeno un componente per abilitare.</small>
</div>
@else
<div class="alert alert-success mt-3">
    <i class="fas fa-lock mr-1"></i> Assemblaggio bloccato. Non è possibile modificare.
</div>
@endif

<!-- MODAL -->
<div class="modal fade" id="asmComponentModal" tabindex="-1">
    <div class="modal-dialog modal-xl"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold"><i class="fas fa-puzzle-piece mr-2"></i> Seleziona Componente</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <table id="asm-components-datatable" class="table table-bordered table-hover table-sm w-100">
                <thead class="bg-light"><tr>
                    <th style="width:50px">Img</th><th>Nome</th><th>Tipologia</th>
                    @if($element->isConsumable())<th>Effetti Consumo</th>@else<th>Geni</th><th>Elementi Chimici</th><th>Cervello</th>@endif
                    <th>Anchors</th><th style="width:80px"></th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div></div>
</div>

<!-- MODAL INFO COMPONENTE -->
<div class="modal fade" id="asmInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold" id="asm-info-modal-title"><i class="fas fa-info-circle mr-2"></i> Info Componente</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body" id="asm-info-modal-body"></div>
    </div></div>
</div>

<!-- MODAL CERVELLO COMPONENTE -->
<div class="modal fade" id="asmBrainModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold"><i class="fas fa-brain mr-2"></i> Cervello Componente</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body text-center" style="position:relative;">
            <canvas id="asm-brain-canvas" width="640" height="640" style="border:2px solid #dee2e6;border-radius:4px;background:#fff;"></canvas>
            <div id="asm-brain-tooltip" style="display:none;position:absolute;background:#fff;border:1px solid #000;border-radius:4px;padding:4px 8px;font-size:12px;font-family:Consolas;pointer-events:none;z-index:10;white-space:nowrap;"></div>
            <div id="asm-brain-info" class="mt-2 text-muted small"></div>
        </div>
    </div></div>
</div>
