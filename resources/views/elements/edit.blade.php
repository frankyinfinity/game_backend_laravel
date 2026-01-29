@extends('adminlte::page')

@section('title', 'Modifica Elemento')

@section('content_header')
    <h1>Modifica Elemento</h1>
@stop

@section('content')
    <form action="{{ route('elements.update', $element) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab" aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-diffusion-link" data-toggle="pill" href="#tab-diffusion" role="tab" aria-controls="tab-diffusion" aria-selected="false">Diffusione</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    
                    <!-- TAB DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-link">
                        <div class="form-group">
                            <label for="name">Nome <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $element->name) }}" 
                                   required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="element_type_id">Tipologia <span class="text-danger">*</span></label>
                            <select class="form-control @error('element_type_id') is-invalid @enderror" 
                                    id="element_type_id" 
                                    name="element_type_id" 
                                    required>
                                <option value="">Seleziona Tipologia</option>
                                @foreach($elementTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('element_type_id', $element->element_type_id) == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('element_type_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="climates">Climi Validi <span class="text-muted">(Seleziona multipla: Ctrl+Click)</span></label>
                            <select class="form-control @error('climates') is-invalid @enderror" 
                                    id="climates" 
                                    name="climates[]" 
                                    multiple
                                    style="min-height: 200px;">
                                @foreach($climates as $climate)
                                    <option value="{{ $climate->id }}" 
                                        {{ (collect(old('climates', $element->climates->pluck('id')))->contains($climate->id)) ? 'selected' : '' }}>
                                        {{ $climate->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Nota: Se modifichi i climi, salva per aggiornare il tab Diffusione.</small>
                            @error('climates')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- TAB DIFFUSIONE -->
                    <div class="tab-pane fade" id="tab-diffusion" role="tabpanel" aria-labelledby="tab-diffusion-link">
                         @if($element->climates->isEmpty())
                            <div class="alert alert-warning">
                                <i class="icon fas fa-exclamation-triangle"></i> Nessun clima associato.
                                Seleziona e salva dei climi nel tab "Dati Generali" per configurare la diffusione.
                            </div>
                         @else
                            <div class="row">
                                <div class="col-3">
                                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                        @foreach($element->climates as $index => $climate)
                                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                                               id="v-pills-{{ $climate->id }}-tab" 
                                               data-toggle="pill" 
                                               href="#v-pills-{{ $climate->id }}" 
                                               role="tab" 
                                               aria-controls="v-pills-{{ $climate->id }}" 
                                               aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                                {{ $climate->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-9">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        @foreach($element->climates as $index => $climate)
                                            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                                                 id="v-pills-{{ $climate->id }}" 
                                                 role="tabpanel" 
                                                 aria-labelledby="v-pills-{{ $climate->id }}-tab">
                                                 
                                                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                                    <table class="table table-bordered table-hover head-fixed">
                                                        <thead>
                                                            <tr>
                                                                <th>Tile</th>
                                                                <th style="width: 150px;">
                                                                    Diffusione (0-100%)
                                                                    <i class="fas fa-info-circle text-muted ml-1" title="Questo indica la percentuale in cui un elemento può apparire in quel tile in quel clima" data-toggle="tooltip" style="cursor: help;"></i>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($allTiles as $tile)
                                                                @php
                                                                    $val = $diffusionMap[$climate->id][$tile->id] ?? 0;
                                                                @endphp
                                                                <tr>
                                                                    <td class="align-middle">
                                                                        <div style="display:flex; align-items:center;">
                                                                            <span style="display:inline-block; width: 24px; height: 24px; background-color: {{ $tile->color }}; margin-right: 10px; border:1px solid #ddd; border-radius: 3px;"></span>
                                                                            {{ $tile->name }}
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number" 
                                                                               name="diffusion[{{ $climate->id }}][{{ $tile->id }}]" 
                                                                               value="{{ $val }}" 
                                                                               min="0" 
                                                                               max="100" 
                                                                               class="form-control">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                         @endif
                    </div>

                </div>
            </div>
            
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Aggiorna
                </button>
                <a href="{{ route('elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
@stop
