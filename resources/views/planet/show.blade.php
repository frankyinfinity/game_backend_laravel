@extends('adminlte::page')

@section('title', 'Pianeta')

@section('content_header')@stop

@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header pb-0">
                <h4 class="mb-0">Pianeta</h5>
            </div>
            <div class="card-body">
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
        </div>
    </div>
    <div class="col-md-7">

    </div>
</div>
@stop

@section('js')
    <script> 
        $(document).ready(function () {

            

        });
    </script>
@stop