@extends('adminlte::page')

@section('title', 'Crea Famiglia Tile')

@section('content_header')
<h1>Crea Famiglia Tile</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Crea Famiglia Tile</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('family-tiles.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name">Nome</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
                    <div class="form-group">
                        <label for="type">Tipo</label>
                        <select class="form-control" id="type" name="type" required>
                            @foreach(\App\Models\FamilyTile::getTypeLabels() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
            <button type="submit" class="btn btn-primary">Salva</button>
            <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
        </form>
    </div>
</div>
@stop