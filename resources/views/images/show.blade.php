@extends('adminlte::page')

@section('title', 'Dettagli Immagine')

@section('content_header')
    <h1>Dettagli Immagine</h1>
@stop

@section('content')
<div class="card card-primary card-outline card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="main-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-current-link" data-toggle="pill" href="#tab-current" role="tab"
                    aria-controls="tab-current" aria-selected="true">Versione Corrente</a>
            </li>
            @if($previousVersions->count() > 0)
            <li class="nav-item">
                <a class="nav-link" id="tab-previous-link" data-toggle="pill" href="#tab-previous" role="tab"
                    aria-controls="tab-previous" aria-selected="false">Versioni Precedenti ({{ $previousVersions->count() }})</a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link" id="tab-containers-link" data-toggle="pill" href="#tab-containers" role="tab"
                    aria-controls="tab-containers" aria-selected="false">Container Associati ({{ $containers->count() }})</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="main-tabs-content">

            <!-- TAB VERSIONE CORRENTE -->
            <div class="tab-pane fade show active" id="tab-current" role="tabpanel"
                aria-labelledby="tab-current-link">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome</label>
                            <p class="form-control-static">{{ $image->name }}</p>
                        </div>
                        <div class="form-group">
                            <label>Immagine Docker</label>
                            <p class="form-control-static">{{ $image->docker_image_name }}</p>
                        </div>
                        <div class="form-group">
                            <label>Tag</label>
                            <p class="form-control-static">{{ $image->docker_tag }}</p>
                        </div>
                        <div class="form-group">
                            <label>Versione</label>
                            <p class="form-control-static">{{ $image->version ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Stato</label>
                            <p class="form-control-static">
                                @if($image->is_active)
                                    <span class="badge badge-success">Attivo</span>
                                @else
                                    <span class="badge badge-secondary">Inattivo</span>
                                @endif
                            </p>
                        </div>
                        <div class="form-group">
                            <label>Creata il</label>
                            <p class="form-control-static">{{ $image->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="form-group">
                            <label>Aggiornata il</label>
                            <p class="form-control-static">{{ $image->updated_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="form-group">
                            <label>Build Input</label>
                            <p class="form-control-static">
                                @if($image->build_input_path)
                                    <a href="{{ route('images.download', $image) }}" class="btn btn-sm btn-info">
                                        <i class="fa fa-download"></i> Scarica Build Input
                                    </a>
                                @else
                                    <span class="text-muted">Non disponibile</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @if($image->description)
                <div class="form-group">
                    <label>Descrizione</label>
                    <p class="form-control-static">{{ $image->description }}</p>
                </div>
                @endif
            </div>

            <!-- TAB VERSIONI PRECEDENTI -->
            @if($previousVersions->count() > 0)
            <div class="tab-pane fade" id="tab-previous" role="tabpanel" aria-labelledby="tab-previous-link">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Versione</th>
                                <th>Stato</th>
                                <th>Creata il</th>
                                <th>Build Input</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($previousVersions as $prev)
                            <tr>
                                <td>{{ $prev->version ?? 'N/A' }}</td>
                                <td>
                                    @if($prev->is_active)
                                        <span class="badge badge-success">Attivo</span>
                                    @else
                                        <span class="badge badge-secondary">Inattivo</span>
                                    @endif
                                </td>
                                <td>{{ $prev->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if($prev->build_input_path)
                                        <a href="{{ route('images.download', $prev) }}" class="btn btn-sm btn-info" title="Scarica Build Input">
                                            <i class="fa fa-download"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('images.show', $prev) }}" class="btn btn-sm btn-primary" title="Visualizza dettagli">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <form action="{{ route('images.activate', $prev) }}" method="POST" style="display:inline" onsubmit="return confirm('Sei sicuro di voler attivare questa versione? La versione corrente verrà disattivata.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Attiva questa versione">
                                            <i class="fa fa-toggle-on"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- TAB CONTAINER ASSOCIATI -->
            <div class="tab-pane fade" id="tab-containers" role="tabpanel" aria-labelledby="tab-containers-link">
                @if($containers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo Parent</th>
                                <th>Parent ID</th>
                                <th>Creato il</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($containers as $container)
                            <tr>
                                <td>{{ $container->id }}</td>
                                <td>
                                    @php
                                        $meta = \App\Models\Container::parentTypeMeta()[$container->parent_type] ?? null;
                                    @endphp
                                    @if($meta)
                                        <span class="badge" style="background-color: {{ $meta['color'] }}; color: #fff;">{{ $meta['label'] }}</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $container->parent_type ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                <td>{{ $container->parent_id ?? '-' }}</td>
                                <td>{{ $container->created_at->format('d/m/Y H:i:s') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info mb-0">
                    <i class="fa fa-info-circle"></i> Nessun container associato a questa immagine.
                </div>
                @endif
            </div>

        </div>
    </div>

    <div class="card-footer" id="main-form-footer">
        <a href="{{ route('images.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alla lista
        </a>
        @if(!$image->is_active)
        <form action="{{ route('images.activate', $image) }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-success">
                <i class="fas fa-toggle-on"></i> Attiva
            </button>
        </form>
        @else
        <form action="{{ route('images.deactivate', $image) }}" method="POST" style="display:inline" onsubmit="return confirm('Sei sicuro di voler disattivare questa immagine?')">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-toggle-off"></i> Disattiva
            </button>
        </form>
        @endif
    </div>
</div>
@stop
