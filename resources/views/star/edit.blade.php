@extends('adminlte::page')

@php $locked = !$star->isCreated(); @endphp

@section('title', $locked ? 'Fascie Stella' : 'Modifica Stella')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">{{ $locked ? 'Fascie Stella' : 'Modifica Stella' }}</h5>
    </div>
    <div class="card-body">
        @if($star->isPlanets())
            <div class="alert alert-warning border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #ffc107 !important;background-color:#fff8e1;color:#856404;">
                <div><i class="fas fa-lock mr-2 text-warning"></i> Stella in stato <strong>Pianeti</strong>. Informazioni e grafico fasce bloccati: posiziona i pianeti dal dettaglio della stella.</div>
                <form action="{{ route('stars.state', $star) }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_ZONES }}">
                    <button type="submit" class="btn btn-teal btn-sm shadow-sm">
                        <i class="fas fa-bullseye"></i> Torna a Fascie
                    </button>
                </form>
            </div>
        @elseif($star->isZones())
            <div class="alert alert-info border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #17a2b8 !important;background-color:#f3fafd;color:#117a8b;">
                <div><i class="fas fa-lock mr-2 text-info"></i> Stella in stato <strong>Fascie</strong>. Informazioni bloccate: gestisci le fasce qui sotto.</div>
                <div class="d-flex">
                    <form action="{{ route('stars.state', $star) }}" method="POST" class="m-0 mr-2">
                        @csrf
                        <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_CREATED }}">
                        <button type="submit" class="btn btn-secondary btn-sm shadow-sm">
                            <i class="fas fa-edit"></i> Torna a Creata
                        </button>
                    </form>
                    <form action="{{ route('stars.state', $star) }}" method="POST" class="m-0">
                        @csrf
                        <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_PLANETS }}">
                        <button type="submit" class="btn btn-warning btn-sm shadow-sm" onclick="return confirm('Passare alla gestione pianeti? Il grafico delle fasce verra\' bloccato.');">
                            <i class="fas fa-globe"></i> Gestisci Pianeti
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left:4px solid #ffc107 !important;">
                <div><i class="fas fa-info-circle mr-2 text-warning"></i> Stato <strong>Creata</strong>. Puoi modificare le informazioni della stella.</div>
                <form action="{{ route('stars.state', $star) }}" method="POST" class="m-0">
                    @csrf
                    <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_ZONES }}">
                    <button type="submit" class="btn btn-teal btn-sm shadow-sm" onclick="return confirm('Passare alla gestione fasce? Il form informazioni verra\' bloccato.');">
                        <i class="fas fa-bullseye"></i> Gestisci Fascie
                    </button>
                </form>
            </div>
        @endif
        <form method="POST" action="{{ route('stars.update', [$star->id]) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-12">
                    <label for="name">Nome*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-font"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" value="{{$star->name}}" placeholder="Nome" required {{ $locked ? 'disabled' : '' }}>
                    </div>
                </div>
                <div class="col-12">
                    <label for="rgb_picker">Colore*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-palette"></i></span>
                        </div>
                        @php
                            $rgbParts = array_map('intval', explode(',', (string) $star->rgb));
                            $rgbHex = (count($rgbParts) === 3)
                                ? sprintf('#%02x%02x%02x', $rgbParts[0], $rgbParts[1], $rgbParts[2])
                                : '#000000';
                        @endphp
                        <input type="color" id="rgb_picker" class="form-control" value="{{$rgbHex}}" style="padding:0.15rem;height:calc(1.6em + 0.75rem + 2px)" {{ $locked ? 'disabled' : '' }}>
                        <input type="hidden" id="rgb" name="rgb" value="{{$star->rgb}}" {{ $locked ? 'disabled' : '' }}>
                        <div class="input-group-append">
                          <span class="input-group-text" id="rgb_value">{{$star->rgb}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description">Descrizione</label>
                    <div class="form-group">
                        <textarea class="form-control" rows="4" id="description" name="description" {{ $locked ? 'disabled' : '' }}>{{$star->description}}</textarea>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row">
                        @if(!$locked)
                        <div class="col-3">
                            <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-save"></i> Salva</button>
                        </div>
                        @endif
                        <div class="col-3">
                            <a href="{{route('stars.show', [$star->id])}}">
                                <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($locked)
    @include('star.zone.index', ['zoneEditorReadonly' => $star->isPlanets()])
@endif
@stop

@section('js')
    <script>
        $(document).ready(function () {

            @if(!$locked)
            $('#rgb_picker').on('input', function () {
                var hex = $(this).val();
                var r = parseInt(hex.substr(1, 2), 16);
                var g = parseInt(hex.substr(3, 2), 16);
                var b = parseInt(hex.substr(5, 2), 16);
                var rgb = r + ',' + g + ',' + b;
                $('#rgb').val(rgb);
                $('#rgb_value').text(rgb);
            });
            @endif

        });
    </script>
@stop
