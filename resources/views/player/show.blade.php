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
        let shapes = {};
        let objects = {};

        class BasicDraw {

            constructor(object) {
                this.object = object;
                this.shapeType = object['type'];
                this.shape = null;
            }

            render(app) {

                if (!this.shape) return;

                let object = this.object;

                //Visible
                this.shape.renderable = object['attributes']['renderable'];

                //Add Scene
                app.stage.addChild(this.shape);

                const uid = object['uid'];
                shapes[uid] = this.shape;
                objects[uid] = this.object;

                //Interaction
                this.addInteractive();

            }

            addInteractive() {

                let object = objects[this.object['uid']];
                if(object['attributes']['interactives']['count'] === 0) return;

                this.shape.interactive = true;
                this.shape.buttonMode = true;

                let items = object['attributes']['interactives']['items'];
                Object.entries(items).forEach(([event, strFunction]) => {
                    let shape = this.shape;
                    this.shape.on(event, () => {
                        eval(strFunction);
                    });
                });

            }

        }

        class Square extends BasicDraw {

            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }

            build() {
                const object = this.object;
                this.shape.beginFill(object['color']);
                this.shape.drawRect(
                    object['x'],
                    object['y'],
                    object['size'],
                    object['size']
                );
                this.shape.endFill();
            }

        }

        class Rectangle extends BasicDraw {

            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }

            build() {
                const object = this.object;
                this.shape.beginFill(object['color']);
                this.shape.drawRect(
                    object['x'],
                    object['y'],
                    object['width'],
                    object['height']
                );
                this.shape.endFill();
            }

        }

        class MultiLine extends BasicDraw {

            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }

            build() {

                const object = this.object;

                const lineColor = object['color'];
                const lineThickness = object['thickness'];

                let points = object['points'];
                this.shape.moveTo(points[0].x, points[0].y);
                this.shape.lineStyle(lineThickness, lineColor);

                this.shape.moveTo(points[0].x, points[0].y);
                for (let i = 1; i < points.length; i++) {
                    this.shape.lineTo(points[i].x, points[i].y);
                }

            }

        }

        class Circle extends BasicDraw {

            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }

            build() {

                const object = this.object;

                const circleX = object['x'];
                const circleY = object['y'];
                const circleRadius = object['radius'];
                const fillColor = object['color'];

                this.shape.beginFill(fillColor);
                this.shape.drawCircle(circleX, circleY, circleRadius);
                this.shape.endFill();

            }

        }

        class Text extends BasicDraw {

            constructor(object) {
                super(object);
                this.shape = new PIXI.Text('',  {});
            }

            build() {
                const object = this.object;
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                if(object['color'] !== null) {
                    const hexValue = object['color'];
                    this.shape.style.fill = '#' + hexValue.toString(16).padStart(6, '0');
                }
                this.shape.text = object['text'];
            }

        }

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

            app.stage.sortableChildren = true;
            app.renderer.view.style.width = '100%';
            app.renderer.view.style.height = 'auto';
            app.renderer.resize(app.view.offsetWidth, app.view.offsetHeight);

            window.addEventListener('resize', () => {
                app.renderer.resize(app.view.offsetWidth, app.view.offsetHeight);
            });

        }

        function drawSquare(object) {
            let draw = new Square(object);
            draw.build();
            draw.render(app);
        }

        function drawRectangle(object) {
            let draw = new Rectangle(object);
            draw.build();
            draw.render(app);
        }

        function drawMultiLine(object) {
            let draw = new MultiLine(object);
            draw.build();
            draw.render(app);
        }

        function drawCircle(object) {
            let draw = new Circle(object);
            draw.build();
            draw.render(app);
        }

        function drawText(object) {
            let draw = new Text(object);
            draw.build();
            draw.render(app);
        }

        $(document).ready(function () {

            initPixi();

            // Enable pusher logging - don't include this in production
            Pusher.logToConsole = true;

            var pusher = new Pusher('f02185b1bc94c884ce5b', {
                cluster: 'eu',
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }
            });

            const playerId = {{ $player->id }};
            let channelName = 'private-player_' + playerId + '_channel';

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
                                if(itemType === 'rectangle') {
                                    drawRectangle(item);
                                }
                                if(itemType === 'multi_line') {
                                    drawMultiLine(item);
                                }
                                if(itemType === 'circle') {
                                    drawCircle(item);
                                }
                                if(itemType === 'text') {
                                    drawText(item);
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

            });

            channel.bind('move_entity', function(data) {

                let uid = data['uid'];
                let i = data['i'];
                let j = data['j'];

                let shape = shapes[uid];

                shape.x += j;
                shape.y += i;
                shape.zIndex = 1000;

                shapes[uid] = shape;

                let object = objects[uid];
                object['attributes']['tile_i'] = data['new_tile_i'];
                object['attributes']['tile_j'] = data['new_tile_j'];
                objects[uid] = object;

                let text2 = shapes[uid+'_text_row_2'];
                text2.text = 'I: ' + data['new_tile_i'];

                let text3 = shapes[uid+'_text_row_3'];
                text3.text = 'J: ' + data['new_tile_j'];

            });

        });
    </script>
@stop
