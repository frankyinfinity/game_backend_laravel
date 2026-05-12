@extends('adminlte::page')

@section('title', 'Famiglia Tile: ' . $familyTile->name)

@section('content_header')
<h1>Famiglia Tile: {{ $familyTile->name }}</h1>
<a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Torna alla Lista</a>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">ID</dt>
            <dd class="col-sm-9">{{ $familyTile->id }}</dd>
            <dt class="col-sm-3">Nome</dt>
            <dd class="col-sm-9">{{ $familyTile->name }}</dd>
            <dt class="col-sm-3">Tipo</dt>
            <dd class="col-sm-9">{{ ucfirst($familyTile->type) }}</dd>
        </dl>
    </div>
</div>
@stop