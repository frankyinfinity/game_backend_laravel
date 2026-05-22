@extends('adminlte::page')

@section('title', 'Modifica Corpo Entity')

@section('content_header')@stop

@section('content')

    @if($entityBody->isCompleted())
        <div class="alert alert-success shadow-sm mb-4" style="border-left: 4px solid #28a745 !important; background-color: #f3fdf6; color: #155724;">
            <div>
                <i class="fas fa-check-double mr-2 text-success"></i> Questo corpo è in stato <strong>Completato</strong>. Tutte le modifiche, la grafica, le zone e le ancore sono definitivamente bloccate.
            </div>
        </div>
    @elseif($entityBody->state == \App\Models\EntityBody::STATE_FINISH_ZONE)
        <div class="alert alert-info border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left: 4px solid #17a2b8 !important; background-color: #f3fafd; color: #117a8b;">
            <div>
                <i class="fas fa-lock mr-2 text-info"></i> Questo corpo è in stato <strong>Zone Terminate</strong>. La grafica e le zone sono bloccate. Configura le ancore.
            </div>
            <form action="{{ route('entity-bodies.toggle-state') }}" method="POST" class="m-0 js-confirm-complete">
                @csrf
                <input type="hidden" name="id" value="{{ $entityBody->id }}">
                <input type="hidden" name="state" value="{{ \App\Models\EntityBody::STATE_COMPLETED }}">
                <button type="submit" class="btn btn-primary btn-sm shadow-sm" onclick="return confirm('Sei sicuro? Questo bloccherà definitivamente le ancore.');">
                    <i class="fas fa-check-double"></i> Termina Ancore e Blocca
                </button>
            </form>
        </div>
    @elseif($entityBody->state == \App\Models\EntityBody::STATE_FINISH_DRAW)
        <div class="alert alert-info shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left: 4px solid #17a2b8 !important; background-color: #f3fafd; color: #117a8b;">
            <div>
                <i class="fas fa-pencil-ruler mr-2 text-info"></i> Questo corpo è in stato <strong>Disegno Terminato</strong>. La grafica è bloccata. Puoi configurare e manipolare le zone.
            </div>
            @if($entityBody->zones()->count() === 0)
                <button type="button" class="btn btn-info btn-sm shadow-sm text-white disabled" disabled data-toggle="tooltip" title="Crea almeno una zona per poter terminare la configurazione delle zone">
                    <i class="fas fa-lock mr-1"></i> Termina Zone e Blocca
                </button>
            @else
                <form action="{{ route('entity-bodies.toggle-state') }}" method="POST" class="js-confirm-complete-zone">
                    @csrf
                    <input type="hidden" name="id" value="{{ $entityBody->id }}">
                    <input type="hidden" name="state" value="{{ \App\Models\EntityBody::STATE_FINISH_ZONE }}">
                    <button type="submit" class="btn btn-info btn-sm shadow-sm text-white">
                        <i class="fas fa-lock mr-1"></i> Termina Zone e Blocca
                    </button>
                </form>
            @endif
        </div>
    @else
        <div class="alert alert-light border shadow-sm d-flex align-items-center justify-content-between mb-4" style="border-left: 4px solid #ffc107 !important;">
            <div>
                <i class="fas fa-info-circle mr-2 text-warning"></i> Questo corpo è in stato <strong>Creato</strong>. Puoi modificare il nome e disegnare la grafica 32x32.
            </div>
            @if(!$entityBody->image || !\Storage::disk('entity_bodies')->exists($entityBody->image))
                <button type="button" class="btn btn-success btn-sm shadow-sm disabled" disabled data-toggle="tooltip" title="Disegna la grafica per poter terminare il disegno del corpo">
                    <i class="fas fa-check-circle mr-1"></i> Termina Disegno e Blocca
                </button>
            @else
                <form action="{{ route('entity-bodies.toggle-state') }}" method="POST" class="js-confirm-complete-draw">
                    @csrf
                    <input type="hidden" name="id" value="{{ $entityBody->id }}">
                    <input type="hidden" name="state" value="{{ \App\Models\EntityBody::STATE_FINISH_DRAW }}">
                    <button type="submit" class="btn btn-success btn-sm shadow-sm">
                        <i class="fas fa-check-circle mr-1"></i> Termina Disegno e Blocca
                    </button>
                </form>
            @endif
        </div>
    @endif

    <form action="{{ route('entity-bodies.update', $entityBody) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs shadow-sm">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab" aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-graphics-link" data-toggle="pill" href="#tab-graphics" role="tab" aria-controls="tab-graphics" aria-selected="false">Grafica</a>
                    </li>
                    @if($entityBody->state >= 1)
                    <li class="nav-item">
                        <a class="nav-link" id="tab-zones-link" data-toggle="pill" href="#tab-zones" role="tab" aria-controls="tab-zones" aria-selected="false">Zone</a>
                    </li>
                    @endif
                    @if($entityBody->state >= 2)
                    <li class="nav-item">
                        <a class="nav-link" id="tab-ancore-link" data-toggle="pill" href="#tab-ancore" role="tab" aria-controls="tab-ancore" aria-selected="false">Ancore</a>
                    </li>
                    @endif
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    
                    <!-- TAB DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-link">
                        <div class="row">
                            <div class="form-group col-md-6 col-12">
                                <label for="name" class="text-dark font-weight-bold">Nome <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $entityBody->name) }}" 
                                        {{ $entityBody->state >= 1 ? 'disabled readonly' : '' }}
                                        required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @if($entityBody->state >= 1)
                            <div class="row">
                                <div class="col-md-6 col-12">
                                    <div class="card card-outline card-secondary shadow-sm text-center py-4">
                                        <div class="card-body">
                                            <h5 class="text-muted mb-3 font-weight-bold">Grafica Salvata (Sola Lettura)</h5>
                                            @if($entityBody->image && \Storage::disk('entity_bodies')->exists($entityBody->image))
                                                <img src="{{ asset('storage/entity_bodies/' . $entityBody->image) }}?v={{ time() }}" style="width: 128px; height: 128px; image-rendering: pixelated; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px;">
                                            @else
                                                <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width: 128px; height: 128px; border-style: dashed !important;">
                                                    <i class="fas fa-image fa-3x text-muted"></i>
                                                </div>
                                                <p class="text-muted mt-3 mb-0">Nessuna grafica disegnata.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            @include('shared.graphics_editor', ['modelType' => 'entity_body', 'model' => $entityBody, 'availableColors' => ['#000000']])
                    @endif
                        </div>

                @if($entityBody->state >= 1)
                    <!-- TAB ZONE -->
                    <div class="tab-pane fade" id="tab-zones" role="tabpanel" aria-labelledby="tab-zones-link">
                        @include('entity_bodies.zones')
                    </div>
                @endif

                @if($entityBody->state >= 2)
                    <!-- TAB ANCORE -->
                    <div class="tab-pane fade" id="tab-ancore" role="tabpanel" aria-labelledby="tab-ancore-link">
                        @include('shared.anchors_editor', ['modelType' => 'entity_body', 'model' => $entityBody, 'isLocked' => $entityBody->isCompleted()])
                    </div>
                @endif

                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-top">
                <div class="row">
                    @if($entityBody->state === 0)
                        <div class="col-md-3 col-sm-6 mb-2">
                            <button type="submit" class="btn btn-primary btn-block btn-sm shadow-sm" id="btn-save-all">
                                <i class="fa fa-save"></i> Aggiorna
                            </button>
                        </div>
                    @endif
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('entity-bodies.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
                            <i class="fa fa-backward"></i> Indietro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('js')
<script>
(function () {
    'use strict';
    var entityBodyId = {{ $entityBody->id }};
    var isZonesLocked = {{ $entityBody->state >= 2 ? 'true' : 'false' }};
    var currentPoints = [];
    var isDrawing     = false;
    var zoneName      = '';

    var $canvas, $img, ctx, imgData;
    var SCALE  = 8;

    // Palette 15 colori -> 15 zone
    var ZONE_COLORS = [
        '#e53935','#1e88e5','#43a047','#fb8c00','#8e24aa',
        '#00897b','#f4511e','#3949ab','#d4e157','#ff7043',
        '#00acc1','#7e57c2','#c0ca33','#ef5350','#26c6da'
    ];

    var allZoneColors = {};   // id -> hexColor string (colore salvato nel database)

    function zoneColorFor(id) {
        if (id === 0) return ZONE_COLORS[0];
        return allZoneColors[id] || ZONE_COLORS[id % ZONE_COLORS.length];
    }

    var $canvas, $img, ctx, imgData;
    var currentPoints = [];
    var isDrawing     = false;
    var zoneName      = '';
    var selectedZoneId= null;
    var allZoneDots   = {};   // id -> [{x,y}, ...]   (punti salvati sul canvas)
    var allZonePixels = {};   // id -> [{x,y}, ...]   (pixel neri all'interno del poligono)
    var pixelBrushMode = null; // 'add', 'remove', o null

    function getCsrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function isPointInPolygon(x, y, polygon) {
        var inside = false;
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var xi = polygon[i].x, yi = polygon[i].y;
            var xj = polygon[j].x, yj = polygon[j].y;
            
            var intersect = ((yi > y) != (yj > y))
                && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
            if (intersect) inside = !inside;
        }
        return inside;
    }

    function getDistanceToSegment(x, y, x1, y1, x2, y2) {
        var A = x - x1;
        var B = y - y1;
        var C = x2 - x1;
        var D = y2 - y1;

        var dot = A * C + B * D;
        var lenSq = C * C + D * D;
        var param = -1;
        if (lenSq !== 0) param = dot / lenSq;

        var xx, yy;
        if (param < 0) {
            xx = x1;
            yy = y1;
        } else if (param > 1) {
            xx = x2;
            yy = y2;
        } else {
            xx = x1 + param * C;
            yy = y1 + param * D;
        }

        var dx = x - xx;
        var dy = y - yy;
        return Math.sqrt(dx * dx + dy * dy);
    }

    function isPointCloseToPolygon(x, y, polygon) {
        // 1. Check if strictly inside
        if (isPointInPolygon(x, y, polygon)) return true;

        // 2. Check if exactly on a vertex
        for (var i = 0; i < polygon.length; i++) {
            if (polygon[i].x === x && polygon[i].y === y) return true;
        }

        // 3. Check distance to each segment (threshold <= 1.0)
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var dist = getDistanceToSegment(x, y, polygon[i].x, polygon[i].y, polygon[j].x, polygon[j].y);
            if (dist <= 1.0) return true;
        }

        return false;
    }

    function isPixelOccupied(gx, gy) {
        var occupied = false;
        $.each(allZonePixels, function (zid) {
            $.each(allZonePixels[zid], function (_, pt) {
                if (pt.x === gx && pt.y === gy) {
                    occupied = true;
                    return false;
                }
            });
            if (occupied) return false;
        });
        return occupied;
    }

    function findBlackPixelsInPolygon(polygon) {
        var blackPixels = [];
        if (!imgData) return blackPixels;
        
        for (var gy = 0; gy < 64; gy++) {
            for (var gx = 0; gx < 64; gx++) {
                if (isPointCloseToPolygon(gx, gy, polygon)) {
                    if (isPixelOccupied(gx, gy)) continue;
                    var cx = Math.floor(gx * SCALE + SCALE / 2);
                    var cy = Math.floor(gy * SCALE + SCALE / 2);
                    
                    if (cx >= 0 && cx < imgData.width && cy >= 0 && cy < imgData.height) {
                        var idx = (cy * imgData.width + cx) * 4;
                        var r = imgData.data[idx];
                        var g = imgData.data[idx+1];
                        var b = imgData.data[idx+2];
                        var a = imgData.data[idx+3];
                        
                        // Check if pure black pixel
                        if (r === 0 && g === 0 && b === 0 && a > 0) {
                            blackPixels.push({ x: gx, y: gy });
                        }
                    }
                }
            }
        }
        return blackPixels;
    }

    function isDrawnPixel(cx, cy) {
        if (!imgData) return false; // Non consentire click se i dati dell'immagine non sono caricati
        cx = Math.floor(cx); cy = Math.floor(cy);
        if (cx < 0 || cx >= imgData.width || cy < 0 || cy >= imgData.height) return false;
        
        var pt = canvasToImg(cx, cy);
        if (isPixelOccupied(pt.x, pt.y)) return false;

        var idx = (cy * imgData.width + cx) * 4;
        return imgData.data[idx] === 0 && imgData.data[idx+1] === 0 && imgData.data[idx+2] === 0 && imgData.data[idx+3] > 0;
    }

    function canvasToImg(cx, cy) {
        return { x: Math.floor(cx / SCALE), y: Math.floor(cy / SCALE) };
    }

    function imgToCanvas(ix, iy) {
        return { x: ix * SCALE, y: iy * SCALE };
    }

    function clearCanvas() {
        ctx.clearRect(0, 0, $canvas.width(), $canvas.height());
    }

    function drawPreview() {
        clearCanvas();
        redrawZoneDots();
        if (currentPoints.length === 0) return;
        ctx.strokeStyle = '#0056e0';
        ctx.lineWidth   = 3;
        ctx.lineJoin    = 'round';
        ctx.setLineDash([]);
        ctx.beginPath();
        $.each(currentPoints, function (i, pt) {
            var p = imgToCanvas(pt.x, pt.y);
            if (i === 0) ctx.moveTo(p.x + SCALE/2, p.y + SCALE/2);
            else         ctx.lineTo(p.x + SCALE/2, p.y + SCALE/2);
        });
        ctx.stroke();

        // dots
        $.each(currentPoints, function (i, pt) {
            var p = imgToCanvas(pt.x, pt.y);
            ctx.beginPath();
            ctx.arc(p.x + SCALE/2, p.y + SCALE/2, SCALE*0.45, 0, 2*Math.PI);
            ctx.fillStyle   = '#ff3d00';
            ctx.lineWidth   = 2;
            ctx.strokeStyle = '#ffffff';
            ctx.stroke();
            ctx.fill();
        });
    }

    // Punti di UNA zona salvata: pallini colorati sul canvas
    function drawZoneDots(zoneId) {
        var dots  = allZoneDots[zoneId];
        var color = zoneColorFor(zoneId);
        if (!dots) return;

        $.each(dots, function (_, pt) {
            var p = imgToCanvas(pt.x, pt.y);
            // cerchio pieno piccolo
            ctx.beginPath();
            ctx.arc(p.x + SCALE/2, p.y + SCALE/2, SCALE*0.35, 0, 2*Math.PI);
            ctx.fillStyle   = color;
            ctx.lineWidth   = 0;
            ctx.fill();
        });
    }

    // Poligono completo di una zona: contorno + puntini
    function drawZonePolygon(zoneId, dots, color, lineW, dotR) {
        if (!dots || dots.length < 2) return;
        lineW = lineW || 2;
        dotR  = dotR  || SCALE*0.38;

        ctx.beginPath();
        $.each(dots, function (i, pt) {
            var p = imgToCanvas(pt.x, pt.y);
            if (i === 0) ctx.moveTo(p.x + SCALE/2, p.y + SCALE/2);
            else         ctx.lineTo(p.x + SCALE/2, p.y + SCALE/2);
        });
        ctx.closePath();
        ctx.strokeStyle = color;
        ctx.lineWidth   = lineW;
        ctx.setLineDash([]);
        ctx.stroke();

        $.each(dots, function (_, pt) {
            var p = imgToCanvas(pt.x, pt.y);
            ctx.beginPath();
            ctx.arc(p.x + SCALE/2, p.y + SCALE/2, dotR, 0, 2*Math.PI);
            ctx.fillStyle   = color;
            ctx.lineWidth   = 1.5;
            ctx.strokeStyle = '#fff';
            ctx.stroke();
            ctx.fill();
        });
    }

    // Ridisegna TUTTI i punti salvati colorati
    function redrawZoneDots() {
        // Ridisegna tutti i pixel neri presi in considerazione in grigio semitrasparente
        $.each(allZonePixels, function (zid) {
            var baseTone = 110;
            var offset = (parseInt(zid) * 23) % 60 - 30; // varia di +/- 30
            var tone = baseTone + offset;
            ctx.fillStyle = 'rgba(' + tone + ', ' + tone + ', ' + tone + ', 0.7)';

            $.each(allZonePixels[zid], function (_, pt) {
                var rx = Math.floor(pt.x / 2) * 2 * SCALE;
                var ry = Math.floor(pt.y / 2) * 2 * SCALE;
                ctx.fillRect(rx, ry, SCALE * 2, SCALE * 2);
            });
        });

        // cancellare solo le zone salvate (non il disegno corrente)
        $.each(allZoneDots, function (zid) {
            var color = zoneColorFor(zid);
            $.each(allZoneDots[zid], function (_, pt) {
                var p = imgToCanvas(pt.x, pt.y);
                ctx.beginPath();
                ctx.arc(p.x + SCALE/2, p.y + SCALE/2, SCALE*0.38, 0, 2*Math.PI);
                ctx.fillStyle   = color;
                ctx.lineWidth   = 1.5;
                ctx.strokeStyle = '#fff';
                ctx.stroke();
                ctx.fill();
            });
        });
    }

    // Ridisegna tutto: layer sfondo + zone selezionata + disegno corrente
    function redrawAll() {
        clearCanvas();
        redrawZoneDots();
    }

    function resetDrawing() {
        currentPoints = [];
        isDrawing     = false;
        zoneName      = '';
        selectedZoneId= null;
        pixelBrushMode = null;
        
        // Reset brush buttons
        $('#btn-brush-add')
            .prop('disabled', true)
            .removeClass('btn-success')
            .addClass('btn-outline-success');
        $('#btn-brush-remove')
            .prop('disabled', true)
            .removeClass('btn-danger')
            .addClass('btn-outline-danger');

        $('#new-zone-name').val('');
        $('#editor-zone-name').text('nessuna').css('color', '');
        // Rimosso riferimento a pulsante salva
        $('#btn-cancel-polygon').hide();
        $('#btn-delete-selected-zone').prop('disabled', true);
        clearCanvas();
        redrawZoneDots();
    }

    function extractImageData() {
        var doExtract = function() {
            try {
                var tempCanvas = document.createElement('canvas');
                tempCanvas.width = 512;
                tempCanvas.height = 512;
                var tempCtx = tempCanvas.getContext('2d');
                tempCtx.imageSmoothingEnabled = false;
                tempCtx.mozImageSmoothingEnabled = false;
                tempCtx.webkitImageSmoothingEnabled = false;
                tempCtx.msImageSmoothingEnabled = false;
                tempCtx.drawImage($img[0], 0, 0, 512, 512);
                imgData = tempCtx.getImageData(0, 0, 512, 512);
            } catch(err) {
                console.error("Errore estrazione dati immagine:", err);
                imgData = null;
            }
        };

        if ($img[0] && $img[0].complete && $img[0].naturalWidth !== 0) {
            doExtract();
        } else if ($img[0]) {
            $img.on('load', doExtract);
        }
        /*
            ctx.drawImage($img[0], 0, 0, $canvas.width(), $canvas.height());
            imgData = ctx.getImageData(0, 0, $canvas.width(), $canvas.height());
        */
    }

    function loadZones() {
        resetDrawing();
        $('#zones-loading').show();
        $('#zones-list-wrapper').hide();
        $('#zones-empty').hide();
        $.get('/entity-bodies/' + entityBodyId + '/zones', function (data) {
            renderZones(data);
        }).fail(function () { alert('Errore durante il caricamento delle zone.'); })
          .always(function () { $('#zones-loading').hide(); });
    }

    function renderZones(zones) {
        var tbody = $('#zones-tbody');
        tbody.empty();
        allZoneDots = {};
        allZonePixels = {};
        clearCanvas();
        if (!zones || zones.length === 0) {
            $('#zones-empty').show();
            return;
        }
        $('#zones-empty').hide();
        $('#zones-list-wrapper').show();
        $.each(zones, function (_, zone) {
            allZoneColors[zone.id] = zone.color || ZONE_COLORS[zone.id % ZONE_COLORS.length];
            var color = zoneColorFor(zone.id);
            allZoneDots[zone.id] = $.map(zone.details, function (d) { return { x: d.x, y: d.y }; });
            allZonePixels[zone.id] = $.map(zone.pixels || [], function (px) { return { x: px.x, y: px.y }; });

            var dotHtml = function (dots) { return dots ? dots.length : 0; };
            /*
                if (!dots || !dots.length) return '<span class="text-muted">0</span>';
                var html = '<span class="d-inline-flex flex-wrap gap-1" style="max-width:320px;">';
                $.each(dots, function (_, d) {
                    html += '<span class="dot-preview" style="background:' + color + '; width:8px; height:8px; border-radius:50%; display:inline-block; margin:1px;" title="(' + d.x + ',' + d.y + ')"></span>';
                });
                return html + '</span>';
            */

            var tr = $('<tr>').attr('data-zone-id', zone.id).append(
                $('<td>').addClass('text-center').append(
                    $('<span>').css({'color': color, 'font-size': '1.2rem', 'line-height': '1'}).html('●')
                ),
                $('<td>').append(
                    
                        (function() {
                            var inp = $('<input>', {
                                type: 'text',
                                class: 'form-control form-control-sm border-0 bg-transparent px-0 py-0 font-weight-bold',
                                style: 'width:100%;',
                                value: zone.name
                            });
                            if (isZonesLocked) {
                                inp.prop('disabled', true).prop('readonly', true);
                            } else {
                                inp.on('change', function () {
                                    var nn = $(this).val().trim();
                                    if (!nn) { loadZones(); return; }
                                    $.ajax({
                                        url: '/entity-bodies/' + entityBodyId + '/zones/' + zone.id,
                                        type: 'PUT',
                                        data: { name: nn },
                                        headers: { 'X-CSRF-TOKEN': getCsrf() },
                                        success: function () { loadZones(); },
                                        error: function () { alert("Errore."); loadZones(); }
                                    });
                                });
                            }
                            return inp;
                        })()
                    
                ),
                $('<td>').addClass('text-center').append(dotHtml(zone.details))
            );
            tbody.append(tr);
        });
        // draw all saved zone dots on canvas
        redrawZoneDots();
    }

    function saveCurrentZone() {
        if (!zoneName.trim()) { alert('Inserisci un nome.'); return; }
        if (currentPoints.length < 3) { alert('Disegna almeno 3 punti.'); return; }
        
        var nextColor = ZONE_COLORS[Object.keys(allZoneDots).length % ZONE_COLORS.length];
        
        $.post('/entity-bodies/' + entityBodyId + '/zones',
            { name: zoneName.trim(), color: nextColor, _token: getCsrf() },
            function (zone) {
                allZoneColors[zone.id] = zone.color || nextColor;
                var color = zoneColorFor(zone.id);
                // Draw polygon on canvas immediately
                drawZonePolygon(zone.id, $.map(currentPoints, function(pt){ return {x:pt.x,y:pt.y}; }), color);
                var blackPixels = findBlackPixelsInPolygon(currentPoints);
                var promises = [];
                $.each(currentPoints, function (_, pt) {
                    promises.push(
                        $.post('/entity-bodies/' + entityBodyId + '/zones/' + zone.id + '/add-detail',
                            { x: pt.x, y: pt.y, _token: getCsrf() })
                    );
                });
                promises.push(
                    $.ajax({
                        url: '/entity-bodies/' + entityBodyId + '/zones/' + zone.id + '/save-pixels',
                        type: 'POST',
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': getCsrf()
                        },
                        data: JSON.stringify({ pixels: blackPixels })
                    })
                );
                $.when.apply($, promises).done(function () {
                    zoneName = '';
                    $('#new-zone-name').val('');
                    resetDrawing();
                    loadZones();
                });
            }
        ).fail(function () { alert('Errore durante il salvataggio.'); });
    }

    $(function () {
        $canvas = $('#zone-canvas');
        $img     = $('#body-image');
        ctx      = $canvas[0].getContext('2d');
        extractImageData();

        // 1-click on image → modal to name zone
        $canvas.on('click', function (e) {
            if (isDrawing) return;

            var rect = $canvas[0].getBoundingClientRect();
            var cx   = (e.clientX - rect.left) * ($canvas.width() / rect.width);
            var cy   = (e.clientY - rect.top)  * ($canvas.height() / rect.height);
            var pt   = canvasToImg(cx, cy);

            if (isPixelOccupied(pt.x, pt.y)) {
                var foundZoneId = null;
                $.each(allZonePixels, function (zid) {
                    $.each(allZonePixels[zid], function (_, px) {
                        if (px.x === pt.x && px.y === pt.y) {
                            foundZoneId = zid;
                            return false;
                        }
                    });
                    if (foundZoneId) return false;
                });

                if (foundZoneId) {
                    if (foundZoneId == selectedZoneId) {
                        if (pixelBrushMode === 'remove' && !isZonesLocked) {
                            // Rimuovi pixel dalla zona selezionata
                            allZonePixels[selectedZoneId] = $.grep(allZonePixels[selectedZoneId], function (px) {
                                return !(px.x === pt.x && px.y === pt.y);
                            });
                            $.ajax({
                                url: '/entity-bodies/' + entityBodyId + '/zones/' + selectedZoneId + '/save-pixels',
                                type: 'POST',
                                contentType: 'application/json',
                                headers: {
                                    'X-CSRF-TOKEN': getCsrf()
                                },
                                data: JSON.stringify({ pixels: allZonePixels[selectedZoneId] }),
                                success: function () {
                                    clearCanvas();
                                    redrawZoneDots();
                                    var dots = allZoneDots[selectedZoneId];
                                    if (dots) {
                                        drawZonePolygon(selectedZoneId, dots, zoneColorFor(selectedZoneId), 3, SCALE * 0.45);
                                    }
                                }
                            });
                        }
                    } else {
                        var tr = $('#zones-tbody tr[data-zone-id="' + foundZoneId + '"]');
                        if (tr.length) {
                            tr.trigger('click');
                        }
                    }
                }
                return;
            }

            if (isZonesLocked) return;

            // Se c'è una zona selezionata e il pixel cliccato è libero, controlla se è nero puro per aggiungerlo
            if (selectedZoneId !== null) {
                if (pixelBrushMode === 'add') {
                    var idx = (Math.floor(cy) * imgData.width + Math.floor(cx)) * 4;
                    var isBlack = (imgData.data[idx] === 0 && imgData.data[idx+1] === 0 && imgData.data[idx+2] === 0 && imgData.data[idx+3] > 0);
                    if (isBlack) {
                        if (!allZonePixels[selectedZoneId]) allZonePixels[selectedZoneId] = [];
                        allZonePixels[selectedZoneId].push({ x: pt.x, y: pt.y });
                        $.ajax({
                            url: '/entity-bodies/' + entityBodyId + '/zones/' + selectedZoneId + '/save-pixels',
                            type: 'POST',
                            contentType: 'application/json',
                            headers: {
                                'X-CSRF-TOKEN': getCsrf()
                            },
                            data: JSON.stringify({ pixels: allZonePixels[selectedZoneId] }),
                            success: function () {
                                clearCanvas();
                                redrawZoneDots();
                                var dots = allZoneDots[selectedZoneId];
                                if (dots) {
                                    drawZonePolygon(selectedZoneId, dots, zoneColorFor(selectedZoneId), 3, SCALE * 0.45);
                                }
                            }
                        });
                    }
                }
                return;
            }

            var existingName = $('#new-zone-name').length ? $('#new-zone-name').val().trim() : '';
            if (existingName) {
                zoneName = existingName;
                $('#editor-zone-name').text(existingName).css('color', zoneColorFor(0));
                isDrawing = true;
                $('#btn-cancel-polygon').show();
                return;
            }
            $('#zoneNameModal').modal('show');
            $('#zone-name-input').val('').focus();
        });

        // Confirm name → start drawing
        $('#btn-confirm-zone-name').on('click', function () {
            var n = $('#zone-name-input').val().trim();
            if (!n) { alert('Inserisci un nome.'); return; }
            zoneName = n;
            $('#new-zone-name').val(n);
            $('#editor-zone-name').text(n).css('color', zoneColorFor(0));
            $('#zoneNameModal').modal('hide');
            isDrawing = true;
            $('#btn-cancel-polygon').show();
        });

        // Ascolto input diretto su Nuova Zona per attivare la modalità disegno senza modale obbligatoria
        $('#new-zone-name').on('input keyup', function () {
            var n = $(this).val().trim();
            if (n) {
                zoneName = n;
                $('#editor-zone-name').text(n).css('color', zoneColorFor(0));
                isDrawing = true;
                $('#btn-cancel-polygon').show();
            } else {
                if (currentPoints.length === 0) {
                    resetDrawing();
                }
            }
        });

        // Enter in modal
        $('#zone-name-input').on('keyup', function (e) {
            if (e.key === 'Enter') $('#btn-confirm-zone-name').trigger('click');
        });

        // Modal dismissed without confirm → reset
        $('#zoneNameModal').on('hidden.bs.modal', function () {
            if (!isDrawing) resetDrawing();
        });

        // Drawing: click pixel → add vertex
        $canvas.on('mousedown', function (e) {
            if (!isDrawing) return;
            e.stopImmediatePropagation();
            var rect = $canvas[0].getBoundingClientRect();
            var cx   = (e.clientX - rect.left) * ($canvas.width() / rect.width);
            var cy   = (e.clientY - rect.top)  * ($canvas.height() / rect.height);
            if (!isDrawnPixel(cx, cy)) return;
            var pt = canvasToImg(cx, cy);
            if (currentPoints.length > 0) {
                var last = currentPoints[currentPoints.length - 1];
                if (last.x === pt.x && last.y === pt.y) return;
            }
            if (currentPoints.length >= 3) {
                var first = imgToCanvas(currentPoints[0].x, currentPoints[0].y);
                var dist  = Math.sqrt(Math.pow(cx - first.x - SCALE/2, 2) + Math.pow(cy - first.y - SCALE/2, 2));
                if (dist <= SCALE * 1.5) {
                    isDrawing = false;
                    saveCurrentZone();
                    drawPreview();
                    return;
                }
            }
            currentPoints.push(pt);
            // Rimosso riferimento pulsante
            drawPreview();
        });

        // Rimosso handler click pulsante
        $('#btn-cancel-polygon').on('click', resetDrawing);

        $('#btn-delete-selected-zone').on('click', function () {
            var row = $('#zones-tbody tr.selected');
            if (!row.length) return;
            if (!confirm('Eliminare la zona?')) return;
            $.ajax({
                url: '/entity-bodies/' + entityBodyId + '/zones/' + row.data('zone-id'),
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': getCsrf() },
                success: loadZones,
                error: function () { alert('Errore durante eliminazione.'); }
            });
        });

        $('#btn-brush-add').on('click', function () {
            pixelBrushMode = 'add';
            $(this).removeClass('btn-outline-success').addClass('btn-success');
            $('#btn-brush-remove').removeClass('btn-danger').addClass('btn-outline-danger');
        });

        $('#btn-brush-remove').on('click', function () {
            pixelBrushMode = 'remove';
            $(this).removeClass('btn-outline-danger').addClass('btn-danger');
            $('#btn-brush-add').removeClass('btn-success').addClass('btn-outline-success');
        });

        $(document).on('click', '#zones-tbody tr', function (e) {
            if ($(e.target).is('input,button,a')) return;
            $('#zones-tbody tr').removeClass('selected');
            var $tr = $(this).addClass('selected');
            if (!isZonesLocked) {
                $('#btn-delete-selected-zone').prop('disabled', false);

                // Attiva automaticamente la modalità di aggiunta pixel per impostazione predefinita
                pixelBrushMode = 'add';
                $('#btn-brush-add')
                    .prop('disabled', false)
                    .removeClass('btn-outline-success')
                    .addClass('btn-success');
                $('#btn-brush-remove')
                    .prop('disabled', false)
                    .removeClass('btn-danger')
                    .addClass('btn-outline-danger');
            }

            // Update "Zona selezionata" label
            var zid    = $tr.data('zone-id');
            var zname  = $tr.find('input').val().trim();
            selectedZoneId = zid;
            $('#editor-zone-name').text(zname || ('#' + zid)).css('color', zoneColorFor(zid));

            // Redraw: sfondo + tutti i punti + poligono selezionato
            clearCanvas();
            redrawZoneDots();
            var dots = allZoneDots[zid];
            if (dots) {
                drawZonePolygon(zid, dots, zoneColorFor(zid), 3, SCALE * 0.45);
            }
        });

        $(document).on('submit', '.js-confirm-complete-draw', function(e) {
            if(!confirm('Sei sicuro di voler terminare il disegno di questo corpo? Questa azione bloccherà la grafica.')) {
                e.preventDefault();
            }
        });

        $(document).on('submit', '.js-confirm-complete-zone', function(e) {
            if(!confirm('Sei sicuro di voler terminare la configurazione delle zone? Questa azione bloccherà le modifiche alle zone.')) {
                e.preventDefault();
            }
        });

        loadZones();
    });
})();
</script>
@endsection
