@extends('adminlte::page')

@section('title', 'Diffusione Elemento: ' . $element->name)

@section('content_header')
<h1>Diffusione Elemento: {{ $element->name }}</h1>
<a href="{{ route('elements.diffusion.index') }}" class="btn btn-secondary">Torna alla Lista</a>
@stop

@section('content')
<form action="{{ route('elements.diffusion.update', $element) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dati di Diffusione</h3>
            <div class="card-tools">
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </div>
        <div class="card-body">
            @include('elements.tabs.diffusion')
        </div>
    </div>
</form>
@stop