<!-- Grid dimensions -->
<div class="row mb-3">
    <div class="col-md-3">
        <label class="font-weight-bold">Larghezza Griglia</label>
        <input type="number" class="form-control" id="el-brain-grid-width" min="1" value="{{ $brainGridWidth }}">
    </div>
    <div class="col-md-3">
        <label class="font-weight-bold">Altezza Griglia</label>
        <input type="number" class="form-control" id="el-brain-grid-height" min="1" value="{{ $brainGridHeight }}">
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <button type="button" class="btn btn-primary btn-sm" id="el-brain-save-grid">
            <i class="fas fa-save mr-1"></i> Salva Griglia
        </button>
    </div>
</div>

<div class="row">
    <!-- LEFT: Brain Grid (read-only, shows placed neurons) -->
    <div class="col-lg-7 col-12">
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header"><span class="font-weight-bold"><i class="fas fa-brain mr-1"></i> Griglia Cervello Element</span></div>
            <div class="card-body p-2 text-center">
                <canvas id="el-brain-canvas" width="640" height="640" style="border:2px solid #dee2e6;border-radius:4px;background:#fff;"></canvas>
            </div>
        </div>
    </div>

    <!-- RIGHT: Component Brains Table -->
    <div class="col-lg-5 col-12">
        <div class="card card-outline card-info shadow-sm mb-3">
            <div class="card-header"><span class="font-weight-bold"><i class="fas fa-puzzle-piece mr-1"></i> Cervelli Componenti</span></div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover table-sm mb-0" id="el-brain-components-table">
                    <thead class="bg-light">
                        <tr><th>Componente</th><th>Neuroni</th><th>Griglia</th><th>Stato</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach($componentBrains as $cb)
                        <tr data-brain-index="{{ $loop->index }}">
                            <td>{{ $cb['component_name'] }}</td>
                            <td>{{ $cb['neuron_count'] }}</td>
                            <td>{{ $cb['grid_width'] }}x{{ $cb['grid_height'] }}</td>
                            <td>
                                @if($cb['is_placed'])
                                <span class="badge badge-success">Posizionato</span>
                                @else
                                <span class="badge badge-secondary" id="el-brain-status-{{ $loop->index }}">Non posizionato</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-info el-brain-view-btn mr-1" data-index="{{ $loop->index }}" title="Visualizza">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(!$cb['is_placed'])
                                <button type="button" class="btn btn-xs btn-success el-brain-place-btn" data-index="{{ $loop->index }}" title="Posiziona">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(empty($componentBrains))
                <p class="text-muted text-center py-3 mb-0">Nessun componente con cervello.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Visualizza Brain Componente -->
<div class="modal fade" id="elBrainViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold" id="el-brain-view-title"><i class="fas fa-brain mr-2"></i> Cervello</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body text-center">
            <canvas id="el-brain-view-canvas" width="480" height="480" style="border:2px solid #dee2e6;border-radius:4px;background:#fff;"></canvas>
        </div>
    </div></div>
</div>

<!-- Modal Posiziona Brain (con anteprima + frecce) -->
<div class="modal fade" id="elBrainPlaceModal" tabindex="-1">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title font-weight-bold" id="el-brain-place-title"><i class="fas fa-crosshairs mr-2"></i> Posiziona Cervello</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <p class="text-muted mb-2">Anteprima: usa le frecce per spostare il cervello nella griglia dell'Element.</p>
            <div class="text-center">
                <canvas id="el-brain-place-canvas" width="480" height="480" style="border:2px solid #dee2e6;border-radius:4px;background:#fff;"></canvas>
            </div>
            <div class="d-flex justify-content-center align-items-center mt-3">
                <div class="text-center">
                    <div><button type="button" class="btn btn-dark btn-sm" id="el-brain-place-up"><i class="fas fa-arrow-up"></i></button></div>
                    <div class="mt-1">
                        <button type="button" class="btn btn-dark btn-sm mr-1" id="el-brain-place-left"><i class="fas fa-arrow-left"></i></button>
                        <button type="button" class="btn btn-dark btn-sm mr-1" id="el-brain-place-down"><i class="fas fa-arrow-down"></i></button>
                        <button type="button" class="btn btn-dark btn-sm" id="el-brain-place-right"><i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
            <button type="button" class="btn btn-success" id="el-brain-place-confirm"><i class="fas fa-check mr-1"></i> Conferma Posizione</button>
        </div>
    </div></div>
</div>
