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
                <div id="display_container" style="max-width: 100%; max-height: 500px; overflow-x: scroll; overflow-y: scroll;"></div>
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
        let objects = {};
        function initPixi() {

            app = new PIXI.Application({
                width: {{ $width }}, 
                height: {{ $height }},
                backgroundColor: 0x00000,
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

            let uid = object['uid'];
            objects[uid] = square;

        }

        function drawMultiLine(object) {

            const lineGraphics = new PIXI.Graphics();

            const lineColor = object['color'];
            const lineThickness = object['thickness'];

            let points = object['points'];
            lineGraphics.moveTo(points[0].x, points[0].y);
            lineGraphics.lineStyle(lineThickness, lineColor);

            lineGraphics.moveTo(points[0].x, points[0].y);
            for (let i = 1; i < points.length; i++) {
                lineGraphics.lineTo(points[i].x, points[i].y);
            }

            app.stage.addChild(lineGraphics);

            let uid = object['uid'];
            objects[uid] = lineGraphics;
            
        }

        function drawCircle(object) {

            const circleGraphics = new PIXI.Graphics();

            const circleX = object['x'];
            const circleY = object['y'];
            const circleRadius = object['radius'];
            const fillColor = object['color'];
            
            circleGraphics.beginFill(fillColor);
            circleGraphics.drawCircle(circleX, circleY, circleRadius);
            circleGraphics.endFill();

            app.stage.addChild(circleGraphics);

            let uid = object['uid'];
            objects[uid] = circleGraphics;

        }

        $(document).ready(function () {

            initPixi();

            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('f02185b1bc94c884ce5b', {
                cluster: 'eu',
            });

            const playerId = {{ $player->id }};
            let channelName = 'player_' + playerId + '_channel';

            var channel = pusher.subscribe(channelName);
            channel.bind('pusher:subscription_succeeded', function() {
                
                $.ajax({
                    url: "{{ route('players.generate.map') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
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
            
            channel.bind('draw_map', function(data) {

                let request_id = data['request_id'];
                let player_id = data['player_id'];
                $.ajax({
                    url: "{{ route('players.get.map') }}",
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { 
                        request_id: request_id,
                        player_id: player_id,
                     },
                    success: function(result) {
                        if(result.success) {
                            let items = result.items;
                            for (const item of items) {
                                let itemType = item['type'];
                                if(itemType === 'square') {
                                    drawSquare(item);
                                }
                                if(itemType === 'multi_line') {
                                    drawMultiLine(item);
                                }
                                if(itemType === 'circle') {
                                    drawCircle(item);
                                }
                            }
                        } else {
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

                /*let type = data['type'];
                if(type === 'draw_map') {
                    console.log(data['items']);
                    for (const item of data['items']) {
                        let itemType = item['type'];
                        if(itemType === 'square') {
                            drawSquare(item);
                        }
                    }
                }*/
                
            });

        });
    </script>
@stop