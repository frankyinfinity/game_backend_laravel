@extends('adminlte::page')

@section('title', 'Modifica Famiglia Tile')

@section('content_header')
<h1>Modifica Famiglia Tile</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Modifica Famiglia Tile</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('family-tiles.update', $familyTile) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $familyTile->name }}" required>
            </div>
            <div class="form-group">
                <label for="type">Tipo</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="solid" {{ $familyTile->type == 'solid' ? 'selected' : '' }}>Solido</option>
                    <option value="liquid" {{ $familyTile->type == 'liquid' ? 'selected' : '' }}>Liquido</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</div>
@stop