@extends('adminlte::page')

@section('title', 'Modifica Tile')

@section('content_header')
<h1>Modifica Tile</h1>
@stop

@section('content')
<form action="{{ route('tiles.update', $tile) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card card-primary card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-informazioni-principali-link" data-toggle="pill" href="#tab-informazioni-principali" role="tab"
                        aria-controls="tab-informazioni-principali" aria-selected="true">Informazioni Principali</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-grafica-link" data-toggle="pill" href="#tab-grafica" role="tab"
                        aria-controls="tab-grafica" aria-selected="false">Grafica</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="main-tabs-content">

                <!-- TAB INFORMAZIONI PRINCIPALI -->
                <div class="tab-pane fade show active" id="tab-informazioni-principali" role="tabpanel"
                    aria-labelledby="tab-informazioni-principali-link">
                    <div class="row">
                        <div class="col-6">
                            <label for="name">Nome*</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-font"></i></span>
                                </div>
                                <input type="text" id="name" name="name" value="{{$tile->name}}" class="form-control" placeholder="Nome" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="family_tile_id">Famiglia Tile*</label>
                                <select id="family_tile_id" name="family_tile_id" class="form-control" required>
                                    @foreach(\App\Models\FamilyTile::all() as $family)
                                        <option value="{{ $family->id }}" {{ $tile->family_tile_id == $family->id ? 'selected' : '' }}>{{ $family->name }} ({{ $family->type == 0 ? 'Solido' : 'Liquido' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row" style="display: none;">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="color">Colore</label>
                                <div class="input-group my-colorpicker colorpicker-element" data-colorpicker-id="2">
                                  <input type="text" id="color" name="color" class="form-control" value="#ffffff" data-original-title="" title="">
                                  <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-square" style="color: {{$rgb}}"></i></span>
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB GRAFICA -->
                <div class="tab-pane fade" id="tab-grafica" role="tabpanel" aria-labelledby="tab-grafica-link">
                    @include('tile.tabs.graphics')
                </div>

            </div>
        </div>

        <div class="card-footer" id="main-form-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salva
            </button>
            <a href="{{ route('tiles.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Annulla
            </a>
        </div>
    </div>
</form>
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