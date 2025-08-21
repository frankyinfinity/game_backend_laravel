@extends('adminlte::page')

@section('title', "Test")

@section('content_header')@stop

@section('content')
<div></div>
@stop

@section('js')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script>
        $(document).ready(function () {

            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('f02185b1bc94c884ce5b', {
                cluster: 'eu',
            });

            let channelName = 'my-channel';
            var channel = pusher.subscribe(channelName);

            channel.bind('TestEvent', function(data) {
                alert(data.msg);
            });

        });
    </script>
@stop
