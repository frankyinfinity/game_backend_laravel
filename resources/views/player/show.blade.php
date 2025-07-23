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
            <div class="col-md-12">
                <div id="display_container"></div>
            </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    <script> 

        let app = null;
        function initPixi() {

            app = new PIXI.Application({
                width: 1300, 
                height: 500,
                backgroundColor: 0xADD8E6,
                antialias: true,
                resolution: window.devicePixelRatio || 1,
                autoDensity: true,
            });
            document.getElementById('display_container').appendChild(app.view);

            app.renderer.view.style.width = '100%';
            app.renderer.view.style.height = 'auto';
            app.renderer.resize(app.view.offsetWidth, app.view.offsetHeight);

            window.addEventListener('resize', () => {
                app.renderer.resize(app.view.offsetWidth, app.view.offsetHeight);
            });

        }

        function drawSquare(object) {

            const square = new PIXI.Graphics();
            square.beginFill(object['color']);

            square.drawRect(
                object['x'],
                object['y'],
                object['size'],
                object['size']
            );
            square.endFill();

            app.stage.addChild(square);

        }

        $(document).ready(function () {

            initPixi();

            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('f02185b1bc94c884ce5b', {
                cluster: 'eu',
            });

            var channel = pusher.subscribe('player_channel');
            channel.bind('pusher:subscription_succeeded', function() {
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: "{{ route('players.draw.map') }}",
                    type: 'POST',
                    data: { 
                        player_id: '{{$player->id}}',
                     },
                    success: function(result) {
                        if(!result.success) {
                            var msg = 'Si è verificato un errore.';
                            if(result.msg != null) msg = result.msg;
                            $.notify({title: "Ops!", message: result.msg}, {type: "warning"})
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Ops!',
                            text: 'Si è verificato un errore imprevisto.',
                            type: 'danger',
                            showCancelButton: false,
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-info',
                            confirmButtonText: 'Ho Capito!',
                        })
                    }
                });

            });
            
            const playerId = {{ $player->id }};
            let event = 'player_' + playerId + '_event';
            channel.bind(event, function(data) {
                
                let type = data['type'];
                if(type === 'draw_map') {
                    for (const item of data['items']) {
                        let itemType = item['type'];
                        if(itemType === 'square') {
                            drawSquare(item);
                        }
                    }
                }
                
            });

        });
    </script>
@stop