<div class="row">
    <div class="col-md-9">
        <div class="text-center">
            <div class="d-flex justify-content-end mb-1">
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-secondary" id="sp_zoom_out" title="Riduci zoom"><i class="fas fa-search-minus"></i></button>
                    <button type="button" class="btn btn-secondary" id="sp_zoom_reset" title="Zoom 1x"><i class="fas fa-expand"></i></button>
                    <button type="button" class="btn btn-secondary" id="sp_zoom_in" title="Aumenta zoom"><i class="fas fa-search-plus"></i></button>
                </div>
            </div>
            <div id="sp_graph">
                <svg viewBox="0 0 900 640" style="width:100%;background:#0a0a14;border-radius:8px">
                    <circle cx="450" cy="320" r="50" fill="rgb({{$star->rgb}})" stroke="#fff" stroke-width="1"><title>{{$star->name}}</title></circle>
                </svg>
            </div>
            <small class="text-muted"><i class="fas fa-mouse-pointer"></i> Clicca dentro una fascia per scegliere l'orbita del nuovo pianeta. Rotellina o pulsanti per lo zoom.</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="modal fade" id="sp_planet_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title"><i class="fa fa-globe"></i> Nuovo pianeta in orbita</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <small class="text-muted">Orbita selezionata</small><br>
                            <strong id="sp_pending_zone"></strong>
                        </div>
                        <div class="form-group mb-2">
                            <label for="sp_planet_name" class="mb-1">Nome pianeta*</label>
                            <input type="text" id="sp_planet_name" class="form-control" placeholder="Nome del nuovo pianeta">
                        </div>
                        <div class="form-group mb-0">
                            <label for="sp_planet_desc" class="mb-1">Descrizione</label>
                            <textarea id="sp_planet_desc" class="form-control" rows="4" placeholder="Descrizione (opzionale)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal"><i class="fa fa-times"></i> Annulla</button>
                        <button type="button" class="btn btn-warning btn-sm" id="sp_save"><i class="fa fa-plus"></i> Crea e posiziona</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="sp_list"></div>
        <div id="sp_empty" class="text-muted text-center border rounded py-3" style="display:none">
            <i class="fas fa-globe"></i> Nessun pianeta in orbita.<br>
            <small>Clicca sul grafico dentro una fascia per posizionarne uno.</small>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        var SP_CX = 450, SP_CY = 320, SP_MAXR = 300, SP_STAR_R = 50, SP_PLANET_R = 10;
        var zoom = 1;
        var starRgb = @json($star->rgb);
        var starName = @json($star->name);
        var zones = @json($zones);
        var storeUrl = '{{ route('stars.zone-planets.store', [$star->id]) }}';
        var destroyUrl = '{{ route('stars.zone-planets.destroy', [$star->id, '__ID__']) }}';
        var planetShowUrl = '{{ route('planets.show', ['__ID__']) }}';
        var pending = null;

        var palette = ['88,166,255', '255,138,101', '186,104,200', '77,208,225', '255,213,79', '161,136,127', '129,199,132'];

        function esc(s) {
            return $('<div/>').text(String(s)).html();
        }

        function maxTo() {
            var m = 0;
            zones.forEach(function (z) { if (z.to > m) m = z.to; });
            return m;
        }

        function scale() {
            var m = maxTo();
            return m > 0 ? SP_MAXR / m : 0;
        }

        // viewBox corrente in base allo zoom (centrato sulla stella)
        function vbString() {
            var w = 900 / zoom, h = 640 / zoom;
            return (SP_CX - w / 2) + ' ' + (SP_CY - h / 2) + ' ' + w + ' ' + h;
        }

        function allZonePlanets() {
            var list = [];
            zones.forEach(function (z) {
                (z.zone_planets || []).forEach(function (zp) {
                    list.push({zone: z, zp: zp});
                });
            });
            return list;
        }

        function draw() {
            var s = scale();

            var svg = '<svg viewBox="' + vbString() + '" style="width:100%;background:#0a0a14;border-radius:8px;cursor:crosshair">';

            // Glow stella
            svg += '<defs><filter id="sp_glow" x="-80%" y="-80%" width="260%" height="260%">' +
                '<feGaussianBlur stdDeviation="10" result="b"/>' +
                '<feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>';

            // Fasce (anelli, dal piu' esterno al piu' interno)
            zones.slice().sort(function (a, b) { return b.from - a.from; }).forEach(function (z) {
                var mid = (z.from + z.to) / 2 * s;
                var w = Math.max(1, (z.to - z.from) * s);
                svg += '<circle cx="' + SP_CX + '" cy="' + SP_CY + '" r="' + mid + '" fill="none" stroke="rgb(' + z.rgb + ')" stroke-width="' + w + '" opacity="0.85">' +
                    '<title>' + esc(z.name) + ' (' + z.from + '-' + z.to + ')</title></circle>';
            });

            // Label fasce
            zones.slice().sort(function (a, b) { return a.from - b.from; }).forEach(function (z) {
                var y = SP_CY + (z.to * s) + 14;
                svg += '<text x="' + SP_CX + '" y="' + y + '" text-anchor="middle" fill="rgba(255,255,255,0.65)" font-size="11">' + esc(z.name) + '</text>';
            });

            // Orbite + pianeti
            var colorIdx = 0;
            zones.slice().sort(function (a, b) { return a.from - b.from; }).forEach(function (z) {
                var planets = z.zone_planets || [];
                planets.forEach(function (zp, i) {
                    var rr = zp.radius * s;
                    // Orbita tratteggiata
                    svg += '<circle cx="' + SP_CX + '" cy="' + SP_CY + '" r="' + rr + '" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="1" stroke-dasharray="3 4"/>';
                    // Pianeta distribuito sull'orbita
                    var angle = (Math.PI * 2 * i / planets.length) + (z.id * 0.7);
                    var px = SP_CX + rr * Math.cos(angle);
                    var py = SP_CY + rr * Math.sin(angle);
                    var col = palette[colorIdx % palette.length];
                    var pname = (zp.planet && zp.planet.name) ? zp.planet.name : ('Pianeta #' + zp.planet_id);
                    svg += '<circle cx="' + px + '" cy="' + py + '" r="' + SP_PLANET_R + '" fill="rgb(' + col + ')" stroke="#fff" stroke-width="1.5">' +
                        '<title>' + esc(pname) + ' - ' + esc(z.name) + ' (r=' + zp.radius + ')</title></circle>';
                    colorIdx++;
                });
            });

            // Orbita in fase di scelta (click)
            if (pending) {
                var pr = pending.radius * s;
                svg += '<circle cx="' + SP_CX + '" cy="' + SP_CY + '" r="' + pr + '" fill="none" stroke="rgb(255,213,79)" stroke-width="2" stroke-dasharray="6 4"/>';
                svg += '<circle cx="' + (SP_CX + pr) + '" cy="' + SP_CY + '" r="' + SP_PLANET_R + '" fill="rgb(255,213,79)" stroke="#fff" stroke-width="1.5"/>';
            }

            // Stella al centro con glow
            svg += '<circle cx="' + SP_CX + '" cy="' + SP_CY + '" r="' + SP_STAR_R + '" fill="rgb(' + starRgb + ')" stroke="#fff" stroke-width="1" filter="url(#sp_glow)">' +
                '<title>' + esc(starName) + '</title></circle>';

            svg += '</svg>';
            $('#sp_graph').html(svg);
        }

        function drawList() {
            var items = allZonePlanets();
            $('#sp_empty').toggle(items.length === 0);

            var html = '';
            items.forEach(function (it, i) {
                var col = palette[i % palette.length];
                var pname = (it.zp.planet && it.zp.planet.name) ? it.zp.planet.name : ('Pianeta #' + it.zp.planet_id);
                html += '<div class="d-flex align-items-center border rounded px-2 py-1 mb-1">' +
                    '<span style="flex:0 0 auto;width:18px;height:18px;border-radius:50%;border:1px solid #999;background:rgb(' + col + ')"></span>' +
                    '<span class="ml-2 flex-grow-1"><strong>' + esc(pname) + '</strong> <small class="text-muted">' + esc(it.zone.name) + ' · raggio ' + it.zp.radius + '</small></span>' +
                    '<a href="' + planetShowUrl.replace('__ID__', it.zp.planet_id) + '" class="btn btn-info btn-xs mr-1" title="Modifica pianeta"><i class="fa fa-edit"></i></a>' +
                    '<button type="button" class="btn btn-danger btn-xs sp_del" data-id="' + it.zp.id + '" title="Rimuovi pianeta"><i class="fa fa-trash"></i></button>' +
                    '</div>';
            });
            $('#sp_list').html(html);
        }

        function refresh() {
            draw();
            drawList();
        }

        function clearPending() {
            pending = null;
            $('#sp_planet_modal').modal('hide');
            draw();
        }

        // Click sul grafico: calcola il raggio e individua la fascia
        $(document).on('click', '#sp_graph svg', function (e) {
            var svg = this;
            var s = scale();
            if (s <= 0) return;

            var rect = svg.getBoundingClientRect();
            var vb = svg.viewBox.baseVal;
            var x = (e.clientX - rect.left) * (vb.width / rect.width);
            var y = (e.clientY - rect.top) * (vb.height / rect.height);
            var r = Math.round(Math.hypot(x - SP_CX, y - SP_CY) / s);

            var z = zones.find(function (zz) { return r >= zz.from && r <= zz.to; });
            if (!z) {
                clearPending();
                return;
            }

            pending = {zoneId: z.id, radius: r};
            $('#sp_pending_zone').text(z.name + ' · raggio ' + r);
            $('#sp_planet_modal').modal('show');
            draw();
        });

        // Chiusura modal (X o Annulla): annulla l'orbita in scelta
        $('#sp_planet_modal').on('hidden.bs.modal', function () {
            pending = null;
            draw();
        });

        $('#sp_save').on('click', function () {
            if (!pending) return;
            $.ajax({
                url: storeUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    star_zone_id: pending.zoneId,
                    name: $('#sp_planet_name').val(),
                    description: $('#sp_planet_desc').val(),
                    radius: pending.radius
                },
                success: function (res) {
                    zones = res.zones;
                    $('#sp_planet_name').val('');
                    $('#sp_planet_desc').val('');
                    clearPending();
                    refresh();
                },
                error: function (xhr) {
                    var msg = 'Errore nel posizionamento del pianeta.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    alert(msg);
                }
            });
        });

        $(document).on('click', '.sp_del', function () {
            var id = $(this).data('id');
            $.ajax({
                url: destroyUrl.replace('__ID__', id),
                type: 'DELETE',
                dataType: 'json',
                data: {_token: $('meta[name="csrf-token"]').attr('content')},
                success: function (res) {
                    zones = res.zones;
                    refresh();
                },
                error: function () {
                    alert('Errore nella rimozione del pianeta.');
                }
            });
        });

        // Zoom: pulsanti e rotellina del mouse (zoom centrato sulla stella)
        $('#sp_zoom_in').on('click', function () { zoom = Math.min(4, zoom + 0.5); draw(); });
        $('#sp_zoom_out').on('click', function () { zoom = Math.max(1, zoom - 0.5); draw(); });
        $('#sp_zoom_reset').on('click', function () { zoom = 1; draw(); });
        $(document).on('wheel', '#sp_graph svg', function (e) {
            e.preventDefault();
            var dy = (e.originalEvent && e.originalEvent.deltaY) || 0;
            zoom = Math.min(4, Math.max(1, zoom + (dy < 0 ? 0.25 : -0.25)));
            draw();
        });

        refresh();
    });
</script>
