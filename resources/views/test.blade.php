@extends('adminlte::page')

@section('title', 'Test Page - Player Environment')

@section('content_header')
    <h1>Test Page - Player Environment</h1>
@stop

@section('content')
    <div id="display_container" style="width: 100%; height: 80vh; background: #fff;"></div>
    <div class="status-msg"
        style="position: absolute; bottom: 10px; right: 10px; opacity: 0.5; background: rgba(0, 0, 0, 0.5); padding: 5px; border-radius: 5px; font-size: 0.8rem; color: white;">
        Test Page - Inizializzazione...</div>
@stop

@section('css')
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: black;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    <script>
        const BACK_URL = '{{ url('/') }}';
        const config = {
            SOCKETIO_URL: '{{ config('broadcasting.connections.socketio.url') }}'
        };
        const testPlayerId = 1; // Use existing player ID
        window.playerId = 60;
        const sessionId = 'test_session_fixed';
        const hostname = new URL(BACK_URL).hostname;

        window.AppData = {
            actual_focus_uid_entity: null
        };
        window.gameWebSockets = {};

        let app = null;
        let shapes = {};
        let objects = {};
        const ENABLE_GLOBAL_PAN = false;
        let worldLayer = null;
        let mainLayer = null;
        let modalViewportMasks = {};
        let globalPan = { x: 0, y: 0 };
        let isGlobalDragging = false;
        let globalDragStart = { x: 0, y: 0 };

        function applyGlobalPan() {
            if (!worldLayer) return;
            worldLayer.x = globalPan.x;
            worldLayer.y = globalPan.y;
        }

        function enableGlobalScrollPan() {
            if (!ENABLE_GLOBAL_PAN || !app || !app.view || !worldLayer) return;

            app.view.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });

            app.view.addEventListener('wheel', function(e) {
                if (!worldLayer.children || worldLayer.children.length === 0) return;
                e.preventDefault();

                const speed = 0.8;
                globalPan.x -= (e.deltaX || 0) * speed;

                if (e.shiftKey) {
                    globalPan.x -= (e.deltaY || 0) * speed;
                } else {
                    globalPan.y -= (e.deltaY || 0) * speed;
                }

                applyGlobalPan();
            }, { passive: false });

            app.view.addEventListener('mousedown', function(e) {
                if (window.__disableGlobalPan) return;
                if (!worldLayer.children || worldLayer.children.length === 0) return;
                // Enable drag with any mouse button in test environment.
                if (e.button !== 0 && e.button !== 1 && e.button !== 2) return;

                isGlobalDragging = true;
                globalDragStart.x = e.clientX - globalPan.x;
                globalDragStart.y = e.clientY - globalPan.y;
                document.body.style.userSelect = 'none';
                document.body.style.cursor = 'grabbing';
            });

            app.view.addEventListener('pointerdown', function(e) {
                if (window.__disableGlobalPan) return;
                if (!worldLayer.children || worldLayer.children.length === 0) return;
                if (typeof e.clientX !== 'number' || typeof e.clientY !== 'number') return;

                isGlobalDragging = true;
                globalDragStart.x = e.clientX - globalPan.x;
                globalDragStart.y = e.clientY - globalPan.y;
                document.body.style.userSelect = 'none';
                document.body.style.cursor = 'grabbing';
            });

            window.addEventListener('mousemove', function(e) {
                if (window.__disableGlobalPan) return;
                if (!isGlobalDragging) return;

                globalPan.x = e.clientX - globalDragStart.x;
                globalPan.y = e.clientY - globalDragStart.y;
                applyGlobalPan();
            });

            window.addEventListener('mouseup', function() {
                isGlobalDragging = false;
                document.body.style.userSelect = '';
                document.body.style.cursor = '';
            });

            window.addEventListener('pointerup', function() {
                isGlobalDragging = false;
                document.body.style.userSelect = '';
                document.body.style.cursor = '';
            });
        }

        function status(msg) {
            console.log('Status:', msg);
            document.querySelector('.status-msg').textContent = msg;
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        function toPixiColor(value, fallback = 0xFFFFFF) {
            if (value === null || value === undefined) return fallback;
            if (typeof value === 'number' && Number.isFinite(value)) return value;
            if (typeof value === 'string') {
                const trimmed = value.trim();
                if (trimmed.startsWith('#')) {
                    const parsed = parseInt(trimmed.slice(1), 16);
                    return Number.isFinite(parsed) ? parsed : fallback;
                }
                if (/^0x[0-9a-f]+$/i.test(trimmed)) {
                    const parsed = parseInt(trimmed, 16);
                    return Number.isFinite(parsed) ? parsed : fallback;
                }
                if (/^[0-9a-f]{6}$/i.test(trimmed)) {
                    const parsed = parseInt(trimmed, 16);
                    return Number.isFinite(parsed) ? parsed : fallback;
                }
            }
            return fallback;
        }

        function ensureModalViewportMask(viewportUid) {
            const viewportObject = objects[viewportUid];
            const viewportShape = shapes[viewportUid];
            if (!viewportObject || !viewportShape || !viewportObject.attributes) return;

            const attrs = viewportObject.attributes;
            const childUids = Array.isArray(attrs.scroll_child_uids) ? attrs.scroll_child_uids : [];
            if (childUids.length === 0) return;

            const width = viewportObject.width || 0;
            const height = viewportObject.height || 0;
            const x = viewportObject.x || 0;
            const y = viewportObject.y || 0;
            if (width <= 0 || height <= 0) return;

            let mask = modalViewportMasks[viewportUid];
            if (!mask) {
                mask = new PIXI.Graphics();
                // Keep mask renderable for reliable clipping in Pixi, but invisible.
                mask.renderable = true;
                mask.alpha = 0;
                modalViewportMasks[viewportUid] = mask;
                const parentLayer = viewportShape.parent || mainLayer;
                parentLayer.addChild(mask);
            }

            mask.clear();
            mask.beginFill(0xFFFFFF);
            mask.drawRect(0, 0, width, height);
            mask.endFill();
            mask.x = x;
            mask.y = y;

            childUids.forEach((childUid) => {
                if (shapes[childUid]) {
                    shapes[childUid].mask = mask;
                }
            });
        }

        function refreshAllModalViewportMasks() {
            Object.keys(objects).forEach((uid) => {
                const obj = objects[uid];
                if (obj && obj.attributes && Array.isArray(obj.attributes.scroll_child_uids)) {
                    ensureModalViewportMask(uid);
                }
            });
        }

        // Expose for modal open/close scripts injected from backend.
        window.refreshAllModalViewportMasks = refreshAllModalViewportMasks;

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
                    if (shapes[uid].parent) {
                        shapes[uid].parent.removeChild(shapes[uid]);
                    } else {
                        pixiApp.stage.removeChild(shapes[uid]);
                    }
                    if (typeof shapes[uid].destroy === 'function') shapes[uid].destroy();
                    delete shapes[uid];
                }

                this.shape.renderable = object['attributes'] && object['attributes']['renderable'] !== undefined ?
                    !!object['attributes']['renderable'] :
                    true;

                if (object['attributes'] && object['attributes']['z_index'] !== undefined) {
                    this.shape.zIndex = object['attributes']['z_index'];
                }

                // Test page: render everything in one layer so modal viewport masks
                // are always applied consistently.
                mainLayer.addChild(this.shape);
                shapes[uid] = this.shape;
                objects[uid] = this.object;
                this.addInteractive();
            }
            addInteractive() {
                const object = objects[this.object['uid']];
                if (!object['attributes'] || !object['attributes']['interactives'] || object['attributes'][
                        'interactives'
                    ]['count'] === 0) return;

                this.shape.interactive = true;
                this.shape.cursor = 'pointer';
                const items = object['attributes']['interactives']['items'];

                const targetShape = this.shape;
                Object.entries(items).forEach(([event, strFunction]) => {
                    const processedScript = strFunction
                        .replace(/<script>/g, '')
                        .replace(/<\/script>/g, '')
                        .replace(/window\.location\.hostname/g, `'${hostname}'`);

                    targetShape.on(event, (evt) => {
                        console.log(`Interaction: ${event} on ${object.uid}`);
                        try {
                            (function(object, shape, shapes, objects, AppData, event) {
                                eval(processedScript);
                            })(object, targetShape, shapes, objects, AppData, evt);
                        } catch (e) {
                            console.error('Error executing interaction script:', e);
                        }
                    });
                });
            }
        }

        class Square extends BasicDraw {
            constructor(object) {
                super(object);
                this.shape = new PIXI.Sprite(PIXI.Texture.WHITE);
            }
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
            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }
            build() {
                const object = this.object;
                const fillColor = toPixiColor(object['color'], 0xFFFFFF);
                const borderColor = object['borderColor'] !== null && object['borderColor'] !== undefined
                    ? toPixiColor(object['borderColor'], fillColor)
                    : null;
                
                // Check if we need rounded corners
                const borderRadius = object['borderRadius'] || 0;
                
                if (borderRadius > 0 && borderColor !== null) {
                    // Draw rounded rectangle with border
                    this.shape.beginFill(borderColor);
                    this.shape.drawRoundedRect(0, 0, object['width'], object['height'], borderRadius);
                    this.shape.endFill();
                    
                    // Draw inner fill if color is different
                    if (fillColor !== borderColor) {
                        const padding = 2;
                        this.shape.beginFill(fillColor);
                        this.shape.drawRoundedRect(
                            padding, 
                            padding, 
                            object['width'] - (padding * 2), 
                            object['height'] - (padding * 2), 
                            Math.max(0, borderRadius - padding)
                        );
                        this.shape.endFill();
                    }
                } else {
                    // Regular rectangle
                    this.shape.beginFill(fillColor);
                    this.shape.drawRect(0, 0, object['width'], object['height']);
                    this.shape.endFill();
                }
                
                this.shape.x = object['x'];
                this.shape.y = object['y'];
            }
        }

        class MultiLine extends BasicDraw {
            constructor(object) {
                super(object);
                this.shape = new PIXI.Graphics();
            }
            build() {
                const object = this.object;
                const lineColor = toPixiColor(object['color'], 0x666666);
                const lineThickness = object['thickness'] || 1;
                const points = object['points'];
                this.shape.lineStyle(lineThickness, lineColor);
                if (points && points.length > 0) {
                    this.shape.moveTo(points[0].x, points[0].y);
                    for (let i = 1; i < points.length; i++) {
                        this.shape.lineTo(points[i].x, points[i].y);
                    }
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
                const fillColor = toPixiColor(object['color'], 0xFFFFFF);
                this.shape.beginFill(fillColor);
                this.shape.drawCircle(0, 0, object['radius']);
                this.shape.endFill();
                this.shape.x = object['x'];
                this.shape.y = object['y'];
            }
        }

        class Text extends BasicDraw {
            constructor(object) {
                super(object);
                this.shape = new PIXI.Text('', {});
            }
            build() {
                const object = this.object;
                
                // Build style first
                const style = new PIXI.TextStyle();
                
                if (object['color'] !== null && object['color'] !== undefined) {
                    let colorValue = object['color'];
                    
                    // Handle string colors - use directly if already formatted
                    if (typeof colorValue === 'string') {
                        // Remove any leading # if present, then add it properly
                        let cleanColor = colorValue.replace(/^#+/, '');
                        style.fill = '#' + cleanColor;
                    } else if (typeof colorValue === 'number') {
                        // Convert numeric hex to #RRGGBB format
                        style.fill = '#' + colorValue.toString(16).padStart(6, '0');
                    }
                }
                
                if (object['fontFamily']) {
                    style.fontFamily = object['fontFamily'];
                }
                if (object['fontSize']) {
                    style.fontSize = object['fontSize'];
                }
                
                this.shape.style = style;
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                this.shape.text = object['text'];
                if (object['centerAnchor']) {
                    this.shape.pivot.set(this.shape.width / 2, this.shape.height / 2);
                }
            }
        }

        class ImageSprite extends BasicDraw {
            constructor(object) {
                super(object);
                this.shape = null;
                this.loaded = false;
            }
            build() {
                return new Promise((resolve, reject) => {
                    const object = this.object;
                    // Build full URL from relative path
                    let src = object['src'];
                    if (src && !src.startsWith('http')) {
                        src = BACK_URL + src;
                    }
                    console.log('Loading image from:', src);
                    
                    const texture = PIXI.Texture.from(src);
                    this.shape = new PIXI.Sprite(texture);
                    
                    if (texture.baseTexture.valid) {
                        this.applyProperties();
                        this.loaded = true;
                        resolve();
                    } else {
                        texture.baseTexture.on('loaded', () => {
                            this.applyProperties();
                            this.loaded = true;
                            resolve();
                        });
                        texture.baseTexture.on('error', (err) => {
                            console.error('Error loading image:', src, err);
                            reject(err);
                        });
                    }
                });
            }
            applyProperties() {
                const object = this.object;
                this.shape.x = object['x'];
                this.shape.y = object['y'];
                if (object['width'] !== undefined && object['width'] !== null) {
                    this.shape.width = object['width'];
                }
                if (object['height'] !== undefined && object['height'] !== null) {
                    this.shape.height = object['height'];
                }
                if (object['color'] !== undefined && object['color'] !== null) {
                    this.shape.tint = object['color'];
                }
            }
        }

        function initPixi() {
            app = new PIXI.Application({
                width: window.innerWidth,
                height: window.innerHeight,
                backgroundColor: 0xffffff,
                antialias: true,
                resolution: window.devicePixelRatio || 1,
                autoDensity: true
            });
            document.getElementById('display_container').appendChild(app.view);
            app.stage.sortableChildren = true;

            worldLayer = new PIXI.Container();
            worldLayer.sortableChildren = true;
            app.stage.addChild(worldLayer);

            mainLayer = new PIXI.Container();
            mainLayer.sortableChildren = true;
            worldLayer.addChild(mainLayer);

            if (typeof AppData !== 'undefined') {
                AppData.enable_global_pan = ENABLE_GLOBAL_PAN;
            }
            enableGlobalScrollPan();
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

        async function drawImage(object) {
            let d = new ImageSprite(object);
            await d.build();
            d.render(app);
        }

        $(document).ready(function() {
            initPixi();
            status('PixiJS Avviato - Connessione a Socket.io...');

            const socket = io(config.SOCKETIO_URL, {
                transports: ['websocket', 'polling'],
                reconnection: true,
                reconnectionDelay: 1000,
                reconnectionAttempts: Infinity
            });

            const channelName = 'player_' + testPlayerId + '_channel';

            socket.on('connect', function() {
                status('Connesso a Socket.io - Sottoscrizione canale...');
                console.log('Socket.io connected:', socket.id);

                // Subscribe to channel
                socket.emit('subscribe', {
                    channel: channelName,
                    auth: {
                        token: localStorage.getItem('auth_token') || null
                    }
                });
            });

            socket.on('subscription_succeeded', function(data) {
                if (data.channel === channelName) {
                    status('Connesso a Socket.io - In attesa di eventi di disegno...');
                    console.log('Test player channel subscribed:', channelName);
                }
            });

            socket.on('subscription_error', function(data) {
                status('Errore sottoscrizione canale: ' + data.error);
                console.error('Subscription error:', data);
            });

            socket.on('disconnect', function(reason) {
                status('Disconnesso da Socket.io: ' + reason);
                console.log('Socket.io disconnected:', reason);
            });

            socket.on('connect_error', function(error) {
                status('Errore connessione Socket.io: ' + error.message);
                console.error('Socket.io connection error:', error);
            });

            // Listen for draw_interface events
            socket.on('draw_interface', function(data) {
                console.log('Draw interface event received:', data);
                status('Evento di disegno ricevuto...');

                let items = data['items'];

                if (!items) {
                    // Fallback: se non ci sono items nell'evento, fai la chiamata AJAX
                    status('Caricamento dati dal server...');
                    const request_id = data['request_id'];
                    const p_id = data['player_id'];

                    $.ajax({
                        url: `${BACK_URL}/api/game/get_draw_item`,
                        type: 'POST',
                        data: {
                            request_id,
                            player_id: testPlayerId,
                            session_id: sessionId
                        },
                        success: async function(result) {
                            if (result.success) {
                                items = result.items;
                                processItems(items);
                            }
                        },
                        error: function(err) {
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
                            else if (obj.type === 'image') await drawImage(obj);
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
                            if (modalViewportMasks[item.uid]) {
                                const mask = modalViewportMasks[item.uid];
                                if (mask.parent) {
                                    mask.parent.removeChild(mask);
                                }
                                if (typeof mask.destroy === 'function') {
                                    mask.destroy();
                                }
                                delete modalViewportMasks[item.uid];
                            }
                            if (shape) {
                                if (typeof shape.clear === 'function') shape.clear();
                                if (shape.parent) {
                                    shape.parent.removeChild(shape);
                                } else {
                                    app.stage.removeChild(shape);
                                }
                                delete shapes[item.uid];
                                delete objects[item.uid];
                            }
                        }
                    }
                    refreshAllModalViewportMasks();
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
