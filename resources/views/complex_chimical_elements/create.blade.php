@extends('adminlte::page')

@section('title', 'Crea Elemento Chimico Complesso')

@section('content_header')
    <h1>Crea Elemento Chimico Complesso</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuovo Elemento Chimico Complesso</h3>
        </div>
        <form action="{{ route('complex-chimical-elements.store') }}" method="POST">
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
                    <label for="symbol">Simbolo <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('symbol') is-invalid @enderror"
                           id="symbol"
                           name="symbol"
                           value="{{ old('symbol') }}"
                           required>
                    @error('symbol')
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
                <a href="{{ route('complex-chimical-elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
