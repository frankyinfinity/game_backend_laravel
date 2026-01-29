@extends('adminlte::page')

@section('title', 'Dettaglio Elemento')

@section('content_header')
    <h1>Dettaglio Elemento</h1>
@stop

@section('content')
    <form action="{{ route('elements.update', $element) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab" aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-graphics-link" data-toggle="pill" href="#tab-graphics" role="tab" aria-controls="tab-graphics" aria-selected="false">Grafica</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    <!-- TAB DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-link">
                        <dl class="row">
                            <dt class="col-sm-3">ID:</dt>
                            <dd class="col-sm-9">{{ $element->id }}</dd>

                            <dt class="col-sm-3">Nome:</dt>
                            <dd class="col-sm-9">{{ $element->name }}</dd>

                            <dt class="col-sm-3">Tipologia:</dt>
                            <dd class="col-sm-9">{{ $element->elementType->name ?? '-' }}</dd>

                            <dt class="col-sm-3">Consumabile:</dt>
                            <dd class="col-sm-9">
                                {!! $element->consumable ? '<span class="badge badge-success">Sì</span>' : '<span class="badge badge-secondary">No</span>' !!}
                            </dd>

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

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @include('elements.tabs.graphics')
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Aggiorna
                </button>
                <a href="{{ route('elements.edit', $element) }}" class="btn btn-info">
                    <i class="fas fa-edit"></i> Modifica Completa
                </a>
                <a href="{{ route('elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Torna all'elenco
                </a>
            </div>
        </div>
    </form>
@stop
