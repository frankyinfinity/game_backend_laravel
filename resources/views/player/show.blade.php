@extends('adminlte::page')

@section('title', 'Nuovo Pianeta')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Giocatore: <strong>{{$player->user->name}}</strong></h5>
    </div>
    <div class="card-body">
        <div class="row">

            <div class="col-12">
                <div class="row">
                    <div class="col-3">
                        <a href="{{route('planets.index')}}">
                            <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>                    
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script> 
        $(document).ready(function () {

            

        });
    </script>
@stop