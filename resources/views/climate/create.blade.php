@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Nuovo Clima</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('climates.store') }}">
            @csrf
            <div class="row">
                <div class="col-6">
                    <label for="name">Nome*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-font"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nome" required>
                    </div>
                </div>
                <div class="col-6">
                    <label class="mb-0 text-white">...</label><br>
                    <div class="form-check form_check_with_checkbox">
                        <input class="form-check-input" type="checkbox" name="started">
                        <label class="form-check-label">Clima Iniziale</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="min_temperature">Temperatura MIN*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-temperature-low"></i></span>
                        </div>
                        <input type="number" id="min_temperature" name="min_temperature" class="form-control" placeholder="Temperatura MIN" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="max_temperature">Temperatura MAX*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-temperature-high"></i></span>
                        </div>
                        <input type="number" id="max_temperature" name="max_temperature" class="form-control" placeholder="Temperatura MAX" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="default_tile_id">Tile*</label>
                        <select id="default_tile_id" name="default_tile_id" class="form-control" required>
                          @foreach ($tiles as $tile)
                            <option value="{{$tile->id}}">{{$tile->name}}</option>
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
                            <a href="{{route('climates.index')}}">
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