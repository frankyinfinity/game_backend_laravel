@extends('adminlte::page')

@section('title', 'Modifica Famiglia Tile')

@section('content_header')
<h1>Modifica Famiglia Tile</h1>
@stop

@section('content')
<div class="card card-primary card-outline card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="main-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-info-link" data-toggle="pill" href="#tab-info" role="tab"
                    aria-controls="tab-info" aria-selected="true">Informazioni Principali</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-limits-link" data-toggle="pill" href="#tab-limits" role="tab"
                    aria-controls="tab-limits" aria-selected="false">Limiti</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="main-tabsContent">
            <!-- TAB INFORMAZIONI PRINCIPALI -->
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-link">
                <form action="{{ route('family-tiles.update', $familyTile) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name">Nome</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ $familyTile->name }}" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Tipo</label>
                        <select class="form-control" id="type" name="type" required>
                            @foreach(App\Models\FamilyTile::getTypeLabels() as $value => $label)
                                <option value="{{ $value }}" {{ $familyTile->type == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva</button>
                    <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
                </form>
            </div>

            <!-- TAB LIMITI -->
            <div class="tab-pane fade" id="tab-limits" role="tabpanel" aria-labelledby="tab-limits-link">
                <div class="mb-3">
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#updateAllModal">Aggiorna Tutti</button>
                </div>
                <form action="{{ route('family-tiles.update-limits', $familyTile) }}" method="POST">
                    @csrf
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Elemento Chimico / Complesso</th>
                                    <th>Limite</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chimicalElements as $element)
                                    @php
                                        $limit = $familyTile->limits->where('chimical_element_id', $element->id)->first();
                                        $value = $limit ? $limit->limit_value : 200;
                                    @endphp
                                    <tr>
                                        <td>{{ $element->name }} ({{ $element->symbol }})</td>
                                        <td>
                                            <input type="number" name="limits[ch{{ $element->id }}][limit_value]" value="{{ $value }}" min="0" class="form-control">
                                            <input type="hidden" name="limits[ch{{ $element->id }}][chimical_element_id]" value="{{ $element->id }}">
                                        </td>
                                    </tr>
                                @endforeach
                                @foreach($complexChimicalElements as $element)
                                    @php
                                        $limit = $familyTile->limits->where('complex_chimical_element_id', $element->id)->first();
                                        $value = $limit ? $limit->limit_value : 200;
                                    @endphp
                                    <tr>
                                        <td>{{ $element->name }} (Complesso)</td>
                                        <td>
                                            <input type="number" name="limits[cc{{ $element->id }}][limit_value]" value="{{ $value }}" min="0" class="form-control">
                                            <input type="hidden" name="limits[cc{{ $element->id }}][complex_chimical_element_id]" value="{{ $element->id }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">Salva Limiti</button>
                    <a href="{{ route('family-tiles.index') }}" class="btn btn-secondary">Annulla</a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Update All -->
<div class="modal fade" id="updateAllModal" tabindex="-1" role="dialog" aria-labelledby="updateAllModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateAllModalLabel">Aggiorna Tutti i Limiti</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateAllForm" action="{{ route('family-tiles.update-limits', $familyTile) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="all_limit_value">Valore Limite</label>
                        <input type="number" class="form-control" id="all_limit_value" name="all_limit_value" value="200" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop