@extends('adminlte::page')

@section('title', 'Modifica Corpo Entity')

@section('content_header')@stop

@section('content')

    <form action="{{ route('entity-bodies.update', $entityBody) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs shadow-sm">
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
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label for="name" class="text-dark font-weight-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $entityBody->name) }}" 
                                       {{ $entityBody->isFinished() ? 'disabled readonly' : '' }}
                                       required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @if($entityBody->isFinished())
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="card card-outline card-secondary shadow-sm text-center py-4">
                                        <div class="card-body">
                                            <h5 class="text-muted mb-3 font-weight-bold">Grafica Salvata (Sola Lettura)</h5>
                                            @if($entityBody->image && \Storage::disk('entity_bodies')->exists($entityBody->image))
                                                <img src="{{ asset('storage/entity_bodies/' . $entityBody->image) }}?v={{ time() }}" style="width: 128px; height: 128px; image-rendering: pixelated; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px;">
                                            @else
                                                <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width: 128px; height: 128px; border-style: dashed !important;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                                <p class="text-muted mt-3 mb-0">Nessuna grafica disegnata.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            @include('shared.graphics_editor', ['modelType' => 'entity_body', 'model' => $entityBody, 'availableColors' => ['#000000']])
                        @endif
                    </div>

                </div>
            </div>
            <div class="card-footer bg-light border-top">
                <div class="row">
                    @if(!$entityBody->isFinished())
                        <div class="col-md-3 col-sm-6 mb-2">
                            <button type="submit" class="btn btn-primary btn-block btn-sm shadow-sm" id="btn-save-all">
                                <i class="fa fa-save"></i> Aggiorna
                            </button>
                        </div>
                    @endif
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('entity-bodies.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
                            <i class="fa fa-backward"></i> Indietro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop
