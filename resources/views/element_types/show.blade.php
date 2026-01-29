@extends('adminlte::page')

@section('title', 'Dettaglio Tipologia Elemento')

@section('content_header')
    <h1>Dettaglio Tipologia Elemento</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informazioni Tipologia</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">ID:</dt>
                        <dd class="col-sm-8">{{ $elementType->id }}</dd>

                        <dt class="col-sm-4">Nome:</dt>
                        <dd class="col-sm-8">{{ $elementType->name }}</dd>

                        <dt class="col-sm-4">Creato il:</dt>
                        <dd class="col-sm-8">{{ $elementType->created_at->format('d/m/Y H:i') }}</dd>

                        <dt class="col-sm-4">Aggiornato il:</dt>
                        <dd class="col-sm-8">{{ $elementType->updated_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="{{ route('element-types.edit', $elementType) }}" class="btn btn-primary">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('element-types.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Torna all'elenco
            </a>
            <form action="{{ route('element-types.destroy', $elementType) }}" method="POST" style="display: inline-block;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Sei sicuro di voler eliminare questa tipologia?')">
                    <i class="fas fa-trash"></i> Elimina
                </button>
            </form>
        </div>
    </div>
@stop
