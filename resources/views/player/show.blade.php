@extends('adminlte::page')

@section('title', "Giocatore $username")

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
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script> 
        $(document).ready(function () {

            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('f02185b1bc94c884ce5b', {
                cluster: 'eu',
                forceTLS: true,
            });

            var channel = pusher.subscribe('my-channel');
            channel.bind('my-event', function(data) {
                alert(JSON.stringify(data));
            });

        });
    </script>
@stop