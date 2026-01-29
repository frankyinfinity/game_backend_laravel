@extends('adminlte::page')

@section('title', 'Dettaglio Elemento')

@section('content_header')
    <h1>Dettaglio Elemento</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informazioni Elemento</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">ID:</dt>
                <dd class="col-sm-9">{{ $element->id }}</dd>

                <dt class="col-sm-3">Nome:</dt>
                <dd class="col-sm-9">{{ $element->name }}</dd>

                <dt class="col-sm-3">Tipologia:</dt>
                <dd class="col-sm-9">{{ $element->elementType->name ?? '-' }}</dd>

                <dt class="col-sm-3">Climi Validi:</dt>
                <dd class="col-sm-9">
                    @forelse($element->climates as $climate)
                        <span class="badge badge-info">{{ $climate->name }}</span>
                    @empty
                        <span class="text-muted">Nessun clima associato</span>
                    @endforelse
                </dd>

                <dt class="col-sm-3">Creato il:</dt>
                <dd class="col-sm-9">{{ $element->created_at->format('d/m/Y H:i') }}</dd>

                <dt class="col-sm-3">Aggiornato il:</dt>
                <dd class="col-sm-9">{{ $element->updated_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </div>
        <div class="card-footer">
            <a href="{{ route('elements.edit', $element) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('elements.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Torna all'elenco
            </a>
        </div>
    </div>
@stop
