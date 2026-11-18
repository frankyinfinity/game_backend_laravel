@extends('adminlte::page')

@section('title', 'Crea Punteggio')

@section('content_header')
    <h1>Crea Punteggio</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuovo Punteggio</h3>
        </div>
        <form action="{{ route('scores.store') }}" method="POST">
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
                    <label for="group_score_id">Gruppo Punteggio</label>
                    <select class="form-control @error('group_score_id') is-invalid @enderror"
                            id="group_score_id"
                            name="group_score_id">
                        <option value="">— Nessun gruppo —</option>
                        @foreach($groupScores as $groupScore)
                            <option value="{{ $groupScore->id }}" {{ (string) old('group_score_id') === (string) $groupScore->id ? 'selected' : '' }}>
                                {{ $groupScore->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('group_score_id')
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
                <a href="{{ route('scores.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </form>
    </div>
@stop
