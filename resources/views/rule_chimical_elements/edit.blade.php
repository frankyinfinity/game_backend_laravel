@extends('adminlte::page')

@section('title', 'Modifica Regola Elemento Chimico')

@section('content_header')
    <h1>Modifica Regola Elemento Chimico</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Modifica Regola</h3>
        </div>
        @if($ruleChimicalElement->details->isNotEmpty())
            <div class="alert alert-warning m-3">
                <i class="fas fa-exclamation-triangle"></i> 
                Non è possibile modificare questa regola perché contiene dei dettagli. 
                <a href="{{ route('rule-chimical-elements.show', $ruleChimicalElement) }}">Visualizza i dettagli</a>
            </div>
        @endif
        <form action="{{ route('rule-chimical-elements.update', $ruleChimicalElement) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="element_type">Tipo Elemento</label>
                    <select class="form-control" id="element_type" name="element_type" onchange="toggleElementSelects()" {{ $ruleChimicalElement->details->isNotEmpty() ? 'disabled' : '' }}>
                        <option value="simple" {{ $ruleChimicalElement->chimical_element_id ? 'selected' : '' }}>Elemento Chimico Semplice</option>
                        <option value="complex" {{ $ruleChimicalElement->complex_chimical_element_id ? 'selected' : '' }}>Elemento Chimico Complesso</option>
                    </select>
                    @if($ruleChimicalElement->details->isNotEmpty())
                        <input type="hidden" name="element_type" value="{{ $ruleChimicalElement->chimical_element_id ? 'simple' : 'complex' }}">
                    @endif
                </div>
                <div class="form-group" id="chimical_element_group" style="{{ $ruleChimicalElement->chimical_element_id ? '' : 'display:none;' }}">
                    <label for="chimical_element_id">Elemento Chimico</label>
                    <select class="form-control" id="chimical_element_id" name="chimical_element_id" {{ $ruleChimicalElement->details->isNotEmpty() ? 'disabled' : '' }}>
                        <option value="">Seleziona Elemento Chimico</option>
                        @foreach($chimicalElements as $ce)
                            <option value="{{ $ce->id }}" {{ old('chimical_element_id', $ruleChimicalElement->chimical_element_id) == $ce->id ? 'selected' : '' }}>{{ $ce->name }} ({{ $ce->symbol }})</option>
                        @endforeach
                    </select>
                    @if($ruleChimicalElement->details->isNotEmpty())
                        <input type="hidden" name="chimical_element_id" value="{{ $ruleChimicalElement->chimical_element_id }}">
                    @endif
                </div>
                <div class="form-group" id="complex_chimical_element_group" style="{{ $ruleChimicalElement->complex_chimical_element_id ? '' : 'display:none;' }}">
                    <label for="complex_chimical_element_id">Elemento Chimico Complesso</label>
                    <select class="form-control" id="complex_chimical_element_id" name="complex_chimical_element_id" {{ $ruleChimicalElement->details->isNotEmpty() ? 'disabled' : '' }}>
                        <option value="">Seleziona Elemento Chimico Complesso</option>
                        @foreach($complexChimicalElements as $cce)
                            <option value="{{ $cce->id }}" {{ old('complex_chimical_element_id', $ruleChimicalElement->complex_chimical_element_id) == $cce->id ? 'selected' : '' }}>{{ $cce->name }}</option>
                        @endforeach
                    </select>
                    @if($ruleChimicalElement->details->isNotEmpty())
                        <input type="hidden" name="complex_chimical_element_id" value="{{ $ruleChimicalElement->complex_chimical_element_id }}">
                    @endif
                </div>
                <div class="form-group">
                    <label for="min">Min <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control"
                           id="min"
                           name="min"
                           value="{{ old('min', $ruleChimicalElement->min) }}"
                           min="0"
                           required
                           {{ $ruleChimicalElement->details->isNotEmpty() ? 'readonly' : '' }}>
                </div>
                <div class="form-group">
                    <label for="max">Max <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control"
                           id="max"
                           name="max"
                           value="{{ old('max', $ruleChimicalElement->max) }}"
                           min="0"
                           required
                           {{ $ruleChimicalElement->details->isNotEmpty() ? 'readonly' : '' }}>
                </div>
            </div>
            <div class="card-footer">
                @if($ruleChimicalElement->details->isEmpty())
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salva
                    </button>
                @endif
                <a href="{{ $ruleChimicalElement->details->isNotEmpty() ? route('rule-chimical-elements.show', $ruleChimicalElement) : route('rule-chimical-elements.index') }}" class="btn {{ $ruleChimicalElement->details->isNotEmpty() ? 'btn-primary' : 'btn-secondary' }}">
                    <i class="fas fa-arrow-left"></i> {{ $ruleChimicalElement->details->isNotEmpty() ? 'Torna ai Dettagli' : 'Annulla' }}
                </a>
            </div>
        </form>
    </div>
@stop

@section('js')
    <script>
        function toggleElementSelects() {
            var type = document.getElementById('element_type').value;
            if (type === 'simple') {
                document.getElementById('chimical_element_group').style.display = 'block';
                document.getElementById('complex_chimical_element_group').style.display = 'none';
                document.getElementById('chimical_element_id').required = true;
                document.getElementById('complex_chimical_element_id').required = false;
            } else {
                document.getElementById('chimical_element_group').style.display = 'none';
                document.getElementById('complex_chimical_element_group').style.display = 'block';
                document.getElementById('chimical_element_id').required = false;
                document.getElementById('complex_chimical_element_id').required = true;
            }
        }
    </script>
@stop
