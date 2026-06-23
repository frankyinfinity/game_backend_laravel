@php
    $imagePath = $elementBody->image ? 'storage/element_bodies/' . $elementBody->image : null;
    $imageUrl  = $imagePath && \Storage::disk('element_bodies')->exists($elementBody->image) ? asset($imagePath) : null;
@endphp

<div id="element-body-zones-component">
<style>
#eb-zones-tbody .dot-preview { flex-shrink: 0; }
#eb-zones-tbody tr.selected td { background-color: #fff3f3 !important; }
#eb-zones-tbody tr { cursor: pointer; }
</style>

@if($elementBody->state < 2)
<div class="row mb-3">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-1">
            <strong class="mr-2">Zona selezionata:</strong>
            <span class="font-weight-bold text-primary" id="eb-editor-zone-name">nessuna</span>
        </div>
        <div class="input-group">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-pen"></i> Nuova zona</span></div>
            <input type="text" class="form-control" id="eb-new-zone-name" placeholder="Nome (es. Testa, Corpo, Gambe)...">
            <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="button" id="eb-btn-cancel-polygon" title="Annulla" style="display:none"><i class="fas fa-times-circle"></i></button>
            </div>
        </div>
        <small class="text-muted d-block mt-1">Clicca sul disegno per tracciare i vertici.</small>
    </div>
    <div class="col-md-5 text-md-right mt-2 mt-md-0">
        <button class="btn btn-outline-success mr-2" type="button" id="eb-btn-brush-add" disabled><i class="fas fa-plus"></i> Aggiungi Pixel</button>
        <button class="btn btn-outline-danger mr-2" type="button" id="eb-btn-brush-remove" disabled><i class="fas fa-minus"></i> Rimuovi Pixel</button>
        <button class="btn btn-danger" type="button" id="eb-btn-delete-selected-zone" disabled><i class="fas fa-trash"></i> Elimina Zona</button>
    </div>
</div>
@else
<div class="row mb-3">
    <div class="col-md-7">
        <div class="d-flex align-items-center mb-1">
            <strong class="mr-2">Zona selezionata:</strong>
            <span class="font-weight-bold text-primary" id="eb-editor-zone-name">nessuna</span>
        </div>
        <span class="badge badge-warning p-2"><i class="fas fa-lock mr-1"></i> Zone in sola lettura</span>
    </div>
    <div class="col-md-5 d-none">
        <button class="btn btn-outline-success mr-2" type="button" id="eb-btn-brush-add" disabled></button>
        <button class="btn btn-outline-danger mr-2" type="button" id="eb-btn-brush-remove" disabled></button>
        <button class="btn btn-danger" type="button" id="eb-btn-delete-selected-zone" disabled></button>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-7 col-12">
        @if(!empty($imageUrl))
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header"><span class="text-dark font-weight-bold"><i class="fas fa-pencil-ruler mr-1"></i>Editor Zone</span></div>
            <div class="card-body p-2 d-flex justify-content-center">
                <div style="position:relative; display:inline-block;">
                    <img id="eb-body-image" src="{{ $imageUrl }}" crossorigin="anonymous" style="width:512px;height:512px;image-rendering:pixelated;display:block;cursor:crosshair;">
                    <canvas id="eb-zone-canvas" width="512" height="512" style="position:absolute;top:0;left:0;pointer-events:auto;cursor:crosshair;"></canvas>
                </div>
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-5 col-12">
        <div class="card card-outline card-light shadow-sm">
            <div class="card-header"><span class="text-dark font-weight-bold"><i class="fas fa-list mr-1"></i>Zone create</span></div>
            <div class="card-body p-0">
                <div id="eb-zones-loading" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                <div id="eb-zones-empty" class="alert alert-light text-center m-3" style="display:none"><i class="fas fa-map-marker-alt fa-2x text-muted mb-2"></i><p class="text-muted mb-0">Nessuna zona.</p></div>
                <div id="eb-zones-list-wrapper" style="display:none">
                    <div class="table-responsive" style="max-height:440px;overflow-y:auto;">
                        <table class="table table-bordered table-hover table-sm mb-0" id="eb-zones-table">
                            <thead class="bg-light text-dark sticky-top"><tr><th style="width:50px" class="text-center">Colore</th><th>Nome</th><th class="text-center">Punti</th></tr></thead>
                            <tbody id="eb-zones-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="ebZoneNameModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold">Nuova zona</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <div class="form-group mb-0"><label class="font-weight-bold">Nome</label><input type="text" class="form-control" id="eb-zone-name-input" placeholder="Es. Testa, Corpo..."></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button><button type="button" class="btn btn-primary" id="eb-btn-confirm-zone-name"><i class="fas fa-check"></i> Conferma</button></div>
    </div></div>
</div>
