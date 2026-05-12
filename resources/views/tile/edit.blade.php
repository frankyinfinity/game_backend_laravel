@extends('adminlte::page')

@section('title', 'Modifica Tile')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Modifica Tile</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('tiles.update', $tile) }}">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-6">
                    <label for="name">Nome*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-font"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nome" value="{{ $tile->name }}" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="color">Colore*</label>
                        <div class="input-group my-colorpicker colorpicker-element" data-colorpicker-id="2">
                          <input type="text" id="color" name="color" class="form-control" data-original-title="" title="" value="{{ $tile->color }}">
                          <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-square" style="color: {{ $tile->color }};"></i></span>
                          </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="family_tile_id">Famiglia Tile*</label>
                        <select id="family_tile_id" name="family_tile_id" class="form-control" required>
                            @foreach(\App\Models\FamilyTile::all() as $family)
                                <option value="{{ $family->id }}" {{ $tile->family_tile_id == $family->id ? 'selected' : '' }}>{{ $family->name }} ({{ \App\Models\FamilyTile::getTypeLabels()[$family->type] }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row">
                        <div class="col-3">
                            <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-save"></i> Salva</button>
                        </div>
                        <div class="col-3">
                            <a href="{{route('tiles.index')}}">
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

            $('.my-colorpicker').colorpicker();
            $('.my-colorpicker').on('colorpickerChange', function(event) {
                $('.my-colorpicker .fa-square').css('color', event.color.toString());
            });

        });
    </script>
@stop