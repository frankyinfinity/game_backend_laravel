@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Nuovo Tile</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('tiles.store') }}">
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
                    <div class="form-group">
                        <label for="color">Colore*</label>
                        <div class="input-group my-colorpicker colorpicker-element" data-colorpicker-id="2">
                          <input type="text" id="color" name="color" class="form-control" data-original-title="" title="">
                          <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-square" style="color: rgb(28, 79, 236);"></i></span>
                          </div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="type">Tipo*</label>
                        <select id="type" name="type" class="form-control" required>
                          <option value="{{ App\Models\Tile::TYPE_SOLID }}">Solido</option>
                          <option value="{{ App\Models\Tile::TYPE_LIQUID }}">Liquido</option>
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