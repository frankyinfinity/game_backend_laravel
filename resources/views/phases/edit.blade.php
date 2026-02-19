@extends('adminlte::page')

@section('title', 'Modifica Fase - ' . $age->name)

@section('content_header')
    <h1>Modifica Fase - {{ $age->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifica Fase</h3>
        </div>
        <form action="{{ route('ages.phases.update', [$age, $phase]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nome <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $phase->name) }}" 
                           required>
                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="height">Altezza <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control @error('height') is-invalid @enderror" 
                           id="height" 
                           name="height" 
                           value="{{ old('height', $phase->height) }}" 
                           min="1"
                           required>
                    @error('height')
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
                <a href="{{ route('ages.phases.index', $age) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
