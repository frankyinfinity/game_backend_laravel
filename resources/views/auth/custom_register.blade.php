@extends('adminlte::master')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('classes_body', 'layout-top-nav')

@section('body')
    <div class="wrapper">
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-12 text-center">
                            <h1 class="m-0">Registrazione</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Inserisci i tuoi dati</h3>
                            <div class="card-tools">
                                <a href="javascript:history.back()" class="btn btn-tool">
                                    <i class="fas fa-arrow-left"></i> Indietro
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('register') }}" method="post">
                                @csrf

                                <div class="row">
                                    {{-- Name field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="name">Nome <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                                       value="{{ old('name') }}" placeholder="Inserisci il tuo nome" required autofocus>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-user"></span>
                                                    </div>
                                                </div>
                                                @error('name')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Email field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="email">Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                                       value="{{ old('email') }}" placeholder="Inserisci la tua email" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-envelope"></span>
                                                    </div>
                                                </div>
                                                @error('email')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Password field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="password">Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                                       placeholder="Inserisci la password" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-lock"></span>
                                                    </div>
                                                </div>
                                                @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    {{-- Birth Planet field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="birth_planet_id">Pianeta Natale <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="birth_planet_id" id="birth_planet_id" class="form-control @error('birth_planet_id') is-invalid @enderror" required>
                                                    <option value="">Caricamento pianeti...</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-globe"></span>
                                                    </div>
                                                </div>
                                                @error('birth_planet_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Birth Region field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="birth_region_id">Regione Natale <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <select name="birth_region_id" id="birth_region_id" class="form-control @error('birth_region_id') is-invalid @enderror" required disabled>
                                                    <option value="">Seleziona prima un pianeta</option>
                                                </select>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-map-marker-alt"></span>
                                                    </div>
                                                </div>
                                                @error('birth_region_id')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Name Specie field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="name_specie">Nome Specie <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" name="name_specie" id="name_specie" class="form-control @error('name_specie') is-invalid @enderror"
                                                       value="{{ old('name_specie') }}" placeholder="Nome della specie" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-dna"></span>
                                                    </div>
                                                </div>
                                                @error('name_specie')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    {{-- Tile I field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="tile_i">Posizione I <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="tile_i" id="tile_i" class="form-control @error('tile_i') is-invalid @enderror"
                                                       value="{{ old('tile_i', 0) }}" placeholder="Coordinata i" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-arrows-alt-h"></span>
                                                    </div>
                                                </div>
                                                @error('tile_i')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tile J field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="tile_j">Posizione J <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="tile_j" id="tile_j" class="form-control @error('tile_j') is-invalid @enderror"
                                                       value="{{ old('tile_j', 0) }}" placeholder="Coordinata j" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-arrows-alt-v"></span>
                                                    </div>
                                                </div>
                                                @error('tile_j')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h5>Geni della Specie</h5>
                                <div id="genes-container" class="mt-3">
                                    <p class="text-muted">Caricamento geni...</p>
                                </div>
                                <input type="hidden" name="gene_ids" id="gene_ids_hidden">

                                {{-- Register button --}}
                                <div class="row mt-3">
                                    <div class="col-md-12 text-left">
                                        <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                                            <i class="fas fa-user-plus"></i> Registrati
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer">
                            <p class="mb-0 text-center">
                                <a href="{{ route('login') }}">Hai già un account? Accedi</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const planetSelect = document.getElementById('birth_planet_id');
            const regionSelect = document.getElementById('birth_region_id');
            
            // Load planets
            fetch('/api/planets')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.planets) {
                        planetSelect.innerHTML = '<option value="">Seleziona un pianeta</option>';
                        data.planets.forEach(planet => {
                            const option = document.createElement('option');
                            option.value = planet.id;
                            option.textContent = planet.name;
                            planetSelect.appendChild(option);
                        });
                    } else {
                        planetSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching planets:', error);
                    planetSelect.innerHTML = '<option value="">Errore di connessione</option>';
                });

            // Handle planet change to load regions
            planetSelect.addEventListener('change', function() {
                const planetId = this.value;
                
                if (!planetId) {
                    regionSelect.innerHTML = '<option value="">Seleziona prima un pianeta</option>';
                    regionSelect.disabled = true;
                    return;
                }

                regionSelect.disabled = false;
                regionSelect.innerHTML = '<option value="">Caricamento regioni...</option>';

                fetch(`/api/regions/${planetId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.regions) {
                            if (data.regions.length > 0) {
                                regionSelect.innerHTML = '<option value="">Seleziona una regione</option>';
                                data.regions.forEach(region => {
                                    const option = document.createElement('option');
                                    option.value = region.id;
                                    option.textContent = `${region.name} (${region.width}x${region.height})`;
                                    regionSelect.appendChild(option);
                                });
                            } else {
                                regionSelect.innerHTML = '<option value="">Nessuna regione disponibile</option>';
                            }
                        } else {
                            regionSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching regions:', error);
                        regionSelect.innerHTML = '<option value="">Errore di connessione</option>';
                    });
            });

            // Load Genes
            const genesContainer = document.getElementById('genes-container');
            const geneIdsHidden = document.getElementById('gene_ids_hidden');

            fetch('/api/registration_genes')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.genes) {
                        genesContainer.innerHTML = `
                            <div class="row font-weight-bold small text-muted mb-2 border-bottom pb-1">
                                <div class="col-md-3">Nome Gene</div>
                                <div class="col-md-1 text-center">Min</div>
                                <div class="col-md-3 text-center">Range Max (Da-A)</div>
                                <div class="col-md-2">Iniziale <span class="text-danger">*</span></div>
                                <div class="col-md-3">Max Sincronizzato</div>
                            </div>
                        `;
                        const geneIds = [];
                        
                        data.genes.forEach(gene => {
                            geneIds.push(gene.id);
                            
                            const isMaxDefinitive = gene.max !== null;
                            const defaultValue = (!isMaxDefinitive && gene.max_from !== null) ? gene.max_from : (gene.min || 0);
                            
                            const geneRow = document.createElement('div');
                            geneRow.className = 'row mb-1 py-1 border-bottom align-items-center bg-white';
                            
                            let rangeHtml = '';
                            if (!isMaxDefinitive) {
                                rangeHtml = `
                                    <div class="col-md-3 text-center">
                                        <span class="small font-weight-bold text-primary">${gene.max_from || 0}</span>
                                        <span class="small text-muted mx-1">/</span>
                                        <span class="small font-weight-bold text-primary">${gene.max_to || 0}</span>
                                    </div>`;
                            } else {
                                rangeHtml = `
                                    <div class="col-md-3 text-center">
                                        <span class="badge badge-secondary px-3">Fisso: ${gene.max}</span>
                                    </div>`;
                            }

                            geneRow.innerHTML = `
                                <div class="col-md-3">
                                    <span class="font-weight-bold small d-block mb-0">${gene.name || gene.key}</span>
                                    <small class="text-muted" style="font-size: 0.7rem;">${gene.key}</small>
                                </div>
                                <div class="col-md-1 text-center">
                                    <input type="hidden" name="gene_min_${gene.id}" value="${gene.min || 0}">
                                    <span class="small font-weight-bold">${gene.min || 0}</span>
                                </div>
                                ${rangeHtml}
                                <div class="col-md-2">
                                    <input type="number" name="gene_value_id_${gene.id}" id="initial_${gene.id}" 
                                           class="form-control form-control-sm" value="${defaultValue}" step="1" required
                                           min="${isMaxDefinitive ? (gene.min || 0) : (gene.max_from || 0)}" 
                                           max="${isMaxDefinitive ? gene.max : (gene.max_to || 999999)}">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="gene_max_${gene.id}" id="max_sync_${gene.id}" 
                                           class="form-control form-control-sm" 
                                           style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;"
                                           value="${isMaxDefinitive ? gene.max : defaultValue}" 
                                           readonly>
                                </div>
                            `;
                            genesContainer.appendChild(geneRow);

                            // Sync logic for non-definitive max
                            if (!isMaxDefinitive) {
                                const initialInput = document.getElementById(`initial_${gene.id}`);
                                const maxSyncInput = document.getElementById(`max_sync_${gene.id}`);
                                
                                initialInput.addEventListener('input', function() {
                                    maxSyncInput.value = this.value;
                                });
                            }
                        });
                        
                        geneIdsHidden.value = geneIds.join(',');
                    } else {
                        genesContainer.innerHTML = '<p class="text-danger">Errore nel caricamento dei geni.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching genes:', error);
                    genesContainer.innerHTML = '<p class="text-danger">Errore di connessione durante il caricamento dei geni.</p>';
                });
        });
    </script>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
