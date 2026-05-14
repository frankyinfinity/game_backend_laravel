@extends('adminlte::page')

@section('title', 'Regione')

@section('content_header')@stop

@section('content')
<div class="card">
        <div class="card-header pb-0">
            <h4 class="mb-0">Regione del Pianeta: {{$region->planet->name}} {!! $region->state_badge !!}</h4>
        </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <label for="name">Nome</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-font"></i></span>
                    </div>
                    <input type="text" id="name" name="name" class="form-control" value="{{$region->name}}" disabled>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-12">
                        <label for="climate">Clima</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-tree"></i></span>
                            </div>
                            <input type="text" id="climate" name="climate" class="form-control" value="{{$region->climate->name}}" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="width">Larghezza (Tile)</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                            </div>
                            <input type="number" id="width" name="width" min="1" value="{{$region->width}}" class="form-control" value="{{$region->width}}" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <label for="height">Altezza (Tile)</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                            </div>
                            <input type="number" id="height" name="height" min="1" value="{{$region->height}}" class="form-control" value="{{$region->height}}" disabled>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label for="description">Descrizione</label>
                <div class="form-group">
                    <textarea class="form-control" rows="4" id="description" name="description" disabled>{{$region->description}}</textarea>
                </div>
            </div>
            <div class="col-12">
                <div class="mb-4">
                    @if($region->canGenerateImages())
                        <button type="button" class="btn btn-success" onclick="showGenerateModal()">
                            <i class="fas fa-image"></i> Genera Immagini
                        </button>
                        @elseif($region->canComplete())
                            <button type="button" class="btn btn-success" onclick="showCompleteModal()">
                                <i class="fas fa-check"></i> Completa Regione
                            </button>
                    @endif
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-3">
                        @if($region->state === \App\Models\Region::STATE_CREATED)
                        <a href="{{route('regions.edit', [$region->id])}}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> Modifica Dati</button>
                        </a>
                        @else
                        <button type="button" class="btn btn-secondary btn-block btn-sm" disabled><i class="fa fa-edit"></i> Modifica Dati</button>
                        @endif
                    </div>
                    <div class="col-3">
                        @if($region->isMapEditable())
                        <a href="{{route('regions.edit-map', [$region->id])}}">
                            <button type="button" class="btn btn-info btn-block btn-sm"><i class="fa fa-map"></i> Modifica Mappa</button>
                        </a>
                        @elseif($region->state === \App\Models\Region::STATE_COMPLETED)
                        <a href="{{route('regions.edit-map', [$region->id])}}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-eye"></i> Visualizza Mappa</button>
                        </a>
                        @else
                        <button type="button" class="btn btn-secondary btn-block btn-sm" disabled><i class="fa fa-map"></i> Modifica Mappa</button>
                        @endif
                    </div>
                    <div class="col-3">
                        <a href="{{route('planets.show', [$region->planet->id])}}">
                            <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Modal for Generate Images -->
<div class="modal fade" id="generateModal" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">Conferma Generazione Immagini</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Sei sicuro di voler generare le immagini per questa regione? Questa azione creerà le immagini originali e modificate e non può essere annullata.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <form method="POST" action="{{ route('regions.generate-images', $region) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Conferma Generazione</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Complete Region -->
<div class="modal fade" id="completeModal" tabindex="-1" role="dialog" aria-labelledby="completeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeModalLabel">Conferma Completamento Regione</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Sei sicuro di voler completare questa regione? Dopo il completamento, non sarà possibile modificare nulla.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <form method="POST" action="{{ route('regions.complete-region', $region) }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Conferma Completamento</button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
function showGenerateModal() {
    $('#generateModal').modal('show');
}

function showCompleteModal() {
    $('#completeModal').modal('show');
}
</script>
@stop
