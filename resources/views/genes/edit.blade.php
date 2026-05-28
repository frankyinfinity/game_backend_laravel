@extends('adminlte::page')

@section('title', 'Modifica Gene')

@section('content_header')
    <h1>Modifica Gene</h1>
@stop

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

                <!-- TAB DATI GENERALI -->
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel"
                    aria-labelledby="tab-general-link">
                    <div class="form-group">
                        <label for="name">Nome <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name', $gene->name) }}"
                               required>
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="key">Key <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('key') is-invalid @enderror"
                               id="key"
                               name="key"
                               value="{{ old('key', $gene->key) }}"
                               required>
                        @error('key')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <!-- TAB IMMAGINE -->
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
                <i class="fas fa-times"></i> Annulla
            </a>
        </div>
    </div>
</form>
@stop