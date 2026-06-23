@extends('adminlte::page')

@section('title', 'Dettaglio Corpo Element')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0"><h4 class="mb-0 text-dark font-weight-bold">Dettaglio Corpo Element</h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 col-12 mb-4">
                <table class="table table-bordered bg-light">
                    <tr><th style="width:30%" class="text-dark font-weight-bold">ID</th><td>{{ $elementBody->id }}</td></tr>
                    <tr><th class="text-dark font-weight-bold">Nome</th><td>{{ $elementBody->name }}</td></tr>
                    <tr><th class="text-dark font-weight-bold">Caratteristica</th><td>{{ $elementBody->getCharacteristicLabel() }}</td></tr>
                    <tr><th class="text-dark font-weight-bold">Stato</th><td>
                        @if($elementBody->isCompleted())<span class="badge badge-success"><i class="fas fa-check-double"></i> Completato</span>
                        @elseif($elementBody->isFinishDraw())<span class="badge badge-info"><i class="fas fa-lock"></i> Disegno Terminato</span>
                        @else<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>@endif
                    </td></tr>
                </table>
                <div class="mt-4">
                    <a href="{{ route('element-bodies.index') }}" class="btn btn-secondary shadow-sm mr-2"><i class="fas fa-backward"></i> Indietro</a>
                    @if($elementBody->isCreated())
                        <a href="{{ route('element-bodies.edit', $elementBody) }}" class="btn btn-primary shadow-sm mr-2"><i class="fas fa-edit"></i> Modifica</a>
                    @endif
                </div>
            </div>
            <div class="col-md-6 col-12 text-center border-left">
                <h5 class="text-muted font-weight-bold mb-3">Grafica Corpo</h5>
                @if($elementBody->image && \Storage::disk('element_bodies')->exists($elementBody->image))
                    <img src="{{ asset('storage/element_bodies/' . $elementBody->image) }}?v={{ time() }}" style="width:200px;height:200px;image-rendering:pixelated;border:4px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,0.1);border-radius:8px;">
                @else
                    <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width:200px;height:200px;border-style:dashed !important;"><i class="fas fa-image fa-4x text-muted"></i></div>
                    <p class="text-muted mt-3">Nessuna grafica generata.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop
