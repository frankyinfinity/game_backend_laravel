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

                                    {{-- Name Space field --}}
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="name_space">Nome Specie <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="text" name="name_space" id="name_space" class="form-control @error('name_space') is-invalid @enderror"
                                                       value="{{ old('name_space') }}" placeholder="Nome della specie" required>
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <span class="fas fa-dna"></span>
                                                    </div>
                                                </div>
                                                @error('name_space')
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
        });
    </script>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
