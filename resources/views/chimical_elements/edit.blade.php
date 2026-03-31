@extends('adminlte::page')

@section('title', 'Modifica Elemento Chimico')

@section('content_header')
    <h1>Modifica Elemento Chimico</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifica Elemento Chimico</h3>
        </div>
        <form action="{{ route('chimical-elements.update', $chimicalElement) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nome <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name', $chimicalElement->name) }}"
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
                           value="{{ old('symbol', $chimicalElement->symbol) }}"
                           required>
                    @error('symbol')
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
                <a href="{{ route('chimical-elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
