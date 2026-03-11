@extends('adminlte::page')

@section('title', 'Test Page - Player Environment')

@section('content_header')
    <h1>Test Page - Player Environment</h1>
@stop

@section('content')
    <div id="display_container" style="width: 100%; height: 80vh; background: #fff; position: relative;">
        <div id="map_direction_pad" class="map-direction-pad">
            <button type="button" class="map-dir-btn map-dir-up" data-dir="up"><i class="fa fa-chevron-up"></i></button>
            <button type="button" class="map-dir-btn map-dir-left" data-dir="left"><i class="fa fa-chevron-left"></i></button>
            <button type="button" class="map-dir-btn map-dir-right" data-dir="right"><i class="fa fa-chevron-right"></i></button>
            <button type="button" class="map-dir-btn map-dir-down" data-dir="down"><i class="fa fa-chevron-down"></i></button>
        </div>
    </div>
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

        .map-direction-pad {
            position: absolute;
            left: 16px;
            bottom: 16px;
            width: 120px;
            height: 120px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 6px;
            z-index: 50;
            pointer-events: auto;
        }

        .map-dir-btn {
            border: 1px solid #cfd8dc;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.95);
            color: #37474f;
            font-size: 14px;
            cursor: pointer;
        }

        .map-dir-btn:active {
            background: #eceff1;
        }

        .map-dir-up { grid-column: 2; grid-row: 1; }
        .map-dir-left { grid-column: 1; grid-row: 2; }
        .map-dir-right { grid-column: 3; grid-row: 2; }
        .map-dir-down { grid-column: 2; grid-row: 3; }
    </style>
@stop

