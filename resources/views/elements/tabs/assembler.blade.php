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
                        <thead class="bg-light"><tr><th>Nome</th><th>Body Anchor</th><th>Comp Anchor</th><th>Offset</th><th style="width:60px"></th></tr></thead>
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
    </div>
</div>

<div class="form-group mt-3">
    <label class="font-weight-bold">JSON Assemblaggio (output)</label>
    <textarea class="form-control" id="asm-json-output" name="assembler_json" rows="6" readonly></textarea>
</div>

<!-- MODAL -->
<div class="modal fade" id="asmComponentModal" tabindex="-1">
    <div class="modal-dialog modal-xl"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold"><i class="fas fa-puzzle-piece mr-2"></i> Seleziona Componente</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <table id="asm-components-datatable" class="table table-bordered table-hover table-sm w-100">
                <thead class="bg-light"><tr>
                    <th style="width:50px">Img</th><th>Nome</th><th>Tipologia</th>
                    @if($element->isConsumable())<th>Effetti Consumo</th>@else<th>Geni</th><th>Elementi Chimici</th>@endif
                    <th>Anchors</th><th style="width:80px"></th>
                </tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div></div>
</div>
