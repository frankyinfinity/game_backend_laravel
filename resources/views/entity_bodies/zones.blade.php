@php
    $imagePath = $entityBody->image ? 'storage/entity_bodies/' . $entityBody->image : null;
    $imageUrl  = $imagePath && \Storage::disk('entity_bodies')->exists($entityBody->image) ? asset($imagePath) : null;
@endphp

<div id="entity-body-zones-component">
    <style>
#zones-tbody .dot-preview { flex-shrink: 0; }
#zones-tbody tr.selected td { background-color: #fff3f3 !important; }
#zones-tbody tr { cursor: pointer; }
</style>

    @if($entityBody->state < 2)
    <!-- ===== HEADER ===== -->
    <div class="row mb-3">
        <div class="col-md-7">
            <div class="d-flex align-items-center mb-1">
                <strong class="mr-2">Zona selezionata:</strong>
                <span class="font-weight-bold text-primary" id="editor-zone-name">nessuna</span>
            </div>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-pen"></i> Nuova zona</span>
                </div>
                <input type="text" class="form-control" id="new-zone-name"
                       placeholder="Nome (es. Testa, Corpo, Gambe)...">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="btn-cancel-polygon" title="Annulla" style="display:none">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
            <small class="text-muted d-block mt-1">Clicca sul disegno per tracciare i vertici. I punti delle zone salvate sono visibili in colori diversi sul disegno.</small>
        </div>
        <div class="col-md-5 text-md-right mt-2 mt-md-0">
            <button class="btn btn-outline-success mr-2" type="button" id="btn-brush-add" title="Pennello Aggiungi: clicca sui pixel neri per aggiungerli alla zona" disabled>
                <i class="fas fa-plus"></i> Aggiungi Pixel
            </button>
            <button class="btn btn-outline-danger mr-2" type="button" id="btn-brush-remove" title="Pennello Rimuovi: clicca sui pixel grigi/colorati per rimuoverli" disabled>
                <i class="fas fa-minus"></i> Rimuovi Pixel
            </button>
            <button class="btn btn-danger" type="button" id="btn-delete-selected-zone" title="Elimina la zona selezionata" disabled>
                <i class="fas fa-trash"></i> Elimina Zona
            </button>
        </div>
    </div>
    @else
    <!-- ===== HEADER IN SOLA LETTURA ===== -->
    <div class="row mb-3">
        <div class="col-md-7">
            <div class="d-flex align-items-center mb-1">
                <strong class="mr-2">Zona selezionata:</strong>
                <span class="font-weight-bold text-primary" id="editor-zone-name">nessuna</span>
            </div>
            <span class="badge badge-warning p-2"><i class="fas fa-lock mr-1"></i> Zone in sola lettura (Stato: Bloccato)</span>
        </div>
        <div class="col-md-5 text-md-right mt-2 mt-md-0 d-none">
            <button class="btn btn-outline-success mr-2" type="button" id="btn-brush-add" disabled></button>
            <button class="btn btn-outline-danger mr-2" type="button" id="btn-brush-remove" disabled></button>
            <button class="btn btn-danger" type="button" id="btn-delete-selected-zone" disabled></button>
        </div>
    </div>
    @endif

    <!-- ===== MAIN CONTENT (Canvas + Tabella affiancati) ===== -->
    <div class="row">
        <!-- CANVAS (a sinistra) -->
        <div class="col-lg-7 col-12">
            @if(!empty($imageUrl))
            <div class="card card-outline card-secondary shadow-sm mb-3">
                <div class="card-header">
                    <span class="text-dark font-weight-bold">
                        <i class="fas fa-pencil-ruler mr-1"></i>Editor Zone
                    </span>
                    <span class="text-muted small float-right mt-1">Il primo click sul disegno apre il nome della zona</span>
                </div>
                <div class="card-body p-2 d-flex justify-content-center">
                    <div style="position:relative; display:inline-block;">
                        <img id="body-image"
                             src="{{ $imageUrl }}"
                             crossorigin="anonymous"
                             alt="Entity Body"
                             style="width:512px; height:512px; image-rendering:pixelated; display:block; cursor:crosshair;">
                        <canvas id="zone-canvas"
                                width="512" height="512"
                                style="position:absolute; top:0; left:0; pointer-events:auto; cursor:crosshair;"></canvas>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- TABELLA ZONE SALVATE (a destra) -->
        <div class="col-lg-5 col-12">
            <div class="card card-outline card-light shadow-sm">
                <div class="card-header">
                    <span class="text-dark font-weight-bold"><i class="fas fa-list mr-1"></i>Zone create</span>
                </div>
                <div class="card-body p-0">
                    <div id="zones-loading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    <div id="zones-empty" class="alert alert-light text-center m-3" style="display:none">
                        <i class="fas fa-map-marker-alt fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Nessuna zona ancora.<br>Clicca sul disegno per iniziare.</p>
                    </div>
                    <div id="zones-list-wrapper" style="display:none">
                        <div class="table-responsive" style="max-height:440px; overflow-y:auto;">
                            <table class="table table-bordered table-hover table-sm mb-0" id="zones-table">
                                <thead class="bg-light text-dark sticky-top">
                                    <tr>
                                        <th style="width:50px" class="text-center">Colore</th>
                                        <th>Nome</th>
                                        <th class="text-center">Punti</th>
                                    </tr>
                                </thead>
                                <tbody id="zones-tbody">
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-muted small px-3 py-2 bg-light border-top">
                            <i class="fas fa-info-circle mr-1"></i>
                            Clicca una riga per evidenziarla e mostrare il poligono.<br>
                            Usa "Elimina Zona" per rimuoverla.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL NOME ZONA ===== -->
<div class="modal fade" id="zoneNameModal" tabindex="-1" role="dialog" aria-labelledby="zoneNameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="zoneNameModalLabel">Nuova zona</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2">Inserisci il nome della zona che vuoi tracciare:</p>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Nome</label>
                    <input type="text" class="form-control" id="zone-name-input"
                           placeholder="Es. Testa, Corpo, Gambe, Braccia...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-zone-name"><i class="fas fa-check"></i> Conferma</button>
            </div>
        </div>
    </div>
</div>
