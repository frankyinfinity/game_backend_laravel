<div class="form-group">
    <label for="name">Nome <span class="text-danger">*</span></label>
    <input type="text" 
            class="form-control @error('name') is-invalid @enderror" 
            id="name" 
            name="name" 
            value="{{ old('name', $element->name) }}" 
            required>
    @error('name')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>

<div class="form-group">
    <div class="custom-control custom-checkbox">
        <input type="checkbox" class="custom-control-input" id="consumable" name="consumable" {{ old('consumable', $element->consumable) ? 'checked' : '' }}>
        <label class="custom-control-label" for="consumable">Consumabile</label>
        <small class="form-text text-muted">Se selezionato, l'elemento può essere consumato.</small>
    </div>
</div>

<div class="form-group">
    <label for="element_type_id">Tipologia <span class="text-danger">*</span></label>
    <select class="form-control @error('element_type_id') is-invalid @enderror" 
            id="element_type_id" 
            name="element_type_id" 
            required>
        <option value="">Seleziona Tipologia</option>
        @foreach($elementTypes as $type)
            <option value="{{ $type->id }}" {{ old('element_type_id', $element->element_type_id) == $type->id ? 'selected' : '' }}>
                {{ $type->name }}
            </option>
        @endforeach
    </select>
    @error('element_type_id')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>

<div class="form-group">
    <label for="climates">Climi Validi <span class="text-muted">(Seleziona multipla: Ctrl+Click)</span></label>
    <select class="form-control @error('climates') is-invalid @enderror" 
            id="climates" 
            name="climates[]" 
            multiple
            style="min-height: 200px;">
        @foreach($climates as $climate)
            <option value="{{ $climate->id }}" 
                {{ (collect(old('climates', $element->climates->pluck('id')))->contains($climate->id)) ? 'selected' : '' }}>
                {{ $climate->name }}
            </option>
        @endforeach
    </select>
    <small class="form-text text-muted">Nota: Se modifichi i climi, salva per aggiornare il tab Diffusione.</small>
    @error('climates')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
