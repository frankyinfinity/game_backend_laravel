@extends('adminlte::page')

@section('title', 'Dettagli Fase - ' . $phase->name)

@section('content_header')
<style>
    #pixi-phase-container canvas {
        display: block;
        width: 100%;
        height: 100%;
    }
</style>
@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Dettagli Fase - {{ $phase->name }}</h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5>Nome: {{ $phase->name }}</h5>
                        <h6>Altezza: {{ $phase->height }}</h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm js-add-column d-flex align-items-center mr-2" style="min-height: 40px;">
                            <i class="fa fa-plus mr-2"></i> Nuova Fascia
                        </button>
                        <a href="{{ route('ages.phases.edit', [$age, $phase]) }}" class="btn btn-secondary btn-sm d-flex align-items-center" style="min-height: 40px;">
                            <i class="fa fa-edit mr-2"></i> Modifica Fase
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <h5 class="mb-3">Configurazione Obiettivi</h5>
                <div id="pixi-phase-container" style="width: 100%; height: 650px; border: 1px solid #ddd; background: #f4f6f9; border-radius: 8px; overflow: hidden; position: relative; box-shadow: inset 0 0 10px rgba(0,0,0,0.05);"></div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <a href="{{ route('ages.phases.index', $age) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Torna alle Fasi
        </a>
    </div>
</div>

<!-- Modal per creazione obiettivo -->
<div class="modal fade" id="createTargetModal" tabindex="-1" role="dialog" aria-labelledby="createTargetModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTargetModalLabel">Crea Nuovo Obiettivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createTargetForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="targetTitle">Titolo *</label>
                        <input type="text" class="form-control" id="targetTitle" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="targetDescription">Descrizione</label>
                        <textarea class="form-control" id="targetDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Crea Obiettivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per dettagli obiettivo -->
<div class="modal fade" id="viewTargetDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewTargetDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTargetDetailsModalLabel">Dettagli Obiettivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="targetDetailsTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="cost-tab" data-toggle="tab" href="#cost" role="tab" aria-controls="cost" aria-selected="false">Costo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="reward-tab" data-toggle="tab" href="#reward" role="tab" aria-controls="reward" aria-selected="false">Ricompensa</a>
                    </li>
                </ul>
                <div class="tab-content" id="targetDetailsTabsContent">
                    <!-- Tab Dati Generali -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form id="updateTargetForm">
                            @csrf
                            @method('PUT')
                            <div class="form-group mt-3">
                                <label for="updateTargetTitle">Titolo *</label>
                                <input type="text" class="form-control" id="updateTargetTitle" name="title" required>
                            </div>
                            <div class="form-group">
                                <label for="updateTargetDescription">Descrizione</label>
                                <textarea class="form-control" id="updateTargetDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                                <button type="submit" class="btn btn-primary">Aggiorna Obiettivo</button>
                            </div>
                        </form>
                    </div>
                    <!-- Tab Costo -->
                    <div class="tab-pane fade" id="cost" role="tabpanel" aria-labelledby="cost-tab">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Elenco Costi</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="addCostButton">
                                <i class="fa fa-plus"></i> Aggiungi Costo
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="targetHasScoresTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Score</th>
                                        <th>Valore</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="targetHasScoresTableBody">
                                    <!-- Dati caricate via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- Tab Ricompensa -->
                    <div class="tab-pane fade" id="reward" role="tabpanel" aria-labelledby="reward-tab">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Codice PHP Ricompensa</h6>
                            <button type="button" class="btn btn-primary btn-sm" id="saveRewardButton">
                                <i class="fa fa-save"></i> Salva
                            </button>
                        </div>
                        <div class="form-group">
                            <div id="rewardMonacoEditor" style="height: 400px; border: 1px solid #ccc; border-radius: 4px;"></div>
                            <textarea class="d-none" id="rewardEditor" name="reward"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per aggiungere/modificare costo -->
