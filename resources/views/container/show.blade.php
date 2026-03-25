@extends('adminlte::page')

@section('title', 'Container Player')

@section('content_header')@stop

@section('content')
<style>
    #container-pixi {
        width: 100%;
        height: clamp(460px, 64vh, 780px);
        border: 1px solid #d8e0ea;
        border-radius: 14px;
        overflow-x: hidden;
        overflow-y: auto;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 32%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.14), transparent 28%),
            linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        position: relative;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.6), 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    #container-pixi canvas {
        display: block;
        width: 100%;
        height: 100%;
    }

    #container-pixi.is-scrollable {
        scrollbar-color: #94a3b8 #e2e8f0;
        scrollbar-width: thin;
    }

    #container-pixi.is-scrollable::-webkit-scrollbar {
        width: 10px;
    }

    #container-pixi.is-scrollable::-webkit-scrollbar-track {
        background: #e2e8f0;
        border-radius: 999px;
    }

    #container-pixi.is-scrollable::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 999px;
    }

    .container-info-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .player-summary-bar {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 10px 14px;
        margin-bottom: 12px;
    }

    .player-summary-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .player-summary-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 999px;
        background: #eef2f7;
        color: #0f172a;
        font-size: 11px;
        font-weight: 600;
    }

    .player-summary-item strong {
        color: #334155;
        font-weight: 700;
    }

    .container-stat-strip {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 12px;
    }

    .container-stat-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        padding: 10px 12px;
        min-height: 64px;
    }

    .container-stat-card .label {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 4px;
    }

    .container-stat-card .value {
        display: block;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .container-stat-card .hint {
        display: block;
        font-size: 11px;
        margin-top: 4px;
        color: #64748b;
    }

    .container-card-footer {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 9px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.14);
        color: #475569;
        letter-spacing: 0.02em;
    }

    .container-section-divider {
        margin: 12px 0 8px;
        border-top: 1px solid #e5e7eb;
        padding-top: 8px;
    }

    .container-section-divider .title {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #334155;
    }

    .container-section-divider .meta {
        font-size: 12px;
        color: #64748b;
    }

    .container-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        background: #e2e8f0;
        color: #334155;
    }

    .container-pill strong {
        color: #0f172a;
    }

    .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 11px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #f1f5f9;
        color: #0f172a;
        border: 1px solid #dbe4f0;
    }

    .status-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        display: inline-block;
        flex: 0 0 auto;
        box-shadow: 0 0 0 4px rgba(255,255,255,0.5);
    }

    .status-chip.is-running {
        background: rgba(22, 163, 74, 0.12);
        border-color: rgba(22, 163, 74, 0.24);
    }

    .status-chip.is-exited {
        background: rgba(220, 38, 38, 0.12);
        border-color: rgba(220, 38, 38, 0.24);
    }

    .status-chip.is-paused {
        background: rgba(217, 119, 6, 0.12);
        border-color: rgba(217, 119, 6, 0.24);
    }

    .status-chip.is-created {
        background: rgba(37, 99, 235, 0.12);
        border-color: rgba(37, 99, 235, 0.24);
    }

    .status-chip.is-unknown {
        background: rgba(100, 116, 139, 0.12);
        border-color: rgba(100, 116, 139, 0.24);
    }

    .container-toolbar {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .container-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
    }

    .container-toolbar-group-title {
        width: 100%;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 2px;
    }

    .container-toolbar-group .btn {
        min-width: 86px;
    }

    .container-toolbar-group .btn.flex-grow {
        flex: 1 1 108px;
    }

    .volume-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .volume-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px solid #e5e7eb;
        background: rgba(255, 255, 255, 0.85);
    }

    .volume-panel-title {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #0f172a;
        margin: 0;
    }

    .volume-panel-meta {
        font-size: 11px;
        color: #64748b;
    }

    .btn-volume-refresh {
        padding: 0.18rem 0.45rem;
        line-height: 1;
    }

    .volume-panel-body {
        padding: 10px 12px;
    }

    .volume-file-list {
        max-height: 220px;
        overflow: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }

    .volume-file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 10px;
        border-bottom: 1px solid #edf2f7;
        font-size: 11px;
    }

    .volume-file-item:last-child {
        border-bottom: 0;
    }

    .volume-file-item.is-clickable {
        cursor: pointer;
        transition: background-color 0.15s ease, transform 0.15s ease;
    }

    .volume-file-item.is-clickable:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }

    .volume-file-path {
        font-family: monospace;
        color: #0f172a;
        word-break: break-all;
    }

    .volume-file-size {
        flex: 0 0 auto;
        color: #64748b;
        font-weight: 700;
        white-space: nowrap;
    }

    .exec-preset-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    .container-actions-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        background: #ffffff;
    }

    .container-actions-bar .form-control,
    .container-actions-bar .custom-select {
        min-width: 160px;
    }

    .container-actions-bar .btn {
        white-space: nowrap;
    }

    .container-issue-toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0 8px;
        min-height: 32px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        user-select: none;
    }

    .inspect-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .inspect-summary-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #f8fafc;
        padding: 10px 12px;
    }

    .inspect-summary-item .label {
        display: block;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 4px;
    }

    .inspect-summary-item .value {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        word-break: break-word;
    }

    .logs-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-bottom: 12px;
    }

    .logs-toolbar .btn.active {
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.15);
    }

    @media (max-width: 991.98px) {
        .container-stat-strip {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #container-pixi {
            height: clamp(420px, 58vh, 700px);
        }
    }

    @media (max-width: 767.98px) {
        .container-stat-strip {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        #container-pixi {
            height: clamp(380px, 52vh, 620px);
        }
    }
</style>

<div class="player-summary-bar">
    <div class="player-summary-row">
        <span class="player-summary-item">Player <strong>#{{ $player->id }}</strong></span>
        <span class="player-summary-item">Utente <strong>{{ optional($player->user)->name ?? '-' }}</strong></span>
        <span class="player-summary-item">Birth Region <strong>{{ optional($player->birthRegion)->name ?? $player->birth_region_id ?? '-' }}</strong></span>
        <a href="{{ route('containers.index') }}" class="btn btn-danger btn-sm ml-auto">
            <i class="fa fa-backward"></i> Indietro
        </a>
    </div>
</div>

