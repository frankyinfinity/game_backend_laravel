@extends('adminlte::page')

@section('title', 'Modifica Punteggio (' . $groupScore->name . ')')

@section('content_header')
    <h1>Modifica Punteggio ({{ $groupScore->name }})</h1>
@stop

@section('content')
    <form action="{{ route('group-scores.scores.update', [$groupScore, $score]) }}" method="POST">
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
                        <div class="form-group">
                            <label>Gruppo Punteggio</label>
                            <p class="form-control-plaintext font-weight-bold">{{ $groupScore->name }}</p>
                        </div>
                        <div class="form-group">
                            <label for="name">Nome <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $score->name) }}"
                                   required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @include('shared.graphics_editor', ['model' => $score, 'modelType' => 'score'])
                    </div>

                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Aggiorna
                </button>
                <a href="{{ route('group-scores.scores.index', $groupScore) }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
                </a>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
@stop
