@extends('adminlte::page')

@section('title', 'Crea Generatore Elemento Chimico')

@section('content_header')
    <h1>Crea Generatore Elemento Chimico</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuovo Generatore</h3>
        </div>
        <form action="{{ route('generator-chimical-elements.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nome <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="chimical_element_id">Elemento Chimico <span class="text-danger">*</span></label>
                    <select class="form-control @error('chimical_element_id') is-invalid @enderror"
                            id="chimical_element_id"
                            name="chimical_element_id"
                            required>
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
                    <label for="tick_quantity">Quantità per Tick <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control @error('tick_quantity') is-invalid @enderror"
                           id="tick_quantity"
                           name="tick_quantity"
                           value="{{ old('tick_quantity') }}"
                           min="1"
                           required>
                    @error('tick_quantity')
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
                <a href="{{ route('generator-chimical-elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
