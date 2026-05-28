@extends('adminlte::page')

@section('title', 'Dettaglio Gene')

@section('content_header')@stop

@section('content')
<form action="{{ route('genes.update', $gene) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card card-primary card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab"
                        aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-image-link" data-toggle="pill" href="#tab-image" role="tab"
                        aria-controls="tab-image" aria-selected="false">Immagine</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="main-tabs-content">
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel"
                    aria-labelledby="tab-general-link">
                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text"
                               class="form-control"
                               id="name"
                               name="name"
                               value="{{ $gene->name }}"
                               readonly>
                    </div>
                    <div class="form-group">
                        <label for="key">Key</label>
                        <input type="text"
                               class="form-control"
                               id="key"
                               name="key"
                               value="{{ $gene->key }}"
                               readonly>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-image" role="tabpanel" aria-labelledby="tab-image-link">
                    @include('shared.graphics_editor', ['model' => $gene, 'modelType' => 'gene'])
                </div>
            </div>
        </div>

        <div class="card-footer" id="main-form-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
            <a href="{{ route('genes.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Torna alla lista
            </a>
        </div>
    </div>
</form>
@stop
