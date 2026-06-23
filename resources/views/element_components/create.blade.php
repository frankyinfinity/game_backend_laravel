@extends('adminlte::page')

@section('title', 'Nuovo Componente Element')

@section('plugins.Select2', true)

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0">
        <h4 class="mb-0 text-dark font-weight-bold">Nuovo Componente Element</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('element-components.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 col-12 mb-3">
                    <label for="name" class="text-dark font-weight-bold">Nome Componente*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-puzzle-piece text-primary"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Inserisci il nome del componente..." value="{{ old('name') }}" required>
                    </div>
                </div>

                <div class="col-md-6 col-12 mb-3">
                    <label for="characteristic" class="text-dark font-weight-bold">Caratteristica <span class="text-danger">*</span></label>
                    <select id="characteristic" name="characteristic" class="form-control @error('characteristic') is-invalid @enderror" required>
                        @foreach(\App\Models\ElementComponent::CHARACTERISTIC_TYPES as $value => $label)
                            <option value="{{ $value }}" {{ old('characteristic') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('characteristic')<span class="invalid-feedback"><strong>{{ $message }}</strong></span>@enderror
                </div>

                <div class="col-md-6 col-12 mb-3">
                    <label for="element_type_component_id" class="text-dark font-weight-bold">Tipologia Componente</label>
                    <div class="input-group mb-3">
                        <select id="element_type_component_id" name="element_type_component_id" class="form-control select2" style="width: 100%;">
                            <option value="">Nessuna Tipologia...</option>
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" data-icon="{{ $type->symbol }}" {{ old('element_type_component_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
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
                            <a href="{{ route('element-components.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
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
        function formatType(state) {
            if (!state.id) return state.text;
            var icon = $(state.element).data('icon');
            if (!icon) return state.text;
            return $('<span><i class="' + icon + ' fa-fw mr-2 text-dark"></i>' + state.text + '</span>');
        }
        $('#element_type_component_id').select2({ templateResult: formatType, templateSelection: formatType, placeholder: "Seleziona una Tipologia...", allowClear: true });
    });
</script>
@stop
