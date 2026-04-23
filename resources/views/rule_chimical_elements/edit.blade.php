@extends('adminlte::page')

@section('title', 'Modifica Regola Elemento Chimico')

@section('content_header')
    <h1>Modifica Regola Elemento Chimico</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="icon fas fa-check"></i>
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="icon fas fa-ban"></i>
            <strong>Errore!</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if($ruleChimicalElement->details->isNotEmpty())
        <div class="alert alert-warning mb-3 border-left">
            <i class="fas fa-exclamation-triangle mr-2"></i> 
            <strong>Attenzione:</strong> Questa regola contiene dei dettagli. Alcuni campi strutturali sono bloccati.
            <a href="{{ route('rule-chimical-elements.show', $ruleChimicalElement) }}" class="alert-link ml-2">Visualizza e gestisci dettagli</a>
        </div>
    @endif
    
    <form action="{{ route('rule-chimical-elements.update', $ruleChimicalElement) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Configurazione Base</h5>
                    </div>
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
                          <div class="form-group">
                              <label for="name">Nome Regola <span class="text-danger">*</span></label>
                              <input type="text"
                                     class="form-control"
                                     id="name"
                                     name="name"
                                     value="{{ old('name', $ruleChimicalElement->name) }}"
                                     required>
                          </div>
                          <div class="form-group" id="chimical_element_group" style="{{ $ruleChimicalElement->chimical_element_id ? '' : 'display: none;' }}">
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
                         <div class="form-group" id="complex_chimical_element_group" style="{{ $ruleChimicalElement->complex_chimical_element_id ? '' : 'display: none;' }}">
                             <label for="complex_chimical_element_id">Elemento Chimico Complesso</label>
                             <select class="form-control" id="complex_chimical_element_id" name="complex_chimical_element_id" {{ $ruleChimicalElement->details->isNotEmpty() ? 'disabled' : '' }}>
                                 <option value="">Seleziona Elemento Chimico Complesso</option>
                                 @foreach($complexChimicalElements as $cce)
                                     <option value="{{ $cce->id }}" {{ old('complex_chimical_element_id', $ruleChimicalElement->complex_chimical_element_id) == $cce->id ? 'selected' : '' }}>{{ $cce->name }}</option>
                                 @endforeach
                             </select>
                         </div>
                          <div class="form-group">
                               <label for="type">Tipo Regola</label>
                               <select class="form-control" id="type" name="type">
                                   <option value="entity" {{ old('type', $ruleChimicalElement->type) == 'entity' ? 'selected' : '' }}>Entità</option>
                                   <option value="element" {{ old('type', $ruleChimicalElement->type) == 'element' ? 'selected' : '' }}>Elemento</option>
                               </select>
                           </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="min">Min <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control"
                                           id="min"
                                           name="min"
                                           value="{{ old('min', $ruleChimicalElement->min) }}"
                                           required
                                           {{ $ruleChimicalElement->details->isNotEmpty() ? 'readonly' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="max">Max <span class="text-danger">*</span></label>
                                    <input type="number"
                                           class="form-control"
                                           id="max"
                                           name="max"
                                           value="{{ old('max', $ruleChimicalElement->max) }}"
                                           required
                                           {{ $ruleChimicalElement->details->isNotEmpty() ? 'readonly' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="default_value">Valore Default</label>
                            <input type="text"
                                   class="form-control"
                                   id="default_value"
                                   name="default_value"
                                   value="{{ old('default_value', $ruleChimicalElement->default_value) }}"
                                   placeholder="es: 0, 50, 100"
                                   {{ $ruleChimicalElement->details->isNotEmpty() ? 'readonly' : '' }}>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Degradazione</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="degradable"
                                       name="degradable"
                                       value="1"
                                       {{ old('degradable', $ruleChimicalElement->degradable) ? 'checked' : '' }}>
                                <label class="form-check-label" for="degradable">Abilitato</label>
                            </div>
                        </div>
                        <div class="border rounded p-3" id="degradation_container" style="{{ old('degradable', $ruleChimicalElement->degradable) ? '' : 'display:none;' }}">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <small class="text-muted"><i class="fas fa-info-circle"></i> Un tick equivale più o meno a 10 secondi di gioco.</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="degradation_fields">
                                        <label for="quantity_tick_degradation">Qtà per Tick</label>
                                        <input type="number"
                                               class="form-control"
                                               id="quantity_tick_degradation"
                                               name="quantity_tick_degradation"
                                               value="{{ old('quantity_tick_degradation', $ruleChimicalElement->quantity_tick_degradation) }}"
                                               min="0"
                                               placeholder="Quantità persa ad ogni tick">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="percentage_degradation_field">
                                        <label for="percentage_degradation">Degrado %</label>
                                        <input type="number"
                                               class="form-control"
                                               id="percentage_degradation"
                                               name="percentage_degradation"
                                               value="{{ old('percentage_degradation', $ruleChimicalElement->percentage_degradation) }}"
                                               min="0"
                                               max="100"
                                               step="0.01"
                                               placeholder="Probabilità di degrado (0-100)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Salva
            </button>
            <a href="{{ route('rule-chimical-elements.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Annulla
            </a>
        </div>
    </form>
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

        document.getElementById('degradable').addEventListener('change', function() {
            var container = document.getElementById('degradation_container');
            if (this.checked) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        });
    </script>
@stop