<div class="container-stat-strip">
    <div class="container-stat-card">
        <span class="label">Container visibili</span>
        <span class="value" id="stat-visible-count">0</span>
        <span class="hint">Filtrati nella vista corrente</span>
    </div>
    <div class="container-stat-card">
        <span class="label">Running</span>
        <span class="value" id="stat-running-count">0</span>
        <span class="hint">Container attivi</span>
    </div>
    <div class="container-stat-card">
        <span class="label">Stopped</span>
        <span class="value" id="stat-stopped-count">0</span>
        <span class="hint">Exited / Paused / Created</span>
    </div>
    <div class="container-stat-card">
        <span class="label">Problemi</span>
        <span class="value" id="stat-issue-count">0</span>
        <span class="hint">Stato non running</span>
    </div>
    <div class="container-stat-card">
        <span class="label">Ultimo refresh</span>
        <span class="value" id="stat-refresh-age">-</span>
        <span class="hint" id="stat-refresh-label">In attesa</span>
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-3 mb-3 mb-xl-0">
        <div class="card container-info-panel mt-3">
            <div class="card-header pb-0">
                <h4 class="mb-0" style="font-size: 1rem;">Container selezionato</h4>
            </div>
            <div class="card-body p-3">
                <div class="mb-2">
                    <div class="container-pill mb-2">Nome: <strong id="selected-container-name">-</strong></div>
                    <div class="container-pill mb-2">Tipo: <strong id="selected-container-type">-</strong></div>
                    <div class="container-pill mb-2">Scope: <strong id="selected-container-scope">-</strong></div>
                    <div class="container-pill mb-2">Container ID: <strong id="selected-container-id">-</strong></div>
                    <div class="container-pill mb-2">WS Port: <strong id="selected-container-port">-</strong></div>
                    <div class="status-chip is-unknown mb-2" id="selected-container-status-chip">
                        <span class="status-dot" id="selected-container-status-dot" style="background: #64748b;"></span>
                        <span id="selected-container-status">-</span>
                    </div>
                    <div class="d-flex flex-wrap" style="gap: 8px;">
                        <button type="button" class="btn btn-light btn-sm js-copy-field" data-target="selected-container-id" data-label="Container ID" disabled>
                            <i class="fa fa-copy"></i> ID
                        </button>
                        <button type="button" class="btn btn-light btn-sm js-copy-field" data-target="selected-container-port" data-label="WS Port" disabled>
                            <i class="fa fa-copy"></i> Port
                        </button>
                        <button type="button" class="btn btn-light btn-sm js-copy-exec" disabled>
                            <i class="fa fa-copy"></i> Exec cmd
                        </button>
                    </div>
                </div>

                <div class="container-toolbar">
                    <div class="container-toolbar-group">
                        <div class="container-toolbar-group-title">Debug</div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-selected-exec flex-grow" disabled>
                            <i class="fa fa-terminal"></i> Exec
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm js-selected-logs flex-grow" disabled>
                            <i class="fa fa-list"></i> Logs
                        </button>
                        <button type="button" class="btn btn-dark btn-sm js-selected-inspect flex-grow" disabled>
                            <i class="fa fa-search"></i> Inspect
                        </button>
                    </div>

                    <div class="container-toolbar-group">
                        <div class="container-toolbar-group-title">Actions</div>
                        <button type="button" class="btn btn-success btn-sm js-selected-action flex-grow" data-action="start" disabled>
                            <i class="fa fa-play"></i> Start
                        </button>
                        <button type="button" class="btn btn-warning btn-sm js-selected-action flex-grow" data-action="stop" disabled>
                            <i class="fa fa-stop"></i> Stop
                        </button>
                        <button type="button" class="btn btn-info btn-sm js-selected-action flex-grow" data-action="restart" disabled>
                            <i class="fa fa-sync"></i> Restart
                        </button>
                        <button type="button" class="btn btn-danger btn-sm js-selected-delete flex-grow" disabled>
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="volume-panel mt-2">
                    <div class="volume-panel-header">
                        <div>
                            <h5 class="volume-panel-title mb-1">Volume player</h5>
                            <div class="volume-panel-meta" id="player-volume-name">{{ $volume['name'] ?? '-' }}</div>
                        </div>
                        <div class="d-flex align-items-center" style="gap: 8px;">
                            <div class="volume-panel-meta">
                                <span id="player-volume-count">{{ $volume['file_count'] ?? 0 }}</span> file
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm btn-volume-refresh" id="refresh-volume" title="Aggiorna volume">
                                <i class="fa fa-redo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="volume-panel-body">
                        <div class="volume-file-list" id="player-volume-files">
                            @forelse(($volume['files'] ?? []) as $file)
                                <div class="volume-file-item">
                                    <div class="volume-file-path">{{ $file['path'] }}</div>
                                    <div class="volume-file-size">{{ number_format((int) $file['size']) }} B</div>
                                </div>
                            @empty
                                <div class="p-3 text-muted small" id="player-volume-empty">
                                    Nessun file nel volume del player.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-9">
        <div class="card container-info-panel mt-3">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0" style="font-size: 1rem;">Container Docker</h4>
                        <small class="text-muted">{{ count($containers) }} container caricati</small>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 10px;">
                        <small class="text-muted" id="container-last-updated">Aggiornamento in corso...</small>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="refresh-pixi">
                            <i class="fa fa-redo"></i> Ricarica
                        </button>
                    </div>
                </div>
            </div>
            <div class="container-actions-bar" style="padding: 10px;">
                <input type="text" class="form-control form-control-sm" id="container-search" placeholder="Cerca nome, ID, porta">
                <select class="custom-select custom-select-sm" id="filter-status" style="max-width: 160px;">
                    <option value="all">Tutti gli stati</option>
                    <option value="running">Running</option>
                    <option value="exited">Exited</option>
                    <option value="paused">Paused</option>
                    <option value="created">Created</option>
                    <option value="unknown">Unknown</option>
                </select>
                <select class="custom-select custom-select-sm" id="filter-type" style="max-width: 180px;">
                    <option value="all">Tutti i tipi</option>
                    <option value="Player">Player</option>
                    <option value="Map">Map</option>
                    <option value="Objective">Objective</option>
                    <option value="Entity">Entity</option>
                    <option value="ElementHasPosition">Element</option>
                </select>
                <label class="container-issue-toggle mb-0">
                    <input type="checkbox" id="filter-issues-only">
                    Solo problemi
                </label>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="clear-filters">
                    <i class="fa fa-eraser"></i> Reset filtri
                </button>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-success" id="bulk-start-visible">
                        <i class="fa fa-play"></i> Start visibili
                    </button>
                    <button type="button" class="btn btn-warning" id="bulk-stop-visible">
                        <i class="fa fa-stop"></i> Stop visibili
                    </button>
                    <button type="button" class="btn btn-info" id="bulk-restart-visible">
                        <i class="fa fa-sync"></i> Restart visibili
                    </button>
                    <button type="button" class="btn btn-danger" id="bulk-delete-visible">
                        <i class="fa fa-trash"></i> Delete visibili
                    </button>
                </div>
                <span class="ml-auto text-muted small" id="visible-count">0 visibili</span>
            </div>
            <div class="card-body p-2">
                <div id="container-pixi"></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script src="https://pixijs.download/v7.4.2/pixi.min.js"></script>
    <script>
        const PLAYER_ID = {{ $player->id }};
        const INITIAL_CONTAINERS = @json($containers);
        const ACTION_URLS = {
            start: "{{ route('containers.start', ['_id_']) }}",
            stop: "{{ route('containers.stop', ['_id_']) }}",
            restart: "{{ route('containers.restart', ['_id_']) }}",
            delete: "{{ route('containers.delete') }}",
            bulk: "{{ route('containers.bulk-action') }}",
        };
        const SNAPSHOT_URL = "{{ route('containers.snapshot', $player) }}";
        const VOLUME_FILE_URL = "{{ route('containers.volume-file', $player) }}";
        const TYPE_ORDER = ['Player', 'Map', 'Objective', 'Entity', 'ElementHasPosition'];

        const containerHost = document.getElementById('container-pixi');
        let app = null;
        let selectedContainer = null;
        let selectedCard = null;
        let cardRegistry = [];
        let resizeTimer = null;
        let refreshTimer = null;
        let logsRefreshTimer = null;
        let refreshInFlight = false;
        let lastRefreshAt = null;
        let containersState = INITIAL_CONTAINERS.slice();
        let filters = {
            query: '',
            status: 'all',
            type: 'all',
            issuesOnly: false,
        };
        let lastUpdatedText = null;
        let emptyText = null;
        let logsModalContainerId = null;
        let logsTail = 200;
        let playerVolumeState = @json($volume);
        const refreshVolumeButton = document.getElementById('refresh-volume');

        function shortId(value) {
            if (!value) return '-';
            const text = String(value);
            return text.length > 14 ? text.slice(0, 11) + '...' : text;
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function typeLabel(type) {
            const labels = {
                'Player': 'Player',
                'Map': 'Map',
                'Objective': 'Objective',
                'Entity': 'Entity',
                'ElementHasPosition': 'Element',
            };
            return labels[type] || type;
        }

        function typeOrder(type) {
            const index = TYPE_ORDER.indexOf(type);
            return index === -1 ? TYPE_ORDER.length : index;
        }

        function isProblematic(container) {
            const status = normalizeValue(container && container.status);
            return status !== 'running';
        }

        function normalizeValue(value) {
            return String(value || '').toLowerCase();
        }

        function getStatusCounters(containers) {
            return containers.reduce(function (acc, container) {
                const status = normalizeValue(container.status);
                if (status === 'running') {
                    acc.running += 1;
                } else {
                    acc.stopped += 1;
                }
                if (status !== 'running') {
                    acc.issues += 1;
                }
                return acc;
            }, { running: 0, stopped: 0, issues: 0 });
        }

        function updateStatsPanel(visibleContainers) {
            const counters = getStatusCounters(visibleContainers);
            const total = visibleContainers.length;
            const ageSeconds = lastRefreshAt ? Math.max(0, Math.round((Date.now() - lastRefreshAt.getTime()) / 1000)) : null;

            const setNode = function (id, value) {
                const node = document.getElementById(id);
                if (node) {
                    node.textContent = value;
                }
            };

            setNode('stat-visible-count', String(total));
            setNode('stat-running-count', String(counters.running));
            setNode('stat-stopped-count', String(counters.stopped));
            setNode('stat-issue-count', String(counters.issues));
            setNode('stat-refresh-age', ageSeconds === null ? '-' : (ageSeconds + 's'));
            setNode('stat-refresh-label', lastRefreshAt ? 'Aggiornato ora' : 'In attesa');
        }

        function groupContainers(containers) {
            const groups = TYPE_ORDER.map(function (type) {
                return {
                    type: type,
                    label: typeLabel(type),
                    items: [],
                };
            });

            containers.forEach(function (container) {
                const group = groups.find(function (item) {
                    return item.type === container.parent_type;
                }) || null;

                if (group) {
                    group.items.push(container);
                } else {
                    groups.push({
                        type: container.parent_type || 'unknown',
                        label: typeLabel(container.parent_type || 'unknown'),
                        items: [container],
                    });
                }
            });

            return groups.filter(function (group) {
                return group.items.length > 0;
            }).map(function (group) {
                group.items.sort(function (a, b) {
                    const aScore = normalizeValue(a.scope || a.name || '');
                    const bScore = normalizeValue(b.scope || b.name || '');
                    if (aScore < bScore) return -1;
                    if (aScore > bScore) return 1;
                    return (a.id || 0) - (b.id || 0);
                });
                return group;
            });
        }

        function getVisibleContainers() {
            return containersState.filter(function (container) {
                const matchesQuery = !filters.query || [
                    container.name,
                    container.container_id,
                    container.ws_port,
                    container.scope,
                    container.parent_type,
                    container.status,
                    container.status_label
                ].some(function (value) {
                    return normalizeValue(value).includes(filters.query);
                });

                const matchesStatus = filters.status === 'all' || normalizeValue(container.status) === normalizeValue(filters.status);
                const matchesType = filters.type === 'all' || container.parent_type === filters.type;
                const matchesIssues = !filters.issuesOnly || isProblematic(container);

                return matchesQuery && matchesStatus && matchesType && matchesIssues;
            });
        }

        function getCardLayout() {
            const gapX = 16;
            const gapY = 16;
            const availableWidth = Math.max(containerHost.clientWidth - 24, 320);
            const availableHeight = Math.max(containerHost.clientHeight - 24, 320);

            let columns = 1;
            if (availableWidth >= 1600) {
                columns = 5;
            } else if (availableWidth >= 1280) {
                columns = 4;
            } else if (availableWidth >= 1024) {
                columns = 3;
            } else if (availableWidth >= 680) {
                columns = 2;
            }

            const cardWidth = Math.max(180, Math.min(300, Math.floor((availableWidth - ((columns - 1) * gapX)) / columns)));
            const cardHeight = Math.max(112, Math.min(160, Math.round(cardWidth * 0.52)));
            const cardsPerPage = Math.max(1, Math.floor((availableHeight + gapY) / (cardHeight + gapY)));

            return { cardWidth, cardHeight, gapX, gapY, columns, cardsPerPage };
        }

        function clearStage() {
            if (!app) return;
            const keep = emptyText ? [emptyText] : [];
            app.stage.children.slice().forEach((child) => {
                if (keep.includes(child)) {
                    return;
                }
                app.stage.removeChild(child);
            });
            cardRegistry.forEach(({ card }) => {
                if (card && card.parent) {
                    card.parent.removeChild(card);
                }
            });
            cardRegistry = [];
        }

        function resizeCanvas(contentHeight) {
            if (!app) return;

            const width = Math.max(containerHost.clientWidth, 320);
            const fallbackHeight = Math.max(containerHost.clientHeight, 320);
            const targetHeight = Math.max(contentHeight || 0, fallbackHeight);
            const maxScrollTop = Math.max(targetHeight - fallbackHeight, 0);

            app.renderer.resize(width, targetHeight);
            app.view.style.width = '100%';
            app.view.style.height = targetHeight + 'px';
            containerHost.classList.toggle('is-scrollable', targetHeight > fallbackHeight);
            if (containerHost.scrollTop > maxScrollTop) {
                containerHost.scrollTop = maxScrollTop;
            }
        }

        function scrollSelectedIntoView(selectedCard) {
            if (!selectedCard || !selectedCard.data) {
                return;
            }

            const fallbackHeight = Math.max(containerHost.clientHeight, 320);
            const visibleTop = containerHost.scrollTop;
            const visibleBottom = visibleTop + fallbackHeight;
            const cardTop = selectedCard.data.y;
            const cardBottom = selectedCard.data.y + getCardLayout().cardHeight + 20;

            if (cardTop < visibleTop + 12) {
                containerHost.scrollTop = Math.max(cardTop - 24, 0);
            } else if (cardBottom > visibleBottom - 12) {
                containerHost.scrollTop = Math.max(cardBottom - fallbackHeight + 24, 0);
            }
        }

        function centerEmptyText(textObject) {
            if (!textObject || !app) {
                return;
            }

            textObject.x = app.renderer.width / 2;
            textObject.y = 36;
        }

        function updateSelectedDetails(container) {
            const fields = {
                name: document.getElementById('selected-container-name'),
                type: document.getElementById('selected-container-type'),
                scope: document.getElementById('selected-container-scope'),
                id: document.getElementById('selected-container-id'),
                port: document.getElementById('selected-container-port'),
                status: document.getElementById('selected-container-status'),
                statusChip: document.getElementById('selected-container-status-chip'),
                statusDot: document.getElementById('selected-container-status-dot'),
            };

            if (!container) {
                fields.name.textContent = '-';
                fields.type.textContent = '-';
                fields.scope.textContent = '-';
                fields.id.textContent = '-';
                fields.port.textContent = '-';
                fields.status.textContent = '-';
                fields.statusChip.className = 'status-chip is-unknown mb-2';
                fields.statusDot.style.background = '#64748b';
                document.querySelectorAll('.js-selected-action, .js-selected-delete, .js-selected-logs, .js-selected-inspect, .js-selected-exec, .js-copy-field, .js-copy-exec').forEach((btn) => btn.disabled = true);
                return;
            }

            fields.name.textContent = container.name || '-';
            fields.type.textContent = typeLabel(container.parent_type);
            fields.scope.textContent = container.scope || '-';
            fields.id.textContent = shortId(container.container_id);
            fields.port.textContent = container.ws_port || '-';
            fields.status.textContent = container.status_label || 'Unknown';
            fields.statusChip.className = 'status-chip is-' + String(container.status || 'unknown').toLowerCase() + ' mb-2';
            fields.statusDot.style.background = container.status_color || '#64748b';
            document.querySelectorAll('.js-selected-action, .js-selected-delete, .js-selected-logs, .js-selected-inspect, .js-selected-exec, .js-copy-field, .js-copy-exec').forEach((btn) => btn.disabled = false);
        }

        function updatePlayerVolume(volume) {
            playerVolumeState = volume || { name: '-', files: [], file_count: 0 };

            const nameNode = document.getElementById('player-volume-name');
            const countNode = document.getElementById('player-volume-count');
            const listNode = document.getElementById('player-volume-files');

            if (nameNode) {
                nameNode.textContent = playerVolumeState.name || '-';
            }

            if (countNode) {
                countNode.textContent = String(playerVolumeState.file_count || 0);
            }

            if (!listNode) {
                return;
            }

            const files = Array.isArray(playerVolumeState.files) ? playerVolumeState.files : [];
            if (files.length === 0) {
                listNode.innerHTML = '<div class="p-3 text-muted small" id="player-volume-empty">Nessun file nel volume del player.</div>';
                return;
            }

            listNode.innerHTML = files.map(function (file) {
                const size = Number(file.size || 0);
                return [
                    '<div class="volume-file-item is-clickable js-volume-file" data-path="' + escapeHtml(file.path || '') + '">',
                    '  <div class="volume-file-path">' + escapeHtml(file.path || '-') + '</div>',
                    '  <div class="volume-file-size">' + escapeHtml(size.toLocaleString('it-IT')) + ' B</div>',
                    '</div>'
                ].join('');
            }).join('');
        }

        function loadVolumeFile() {
            Swal.fire({
                title: 'Volume player',
                html: [
                    '<div class="d-flex justify-content-between align-items-center mb-2">',
                    '  <small class="text-muted" id="volume-file-meta">Caricamento contenuto in corso...</small>',
                    '  <button type="button" class="btn btn-outline-primary btn-sm" id="copy-volume-file" disabled>',
                    '    <i class="fa fa-copy"></i> Copy',
                    '  </button>',
                    '</div>',
                    '<textarea id="volume-file-content" readonly class="form-control" style="min-height: 520px; font-family: monospace; white-space: pre; overflow: auto;">Caricamento...</textarea>'
                ].join(''),
                width: 1200,
                showConfirmButton: true,
                confirmButtonText: 'Chiudi',
                confirmButtonClass: 'btn btn-primary',
                didOpen: function () {
                    const popup = Swal.getPopup ? Swal.getPopup() : null;
                    if (!popup) return;

                    $.ajax({
                        url: VOLUME_FILE_URL,
                        type: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (!response || !response.success) {
                                return;
                            }

                            const textarea = popup.querySelector('#volume-file-content');
                            const copyButton = popup.querySelector('#copy-volume-file');
                            const titleNode = popup.querySelector('.swal2-title');
                            const metaNode = popup.querySelector('#volume-file-meta');

                            if (titleNode && response.path) {
                                titleNode.textContent = 'Volume file: ' + response.path;
                            }
                            if (metaNode) {
                                metaNode.textContent = 'Size: ' + Number(response.size || 0).toLocaleString('it-IT') + ' B';
                            }
                            if (textarea) {
                                textarea.value = response.content || '(file vuoto)';
                            }
                            if (copyButton && textarea) {
                                copyButton.disabled = false;
                                copyButton.addEventListener('click', function () {
                                    copyText(textarea.value || '');
                                });
                            }
                        },
                        error: function (xhr) {
                            const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Impossibile leggere il file del volume.';
                            const textarea = popup.querySelector('#volume-file-content');
                            const metaNode = popup.querySelector('#volume-file-meta');
                            if (textarea) {
                                textarea.value = message;
                            }
                            if (metaNode) {
                                metaNode.textContent = 'Errore di lettura';
                            }
                        }
                    });
                }
            });
        }

        function refreshVolume() {
            if (refreshInFlight) {
                return;
            }
            refreshContainers(false);
        }

        function drawCard(container, x, y, idx) {
            const { cardWidth: width, cardHeight: height } = getCardLayout();
            const compact = width < 220;
            const card = new PIXI.Container();
            card.x = x;
            card.y = y;
            card.eventMode = 'static';
            card.cursor = 'pointer';
            const cardRadius = 18;
            const accentInset = 2;
            const accentRadius = 10;

            const bg = new PIXI.Graphics();
            bg.beginFill(0xffffff, 0.95);
            bg.drawRoundedRect(0, 0, width, height, cardRadius);
            bg.endFill();
            bg.lineStyle(2, 0xdbe4f0, 1);
            bg.drawRoundedRect(0, 0, width, height, cardRadius);
            card.addChild(bg);

            const accent = new PIXI.Graphics();
            accent.beginFill(container.color || 0x64748b, 1);
            accent.drawRoundedRect(accentInset, accentInset, 12 - (accentInset * 2), height - (accentInset * 2), accentRadius);
            accent.endFill();
            card.addChild(accent);

            const badgeLabel = (container.type_label || typeLabel(container.parent_type)) + ': #' + (container.parent_id || container.id || '-');
            const badge = new PIXI.Text(badgeLabel, {
                fontFamily: 'Arial',
                fontSize: compact ? 10 : 11,
                fontWeight: '700',
                fill: container.color || 0x64748b,
            });
            const badgeBg = new PIXI.Graphics();
            const badgeWidth = Math.max(86, Math.min(140, badge.width + 18));
            badgeBg.lineStyle(2, container.color || 0x64748b, 1);
            badgeBg.drawRoundedRect(24, 12, badgeWidth, 24, 999);
            card.addChild(badgeBg);
            badge.x = 24 + 9;
            badge.y = 18;
            card.addChild(badge);

            const statusValue = container.status_label || 'Unknown';
            const statusBg = new PIXI.Graphics();
            const statusText = new PIXI.Text(statusValue, {
                fontFamily: 'Arial',
                fontSize: compact ? 9 : 10,
                fontWeight: '700',
                fill: 0xffffff,
            });
            const statusWidth = Math.max(82, Math.min(width - 160, statusText.width + 24));
            statusBg.beginFill(container.status_color || 0x64748b, 1);
            statusBg.drawRoundedRect(width - statusWidth - 14, 12, statusWidth, 24, 999);
            statusBg.endFill();
            card.addChild(statusBg);

            const statusDot = new PIXI.Graphics();
            statusDot.beginFill(0xffffff, 1);
            statusDot.drawCircle(5, 5, 4);
            statusDot.endFill();
            statusDot.x = width - statusWidth - 14 + 9;
            statusDot.y = 18;
            card.addChild(statusDot);

            statusText.x = width - statusWidth - 14 + 21;
            statusText.y = 18;
            card.addChild(statusText);

            const meta = new PIXI.Text(
                [
                    'WS: ' + (container.ws_port || '-'),
                ].join('\n'),
                {
                    fontFamily: 'Arial',
                    fontSize: compact ? 11 : 12,
                    fill: 0x475569,
                    lineHeight: compact ? 15 : 16,
                }
            );
            meta.x = 24;
            meta.y = 44;
            card.addChild(meta);

            if (!compact) {
                const footer = new PIXI.Text('Clicca per gestire', {
                    fontFamily: 'Arial',
                    fontSize: 10,
                    fontWeight: '700',
                    fill: 0x475569,
                });
                footer.anchor.set(0.5, 0.5);
                footer.x = 18 + ((footer.width + 18) / 2);
                footer.y = height - 17;
                card.addChild(footer);

                const footerBg = new PIXI.Graphics();
                footerBg.beginFill(0xe2e8f0, 1);
                footerBg.drawRoundedRect(0, 0, footer.width + 18, 22, 999);
                footerBg.endFill();
                footerBg.x = 18;
                footerBg.y = height - 28;
                card.addChild(footerBg);
                footer.zIndex = footerBg.zIndex + 1;
                card.setChildIndex(footerBg, card.children.length - 2);
                card.setChildIndex(footer, card.children.length - 1);
            }

            const selectedStroke = new PIXI.Graphics();
            selectedStroke.lineStyle(0, 0x2563eb, 0);
            selectedStroke.drawRoundedRect(0, 0, width, height, cardRadius);
            card.addChild(selectedStroke);

            card.on('pointertap', () => {
                selectedContainer = container;
                cardRegistry.forEach(({ selectedStroke: stroke, card: otherCard, data }) => {
                    const active = data.id === container.id;
                    stroke.clear();
                    stroke.lineStyle(active ? 4 : 0, active ? 0x2563eb : 0x000000, 1);
                    stroke.drawRoundedRect(0, 0, width, height, cardRadius);
                    otherCard.scale.set(active ? 1.02 : 1);
                });
                updateSelectedDetails(container);
                scheduleRefresh();
            });

            cardRegistry.push({ card, selectedStroke, data: container });
            app.stage.addChild(card);
        }

        function drawSectionHeader(group, x, y, width, issueCount) {
            const container = new PIXI.Container();
            container.x = x;
            container.y = y;

            const background = new PIXI.Graphics();
            background.beginFill(0xffffff, 0.82);
            background.drawRoundedRect(0, 0, width, 34, 12);
            background.endFill();
            background.lineStyle(1, 0xdbe4f0, 1);
            background.drawRoundedRect(0, 0, width, 34, 12);
            container.addChild(background);

            const title = new PIXI.Text(group.label, {
                fontFamily: 'Arial',
                fontSize: 13,
                fontWeight: '800',
                fill: 0x0f172a,
            });
            title.x = 14;
            title.y = 9;
            container.addChild(title);

            const counter = new PIXI.Text(String(group.items.length) + ' container', {
                fontFamily: 'Arial',
                fontSize: 11,
                fill: 0x64748b,
            });
            counter.x = width - counter.width - 14;
            counter.y = 11;
            container.addChild(counter);

            if (issueCount > 0) {
                const issue = new PIXI.Text(String(issueCount) + ' issue', {
                    fontFamily: 'Arial',
                    fontSize: 11,
                    fontWeight: '700',
                    fill: 0xdc2626,
                });
                issue.x = Math.max(120, width - counter.width - issue.width - 24);
                issue.y = 11;
                container.addChild(issue);
            }

            app.stage.addChild(container);
            return 42;
        }

        function layoutCards() {
            if (!app) return;
            clearStage();

            const visibleContainers = getVisibleContainers();
            const groupedContainers = groupContainers(visibleContainers);
            const { cardWidth: width, cardHeight: height, gapX, gapY, columns } = getCardLayout();
            const startX = 12;
            const startY = 12;
            const availableWidth = Math.max(containerHost.clientWidth - 24, 320);
            let cursorY = startY;

            groupedContainers.forEach(function (group) {
                const issueCount = group.items.filter(isProblematic).length;
                cursorY += drawSectionHeader(group, startX, cursorY, availableWidth, issueCount);

                group.items.forEach(function (container, index) {
                    const col = index % columns;
                    const row = Math.floor(index / columns);
                    const x = startX + col * (width + gapX);
                    const y = cursorY + row * (height + gapY);
                    drawCard(container, x, y, index);
                });

                const rows = Math.max(1, Math.ceil(group.items.length / columns));
                cursorY += rows * (height + gapY) + 14;
            });

            const contentHeight = Math.max(cursorY + 20, 320);
            resizeCanvas(contentHeight);
            app.stage.hitArea = new PIXI.Rectangle(0, 0, app.renderer.width, contentHeight);

            if (selectedContainer && !visibleContainers.some(function (container) {
                return container.id === selectedContainer.id;
            })) {
                selectedContainer = visibleContainers[0] || null;
            }

            if (!selectedContainer && visibleContainers.length > 0) {
                selectedContainer = visibleContainers[0];
            }

            const selectedCard = selectedContainer
                ? cardRegistry.find(({ data }) => data.id === selectedContainer.id)
                : null;

            if (selectedCard) {
                selectedCard.selectedStroke.clear();
                selectedCard.selectedStroke.lineStyle(4, 0x2563eb, 1);
                selectedCard.selectedStroke.drawRoundedRect(0, 0, width, height, 18);
                selectedCard.card.scale.set(1.02);
                selectedContainer = selectedCard.data;
                scrollSelectedIntoView(selectedCard);
            }

            if (emptyText) {
                emptyText.text = visibleContainers.length === 0 ? 'Nessun container trovato' : (containerHost.clientWidth < 700 ? 'Tocca un container' : 'Seleziona un container');
                centerEmptyText(emptyText);
            }

            updateSelectedDetails(selectedContainer);
            updateVisibleCount(visibleContainers.length);
            updateStatsPanel(visibleContainers);
        }

        function setLastUpdated(text, markTimestamp = true) {
            lastUpdatedText = text;
            if (markTimestamp) {
                lastRefreshAt = new Date();
            }
            const node = document.getElementById('container-last-updated');
            if (node) {
                node.textContent = text;
            }
            updateStatsPanel(getVisibleContainers());
        }

        function updateVisibleCount(count) {
            const node = document.getElementById('visible-count');
            if (node) {
                node.textContent = count + ' visibili';
            }
        }

        function refreshCards(newContainers) {
            const selectedId = selectedContainer ? selectedContainer.id : null;
            containersState = Array.isArray(newContainers) ? newContainers : [];
            selectedContainer = selectedId ? containersState.find((item) => item.id === selectedId) || null : (containersState[0] || null);
            layoutCards();
            scheduleRefresh();
        }

        function refreshContainers(silent) {
            if (refreshInFlight) {
                return Promise.resolve(false);
            }

            refreshInFlight = true;
            return $.ajax({
                url: SNAPSHOT_URL,
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response && response.success) {
                        refreshCards(response.containers || []);
                        updatePlayerVolume(response.volume || playerVolumeState);
                        setLastUpdated(response.updated_at ? ('Aggiornato: ' + response.updated_at) : 'Aggiornato');
                    }
                },
                error: function () {
                    if (!silent) {
                        Swal.fire({
                            title: 'Ops!',
                            text: 'Impossibile aggiornare lo stato dei container.',
                            type: 'danger',
                            confirmButtonClass: 'btn btn-info'
                        });
                    }
                },
                complete: function () {
                    refreshInFlight = false;
                }
            });
        }

        function scheduleRefresh() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }

            const intervalMs = selectedContainer ? 6000 : 12000;
            refreshTimer = setInterval(function () {
                refreshContainers(true);
            }, intervalMs);
        }

        function openTextModal(title, content, width) {
            Swal.fire({
                title: title,
                html: '<textarea readonly class="form-control" style="min-height: 420px; font-family: monospace; white-space: pre; overflow: auto;">' + escapeHtml(content) + '</textarea>',
                width: width || 900,
                confirmButtonText: 'Chiudi',
                confirmButtonClass: 'btn btn-primary',
            });
        }

        function copyText(text) {
            const value = String(text || '');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(value);
            }

            const fallback = document.createElement('textarea');
            fallback.value = value;
            fallback.setAttribute('readonly', 'true');
            fallback.style.position = 'absolute';
            fallback.style.left = '-9999px';
            document.body.appendChild(fallback);
            fallback.select();
            document.execCommand('copy');
            document.body.removeChild(fallback);
            return Promise.resolve();
        }

        function buildInspectSummary(inspect) {
            const state = inspect && inspect.State ? inspect.State : {};
            const config = inspect && inspect.Config ? inspect.Config : {};
            const networkSettings = inspect && inspect.NetworkSettings ? inspect.NetworkSettings : {};
            const hostConfig = inspect && inspect.HostConfig ? inspect.HostConfig : {};
            const health = state && state.Health ? state.Health : null;
            const mounts = Array.isArray(inspect && inspect.Mounts) ? inspect.Mounts : [];
            const env = Array.isArray(config.Env) ? config.Env : [];
            const healthValue = health && health.Status
                ? String(health.Status).charAt(0).toUpperCase() + String(health.Status).slice(1)
                : 'No healthcheck defined';

            return [
                { label: 'Image', value: config.Image || '-' },
                { label: 'Status', value: state.Status || '-' },
                { label: 'Healthcheck', value: healthValue, muted: !health },
                { label: 'Created', value: inspect.Created || '-' },
                { label: 'Path', value: inspect.Path || '-' },
                { label: 'Args', value: Array.isArray(inspect.Args) ? inspect.Args.join(' ') : '-' },
                { label: 'Network', value: hostConfig.NetworkMode || '-' },
                { label: 'Mounts', value: String(mounts.length) },
                { label: 'Env', value: String(env.length) },
                { label: 'IP', value: networkSettings.IPAddress || '-' },
            ];
        }

        function renderExecPresetButtons(containerName) {
            const presets = [
                { label: 'ps aux', command: 'ps aux' },
                { label: 'env', command: 'env | sort' },
                { label: 'ports', command: 'netstat -tulpn 2>/dev/null || ss -tulpn' },
            ];

            return [
                '<div class="exec-preset-row">',
                '<div class="w-100 text-muted small mb-1">Preset rapidi</div>',
                presets.map(function (preset) {
                    return '<button type="button" class="btn btn-light btn-sm js-exec-preset-modal" data-command="' + escapeHtml(preset.command) + '">' + escapeHtml(preset.label) + '</button>';
                }).join(''),
                '</div>'
            ].join('');
        }

        function stopLogsRefresh() {
            if (logsRefreshTimer) {
                clearInterval(logsRefreshTimer);
                logsRefreshTimer = null;
            }
            logsModalContainerId = null;
        }

        function updateLogsModal(content, title) {
            const popup = Swal.getPopup ? Swal.getPopup() : null;
            if (!popup) {
                return;
            }

            const textarea = popup.querySelector('#container-logs-content');
            const titleNode = popup.querySelector('#container-logs-title');
            if (textarea) {
                textarea.value = content || '(nessun log)';
                textarea.scrollTop = textarea.scrollHeight;
            }
            if (titleNode && title) {
                titleNode.textContent = title;
            }
        }

        function fetchLogsContent(containerId, tail) {
            return $.ajax({
                url: "{{ route('containers.logs', ['container' => '_id_']) }}".replace('_id_', containerId) + '?tail=' + encodeURIComponent(tail || logsTail || 200),
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }

        function startLogsPolling(containerId) {
            stopLogsRefresh();
            logsModalContainerId = containerId;

            const loadLogs = function () {
                if (logsModalContainerId !== containerId) {
                    return;
                }

                fetchLogsContent(containerId, logsTail)
                    .done(function (response) {
                        if (response && response.success) {
                            updateLogsModal(response.logs || '(nessun log)', 'Logs: ' + (response.name || selectedContainer.name || 'Container') + ' - tail ' + (response.tail || logsTail));
                        }
                    })
                    .fail(function () {
                        updateLogsModal('Impossibile leggere i log del container.', 'Logs: ' + (selectedContainer.name || 'Container'));
                    });
            };

            loadLogs();
            logsRefreshTimer = setInterval(loadLogs, 4000);
        }

        function ajaxAction(url, confirmText) {
            return new Promise((resolve) => {
                Swal.fire({
                    title: 'Attenzione',
                    text: confirmText,
                    type: 'warning',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonClass: 'btn btn-primary',
                    cancelButtonClass: 'btn btn-default',
                    confirmButtonText: 'Conferma',
                    cancelButtonText: 'Annulla'
                }).then(function (result) {
                    if (!result.value) {
                        resolve(false);
                        return;
                    }

                    $.ajax({
                        url: url,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (response && response.success) {
                                resolve(true);
                            } else {
                                resolve(false);
                            }
                        },
                        error: function () {
                            Swal.fire({
                                title: 'Ops!',
                                text: 'Si è verificato un errore imprevisto.',
                                type: 'danger',
                                confirmButtonClass: 'btn btn-info'
                            });
                            resolve(false);
                        }
                    });
                });
            });
        }

        function postContainerAction(containerId, action) {
            const url = ACTION_URLS[action].replace('_id_', containerId);
            return $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        }

        function bulkOperateVisible(action) {
            const visibleContainers = getVisibleContainers();
            if (visibleContainers.length === 0) {
                Swal.fire({
                    title: 'Ops!',
                    text: 'Nessun container visibile da gestire.',
                    type: 'warning',
                    confirmButtonClass: 'btn btn-info'
                });
                return;
            }

            const ids = visibleContainers.map(function (item) { return item.id; });
            const confirmText = action === 'delete'
                ? 'Eliminare tutti i container visibili?'
                : (action === 'start' ? 'Avviare tutti i container visibili?' : action === 'stop' ? 'Fermare tutti i container visibili?' : 'Riavviare tutti i container visibili?');

            Swal.fire({
                title: 'Attenzione',
                text: confirmText + ' (' + ids.length + ')',
                type: 'warning',
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonClass: action === 'delete' ? 'btn btn-danger' : 'btn btn-primary',
                cancelButtonClass: 'btn btn-default',
                confirmButtonText: 'Conferma',
                cancelButtonText: 'Annulla'
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                if (action === 'delete') {
                    $.ajax({
                        url: ACTION_URLS.delete,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        data: { ids: ids },
                        success: function (response) {
                            if (response && response.success) {
                                refreshContainers(true);
                            }
                        },
                        error: function () {
                            Swal.fire({
                                title: 'Ops!',
                                text: 'Impossibile eliminare i container visibili.',
                                type: 'danger',
                                confirmButtonClass: 'btn btn-info'
                            });
                        }
                    });
                    return;
                }

                $.ajax({
                    url: ACTION_URLS.bulk,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        action: action,
                        ids: ids
                    },
                    success: function (response) {
                        if (response && response.success) {
                            refreshContainers(true);
                        } else {
                            Swal.fire({
                                title: 'Ops!',
                                text: 'L’operazione bulk non è andata a buon fine.',
                                type: 'danger',
                                confirmButtonClass: 'btn btn-info'
                            });
                        }
                    },
                    error: function (xhr) {
                        const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'L’operazione bulk non è andata a buon fine.';
                        Swal.fire({
                            title: 'Ops!',
                            text: message,
                            type: 'danger',
                            confirmButtonClass: 'btn btn-info'
                        });
                    }
                });
            });
        }

        function selectedAction(action) {
            if (!selectedContainer) return;
            const containerId = selectedContainer.id;
            let url = ACTION_URLS[action];
            if (action !== 'delete') {
                url = url.replace('_id_', containerId);
                return ajaxAction(url, action === 'start' ? 'Avviare questo container?' : action === 'stop' ? 'Fermare questo container?' : 'Riavviare questo container?')
                    .then((ok) => {
                        if (ok) {
                            refreshContainers(true);
                        }
                    });
            }

            Swal.fire({
                title: 'Attenzione',
                text: 'Eliminare questo container?',
                type: 'warning',
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonClass: 'btn btn-danger',
                cancelButtonClass: 'btn btn-default',
                confirmButtonText: 'Conferma',
                cancelButtonText: 'Annulla'
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { ids: [containerId] },
                    success: function (response) {
                        if (response && response.success) {
                            refreshContainers(true);
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Ops!',
                            text: 'Si è verificato un errore imprevisto.',
                            type: 'danger',
                            confirmButtonClass: 'btn btn-info'
                        });
                    }
                });
            });
        }

        function fetchLogs() {
            if (!selectedContainer) return;

            const containerId = selectedContainer.id;
            logsModalContainerId = containerId;

            Swal.fire({
                title: '<span id="container-logs-title">Logs: ' + escapeHtml(selectedContainer.name || 'Container') + '</span>',
                html: [
                    '<div class="logs-toolbar">',
                    '  <button type="button" class="btn btn-light btn-sm js-log-tail" data-tail="50">50</button>',
                    '  <button type="button" class="btn btn-light btn-sm js-log-tail active" data-tail="200">200</button>',
                    '  <button type="button" class="btn btn-light btn-sm js-log-tail" data-tail="1000">1000</button>',
                    '  <button type="button" class="btn btn-outline-primary btn-sm ml-auto" id="copy-logs-output">',
                    '    <i class="fa fa-copy"></i> Copy output',
                    '  </button>',
                    '</div>',
                    '<textarea id="container-logs-content" readonly class="form-control" style="min-height: 420px; font-family: monospace; white-space: pre; overflow: auto;">Caricamento logs...</textarea>'
                ].join(''),
                width: 1100,
                showConfirmButton: true,
                confirmButtonText: 'Chiudi',
                confirmButtonClass: 'btn btn-primary',
                didOpen: function () {
                    startLogsPolling(containerId);
                    const popup = Swal.getPopup ? Swal.getPopup() : null;
                    if (!popup) return;

                    popup.querySelectorAll('.js-log-tail').forEach(function (item) {
                        item.classList.toggle('active', parseInt(item.getAttribute('data-tail') || '0', 10) === logsTail);
                    });

                    popup.querySelectorAll('.js-log-tail').forEach(function (button) {
                        button.addEventListener('click', function () {
                            logsTail = parseInt(button.getAttribute('data-tail') || '200', 10) || 200;
                            popup.querySelectorAll('.js-log-tail').forEach(function (item) {
                                item.classList.remove('active');
                            });
                            button.classList.add('active');
                            startLogsPolling(containerId);
                        });
                    });

                    const copyButton = popup.querySelector('#copy-logs-output');
                    const textarea = popup.querySelector('#container-logs-content');
                    if (copyButton && textarea) {
                        copyButton.addEventListener('click', function () {
                            copyText(textarea.value || '');
                        });
                    }
                },
                onOpen: function () {
                    startLogsPolling(containerId);
                },
                willClose: function () {
                    stopLogsRefresh();
                },
                onClose: function () {
                    stopLogsRefresh();
                }
            }).then(function () {
                stopLogsRefresh();
            });
        }

        function fetchInspect() {
            if (!selectedContainer) return;

            $.ajax({
                url: "{{ route('containers.inspect', ['container' => '_id_']) }}".replace('_id_', selectedContainer.id),
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response && response.success) {
                        const summary = buildInspectSummary(response.inspect || {});
                        const summaryHtml = [
                            '<div class="inspect-summary">',
                            summary.map(function (item) {
                                return '<div class="inspect-summary-item"><span class="label">' + escapeHtml(item.label) + '</span><span class="value' + (item.muted ? ' text-muted' : '') + '">' + escapeHtml(item.value) + '</span></div>';
                            }).join(''),
                            '</div>',
                            '<textarea readonly class="form-control" style="min-height: 360px; font-family: monospace; white-space: pre; overflow: auto;">' + escapeHtml(JSON.stringify(response.inspect || {}, null, 2)) + '</textarea>'
                        ].join('');

                        Swal.fire({
                            title: 'Inspect: ' + (response.name || selectedContainer.name || 'Container'),
                            html: summaryHtml,
                            width: 1200,
                            confirmButtonText: 'Chiudi',
                            confirmButtonClass: 'btn btn-primary',
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        title: 'Ops!',
                        text: 'Impossibile leggere i dettagli del container.',
                        type: 'danger',
                        confirmButtonClass: 'btn btn-info'
                    });
                }
            });
        }

        function promptExecCommand(initialCommand) {
            if (!selectedContainer) return;

            Swal.fire({
                title: 'Exec su ' + (selectedContainer.name || 'container'),
                showCancelButton: true,
                buttonsStyling: false,
                confirmButtonText: 'Esegui',
                cancelButtonText: 'Annulla',
                confirmButtonClass: 'btn btn-primary',
                cancelButtonClass: 'btn btn-default',
                width: 900,
                html: [
                    renderExecPresetButtons(),
                    '<textarea id="exec-command-input" class="form-control" style="min-height: 160px; font-family: monospace; white-space: pre;" placeholder="Esempio: ps aux | head -n 20">',
                    escapeHtml(initialCommand || 'ps aux'),
                    '</textarea>'
                ].join(''),
                didOpen: function () {
                    const popup = Swal.getPopup ? Swal.getPopup() : null;
                    if (!popup) return;

                    popup.querySelectorAll('.js-exec-preset-modal').forEach(function (button) {
                        button.addEventListener('click', function () {
                            const input = popup.querySelector('#exec-command-input');
                            if (input) {
                                input.value = button.getAttribute('data-command') || '';
                                input.focus();
                            }
                        });
                    });
                    const input = popup.querySelector('#exec-command-input');
                    if (input) {
                        input.focus();
                        input.select();
                    }
                },
                preConfirm: function () {
                    const popup = Swal.getPopup ? Swal.getPopup() : null;
                    const input = popup ? popup.querySelector('#exec-command-input') : null;
                    const command = input ? (input.value || '').trim() : '';
                    if (!command) {
                        Swal.showValidationMessage('Inserisci un comando');
                        return false;
                    }
                    return command;
                }
            }).then(function (result) {
                if (!result.value) {
                    return;
                }

                const command = result.value;

                $.ajax({
                    url: "{{ route('containers.exec', ['container' => '_id_']) }}".replace('_id_', selectedContainer.id),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: { command: command },
                    success: function (response) {
                        if (response && response.success) {
                            const output = '$ ' + (response.command || '') + '\n\n' + (response.output || '(nessun output)');
                            Swal.fire({
                                title: 'Exec: ' + (response.name || selectedContainer.name || 'Container'),
                                html: [
                                    '<div class="d-flex justify-content-end mb-2">',
                                    '  <button type="button" class="btn btn-outline-primary btn-sm" id="copy-exec-output">',
                                    '    <i class="fa fa-copy"></i> Copy output',
                                    '  </button>',
                                    '</div>',
                                    '<textarea id="container-exec-output" readonly class="form-control" style="min-height: 420px; font-family: monospace; white-space: pre; overflow: auto;">' + escapeHtml(output) + '</textarea>'
                                ].join(''),
                                width: 1100,
                                confirmButtonText: 'Chiudi',
                                confirmButtonClass: 'btn btn-primary',
                                    didOpen: function () {
                                        const copyButton = document.getElementById('copy-exec-output');
                                        const textarea = document.getElementById('container-exec-output');
                                        if (copyButton && textarea) {
                                            copyButton.addEventListener('click', function () {
                                                copyText(textarea.value || '');
                                            });
                                        }
                                    }
                                });
                            }
                    },
                    error: function (xhr) {
                        const message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Impossibile eseguire il comando nel container.';
                        Swal.fire({
                            title: 'Ops!',
                            text: message,
                            type: 'danger',
                            confirmButtonClass: 'btn btn-info'
                        });
                    }
                });
            });
        }

        $(document).ready(function () {
            if (typeof PIXI === 'undefined') {
                console.error('PIXI.js is not loaded.');
                return;
            }

            app = new PIXI.Application({
                backgroundAlpha: 0,
                antialias: true,
                resolution: Math.max(window.devicePixelRatio || 1, 1),
                autoDensity: true,
            });

            containerHost.appendChild(app.view);
            app.view.style.display = 'block';
            app.view.style.width = '100%';
            app.view.style.height = '100%';

            emptyText = new PIXI.Text(containersState.length === 0 ? 'Nessun container trovato' : 'Seleziona un container', {
                fontFamily: 'Arial',
                fontSize: 20,
                fontWeight: '700',
                fill: 0x64748b,
            });
            emptyText.anchor.set(0.5);
            centerEmptyText(emptyText);
            app.stage.addChild(emptyText);

            if (containersState.length === 0) {
                resizeCanvas(containerHost.clientHeight);
                centerEmptyText(emptyText);
                updateSelectedDetails(null);
                setLastUpdated('Nessun container disponibile', false);
            } else {
                layoutCards();
                setLastUpdated('Caricamento stato...', false);
                refreshContainers(true);
            }

            scheduleRefresh();

            window.addEventListener('resize', function () {
                if (resizeTimer) {
                    clearTimeout(resizeTimer);
                }

                resizeTimer = setTimeout(function () {
                    centerEmptyText(emptyText);
                    layoutCards();
                }, 100);
            });

            $('#refresh-pixi').on('click', function () {
                refreshContainers(false);
            });

            if (refreshVolumeButton) {
                refreshVolumeButton.addEventListener('click', function () {
                    refreshVolume();
                });
            }

            $(document).on('click', '.js-volume-file', function () {
                loadVolumeFile();
            });

            $('#container-search').on('input', function () {
                filters.query = normalizeValue($(this).val().trim());
                layoutCards();
            });

            $('#filter-status').on('change', function () {
                filters.status = $(this).val();
                layoutCards();
            });

            $('#filter-type').on('change', function () {
                filters.type = $(this).val();
                layoutCards();
            });

            $('#filter-issues-only').on('change', function () {
                filters.issuesOnly = $(this).is(':checked');
                layoutCards();
            });

            $('#clear-filters').on('click', function () {
                filters = { query: '', status: 'all', type: 'all', issuesOnly: false };
                $('#container-search').val('');
                $('#filter-status').val('all');
                $('#filter-type').val('all');
                $('#filter-issues-only').prop('checked', false);
                layoutCards();
            });

            $('#bulk-start-visible').on('click', function () {
                bulkOperateVisible('start');
            });

            $('#bulk-stop-visible').on('click', function () {
                bulkOperateVisible('stop');
            });

            $('#bulk-restart-visible').on('click', function () {
                bulkOperateVisible('restart');
            });

            $('#bulk-delete-visible').on('click', function () {
                bulkOperateVisible('delete');
            });

            $(document).on('click', '.js-selected-action', function () {
                selectedAction($(this).data('action'));
            });

            $(document).on('click', '.js-selected-delete', function () {
                selectedAction('delete');
            });

            $(document).on('click', '.js-selected-logs', function () {
                fetchLogs();
            });

            $(document).on('click', '.js-selected-inspect', function () {
                fetchInspect();
            });

            $(document).on('click', '.js-selected-exec', function () {
                promptExecCommand();
            });

            $(document).on('click', '.js-copy-field', function () {
                const target = $(this).data('target');
                const label = $(this).data('label') || 'Valore';
                const field = document.getElementById(target);
                if (!field) return;

                copyText(field.textContent || field.innerText || '').then(function () {
                    Swal.fire({
                        title: 'Copiato',
                        text: label + ' copiato negli appunti.',
                        type: 'success',
                        timer: 1200,
                        showConfirmButton: false,
                    });
                });
            });

            $(document).on('click', '.js-copy-exec', function () {
                const value = selectedContainer ? ('docker exec ' + selectedContainer.container_id + ' sh -lc "<command>"') : '';
                copyText(value).then(function () {
                    Swal.fire({
                        title: 'Copiato',
                        text: 'Comando exec copiato negli appunti.',
                        type: 'success',
                        timer: 1200,
                        showConfirmButton: false,
                    });
                });
            });

            scheduleRefresh();

            window.addEventListener('beforeunload', function () {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                }
                stopLogsRefresh();
            });
        });
    </script>
@stop
