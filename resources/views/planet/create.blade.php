@extends('adminlte::page')

@section('title', 'Nuovo Pianeta')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Nuovo Pianeta</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('planets.store') }}">
            @csrf
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
                            <a href="{{route('planets.index')}}">
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