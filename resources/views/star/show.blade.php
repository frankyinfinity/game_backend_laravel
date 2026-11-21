@extends('adminlte::page')

@section('title', 'Stella')

@section('content_header')@stop

@section('content')
<div class="card card-primary card-outline card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="star-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-info-link" data-toggle="pill" href="#tab-info" role="tab"
                    aria-controls="tab-info" aria-selected="true">Informazioni Principali</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-pianeti-link" data-toggle="pill" href="#tab-pianeti" role="tab"
                    aria-controls="tab-pianeti" aria-selected="false">Pianeti</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="star-tabs-content">

            <!-- TAB INFORMAZIONI PRINCIPALI -->
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel" aria-labelledby="tab-info-link">
                <div class="row">
                    <div class="col-12">
                        <label for="name">Nome</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-font"></i></span>
                            </div>
                            <input type="text" id="name" name="name" class="form-control" value="{{$star->name}}" placeholder="Nome" disabled>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="rgb">RGB</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-palette"></i></span>
                            </div>
                            <input type="text" id="rgb" name="rgb" class="form-control" value="{{$star->rgb}}" disabled>
                            <div class="input-group-append">
                                <span class="input-group-text" style="width:42px;background-color:rgb({{$star->rgb}})"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="description">Descrizione</label>
                        <div class="form-group">
                            <textarea class="form-control" rows="4" id="description" name="description" disabled>{{$star->description}}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row">
                            <div class="col-4">
                                <a href="{{route('stars.edit', [$star->id])}}">
                                    <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> {{ $star->isCreated() ? 'Modifica' : ($star->isZones() ? 'Fascie' : 'Gestione') }}</button>
                                </a>
                            </div>
                            @if($star->isCreated())
                            <div class="col-4">
                                <form method="POST" action="{{ route('stars.state', [$star->id]) }}">
                                    @csrf
                                    <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_ZONES }}">
                                    <button type="submit" class="btn btn-teal btn-block btn-sm"><i class="fa fa-bullseye"></i> Gestisci Fascie</button>
                                </form>
                            </div>
                            @endif
                            <div class="col-4">
                                <a href="{{route('stars.index')}}">
                                    <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB PIANETI -->
            <div class="tab-pane fade" id="tab-pianeti" role="tabpanel" aria-labelledby="tab-pianeti-link">
                @if(!$star->isPlanets())
                    <div class="alert alert-info">
                        <i class="fas fa-lock"></i> La gestione dei pianeti si sblocca quando la stella e' in stato <strong>Pianeti</strong>: dalla pagina di modifica vai a "Gestisci Fascie" e poi "Gestisci Pianeti".
                    </div>
                @elseif($zones->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nessuna fascia definita: crea prima le fasce della stella dalla pagina di modifica ("Gestisci Fascie").
                    </div>
                @else
                    @include('star.planet.index')
                @endif
            </div>

        </div>
    </div>
</div>
@stop

@section('js')
    @parent
    <script>
        $(document).ready(function () {
            // Attiva il tab indicato nell'hash dell'URL (es. #tab-pianeti)
            if (window.location.hash) {
                var tab = $('a[data-toggle="pill"][href="' + window.location.hash + '"]');
                if (tab.length) tab.tab('show');
            }
        });
    </script>
@stop
