@extends('adminlte::page')

@section('title', 'Modifica Elemento')

@section('content_header')
    <h1>Modifica Elemento</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifica Elemento</h3>
        </div>
        <form action="{{ route('elements.update', $element) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
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
                    @error('climates')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
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
        </form>
    </div>
@stop
