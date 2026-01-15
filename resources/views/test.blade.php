@extends('adminlte::page')

@section('title', 'Test Page - Player Environment')

@section('content_header')
    <h1>Test Page - Player Environment</h1>
@stop

@section('content')
    <div id="display_container" style="width: 100%; height: 80vh; background: #000;"></div>
    <div class="status-msg" style="position: absolute; bottom: 10px; right: 10px; opacity: 0.5; background: rgba(0, 0, 0, 0.5); padding: 5px; border-radius: 5px; font-size: 0.8rem; color: white;">Test Page - Inizializzazione...</div>
@stop

@section('css')
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            color: black;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }
    </style>
@stop

@section('js')
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    <script>
        const BACK_URL = '{{ url("/") }}';
        const config = {
            PUSHER_KEY: '{{ config("broadcasting.connections.pusher.key") }}',
            PUSHER_CLUSTER: '{{ config("broadcasting.connections.pusher.options.cluster") }}'
        };
        const testPlayerId = 1; // Use existing player ID
        const sessionId = 'test_session_fixed';
        const hostname = new URL(BACK_URL).hostname;

        window.AppData = { actual_focus_uid_entity: null };
        window.gameWebSockets = {};

        let app = null;
        let shapes = {};
        let objects = {};

        function status(msg) {
            console.log('Status:', msg);
            document.querySelector('.status-msg').textContent = msg;
        }

        function sleep(ms) { return new Promise(resolve => setTimeout(resolve, ms)); }

        class BasicDraw {
            constructor(object) {
                this.object = object;
                this.shapeType = object['type'];
                this.shape = null;
            }
            render(pixiApp) {
                if (!this.shape) return;
                const object = this.object;
                const uid = object['uid'];

                // Rimuovi eventuale oggetto duplicato con lo stesso UID per evitare "ghosting"
                if (shapes[uid]) {
                    pixiApp.stage.removeChild(shapes[uid]);
                    if (typeof shapes[uid].destroy === 'function') shapes[uid].destroy();
                    delete shapes[uid];
                }

                this.shape.renderable = object['attributes'] && object['attributes']['renderable'] !== undefined
                    ? !!object['attributes']['renderable']
                    : true;

                if (object['attributes'] && object['attributes']['z_index'] !== undefined) {
                    this.shape.zIndex = object['attributes']['z_index'];
                }

                pixiApp.stage.addChild(this.shape);
                shapes[uid] = this.shape;
                objects[uid] = this.object;
                this.addInteractive();
            }
            addInteractive() {
                const object = objects[this.object['uid']];
                if (!object['attributes'] || !object['attributes']['interactives'] || object['attributes']['interactives']['count'] === 0) return;

                this.shape.interactive = true;
                this.shape.cursor = 'pointer';
                const items = object['attributes']['interactives']['items'];

                const targetShape = this.shape;
                Object.entries(items).forEach(([event, strFunction]) => {
                    const processedScript = strFunction
                        .replace(/<script>/g, '')
                        .replace(/<\/script>/g, '')
                        .replace(/window\.location\.hostname/g, `'${hostname}'`);

                    targetShape.on(event, () => {
                        console.log(`Interaction: ${event} on ${object.uid}`);
                        try {
                            (function (object, shape, shapes, objects, AppData) {
                                eval(processedScript);
                            })(object, targetShape, shapes, objects, AppData);
                        } catch (e) { console.error('Error executing interaction script:', e); }
                    });
                });
            }
        }

        class Square extends BasicDraw {
            constructor(object) { super(object); this.shape = new PIXI.Sprite(PIXI.Texture.WHITE); }
            build() {
                const object = this.object;
                this.shape.width = object['size'];
                this.shape.height = object['size'];
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                this.shape.tint = object['color'];
            }
        }

        class Rectangle extends BasicDraw {
            constructor(object) { super(object); this.shape = new PIXI.Sprite(PIXI.Texture.WHITE); }
            build() {
                const object = this.object;
                this.shape.width = object['width'];
                this.shape.height = object['height'];
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                this.shape.tint = object['color'];
            }
        }

        class MultiLine extends BasicDraw {
            constructor(object) { super(object); this.shape = new PIXI.Graphics(); }
            build() {
                const object = this.object;
                const lineColor = object['color'];
                const lineThickness = object['thickness'] || 1;
                const points = object['points'];
                this.shape.lineStyle(lineThickness, 0xFFFFFF);
                this.shape.tint = lineColor;
                if (points && points.length > 0) {
                    this.shape.moveTo(points[0].x, points[0].y);
                    for (let i = 1; i < points.length; i++) {
                        this.shape.lineTo(points[i].x, points[i].y);
                    }
                }
            }
        }

        class Circle extends BasicDraw {
            constructor(object) { super(object); this.shape = new PIXI.Graphics(); }
            build() {
                const object = this.object;
                this.shape.beginFill(0xFFFFFF);
                this.shape.drawCircle(0, 0, object['radius']);
                this.shape.endFill();
                this.shape.tint = object['color'];
                this.shape.x = object['x'];
                this.shape.y = object['y'];
            }
        }

        class Text extends BasicDraw {
            constructor(object) { super(object); this.shape = new PIXI.Text('', {}); }
            build() {
                const object = this.object;
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                if (object['color'] !== null) {
                    const hexValue = object['color'];
                    this.shape.style.fill = '#' + hexValue.toString(16).padStart(6, '0');
                }
                this.shape.style.fontFamily = object['fontFamily'];
                this.shape.style.fontSize = object['fontSize'];
                this.shape.text = object['text'];
                if (object['centerAnchor']) {
                    this.shape.pivot.set(this.shape.width / 2, this.shape.height / 2);
                }
            }
        }

        function initPixi() {
            app = new PIXI.Application({
                width: window.innerWidth,
                height: window.innerHeight,
                backgroundColor: 0x000000,
                antialias: true,
                resolution: window.devicePixelRatio || 1,
                autoDensity: true
            });
            document.getElementById('display_container').appendChild(app.view);
            app.stage.sortableChildren = true;
        }

        function drawSquare(object) {
            let d = new Square(object);
            d.build();
            d.render(app);
        }

        function drawRectangle(object) {
            let d = new Rectangle(object);
            d.build();
            d.render(app);
        }

        function drawMultiLine(object) {
            let d = new MultiLine(object);
            d.build();
            d.render(app);
        }

        function drawCircle(object) {
            let d = new Circle(object);
            d.build();
            d.render(app);
        }

        function drawText(object) {
            let d = new Text(object);
            d.build();
            d.render(app);
        }

        $(document).ready(function () {
            initPixi();
            status('PixiJS Avviato - Connessione a Pusher...');

            const pusher = new Pusher(config.PUSHER_KEY, {
                cluster: config.PUSHER_CLUSTER,
                forceTLS: true
            });

            const channelName = 'player_' + testPlayerId + '_channel';
            let channel = pusher.subscribe(channelName);

            channel.bind('pusher:subscription_succeeded', function () {
                status('Connesso a Pusher - In attesa di eventi di disegno...');
                console.log('Test player channel subscribed:', channelName);
            });

            channel.bind('draw_interface', function (data) {
                console.log('Draw interface event received:', data);
                status('Evento di disegno ricevuto...');

                let items = data['items'];

                if (!items) {
                    // Fallback: se non ci sono items nell'evento, fai la chiamata AJAX
                    status('Caricamento dati dal server...');
                    const request_id = data['request_id'];
                    const p_id = data['player_id'];

                    $.ajax({
                        url: `${BACK_URL}/api/players/get-map`,
                        type: 'POST',
                        data: { request_id, player_id: testPlayerId, session_id: sessionId },
                        success: async function (result) {
                            if (result.success) {
                                items = result.items;
                                processItems(items);
                            }
                        },
                        error: function (err) {
                            status('Errore nel caricamento dati');
                            console.error('Error loading map data:', err);
                        }
                    });
                } else {
                    // Items ricevuti direttamente nell'evento
                    processItems(items);
                }

                async function processItems(items) {
                    status('Disegno elementi...');

                    for (const item of items) {
                        let itemType = item['type'].toString();
                        if (itemType === 'draw') {
                            let obj = item['object'];
                            if (obj.id === undefined) obj.id = obj.uid;
                            if (obj.type === 'square') drawSquare(obj);
                            else if (obj.type === 'rectangle') drawRectangle(obj);
                            else if (obj.type === 'multi_line') drawMultiLine(obj);
                            else if (obj.type === 'circle') drawCircle(obj);
                            else if (obj.type === 'text') drawText(obj);
                        } else if (itemType === 'update') {
                            if (item.sleep) await sleep(item.sleep);
                            let shape = shapes[item.uid];
                            if (shape) {
                                Object.keys(item.attributes).forEach(key => {
                                    if (key === 'color') {
                                        shape.tint = item.attributes[key];
                                    } else if (key === 'renderable') {
                                        shape.renderable = !!item.attributes[key];
                                    } else {
                                        shape[key] = item.attributes[key];
                                    }
                                });
                            }
                        } else if (itemType === 'clear') {
                            let shape = shapes[item.uid];
                            if (shape) {
                                if (typeof shape.clear === 'function') shape.clear();
                                app.stage.removeChild(shape);
                                delete shapes[item.uid];
                                delete objects[item.uid];
                            }
                        }
                    }
                    app.stage.sortChildren();
                    status('Disegno completato');
                }
            });

            // Function to manually trigger test drawing
            window.testDraw = function(circleData) {
                console.log('Manual test draw:', circleData);
                drawCircle(circleData);
                status('Disegno manuale completato');
            };
        });
    </script>
@stop