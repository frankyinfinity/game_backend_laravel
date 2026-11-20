@extends('adminlte::page')

@section('title', 'Modifica Stella')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Modifica Stella</h5>
    </div>
    <div class="card-body">
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
                        <input type="text" id="name" name="name" class="form-control" value="{{$star->name}}" placeholder="Nome" required>
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
                        <input type="color" id="rgb_picker" class="form-control" value="{{$rgbHex}}" style="padding:0.15rem;height:calc(1.6em + 0.75rem + 2px)">
                        <input type="hidden" id="rgb" name="rgb" value="{{$star->rgb}}">
                        <div class="input-group-append">
                          <span class="input-group-text" id="rgb_value">{{$star->rgb}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description">Descrizione</label>
                    <div class="form-group">
                        <textarea class="form-control" rows="4" id="description" name="description">{{$star->description}}</textarea>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row">
                        <div class="col-3">
                            <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-save"></i> Salva</button>
                        </div>
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
@stop

@section('js')
    <script>
        $(document).ready(function () {

            $('#rgb_picker').on('input', function () {
                var hex = $(this).val();
                var r = parseInt(hex.substr(1, 2), 16);
                var g = parseInt(hex.substr(3, 2), 16);
                var b = parseInt(hex.substr(5, 2), 16);
                var rgb = r + ',' + g + ',' + b;
                $('#rgb').val(rgb);
                $('#rgb_value').text(rgb);
            });

        });
    </script>
@stop
