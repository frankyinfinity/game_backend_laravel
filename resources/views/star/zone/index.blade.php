@php $szReadonly = $zoneEditorReadonly ?? false; @endphp
<div class="card card-teal card-outline mt-3">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-bullseye"></i> Fascie della stella
            <span class="badge badge-teal ml-2" id="sz_count">0</span>
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="{{ $szReadonly ? 'col-md-12' : 'col-md-7' }}">
                <div class="text-center">
                    <div id="sz_graph">
                        <svg viewBox="0 0 620 440" style="width:100%;max-width:620px;background:#0a0a14;border-radius:8px">
                            <circle cx="310" cy="220" r="34" fill="rgb({{$star->rgb}})" stroke="#fff" stroke-width="1"><title>{{$star->name}}</title></circle>
                        </svg>
                    </div>
                    <small class="text-muted">{{ $szReadonly ? "Grafico in sola lettura: la stella e' in gestione pianeti." : "Anteprima in tempo reale: la fascia tratteggiata e' quella in definizione." }}</small>
                </div>
            </div>
            @if(!$szReadonly)
            <div class="col-md-5">
                <div class="card card-outline card-primary mb-3">
                    <div class="card-header py-2 d-flex align-items-center justify-content-between">
                        <strong id="sz_form_title"><i class="fa fa-plus-circle"></i> Nuova fascia</strong>
                        <button type="button" class="btn btn-secondary btn-xs" id="sz_cancel" style="display:none"><i class="fa fa-times"></i> Annulla</button>
                    </div>
                    <div class="card-body py-3">
                        <form id="sz_form">
                            @csrf
                            <div class="form-group mb-2">
                                <label for="sz_name" class="mb-1">Nome*</label>
                                <input type="text" id="sz_name" class="form-control form-control-sm" placeholder="Es. Fascia abitabile" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-6 mb-2">
                                    <label for="sz_from" class="mb-1">Da*</label>
                                    <input type="number" id="sz_from" class="form-control form-control-sm" min="0" step="1" value="0" required>
                                </div>
                                <div class="form-group col-6 mb-2">
                                    <label for="sz_to" class="mb-1">A*</label>
                                    <input type="number" id="sz_to" class="form-control form-control-sm" min="1" step="1" value="10" required>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="sz_picker" class="mb-1">Colore*</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-palette"></i></span>
                                    </div>
                                    <input type="color" id="sz_picker" class="form-control" value="#ffdd44" style="padding:0.15rem;height:calc(1.5em + 0.5rem + 2px)">
                                    <input type="hidden" id="sz_rgb" value="255,221,68">
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="sz_rgb_value">255,221,68</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="sz_habitable">
                                    <label class="custom-control-label" for="sz_habitable"><i class="fas fa-leaf text-success"></i> Abitabile</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block btn-sm" id="sz_submit"><i class="fa fa-plus"></i> Aggiungi fascia</button>
                        </form>
                    </div>
                </div>

                <div id="sz_list"></div>
                <div id="sz_empty" class="text-muted text-center border rounded py-3" style="display:none">
                    <i class="fas fa-circle-notch"></i> Nessuna fascia definita.<br>
                    <small>Definisci la prima fascia dal form qui sopra.</small>
                </div>
            </div>
            @endif
        </div>
        @if(!$szReadonly)
        <div class="row mt-3">
            <div class="col-3">
                <form method="POST" action="{{ route('stars.state', [$star->id]) }}">
                    @csrf
                    <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_CREATED }}">
                    <button type="submit" class="btn btn-secondary btn-block btn-sm"><i class="fa fa-edit"></i> Torna alle informazioni</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        var SZ_CX = 310, SZ_CY = 220, SZ_MAXR = 200, SZ_STAR_R = 34;
        var SZ_READONLY = {{ $szReadonly ? 'true' : 'false' }};
        var starRgb = @json($star->rgb);
        var starName = @json($star->name);
        var zones = @json($zones);
        var storeUrl = '{{ route('stars.zones.store', [$star->id]) }}';
        var destroyUrl = '{{ route('stars.zones.destroy', [$star->id, '__ID__']) }}';
        var updateUrl = '{{ route('stars.zones.update', [$star->id, '__ID__']) }}';
        var highlightId = null;
        var editingId = null;

        function esc(s) {
            return $('<div/>').text(String(s)).html();
        }

        function hexToRgbStr(hex) {
            var r = parseInt(hex.substr(1, 2), 16);
            var g = parseInt(hex.substr(3, 2), 16);
            var b = parseInt(hex.substr(5, 2), 16);
            return r + ',' + g + ',' + b;
        }

        $('#sz_picker').on('input', function () {
            var rgb = hexToRgbStr($(this).val());
            $('#sz_rgb').val(rgb);
            $('#sz_rgb_value').text(rgb);
            draw();
        });

        // 'Da' non puo' scendere sotto il bordo esterno dell'ultima fascia:
        // la zona tratteggiata sta sempre sopra (fuori da) tutte le altre.
        $('#sz_from').on('input', function () {
            var mf = minFrom();
            if (parseInt($(this).val(), 10) < mf) $(this).val(mf);
            draw();
        });
        $('#sz_to, #sz_name').on('input', draw);

        // Bordo interno minimo: 'to' massimo delle fasce.
        // Vale solo in creazione: in modifica la fascia mantiene la sua posizione reale.
        function minFrom() {
            if (editingId !== null) return 0;
            var m = 0;
            zones.forEach(function (z) { if (z.to > m) m = z.to; });
            return m;
        }

        function previewZone() {
            if (SZ_READONLY) return null;
            var from = parseInt($('#sz_from').val(), 10);
            var to = parseInt($('#sz_to').val(), 10);
            var name = $('#sz_name').val() || '(anteprima)';
            if (isNaN(from) || isNaN(to) || to <= from || from < 0) return null;
            from = Math.max(from, minFrom());
            if (to <= from) return null;
            return {id: '__preview__', name: name, from: from, to: to, rgb: $('#sz_rgb').val(), preview: true};
        }

        function ringSvg(z, scale) {
            var mid = (z.from + z.to) / 2 * scale;
            var w = Math.max(1, (z.to - z.from) * scale);
            var attrs = '';
            if (z.preview) {
                attrs = ' stroke-dasharray="6 4" opacity="0.75"';
            } else if (highlightId !== null) {
                attrs = (z.id === highlightId) ? ' opacity="1"' : ' opacity="0.25"';
            }
            return '<circle cx="' + SZ_CX + '" cy="' + SZ_CY + '" r="' + mid + '" fill="none" stroke="rgb(' + z.rgb + ')" stroke-width="' + w + '"' + attrs + '>' +
                '<title>' + esc(z.name) + ' (' + z.from + '-' + z.to + ')</title></circle>';
        }

        function draw() {
            var prev = previewZone();

            var maxTo = 0;
            zones.forEach(function (z) { if (z.to > maxTo) maxTo = z.to; });
            if (prev && prev.to > maxTo) maxTo = prev.to;
            var scale = maxTo > 0 ? SZ_MAXR / maxTo : 0;

            var svg = '<svg viewBox="0 0 620 440" style="width:100%;max-width:620px;background:#0a0a14;border-radius:8px">';

            // Glow stella
            svg += '<defs><filter id="sz_glow" x="-80%" y="-80%" width="260%" height="260%">' +
                '<feGaussianBlur stdDeviation="10" result="b"/>' +
                '<feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>';

            // Guide concentriche leggere
            if (maxTo > 0) {
                var steps = 4;
                for (var i = 1; i <= steps; i++) {
                    var gr = maxTo / steps * i * scale;
                    svg += '<circle cx="' + SZ_CX + '" cy="' + SZ_CY + '" r="' + gr + '" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="1" stroke-dasharray="2 6"/>';
                }
            }

            // Anelli esistenti (dal piu' esterno al piu' interno); quella in modifica e' disegnata come anteprima
            zones.filter(function (z) { return z.id !== editingId; }).sort(function (a, b) { return b.from - a.from; }).forEach(function (z) {
                svg += ringSvg(z, scale);
            });

            // Anello in anteprima
            if (prev) svg += ringSvg(prev, scale);

            // Stella al centro con glow
            svg += '<circle cx="' + SZ_CX + '" cy="' + SZ_CY + '" r="' + SZ_STAR_R + '" fill="rgb(' + starRgb + ')" stroke="#fff" stroke-width="1" filter="url(#sz_glow)">' +
                '<title>' + esc(starName) + '</title></circle>';

            // Label sotto il bordo esterno di ogni fascia
            zones.filter(function (z) { return z.id !== editingId; }).sort(function (a, b) { return a.from - b.from; }).forEach(function (z) {
                var y = SZ_CY + (z.to * scale) + 14;
                svg += '<text x="' + SZ_CX + '" y="' + y + '" text-anchor="middle" fill="rgba(255,255,255,0.65)" font-size="11">' + esc(z.name) + '</text>';
            });

            svg += '</svg>';
            $('#sz_graph').html(svg);
        }

        function drawList() {
            $('#sz_count').text(zones.length);
            $('#sz_empty').toggle(zones.length === 0);

            var html = '';
            zones.slice().sort(function (a, b) { return a.from - b.from; }).forEach(function (z) {
                var editingCls = (z.id === editingId) ? ' border-primary' : '';
                var buttons = SZ_READONLY ? '' :
                    '<button type="button" class="btn btn-info btn-xs sz_edit mr-1" data-id="' + z.id + '" title="Modifica fascia"><i class="fa fa-pencil-alt"></i></button>' +
                    '<button type="button" class="btn btn-danger btn-xs sz_del" data-id="' + z.id + '" title="Elimina fascia"><i class="fa fa-trash"></i></button>';
                html += '<div class="sz_item d-flex align-items-center border rounded px-2 py-1 mb-1' + editingCls + '" data-id="' + z.id + '">' +
                    '<span style="flex:0 0 auto;width:18px;height:18px;border-radius:4px;border:1px solid #999;background:rgb(' + z.rgb + ')" title="rgb(' + z.rgb + ')"></span>' +
                    '<span class="ml-2 flex-grow-1"><strong>' + esc(z.name) + '</strong> <small class="text-muted">da ' + z.from + ' a ' + z.to + '</small></span>' +
                    '<span class="mx-2" title="Abitabile">' + (z.habitable ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>') + '</span>' +
                    buttons +
                    '</div>';
            });
            $('#sz_list').html(html);
        }

        function refresh() {
            draw();
            drawList();
        }

        // Evidenzia l'anello passando sulla voce in lista
        $(document).on('mouseenter', '.sz_item', function () {
            highlightId = $(this).data('id');
            draw();
        });
        $(document).on('mouseleave', '.sz_item', function () {
            highlightId = null;
            draw();
        });

        function rgbToHex(rgb) {
            var parts = String(rgb).split(',');
            if (parts.length !== 3) return '#000000';
            var h = function (v) { return ('0' + Math.max(0, Math.min(255, parseInt(v, 10) || 0)).toString(16)).slice(-2); };
            return '#' + h(parts[0]) + h(parts[1]) + h(parts[2]);
        }

        function resetFormMode() {
            editingId = null;
            $('#sz_from').attr('min', 0).val(minFrom());
            $('#sz_habitable').prop('checked', false);
            $('#sz_form_title').html('<i class="fa fa-plus-circle"></i> Nuova fascia');
            $('#sz_submit').html('<i class="fa fa-plus"></i> Aggiungi fascia').removeClass('btn-success').addClass('btn-primary');
            $('#sz_cancel').hide();
            drawList();
        }

        function startEdit(z) {
            editingId = z.id;
            $('#sz_name').val(z.name);
            $('#sz_from').attr('min', 0).val(z.from);
            $('#sz_to').val(z.to);
            $('#sz_rgb').val(z.rgb);
            $('#sz_rgb_value').text(z.rgb);
            $('#sz_picker').val(rgbToHex(z.rgb));
            $('#sz_habitable').prop('checked', !!z.habitable);
            $('#sz_form_title').html('<i class="fa fa-pencil-alt"></i> Modifica fascia');
            $('#sz_submit').html('<i class="fa fa-save"></i> Salva modifiche').removeClass('btn-primary').addClass('btn-success');
            $('#sz_cancel').show();
            draw();
        }

        $(document).on('click', '.sz_edit', function () {
            var id = $(this).data('id');
            var z = zones.find(function (x) { return x.id === id; });
            if (z) startEdit(z);
        });

        $('#sz_cancel').on('click', function () {
            resetFormMode();
            $('#sz_name').val('');
            draw();
        });

        $('#sz_form').on('submit', function (e) {
            e.preventDefault();
            var isEdit = (editingId !== null);
            $.ajax({
                url: isEdit ? updateUrl.replace('__ID__', editingId) : storeUrl,
                type: isEdit ? 'PUT' : 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    name: $('#sz_name').val(),
                    from: parseInt($('#sz_from').val(), 10),
                    to: parseInt($('#sz_to').val(), 10),
                    rgb: $('#sz_rgb').val(),
                    habitable: $('#sz_habitable').is(':checked') ? 1 : 0
                },
                success: function (res) {
                    zones = res.zones;
                    resetFormMode();
                    $('#sz_name').val('');
                    // Suggerisce l'intervallo contiguo successivo
                    var maxTo = 0;
                    zones.forEach(function (z) { if (z.to > maxTo) maxTo = z.to; });
                    $('#sz_from').attr('min', 0).val(maxTo);
                    $('#sz_to').val(maxTo + 10);
                    refresh();
                },
                error: function (xhr) {
                    var msg = 'Errore nel salvataggio della fascia.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }
                    alert(msg);
                }
            });
        });

        $(document).on('click', '.sz_del', function () {
            var id = $(this).data('id');
            $.ajax({
                url: destroyUrl.replace('__ID__', id),
                type: 'DELETE',
                dataType: 'json',
                data: {_token: $('meta[name="csrf-token"]').attr('content')},
                success: function (res) {
                    zones = res.zones;
                    if (id === editingId) resetFormMode();
                    refresh();
                },
                error: function () {
                    alert('Errore nella cancellazione della fascia.');
                }
            });
        });

        refresh();
    });
</script>
