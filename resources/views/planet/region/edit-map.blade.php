@extends('adminlte::page')

@section('title', 'Modifica Mappa Regione')

@section('content_header')@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="mb-0">Modifica Mappa Regione: {{$region->name}}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <button type="button" id="map-save-all" class="btn btn-primary btn-block btn-sm">
                            <i class="fa fa-save"></i> Salva
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{route('planets.show', [$region->planet->id])}}">
                            <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        @include('planet.region.map', compact('region', 'tiles', 'map'))
    </div>
</div>
@stop