@section('js')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.4.2/pixi.min.js"></script>
    <script>
        const BACK_URL = '{{ url('/') }}';
        window.BACK_URL = BACK_URL;
        const config = {
            REVERB_APP_KEY: '{{ config('broadcasting.connections.reverb.key') }}',
            REVERB_HOST: '{{ config('broadcasting.connections.reverb.options.host') }}',
            REVERB_PORT: {{ config('broadcasting.connections.reverb.options.port') ?? 8081 }},
            REVERB_SCHEME: '{{ config('broadcasting.connections.reverb.options.scheme') }}',
            REVERB_CLUSTER: 'mt1',
            DRAW_WS_URL: 'ws://localhost:8080',
            DEBUG_MODE: true
        };
        const testPlayerId = 1; // Use existing player ID
        window.playerId = 61;
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
        let directionPadTimer = null;
        const DIRECTION_STEP = 60;
        let drawWs = null;
        let drawWsReady = false;
        let drawWsReconnectTimer = null;
        const drawWsPending = new Map();
        const drawWsQueue = [];

        function initDrawItemsSocket() {
            if (!config.DRAW_WS_URL) return;
            if (drawWs && (drawWs.readyState === WebSocket.OPEN || drawWs.readyState === WebSocket.CONNECTING)) {
                return;
            }
            drawWs = new WebSocket(config.DRAW_WS_URL);

            drawWs.onopen = function () {
                drawWsReady = true;
                while (drawWsQueue.length > 0) {
                    const payload = drawWsQueue.shift();
                    drawWs.send(JSON.stringify(payload));
                }
            };

            drawWs.onmessage = function (event) {
                let response = null;
                try {
                    response = JSON.parse(event.data);
                } catch (error) {
                    console.error('Invalid draw WS response:', error);
                    return;
                }

                const requestId = response.request_id;
                if (!requestId || !drawWsPending.has(requestId)) {
                    return;
                }

                const pending = drawWsPending.get(requestId);
                drawWsPending.delete(requestId);
                pending.resolve(response);
            };

            drawWs.onclose = function () {
                drawWsReady = false;
                if (drawWsReconnectTimer) {
                    clearTimeout(drawWsReconnectTimer);
                }
                drawWsReconnectTimer = setTimeout(initDrawItemsSocket, 1000);
            };

            drawWs.onerror = function (error) {
                console.error('Draw WS error:', error);
            };
        }

        function fetchDrawItemsWs(requestId, playerIdFromEvent) {
            return new Promise((resolve, reject) => {
                const payload = {
                    action: 'get_draw_item',
                    request_id: requestId,
                    player_id: playerIdFromEvent,
                    session_id: sessionId
                };

                drawWsPending.set(requestId, { resolve, reject });

                if (drawWsReady && drawWs) {
                    drawWs.send(JSON.stringify(payload));
                } else {
                    drawWsQueue.push(payload);
                    initDrawItemsSocket();
                }

                setTimeout(() => {
                    if (drawWsPending.has(requestId)) {
                        drawWsPending.delete(requestId);
                        reject(new Error('draw_ws_timeout'));
                    }
                }, 10000);
            });
        }

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

        function moveMapByDirection(direction) {
            switch (direction) {
                case 'up':
                    globalPan.y += DIRECTION_STEP;
                    break;
                case 'down':
                    globalPan.y -= DIRECTION_STEP;
                    break;
                case 'left':
                    globalPan.x += DIRECTION_STEP;
                    break;
                case 'right':
                    globalPan.x -= DIRECTION_STEP;
                    break;
                default:
                    return;
            }
            applyGlobalPan();
        }

        function startDirectionHold(direction) {
            moveMapByDirection(direction);
            if (directionPadTimer) clearInterval(directionPadTimer);
            directionPadTimer = setInterval(() => {
                moveMapByDirection(direction);
            }, 120);
        }

        function stopDirectionHold() {
            if (directionPadTimer) {
                clearInterval(directionPadTimer);
                directionPadTimer = null;
            }
        }

        function status(msg) {
            console.log('Status:', msg);
            document.querySelector('.status-msg').textContent = msg;
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        function runBackendCode(code) {
            if (typeof code !== 'string' || code.trim() === '') return;
            try {
                (new Function('app', 'shapes', 'objects', 'AppData', code))(app, shapes, objects, AppData);
            } catch (error) {
                console.error('Error executing backend code item:', error);
            }
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

        function applyModalOpenState(modalUid) {
            const viewportUid = modalUid + '_content_viewport';
            const idsToAlwaysShow = [
                modalUid + '_body',
                modalUid + '_header',
                modalUid + '_title',
                modalUid + '_close_button',
                modalUid + '_close_text',
                viewportUid,
            ];

            idsToAlwaysShow.forEach((uid) => {
                if (shapes[uid]) shapes[uid].renderable = true;
                if (objects[uid] && objects[uid].attributes) {
                    objects[uid].attributes.renderable = true;
                }
            });

            const viewportObject = objects[viewportUid];
            if (viewportObject && viewportObject.attributes && Array.isArray(viewportObject.attributes.scroll_child_uids)) {
                const initialRenderables = (viewportObject.attributes.scroll_initial_renderables && typeof viewportObject.attributes.scroll_initial_renderables === 'object')
                    ? viewportObject.attributes.scroll_initial_renderables
                    : {};
                const childUids = viewportObject.attributes.scroll_child_uids;

                const isPanelUid = (uid) => typeof uid === 'string' && uid.indexOf('_container_panel') !== -1;
                const hasVisibleBaseContent = childUids.some((uid) => {
                    if (isPanelUid(uid)) return false;
                    return initialRenderables[uid] === undefined ? true : !!initialRenderables[uid];
                });

                childUids.forEach((uid) => {
                    let shouldShow = initialRenderables[uid] === undefined ? true : !!initialRenderables[uid];
                    // Fallback for objective redraw: if server rebuilt modal content while closed,
                    // all initial renderables may be false. When modal is open, keep base tree
                    // content visible and keep panel elements hidden until explicit click.
                    if (!hasVisibleBaseContent) {
                        shouldShow = !isPanelUid(uid);
                    }
                    if (shapes[uid]) shapes[uid].renderable = shouldShow;
                    if (objects[uid] && objects[uid].attributes) {
                        objects[uid].attributes.renderable = shouldShow;
                    }
                });
            }
        }

        function reapplyOpenModalsState() {
            const openModals = (window.AppData && window.AppData.open_modals) ? window.AppData.open_modals : {};
            Object.keys(openModals).forEach((modalUid) => {
                if (openModals[modalUid]) {
                    applyModalOpenState(modalUid);
                }
            });
            refreshAllModalViewportMasks();
        }

        window.reapplyOpenModalsState = reapplyOpenModalsState;

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
            status('PixiJS Avviato - Connessione a Reverb...');

            $(document).on('mousedown touchstart', '.map-dir-btn', function(e) {
                e.preventDefault();
                const direction = $(this).data('dir');
                startDirectionHold(direction);
            });

            $(document).on('mouseup mouseleave touchend touchcancel', '.map-dir-btn', function() {
                stopDirectionHold();
            });

            $(window).on('mouseup touchend touchcancel blur', function() {
                stopDirectionHold();
            });

            const channelName = 'player_' + testPlayerId + '_channel';
            if (config.DEBUG_MODE) {
                Pusher.logToConsole = true;
            }

            initDrawItemsSocket();

            const pusher = new Pusher(config.REVERB_APP_KEY, {
                cluster: config.REVERB_CLUSTER,
                wsHost: config.REVERB_HOST,
                wsPort: config.REVERB_PORT,
                wssPort: config.REVERB_PORT,
                forceTLS: config.REVERB_SCHEME === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true
            });

            pusher.connection.bind('connected', function() {
                status('Connesso a Reverb - Sottoscrizione canale...');
                console.log('Reverb connected');
            });

            pusher.connection.bind('disconnected', function() {
                status('Disconnesso da Reverb');
                console.log('Reverb disconnected');
            });

            pusher.connection.bind('error', function(error) {
                status('Errore connessione Reverb');
                console.error('Reverb connection error:', error);
            });

            const channel = pusher.subscribe(channelName);
            channel.bind('pusher:subscription_succeeded', function() {
                status('Connesso a Reverb - In attesa di eventi di disegno...');
                console.log('Test player channel subscribed:', channelName);
            });

            channel.bind('pusher:subscription_error', function(data) {
                status('Errore sottoscrizione canale: ' + (data?.error || 'unknown'));
                console.error('Subscription error:', data);
            });

            // Listen for draw_interface events
            channel.bind('draw_interface', function(data) {
                console.log('Draw interface event received:', data);
                status('Evento di disegno ricevuto...');

                const requestId = data && data.request_id ? data.request_id : null;
                const playerIdFromEvent = data && data.player_id ? data.player_id : null;
                if (!requestId || !playerIdFromEvent) {
                    console.warn('draw_interface missing request_id/player_id');
                    return;
                }

                fetchDrawItemsWs(requestId, playerIdFromEvent)
                    .then((result) => {
                        let items = result && result.items ? result.items : null;
                        if (!items) {
                            console.warn('draw_interface missing items payload');
                            return;
                        }
                        if (typeof items === 'string' || items instanceof String) {
                            try {
                                items = JSON.parse(items);
                            } catch (error) {
                                console.error('Error parsing draw items JSON:', error);
                                items = [];
                            }
                        }
                        if (!Array.isArray(items)) {
                            const maybeJson = (items !== null && items !== undefined) ? String(items).trim() : '';
                            if (maybeJson.startsWith('[') || maybeJson.startsWith('{')) {
                                try {
                                    items = JSON.parse(maybeJson);
                                } catch (error) {
                                    console.error('Error parsing draw items JSON:', error);
                                    items = [];
                                }
                            }
                        }
                        processItems(items);
                    })
                    .catch((error) => {
                        console.error('draw items ws error:', error);
                    });

                async function processItems(items) {
                    status('Disegno elementi...');

                    if (!Array.isArray(items)) {
                        console.warn('Draw items is not an array:', items);
                        items = [];
                    }
                    for (const item of items) {
                        if (!item || typeof item !== 'object') {
                            console.warn('Skipping invalid draw item:', item);
                            continue;
                        }
                        if (item['type'] === undefined || item['type'] === null) {
                            console.warn('Skipping draw item without type:', item);
                            continue;
                        }
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
                        } else if (itemType === 'code') {
                            if (item.sleep) await sleep(item.sleep);
                            runBackendCode(item.code);
                        }
                    }
                    refreshAllModalViewportMasks();
                    reapplyOpenModalsState();
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
