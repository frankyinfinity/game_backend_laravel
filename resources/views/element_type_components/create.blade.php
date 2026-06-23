@extends('adminlte::page')

@section('title', 'Nuova Tipologia Componente')

@section('plugins.Select2', true)

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0">
        <h4 class="mb-0 text-dark font-weight-bold">Nuova Tipologia Componente</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('element-type-components.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 col-12 mb-3">
                    <label for="name" class="text-dark font-weight-bold">Nome Tipologia Componente*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-tags text-primary"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Es. Struttura, Funzione..." value="{{ old('name') }}" required>
                    </div>
                </div>

                <div class="col-md-6 col-12 mb-3">
                    <label for="symbol" class="text-dark font-weight-bold">Simbolo (FontAwesome Icon)*</label>
                    <div class="input-group mb-3">
                        <select id="symbol" name="symbol" class="form-control select2" required style="width: 100%;">
                            <option value="">Seleziona un Simbolo...</option>
                            @foreach($icons as $class => $label)
                                <option value="{{ $class }}" {{ old('symbol') == $class ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 mt-4 border-top pt-3">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <button type="submit" class="btn btn-primary btn-block btn-sm shadow-sm">
                                <i class="fa fa-save"></i> Salva
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="{{ route('element-type-components.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
                                <i class="fa fa-backward"></i> Indietro
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('css')
<style>
    .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 0.25rem !important; display: flex !important; align-items: center !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: normal !important; padding-left: 12px !important; color: #495057 !important; display: flex !important; align-items: center !important; width: 100% !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px !important; display: flex !important; align-items: center !important; justify-content: center !important; }
</style>
@stop

@section('js')
<script>
    $(document).ready(function () {
        function formatIcon(state) {
            if (!state.id) return state.text;
            return $('<span><i class="' + state.element.value + ' fa-fw mr-2 text-dark"></i>' + state.text + ' <small class="text-muted">(' + state.element.value + ')</small></span>');
        }
        $('#symbol').select2({ templateResult: formatIcon, templateSelection: formatIcon, placeholder: "Cerca e seleziona un simbolo..." });
    });
</script>
@stop
