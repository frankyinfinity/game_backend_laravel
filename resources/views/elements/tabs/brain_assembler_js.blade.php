<script>
$(function() {
    var brainCanvas = document.getElementById('el-brain-canvas');
    if (!brainCanvas) return;

    var brainCtx = brainCanvas.getContext('2d');
    var TYPE_SYMBOLS_BRAIN = @json(\App\Models\Neuron::TYPE_SYMBOLS);
    var TYPE_LABELS_BRAIN = @json(\App\Models\Neuron::TYPE_LABELS);
    var componentBrains = @json($componentBrains ?? []);
    var gridWidth = +document.getElementById('el-brain-grid-width').value || 10;
    var gridHeight = +document.getElementById('el-brain-grid-height').value || 10;
    var placedBrains = [];
    var existingNeurons = @json($existingBrainNeurons ?? []);
    var existingLinks = @json($existingBrainLinks ?? []); // [{index, offset_i, offset_j, neurons, links}]

    var widthInput = document.getElementById('el-brain-grid-width');
    var heightInput = document.getElementById('el-brain-grid-height');

    function getCellSize() {
        return Math.floor(Math.min(640 / gridWidth, 640 / gridHeight));
    }

    function drawBrainGrid() {
        var cs = getCellSize();
        var cw = gridWidth * cs, ch = gridHeight * cs;
        brainCanvas.width = cw; brainCanvas.height = ch;

        brainCtx.fillStyle = '#fff';
        brainCtx.fillRect(0, 0, cw, ch);

        // Dashed grid lines
        brainCtx.strokeStyle = '#ddd'; brainCtx.lineWidth = 1; brainCtx.setLineDash([4, 4]);
        for (var i = 0; i <= gridWidth; i++) { brainCtx.beginPath(); brainCtx.moveTo(i*cs, 0); brainCtx.lineTo(i*cs, ch); brainCtx.stroke(); }
        for (var j = 0; j <= gridHeight; j++) { brainCtx.beginPath(); brainCtx.moveTo(0, j*cs); brainCtx.lineTo(cw, j*cs); brainCtx.stroke(); }
        brainCtx.setLineDash([]);

        // Draw existing neurons from element brain (from DB)
        existingLinks.forEach(function(link) {
            var fromN = existingNeurons.find(function(n) { return n.id === link.from_neuron_id; });
            var toN = existingNeurons.find(function(n) { return n.id === link.to_neuron_id; });
            if (!fromN || !toN) return;
            brainCtx.strokeStyle = link.color || '#16A34A'; brainCtx.lineWidth = 2;
            brainCtx.beginPath();
            brainCtx.moveTo((fromN.grid_j + 1) * cs, fromN.grid_i * cs + cs / 2);
            brainCtx.lineTo(toN.grid_j * cs, toN.grid_i * cs + cs / 2);
            brainCtx.stroke();
        });
        existingNeurons.forEach(function(n) {
            var x = n.grid_j * cs, y = n.grid_i * cs;
            brainCtx.fillStyle = '#fff'; brainCtx.strokeStyle = '#111827'; brainCtx.lineWidth = 2;
            brainCtx.fillRect(x+2, y+2, cs-4, cs-4); brainCtx.strokeRect(x+2, y+2, cs-4, cs-4);
            brainCtx.fillStyle = '#1f2937'; brainCtx.font = 'bold ' + Math.floor(cs * 0.45) + 'px Consolas';
            brainCtx.textAlign = 'center'; brainCtx.textBaseline = 'middle';
            brainCtx.fillText(TYPE_SYMBOLS_BRAIN[n.type] || '?', x + cs/2, y + cs/2);
        });

        // Draw placed brains (from current session, not yet in DB)
        placedBrains.forEach(function(pb) {
            var cb = componentBrains[pb.index];
            if (!cb) return;
            (cb.links || []).forEach(function(link) {
                var fromN = cb.neurons.find(function(n) { return n.id === link.from_neuron_id; });
                var toN = cb.neurons.find(function(n) { return n.id === link.to_neuron_id; });
                if (!fromN || !toN) return;
                brainCtx.strokeStyle = link.color || '#16A34A'; brainCtx.lineWidth = 2;
                brainCtx.beginPath();
                brainCtx.moveTo((fromN.grid_j + pb.offset_j + 1) * cs, (fromN.grid_i + pb.offset_i) * cs + cs / 2);
                brainCtx.lineTo((toN.grid_j + pb.offset_j) * cs, (toN.grid_i + pb.offset_i) * cs + cs / 2);
                brainCtx.stroke();
            });
            cb.neurons.forEach(function(n) {
                var x = (n.grid_j + pb.offset_j) * cs, y = (n.grid_i + pb.offset_i) * cs;
                brainCtx.fillStyle = '#fff'; brainCtx.strokeStyle = '#111827'; brainCtx.lineWidth = 2;
                brainCtx.fillRect(x+2, y+2, cs-4, cs-4); brainCtx.strokeRect(x+2, y+2, cs-4, cs-4);
                brainCtx.fillStyle = '#1f2937'; brainCtx.font = 'bold ' + Math.floor(cs * 0.45) + 'px Consolas';
                brainCtx.textAlign = 'center'; brainCtx.textBaseline = 'middle';
                brainCtx.fillText(TYPE_SYMBOLS_BRAIN[n.type] || '?', x + cs/2, y + cs/2);
            });
        });
    }

    var hasBrain = {{ $element->brain_id ? 'true' : 'false' }};

    // Place a brain into the grid
    function placeBrain(index) {
        var cb = componentBrains[index];
        if (!cb) return;

        if (!hasBrain) {
            alert('Devi prima salvare le dimensioni della griglia.');
            return;
        }

        // Check if already placed
        if (placedBrains.find(function(p) { return p.index === index; })) {
            alert('Questo cervello è già posizionato.');
            return;
        }

        // Find next available position
        var startI = 0, startJ = 0;
        for (var oi = 0; oi <= gridHeight - cb.grid_height; oi++) {
            for (var oj = 0; oj <= gridWidth - cb.grid_width; oj++) {
                if (canFit(cb, oi, oj)) { startI = oi; startJ = oj; break; }
            }
            if (startI !== 0 || startJ !== 0 || canFit(cb, 0, 0)) break;
        }

        // Open placement modal
        openPlaceModal(index, startI, startJ);
    }

    var placingIndex = null, placingI = 0, placingJ = 0;

    function openPlaceModal(index, oi, oj) {
        var cb = componentBrains[index];
        placingIndex = index; placingI = oi; placingJ = oj;
        document.getElementById('el-brain-place-title').innerHTML = '<i class="fas fa-crosshairs mr-2"></i> Posiziona: ' + cb.component_name;
        drawPlacePreview();
        $('#elBrainPlaceModal').modal('show');
    }

    function drawPlacePreview() {
        var canvas = document.getElementById('el-brain-place-canvas');
        var ctx = canvas.getContext('2d');
        var cs = Math.floor(Math.min(480 / gridWidth, 480 / gridHeight));
        var cw = gridWidth * cs, ch = gridHeight * cs;
        canvas.width = cw; canvas.height = ch;
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cw, ch);

        // Grid lines
        ctx.strokeStyle = '#ddd'; ctx.lineWidth = 1; ctx.setLineDash([4, 4]);
        for (var i = 0; i <= gridWidth; i++) { ctx.beginPath(); ctx.moveTo(i*cs, 0); ctx.lineTo(i*cs, ch); ctx.stroke(); }
        for (var j = 0; j <= gridHeight; j++) { ctx.beginPath(); ctx.moveTo(0, j*cs); ctx.lineTo(cw, j*cs); ctx.stroke(); }
        ctx.setLineDash([]);

        // Draw already placed brains (grey)
        placedBrains.forEach(function(pb) {
            var existCb = componentBrains[pb.index];
            if (!existCb) return;
            existCb.neurons.forEach(function(n) {
                var x = (n.grid_j + pb.offset_j) * cs, y = (n.grid_i + pb.offset_i) * cs;
                ctx.fillStyle = '#e0e0e0'; ctx.fillRect(x+1, y+1, cs-2, cs-2);
                ctx.strokeStyle = '#999'; ctx.lineWidth = 1; ctx.strokeRect(x+1, y+1, cs-2, cs-2);
            });
        });

        // Draw existing neurons from DB (light blue)
        existingNeurons.forEach(function(n) {
            var x = n.grid_j * cs, y = n.grid_i * cs;
            ctx.fillStyle = '#d1ecf1'; ctx.fillRect(x+1, y+1, cs-2, cs-2);
            ctx.strokeStyle = '#17a2b8'; ctx.lineWidth = 1; ctx.strokeRect(x+1, y+1, cs-2, cs-2);
            ctx.fillStyle = '#0c5460'; ctx.font = 'bold ' + Math.floor(cs*0.35) + 'px Consolas';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(TYPE_SYMBOLS_BRAIN[n.type] || '?', x+cs/2, y+cs/2);
        });

        // Draw current brain being placed (colored)
        var cb = componentBrains[placingIndex];
        if (!cb) return;
        // Links
        (cb.links || []).forEach(function(link) {
            var fromN = cb.neurons.find(function(n) { return n.id === link.from_neuron_id; });
            var toN = cb.neurons.find(function(n) { return n.id === link.to_neuron_id; });
            if (!fromN || !toN) return;
            ctx.strokeStyle = link.color || '#16A34A'; ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo((fromN.grid_j + placingJ + 1) * cs, (fromN.grid_i + placingI) * cs + cs/2);
            ctx.lineTo((toN.grid_j + placingJ) * cs, (toN.grid_i + placingI) * cs + cs/2);
            ctx.stroke();
        });
        // Neurons
        cb.neurons.forEach(function(n) {
            var x = (n.grid_j + placingJ) * cs, y = (n.grid_i + placingI) * cs;
            ctx.fillStyle = '#fff'; ctx.strokeStyle = '#111827'; ctx.lineWidth = 2;
            ctx.fillRect(x+2, y+2, cs-4, cs-4); ctx.strokeRect(x+2, y+2, cs-4, cs-4);
            ctx.fillStyle = '#1f2937'; ctx.font = 'bold ' + Math.floor(cs*0.45) + 'px Consolas';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(TYPE_SYMBOLS_BRAIN[n.type] || '?', x+cs/2, y+cs/2);
        });
    }

    document.getElementById('el-brain-place-up').addEventListener('click', function() {
        if (placingI > 0) { placingI--; drawPlacePreview(); }
    });
    document.getElementById('el-brain-place-down').addEventListener('click', function() {
        var cb = componentBrains[placingIndex]; if (!cb) return;
        if (placingI + cb.grid_height < gridHeight) { placingI++; drawPlacePreview(); }
    });
    document.getElementById('el-brain-place-left').addEventListener('click', function() {
        if (placingJ > 0) { placingJ--; drawPlacePreview(); }
    });
    document.getElementById('el-brain-place-right').addEventListener('click', function() {
        var cb = componentBrains[placingIndex]; if (!cb) return;
        if (placingJ + cb.grid_width < gridWidth) { placingJ++; drawPlacePreview(); }
    });

    document.getElementById('el-brain-place-confirm').addEventListener('click', function() {
        var cb = componentBrains[placingIndex];
        if (!cb) return;
        if (!canFit(cb, placingI, placingJ)) {
            alert('Posizione occupata! Sposta il cervello in una posizione libera.');
            return;
        }

        // Save to server immediately
        fetch('{{ route("elements.brain.place-component", $element) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                component_id: cb.component_id,
                detail_id: cb.detail_id,
                offset_i: placingI,
                offset_j: placingJ
            })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                placedBrains.push({ index: placingIndex, offset_i: placingI, offset_j: placingJ });
                var badge = document.getElementById('el-brain-status-' + placingIndex);
                if (badge) { badge.className = 'badge badge-success'; badge.textContent = 'Posizionato'; }
                // Hide the place button
                var placeBtn = document.querySelector('.el-brain-place-btn[data-index="' + placingIndex + '"]');
                if (placeBtn) placeBtn.style.display = 'none';
                $('#elBrainPlaceModal').modal('hide');
                drawBrainGrid();
                if (typeof toastr !== 'undefined') toastr.success('Cervello posizionato e salvato!');
                else alert('Cervello posizionato e salvato!');
            } else {
                if (typeof toastr !== 'undefined') toastr.error(d.message || 'Errore nel posizionamento.');
                else alert(d.message || 'Errore nel posizionamento.');
            }
        }).catch(function(e) { console.error(e); alert('Errore di rete.'); });
    });

    // View brain modal
    $(document).on('click', '.el-brain-view-btn', function() {
        var index = +$(this).data('index');
        var cb = componentBrains[index];
        if (!cb) return;
        document.getElementById('el-brain-view-title').innerHTML = '<i class="fas fa-brain mr-2"></i> ' + cb.component_name;
        var canvas = document.getElementById('el-brain-view-canvas');
        var ctx = canvas.getContext('2d');
        var cs = Math.floor(Math.min(480 / cb.grid_width, 480 / cb.grid_height));
        var cw = cb.grid_width * cs, ch = cb.grid_height * cs;
        canvas.width = cw; canvas.height = ch;
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, cw, ch);
        ctx.strokeStyle = '#ddd'; ctx.lineWidth = 1; ctx.setLineDash([4,4]);
        for (var i = 0; i <= cb.grid_width; i++) { ctx.beginPath(); ctx.moveTo(i*cs,0); ctx.lineTo(i*cs,ch); ctx.stroke(); }
        for (var j = 0; j <= cb.grid_height; j++) { ctx.beginPath(); ctx.moveTo(0,j*cs); ctx.lineTo(cw,j*cs); ctx.stroke(); }
        ctx.setLineDash([]);
        (cb.links || []).forEach(function(link) {
            var fromN = cb.neurons.find(function(n){return n.id===link.from_neuron_id;});
            var toN = cb.neurons.find(function(n){return n.id===link.to_neuron_id;});
            if (!fromN||!toN) return;
            ctx.strokeStyle = link.color||'#16A34A'; ctx.lineWidth = 2;
            ctx.beginPath(); ctx.moveTo((fromN.grid_j+1)*cs, fromN.grid_i*cs+cs/2); ctx.lineTo(toN.grid_j*cs, toN.grid_i*cs+cs/2); ctx.stroke();
        });
        cb.neurons.forEach(function(n) {
            var x=n.grid_j*cs, y=n.grid_i*cs;
            ctx.fillStyle='#fff'; ctx.strokeStyle='#111827'; ctx.lineWidth=2;
            ctx.fillRect(x+2,y+2,cs-4,cs-4); ctx.strokeRect(x+2,y+2,cs-4,cs-4);
            ctx.fillStyle='#1f2937'; ctx.font='bold '+Math.floor(cs*0.45)+'px Consolas';
            ctx.textAlign='center'; ctx.textBaseline='middle';
            ctx.fillText(TYPE_SYMBOLS_BRAIN[n.type]||'?', x+cs/2, y+cs/2);
        });
        $('#elBrainViewModal').modal('show');

        // Tooltip for view modal
        canvas.onmousemove = function(ev) {
            var rect = canvas.getBoundingClientRect();
            var mx = ev.clientX - rect.left, my = ev.clientY - rect.top;
            var ci = Math.floor(my / cs), cj = Math.floor(mx / cs);
            var neuron = cb.neurons.find(function(n) { return n.grid_i === ci && n.grid_j === cj; });
            canvas.title = neuron ? (TYPE_LABELS_BRAIN[neuron.type] || neuron.type) + (neuron.tooltip ? ' — ' + neuron.tooltip : '') : '';
        };
        canvas.onmouseleave = function() { canvas.title = ''; };
    });

    function canFit(cb, offsetI, offsetJ) {
        // Check if all neurons of this brain fit without overlapping placed ones
        var occupied = {};
        placedBrains.forEach(function(pb) {
            var existCb = componentBrains[pb.index];
            if (!existCb) return;
            existCb.neurons.forEach(function(n) {
                occupied[(n.grid_i + pb.offset_i) + '|' + (n.grid_j + pb.offset_j)] = true;
            });
        });

        for (var ni = 0; ni < cb.neurons.length; ni++) {
            var n = cb.neurons[ni];
            var ri = n.grid_i + offsetI, rj = n.grid_j + offsetJ;
            if (ri >= gridHeight || rj >= gridWidth) return false;
            if (occupied[ri + '|' + rj]) return false;
        }
        return true;
    }

    // Remove a placed brain
    function removeBrain(index) {
        placedBrains = placedBrains.filter(function(p) { return p.index !== index; });
        var badge = document.getElementById('el-brain-status-' + index);
        if (badge) { badge.className = 'badge badge-secondary'; badge.textContent = 'Non posizionato'; }
        drawBrainGrid();
        saveBrainData();
    }

    // Event handlers
    $(document).on('click', '.el-brain-place-btn', function() {
        var index = +$(this).data('index');
        placeBrain(index);
    });

    document.getElementById('el-brain-save-grid').addEventListener('click', function() {
        gridWidth = Math.max(1, +widthInput.value || 10);
        gridHeight = Math.max(1, +heightInput.value || 10);
        // Reset placements if grid shrinks
        placedBrains = placedBrains.filter(function(pb) {
            var cb = componentBrains[pb.index];
            if (!cb) return false;
            return cb.neurons.every(function(n) {
                return (n.grid_i + pb.offset_i) < gridHeight && (n.grid_j + pb.offset_j) < gridWidth;
            });
        });
        drawBrainGrid();

        // Save grid dimensions (creates brain if not exists)
        fetch('{{ route("elements.brain.save-grid", $element) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ grid_width: gridWidth, grid_height: gridHeight })
        }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                hasBrain = true;
                if (typeof toastr !== 'undefined') toastr.success('Griglia salvata con successo!');
                else alert('Griglia salvata con successo!');
                drawBrainGrid();
            } else {
                if (typeof toastr !== 'undefined') toastr.error(d.message || 'Errore');
                else alert(d.message || 'Errore');
            }
        }).catch(function(e) { console.error(e); alert('Errore di rete.'); });
    });

    // Tooltip on hover
    brainCanvas.addEventListener('mousemove', function(ev) {
        var rect = brainCanvas.getBoundingClientRect();
        var cs = getCellSize();
        var mx = ev.clientX - rect.left, my = ev.clientY - rect.top;
        var ci = Math.floor(my / cs), cj = Math.floor(mx / cs);
        var found = null;
        placedBrains.forEach(function(pb) {
            var cb = componentBrains[pb.index];
            if (!cb || found) return;
            cb.neurons.forEach(function(n) {
                if ((n.grid_i + pb.offset_i) === ci && (n.grid_j + pb.offset_j) === cj) found = n;
            });
        });
        brainCanvas.title = found ? (TYPE_LABELS_BRAIN[found.type] || found.type) + (found.tooltip ? ' — ' + found.tooltip : '') : '';
    });

    // Init
    drawBrainGrid();
});
</script>
