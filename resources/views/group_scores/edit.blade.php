@extends('adminlte::page')

@section('title', 'Modifica Gruppo Punteggio')

@section('content_header')
    <h1>Modifica Gruppo Punteggio</h1>
@stop

@section('content')
    <form action="{{ route('group-scores.update', $groupScore) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Dati Generali</h3>
            </div>

            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nome <span class="text-danger">*</span></label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name', $groupScore->name) }}"
                           required>
                    @error('name')
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
                <a href="{{ route('group-scores.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </div>
    </form>
@stop
