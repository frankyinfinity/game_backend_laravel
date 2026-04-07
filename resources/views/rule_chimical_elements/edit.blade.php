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
        <form action="{{ route('rule-chimical-elements.update', $ruleChimicalElement) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="element_type">Tipo Elemento</label>
                    <select class="form-control" id="element_type" name="element_type" onchange="toggleElementSelects()">
                        <option value="simple" {{ $ruleChimicalElement->chimical_element_id ? 'selected' : '' }}>Elemento Chimico Semplice</option>
                        <option value="complex" {{ $ruleChimicalElement->complex_chimical_element_id ? 'selected' : '' }}>Elemento Chimico Complesso</option>
                    </select>
                </div>
                <div class="form-group" id="chimical_element_group" style="{{ $ruleChimicalElement->chimical_element_id ? '' : 'display:none;' }}">
                    <label for="chimical_element_id">Elemento Chimico</label>
                    <select class="form-control" id="chimical_element_id" name="chimical_element_id">
                        <option value="">Seleziona Elemento Chimico</option>
                        @foreach($chimicalElements as $ce)
                            <option value="{{ $ce->id }}" {{ old('chimical_element_id', $ruleChimicalElement->chimical_element_id) == $ce->id ? 'selected' : '' }}>{{ $ce->name }} ({{ $ce->symbol }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="complex_chimical_element_group" style="{{ $ruleChimicalElement->complex_chimical_element_id ? '' : 'display:none;' }}">
                    <label for="complex_chimical_element_id">Elemento Chimico Complesso</label>
                    <select class="form-control" id="complex_chimical_element_id" name="complex_chimical_element_id">
                        <option value="">Seleziona Elemento Chimico Complesso</option>
                        @foreach($complexChimicalElements as $cce)
                            <option value="{{ $cce->id }}" {{ old('complex_chimical_element_id', $ruleChimicalElement->complex_chimical_element_id) == $cce->id ? 'selected' : '' }}>{{ $cce->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="min">Min <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control"
                           id="min"
                           name="min"
                           value="{{ old('min', $ruleChimicalElement->min) }}"
                           min="0"
                           required>
                </div>
                <div class="form-group">
                    <label for="max">Max <span class="text-danger">*</span></label>
                    <input type="number"
                           class="form-control"
                           id="max"
                           name="max"
                           value="{{ old('max', $ruleChimicalElement->max) }}"
                           min="0"
                           required>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Salva
                </button>
                <a href="{{ route('rule-chimical-elements.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annulla
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
