@extends('adminlte::page')

@section('title', 'Pianeta')

@section('content_header')@stop

@section('content')
<div class="card card-primary card-outline card-tabs">
    <div class="card-header p-0 pt-1 border-bottom-0">
        <ul class="nav nav-tabs" id="planet-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-informazioni-link" data-toggle="pill" href="#tab-informazioni" role="tab"
                    aria-controls="tab-informazioni" aria-selected="true">Informazioni Principali</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-regioni-link" data-toggle="pill" href="#tab-regioni" role="tab"
                    aria-controls="tab-regioni" aria-selected="false">Regioni</a>
            </li>
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content" id="planet-tabs-content">

            <!-- TAB INFORMAZIONI PRINCIPALI -->
            <div class="tab-pane fade show active" id="tab-informazioni" role="tabpanel"
                aria-labelledby="tab-informazioni-link">
                <div class="row">
                    <div class="col-12">
                        <label for="name">Nome</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-font"></i></span>
                            </div>
                            <input type="text" id="name" name="name" class="form-control" value="{{$planet->name}}" placeholder="Nome" disabled>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="description">Descrizione</label>
                        <div class="form-group">
                            <textarea class="form-control" rows="4" id="description" name="description" disabled>{{$planet->description}}</textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row">
                            <div class="col-4">
                                <a href="{{route('planets.edit', [$planet->id])}}">
                                    <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> Modifica</button>
                                </a>
                            </div>
                            <div class="col-4">
                                <a href="{{route('planets.index')}}">
                                    <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB REGIONI -->
            <div class="tab-pane fade" id="tab-regioni" role="tabpanel" aria-labelledby="tab-regioni-link">
                @include('planet.region.index', compact('planet'))
            </div>

        </div>
    </div>
</div>
@stop

@section('js')
    @parent
@stop
