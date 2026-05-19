@extends('adminlte::page')

@section('title', 'Modifica Corpo Entity')

@section('content_header')@stop

@section('content')

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
                    @if($entityBody->isFinishDraw())
                    <li class="nav-item">
                        <a class="nav-link" id="tab-zones-link" data-toggle="pill" href="#tab-zones" role="tab" aria-controls="tab-zones" aria-selected="false">Zone</a>
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
                                        {{ $entityBody->isFinishDraw() ? 'disabled readonly' : '' }}
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
                        @if($entityBody->isFinishDraw())
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

                @if($entityBody->isFinishDraw())
                    <!-- TAB ZONE -->
                    <div class="tab-pane fade" id="tab-zones" role="tabpanel" aria-labelledby="tab-zones-link">
                        @include('entity_bodies._zones')
                    </div>
                @endif

                    </div>
                </div>
            </div>
            <div class="card-footer bg-light border-top">
                <div class="row">
                    @if(!$entityBody->isFinishDraw())
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

    function zoneColorFor(id) {
        return ZONE_COLORS[id % ZONE_COLORS.length];
    }

    var $canvas, $img, ctx, imgData;
    var currentPoints = [];
    var isDrawing     = false;
    var zoneName      = '';
    var selectedZoneId= null;
    var allZoneDots   = {};   // id -> [{x,y}, ...]   (punti salvati sul canvas)

    function getCsrf() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    function isDrawnPixel(cx, cy) {
        if (!imgData) return true; // Fallback di sicurezza se i dati dell'immagine non sono disponibili
        cx = Math.floor(cx); cy = Math.floor(cy);
        if (cx < 0 || cx >= imgData.width || cy < 0 || cy >= imgData.height) return false;
        return imgData.data[(cy * imgData.width + cx) * 4 + 3] > 0;
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
        if (!zones || zones.length === 0) {
            $('#zones-empty').show();
            return;
        }
        $('#zones-empty').hide();
        $('#zones-list-wrapper').show();
        $.each(zones, function (_, zone) {
            var color = zoneColorFor(zone.id);
            allZoneDots[zone.id] = $.map(zone.details, function (d) { return { x: d.x, y: d.y }; });

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
                    
                        $('<input>', {
                            type: 'text',
                            class: 'form-control form-control-sm border-0 bg-transparent px-0 py-0 font-weight-bold',
                            style: 'width:100%;',
                            value: zone.name
                        }).on('change', function () {
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
                        })
                    
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
        $.post('/entity-bodies/' + entityBodyId + '/zones',
            { name: zoneName.trim(), _token: getCsrf() },
            function (zone) {
                var color = zoneColorFor(zone.id);
                // Draw polygon on canvas immediately
                drawZonePolygon(zone.id, $.map(currentPoints, function(pt){ return {x:pt.x,y:pt.y}; }), color);
                var promises = [];
                $.each(currentPoints, function (_, pt) {
                    promises.push(
                        $.post('/entity-bodies/' + entityBodyId + '/zones/' + zone.id + '/add-detail',
                            { x: pt.x, y: pt.y, _token: getCsrf() })
                    );
                });
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
        $canvas.on('click', function () {
            if (isDrawing) return;
            var existingName = $('#new-zone-name').val().trim();
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

        $(document).on('click', '#zones-tbody tr', function (e) {
            if ($(e.target).is('input,button,a')) return;
            $('#zones-tbody tr').removeClass('selected');
            var $tr = $(this).addClass('selected');
            $('#btn-delete-selected-zone').prop('disabled', false);

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

        loadZones();
    });
})();
</script>
@endsection
