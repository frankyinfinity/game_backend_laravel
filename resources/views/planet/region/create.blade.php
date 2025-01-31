@extends('adminlte::page')

@section('title', 'Nuova Regione')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Nuova Regione del Pianeta: {{$planet->name}}</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('regions.store') }}">
            @csrf
            <input type="hidden" name="planet_id" value="{{$planet->id}}">
            <div class="row">
                <div class="col-12">
                    <label for="name">Nome*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-font"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Nome" required>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label for="climate_id">Clima*</label>
                                <select id="climate_id" name="climate_id" class="form-control" required>
                                  @foreach ($climates as $climate)
                                    <option value="{{$climate->id}}">{{$climate->name}}</option>
                                  @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="width">Larghezza (Tile)*</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-arrows-alt-h"></i></span>
                                </div>
                                <input type="number" id="width" name="width" min="1" value="1" class="form-control" placeholder="Larghezza" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="height">Altezza (Tile)*</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-arrows-alt-v"></i></span>
                                </div>
                                <input type="number" id="height" name="height" min="1" value="1" class="form-control" placeholder="Altezza" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label for="description">Descrizione</label>
                    <div class="form-group">
                        <textarea class="form-control" rows="4" id="description" name="description"></textarea>
                    </div>
                </div>
                <div class="col-12">
                    <div class="row">
                        <div class="col-3">
                            <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-save"></i> Salva</button>
                        </div>
                        <div class="col-3">
                            <a href="{{route('planets.show', [$planet->id])}}">
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

            

        });
    </script>
@stop