@extends('adminlte::page')

@section('title', 'Crea Regola Elemento Chimico')

@section('content_header')
    <h1>Crea Regola Elemento Chimico</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuova Regola</h3>
        </div>
        <form action="{{ route('rule-chimical-elements.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="chimical_element_id">Elemento Chimico</label>
                    <select class="form-control @error('chimical_element_id') is-invalid @enderror"
                            id="chimical_element_id"
                            name="chimical_element_id">
                        <option value="">Seleziona Elemento Chimico</option>
                        @foreach($chimicalElements as $ce)
                            <option value="{{ $ce->id }}" {{ old('chimical_element_id') == $ce->id ? 'selected' : '' }}>{{ $ce->name }} ({{ $ce->symbol }})</option>
                        @endforeach
                    </select>
                    @error('chimical_element_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="complex_chimical_element_id">Elemento Chimico Complesso</label>
                    <select class="form-control @error('complex_chimical_element_id') is-invalid @enderror"
                            id="complex_chimical_element_id"
                            name="complex_chimical_element_id">
                        <option value="">Seleziona Elemento Chimico Complesso</option>
                        @foreach($complexChimicalElements as $cce)
                            <option value="{{ $cce->id }}" {{ old('complex_chimical_element_id') == $cce->id ? 'selected' : '' }}>{{ $cce->name }}</option>
                        @endforeach
                    </select>
                    @error('complex_chimical_element_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="text-muted">Seleziona almeno uno dei due campi sopra.</small>
                </div>
                <div class="form-group">
                    <label for="min">Min <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('min') is-invalid @enderror"
                           id="min"
                           name="min"
                           value="{{ old('min', 0) }}"
                           min="0"
                           required>
                    @error('min')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="max">Max <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('max') is-invalid @enderror"
                           id="max"
                           name="max"
                           value="{{ old('max', 0) }}"
                           min="0"
                           required>
                    @error('max')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salva
                </button>
                <a href="{{ route('rule-chimical-elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