<div class="modal fade" id="addEditCostModal" tabindex="-1" role="dialog" aria-labelledby="addEditCostModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditCostModalLabel">Aggiungi Costo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addEditCostForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="scoreSelect">Score *</label>
                        <select class="form-control" id="scoreSelect" name="score_id" required>
                            <!-- Options caricate via JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="costValue">Valore *</label>
                        <input type="number" class="form-control" id="costValue" name="value" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="https://pixijs.download/v7.4.2/pixi.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
    <script>
        // Monaco Initialization
        require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
        require(['vs/editor/editor.main'], function () {
            monaco.languages.register({ id: 'php' });
            const pOpen = String.fromCharCode(60) + '?';
            const initialValue = pOpen + 'php\n// Scrivi qui il codice PHP per la ricompensa\n';
            window.mEditor = monaco.editor.create(document.getElementById('rewardMonacoEditor'), {
                value: initialValue,
                language: 'php',
                theme: 'vs-dark',
                automaticLayout: true,
                minimap: { enabled: false },
                fontSize: 14,
                lineNumbers: 'on',
                scrollBeyondLastLine: false,
                wordWrap: 'on'
            });
            window.mEditor.onDidChangeModelContent(function () {
                document.getElementById('rewardEditor').value = window.mEditor.getValue();
            });
        });

        // Config & Data
        const CONFIG = {
            ageId: {{ $age->id }},
            phaseId: {{ $phase->id }},
            height: {{ $phase->height }},
            routes: {
                addColumn: "{{ route('ages.phases.columns.store', [$age, $phase]) }}",
                deleteColumn: "{{ route('ages.phases.columns.destroy', [$age, $phase, ':columnId']) }}",
                addTarget: "{{ route('ages.phases.columns.targets.store', [$age, $phase, ':columnId']) }}",
                showTarget: "{{ route('ages.phases.columns.targets.show', [$age, $phase, ':columnId', ':targetId']) }}",
                updateTarget: "{{ route('ages.phases.columns.targets.update', [$age, $phase, ':columnId', ':targetId']) }}",
                deleteTarget: "{{ route('ages.phases.columns.targets.destroy', [$age, $phase, ':columnId', ':targetId']) }}",
                linksIndex: "{{ route('ages.phases.target-links.index', [$age, $phase]) }}",
                saveLink: "{{ route('ages.phases.columns.targets.target-links.store', [$age, $phase, ':columnId', ':targetId']) }}",
                deleteLink: "{{ route('ages.phases.columns.targets.target-links.destroy', [$age, $phase, ':columnId', ':targetId', ':linkId']) }}",
                scoresIndex: "{{ route('scores.index') }}",
                targetScoresIndex: "{{ route('ages.phases.columns.targets.target-has-scores.index', [$age, $phase, ':columnId', ':targetId']) }}",
                storeTargetScore: "{{ route('ages.phases.columns.targets.target-has-scores.store', [$age, $phase, ':columnId', ':targetId']) }}",
                updateTargetScore: "{{ route('ages.phases.columns.targets.target-has-scores.update', [$age, $phase, ':columnId', ':targetId', ':id']) }}",
                deleteTargetScore: "{{ route('ages.phases.columns.targets.target-has-scores.destroy', [$age, $phase, ':columnId', ':targetId', ':id']) }}",
                phaseData: "{{ route('ages.phases.data', [$age, $phase]) }}"
            }
        };

        let phaseData = @json($phase->load('phaseColumns.targets'));
        let linkData = [];

        // App PIXI
        const container = document.getElementById('pixi-phase-container');
        const app = new PIXI.Application({
            width: container.clientWidth,
            height: container.clientHeight,
            backgroundColor: 0xf4f6f9,
            antialias: true,
            resolution: window.devicePixelRatio || 1,
            autoDensity: true
        });
        container.appendChild(app.view);

        const world = new PIXI.Container();
        app.stage.addChild(world);
        app.stage.eventMode = 'static';
        app.stage.hitArea = app.screen;

        const linkLayer = new PIXI.Graphics();
        const gridLayer = new PIXI.Container();
        const uiLayer = new PIXI.Container();
        world.addChild(gridLayer, linkLayer, uiLayer);

        const COLUMN_WIDTH = 320;
        const TARGET_HEIGHT = 100;
        const SPACING = 20;
        const HEADER_HEIGHT = 50;

        let draggingTarget = null;
        let dragOffset = { x: 0, y: 0 };
        let tempLinkLine = new PIXI.Graphics();
        world.addChild(tempLinkLine);

        let activeFromTargetId = null;

        // Inizializzazione
        async function init() {
            console.log("PIXI Init - Phase:", CONFIG.phaseId);
            await loadLinks();
            render();
            console.log("PIXI Render Complete - Links:", linkData.length);
            
            window.addEventListener('resize', () => {
                app.renderer.resize(container.clientWidth, container.clientHeight);
                app.stage.hitArea = app.screen;
                render();
            });
        }

        async function softReload() {
            try {
                const res = await fetch(CONFIG.routes.phaseData);
                phaseData = await res.json();
                await loadLinks();
                render();
            } catch (e) { console.error("Soft reload failed:", e); }
        }

        async function loadLinks() {
            try {
                const res = await fetch(CONFIG.routes.linksIndex);
                const json = await res.json();
                linkData = json.links;
            } catch (e) { console.error(e); }
        }

        function getTargetPos(columnId, slot) {
            const colIndex = phaseData.phase_columns.findIndex(c => c.id === columnId);
            if (colIndex === -1) return { x: 0, y: 0 };
            return {
                x: colIndex * COLUMN_WIDTH + SPACING,
                y: slot * (TARGET_HEIGHT + SPACING) + HEADER_HEIGHT + SPACING
            };
        }

        function findTargetById(id) {
            for (const col of phaseData.phase_columns) {
                const t = col.targets.find(t => t.id === id);
                if (t) return { target: t, column: col };
            }
            return null;
        }

        function render() {
            gridLayer.removeChildren();
            uiLayer.removeChildren();
            linkLayer.clear();

            // Sfondi colonne
            phaseData.phase_columns.forEach((col, idx) => {
                const x = idx * COLUMN_WIDTH + SPACING;
                
                // Card colonna
                const colBg = new PIXI.Graphics()
                    .beginFill(0xffffff)
                    .drawRoundedRect(0, 0, COLUMN_WIDTH - SPACING, app.screen.height - SPACING * 2, 8)
                    .endFill();
                colBg.x = x;
                colBg.y = SPACING;
                colBg.alpha = 0.5;
                gridLayer.addChild(colBg);

                // Header colonna
                const header = new PIXI.Text(`Fascia ${idx + 1}`, { fontSize: 16, fontWeight: 'bold', fill: 0x333 });
                header.x = x + 15;
                header.y = SPACING + 15;
                uiLayer.addChild(header);

                // Delete colonna
                const delCol = createIconButton(0xdc3545, 'fa-trash', 14);
                delCol.x = x + COLUMN_WIDTH - SPACING - 30;
                delCol.y = SPACING + 15;
                delCol.on('pointerdown', (e) => {
                    e.stopPropagation();
                    deleteColumn(col.id);
                });
                uiLayer.addChild(delCol);

                // Slot
                for (let i = 0; i < CONFIG.height; i++) {
                    const py = i * (TARGET_HEIGHT + SPACING) + HEADER_HEIGHT + SPACING;
                    const target = col.targets.find(t => t.slot === i);

                    if (target) {
                        drawTarget(target, col, x, py);
                    } else {
                        drawEmptySlot(col, i, x, py);
                    }
                }
            });

            drawLinks();
        }

        function drawTarget(target, column, x, y) {
            const card = new PIXI.Container();
            card.x = x;
            card.y = y;

            const bg = new PIXI.Graphics()
                .lineStyle(1, 0x333, 1)
                .beginFill(0xf5f5f5)
                .drawRect(8, 0, COLUMN_WIDTH - SPACING - 16, TARGET_HEIGHT)
                .endFill();
            card.addChild(bg);

            const title = new PIXI.Text(target.title, { fontSize: 14, fontWeight: 'bold', fill: 0x000, wordWrap: true, wordWrapWidth: COLUMN_WIDTH - 50 });
            title.x = 20;
            title.y = 10;
            card.addChild(title);

            if (target.description) {
                const desc = new PIXI.Text(target.description.substring(0, 40) + (target.description.length > 40 ? '...' : ''), { fontSize: 11, fill: 0x666 });
                desc.x = 20;
                desc.y = 30;
                card.addChild(desc);
            }

            // Pulsanti
            const btnDetails = createSmallButton("Dettagli", 0x17a2b8);
            btnDetails.x = 20;
            btnDetails.y = 65;
            btnDetails.on('pointerdown', (e) => e.stopPropagation());
            btnDetails.on('pointertap', (e) => {
                e.stopPropagation();
                openTargetModal(column.id, target.id);
            });
            card.addChild(btnDetails);

            const btnDel = createSmallButton("Elimina", 0xdc3545);
            btnDel.x = 100;
            btnDel.y = 65;
            btnDel.on('pointerdown', (e) => e.stopPropagation());
            btnDel.on('pointertap', (e) => {
                e.stopPropagation();
                deleteTarget(column.id, target.id);
            });
            card.addChild(btnDel);

            // Ancore
            const colIndex = phaseData.phase_columns.findIndex(c => c.id === column.id);
            if (colIndex < phaseData.phase_columns.length - 1) {
                const anchorR = createAnchor(target.id, 'right');
                anchorR.x = COLUMN_WIDTH - SPACING - 8;
                anchorR.y = TARGET_HEIGHT / 2;
                card.addChild(anchorR);
            }
            if (colIndex > 0) {
                const anchorL = createAnchor(target.id, 'left');
                anchorL.x = 8;
                anchorL.y = TARGET_HEIGHT / 2;
                card.addChild(anchorL);
            }

            card.eventMode = 'static';
            card.cursor = 'grab';
            card.on('pointerdown', (e) => startDragTarget(e, target, column, card));

            uiLayer.addChild(card);
        }

        function drawEmptySlot(column, slot, x, y) {
            const bg = new PIXI.Graphics()
                .lineStyle(1, 0xccc, 1)
                .beginFill(0xffffff)
                .drawRect(8, 0, COLUMN_WIDTH - SPACING - 16, TARGET_HEIGHT)
                .endFill();
            bg.x = x;
            bg.y = y;
            bg.eventMode = 'static';
            bg.cursor = 'pointer';
            bg.on('pointerdown', (e) => e.stopPropagation());
            bg.on('pointertap', (e) => {
                console.log("Empty slot clicked", column.id, slot);
                e.stopPropagation();
                openCreateTargetModal(column.id, slot);
            });

            const txt = new PIXI.Text("+", { fontSize: 40, fill: 0xccc });
            txt.eventMode = 'none';
            txt.anchor.set(0.5);
            txt.x = x + (COLUMN_WIDTH - SPACING) / 2;
            txt.y = y + TARGET_HEIGHT / 2;

            uiLayer.addChild(bg, txt);
        }

        function createSmallButton(text, color) {
            const container = new PIXI.Container();
            const bg = new PIXI.Graphics().beginFill(color).drawRoundedRect(0, 0, 70, 24, 4).endFill();
            const txt = new PIXI.Text(text, { fontSize: 10, fill: 0xffffff });
            txt.anchor.set(0.5);
            txt.x = 35;
            txt.y = 12;
            container.addChild(bg, txt);
            container.eventMode = 'static';
            container.cursor = 'pointer';
            return container;
        }

        function createIconButton(color, iconClass, size) {
            const g = new PIXI.Graphics().beginFill(color).drawCircle(0, 0, 12).endFill();
            const txt = new PIXI.Text("×", { fontSize: 20, fill: 0xffffff }); // Placeholder for trash
            txt.anchor.set(0.5);
            g.addChild(txt);
            g.eventMode = 'static';
            g.cursor = 'pointer';
            return g;
        }

        function createAnchor(targetId, side) {
            const g = new PIXI.Graphics().beginFill(0x007bff).drawCircle(0, 0, 8).endFill();
            g.eventMode = 'static';
            g.cursor = 'crosshair';
            if (side === 'right') {
                g.on('pointerdown', (e) => {
                    e.stopPropagation();
                    startCreateLink(e, targetId);
                });
            }
            g.targetId = targetId;
            g.side = side;
            return g;
        }

        function drawLinks() {
            linkLayer.clear();
            linkData.forEach(link => {
                const from = findTargetById(link.from_target_id);
                const to = findTargetById(link.to_target_id);
                if (from && to) {
                    const p1 = getTargetPos(from.column.id, from.target.slot);
                    const p2 = getTargetPos(to.column.id, to.target.slot);
                    
                    const x1 = p1.x + (COLUMN_WIDTH - SPACING) - 8;
                    const y1 = p1.y + TARGET_HEIGHT / 2;
                    const x2 = p2.x + 8;
                    const y2 = p2.y + TARGET_HEIGHT / 2;

                    linkLayer.lineStyle(3, 0x007bff, 1);
                    linkLayer.moveTo(x1, y1);
                    linkLayer.lineTo(x2, y2);

                    // Pulsante cancella link
                    const mx = (x1 + x2) / 2;
                    const my = (y1 + y2) / 2;
                    drawLinkDelete(mx, my, from.column.id, from.target.id, link.id);
                }
            });
        }

        function drawLinkDelete(x, y, colId, targetId, linkId) {
            const btn = new PIXI.Graphics().beginFill(0xdc3545).drawCircle(0, 0, 8).endFill();
            btn.x = x;
            btn.y = y;
            btn.eventMode = 'static';
            btn.cursor = 'pointer';
            const txt = new PIXI.Text("×", { fontSize: 12, fill: 0xffffff, fontWeight: 'bold' });
            txt.anchor.set(0.5);
            btn.addChild(txt);
            btn.on('pointerdown', (e) => {
                e.stopPropagation();
                deleteLink(colId, targetId, linkId);
            });
            uiLayer.addChild(btn);
        }

        // Actions
        async function deleteColumn(id) {
            if (!confirm('Eliminare fascia?')) return;
            try {
                const url = CONFIG.routes.deleteColumn.replace(':columnId', id);
                await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                await softReload();
            } catch (e) { console.error(e); }
        }

        async function deleteTarget(colId, id) {
            if (!confirm('Eliminare obiettivo?')) return;
            try {
                const url = CONFIG.routes.deleteTarget.replace(':columnId', colId).replace(':targetId', id);
                await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                await softReload();
            } catch (e) { console.error(e); }
        }

        async function deleteLink(colId, targetId, linkId) {
            if (!confirm('Eliminare collegamento?')) return;
            try {
                const url = CONFIG.routes.deleteLink.replace(':columnId', colId).replace(':targetId', targetId).replace(':linkId', linkId);
                await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
                await loadLinks();
                render();
            } catch (e) { console.error(e); }
        }

        let selectedColumnId, selectedSlot;
        function openCreateTargetModal(colId, slot) {
            selectedColumnId = colId;
            selectedSlot = slot;
            $('#createTargetForm')[0].reset();
            $('#createTargetModal').modal('show');
        }

        $('#createTargetForm').on('submit', async function(e) {
            e.preventDefault();
            const url = CONFIG.routes.addTarget.replace(':columnId', selectedColumnId);
            const data = $(this).serialize() + `&slot=${selectedSlot}`;
            try {
                await $.post(url, data);
                $('#createTargetModal').modal('hide');
                await softReload();
            } catch (e) { console.error(e); }
        });

        async function openTargetModal(colId, targetId) {
            const url = CONFIG.routes.showTarget.replace(':columnId', colId).replace(':targetId', targetId);
            const res = await fetch(url);
            const json = await res.json();
            const target = json.target;
            
            $('#updateTargetTitle').val(target.title);
            $('#updateTargetDescription').val(target.description);
            if (window.mEditor) {
                window.mEditor.setValue(target.reward || String.fromCharCode(60) + '?php\n// ...\n');
            }
            
            // Setup scores list
            await refreshScoresTable(colId, targetId);

            $('#updateTargetForm').off('submit').on('submit', async function(e) {
                e.preventDefault();
                const updateUrl = CONFIG.routes.updateTarget.replace(':columnId', colId).replace(':targetId', targetId);
                await $.ajax({ url: updateUrl, method: 'PUT', data: $(this).serialize() });
                $('#viewTargetDetailsModal').modal('hide');
                await softReload();
            });

            $('#saveRewardButton').off('click').on('click', async function() {
                const updateUrl = CONFIG.routes.updateTarget.replace(':columnId', colId).replace(':targetId', targetId);
                await $.ajax({ url: updateUrl, method: 'PUT', data: { reward: window.mEditor.getValue(), _token: '{{ csrf_token() }}' } });
                alert('Ricompensa salvata');
            });

            $('#viewTargetDetailsModal').modal('show');
        }

        // Link Creation
        function startCreateLink(e, fromId) {
            activeFromTargetId = fromId;
            const from = findTargetById(fromId);
            const p = getTargetPos(from.column.id, from.target.slot);
            const x1 = p.x + (COLUMN_WIDTH - SPACING) - 8;
            const y1 = p.y + TARGET_HEIGHT / 2;

            const onMove = (ev) => {
                const pos = ev.getLocalPosition(world);
                tempLinkLine.clear().lineStyle(2, 0x28a745, 0.8).moveTo(x1, y1).lineTo(pos.x, pos.y);
            };

            const onUp = async (ev) => {
                const pos = ev.getLocalPosition(world);
                tempLinkLine.clear();
                app.stage.off('pointermove', onMove);
                app.stage.off('pointerup', onUp);

                // Find valid target L anchor
                let hitToId = null;
                for (const col of phaseData.phase_columns) {
                    for (const t of col.targets) {
                        const tp = getTargetPos(col.id, t.slot);
                        const ax = tp.x + 8;
                        const ay = tp.y + TARGET_HEIGHT / 2;
                        if (Math.hypot(pos.x - ax, pos.y - ay) < 20) {
                            hitToId = t.id;
                            break;
                        }
                    }
                }

                if (hitToId && hitToId !== activeFromTargetId) {
                    const from = findTargetById(activeFromTargetId);
                    try {
                        const url = CONFIG.routes.saveLink.replace(':columnId', from.column.id).replace(':targetId', activeFromTargetId);
                        await $.post(url, { 
                            from_target_id: activeFromTargetId,
                            to_target_id: hitToId, 
                            _token: '{{ csrf_token() }}' 
                        });
                        await loadLinks();
                        render();
                    } catch (e) { alert(e.responseJSON?.message || 'Errore'); }
                }
                activeFromTargetId = null;
            };

            app.stage.on('pointermove', onMove);
            app.stage.on('pointerup', onUp);
        }

        function startDragTarget(e, target, column, cardGraphic) {
            console.log("Start dragging target", target.id);
            // Porta l'elemento in cima alla lista dei figli per renderlo sopra gli altri
            cardGraphic.parent.addChild(cardGraphic);
            
            draggingTarget = { target, column, originalSlot: target.slot, graphic: cardGraphic };
            cardGraphic.alpha = 0.8; // Leggera trasparenza per feedback visivo
            const p = getTargetPos(column.id, target.slot);
            const startPos = e.getLocalPosition(world);
            dragOffset = { x: startPos.x - p.x, y: startPos.y - p.y };

            const onMove = (ev) => {
                const pos = ev.getLocalPosition(world);
                draggingTarget.graphic.x = pos.x - dragOffset.x;
                draggingTarget.graphic.y = pos.y - dragOffset.y;
            };

            const onUp = async (ev) => {
                const pos = ev.getLocalPosition(world);
                console.log("Release target at y", pos.y);
                app.stage.off('pointermove', onMove);
                app.stage.off('pointerup', onUp);
                
                draggingTarget.graphic.alpha = 1.0;

                const colIndex = phaseData.phase_columns.findIndex(c => c.id === column.id);
                const colLeft = colIndex * COLUMN_WIDTH + SPACING;
                if (pos.x >= colLeft && pos.x <= colLeft + COLUMN_WIDTH) {
                    const newSlot = Math.floor((pos.y - HEADER_HEIGHT - SPACING) / (TARGET_HEIGHT + SPACING));
                    if (newSlot >= 0 && newSlot < CONFIG.height && newSlot !== draggingTarget.originalSlot) {
                        const url = CONFIG.routes.updateTarget.replace(':columnId', column.id).replace(':targetId', target.id).replace(':id', target.id);
                        try {
                            await $.ajax({ url, method: 'PUT', data: { slot: newSlot, _token: '{{ csrf_token() }}' } });
                            await softReload();
                        } catch(e) { console.error(e); }
                    }
                }
                draggingTarget = null;
                render();
            };

            app.stage.on('pointermove', onMove);
            app.stage.on('pointerup', onUp);
        }

        // Cost/Scores management
        async function refreshScoresTable(colId, targetId) {
            const url = CONFIG.routes.targetScoresIndex.replace(':columnId', colId).replace(':targetId', targetId);
            const res = await fetch(url);
            const json = await res.json();
            const tbody = $('#targetHasScoresTableBody').empty();
            json.target_has_scores.forEach(ths => {
                tbody.append(`<tr>
                    <td>${ths.id}</td>
                    <td>${ths.score.name}</td>
                    <td>${ths.value}</td>
                    <td>
                        <button class="btn btn-danger btn-xs" onclick="deleteTargetScore(${colId}, ${targetId}, ${ths.id})">Elimina</button>
                    </td>
                </tr>`);
            });

            // Re-bind modal buttons
            $('#addCostButton').off('click').on('click', () => openAddScoreModal(colId, targetId));
        }

        async function openAddScoreModal(colId, targetId) {
            const res = await fetch(CONFIG.routes.scoresIndex);
            const json = await res.json();
            const select = $('#scoreSelect').empty();
            json.forEach(s => select.append(`<option value="${s.id}">${s.name}</option>`));
            
            $('#addEditCostForm').off('submit').on('submit', async function(e) {
                e.preventDefault();
                const url = CONFIG.routes.storeTargetScore.replace(':columnId', colId).replace(':targetId', targetId);
                await $.post(url, $(this).serialize());
                $('#addEditCostModal').modal('hide');
                await refreshScoresTable(colId, targetId);
            });
            $('#addEditCostModal').modal('show');
        }

        window.deleteTargetScore = async (colId, targetId, id) => {
            if (!confirm('Eliminare costo?')) return;
            const url = CONFIG.routes.deleteTargetScore.replace(':columnId', colId).replace(':targetId', targetId).replace(':id', id);
            await $.ajax({ url, method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            await refreshScoresTable(colId, targetId);
        };

        $(document).on('click', '.js-add-column', async function() {
            try {
                await $.post(CONFIG.routes.addColumn, { uid: 'col-' + Date.now(), _token: '{{ csrf_token() }}' });
                await softReload();
            } catch (e) { console.error(e); }
        });

        init();
    </script>
@stop
