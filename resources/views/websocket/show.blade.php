@extends('adminlte::page')

@section('title', 'WebSocket Player Dashboard')

@section('content_header')@stop

@section('content')
<style>
    /* -- shared -- */
    .ws-stat-card {
        border: 1px solid #e2e8f0; border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        padding: 12px 16px; min-height: 68px;
        display: flex; align-items: center; gap: 12px;
    }
    .ws-stat-card .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; flex: 0 0 auto; }
    .ws-stat-card .icon.bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .ws-stat-card .icon.bg-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .ws-stat-card .icon.bg-purple { background: linear-gradient(135deg, #a855f7, #7c3aed); }
    .ws-stat-card .icon.bg-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
    .ws-stat-card .info { display: flex; flex-direction: column; }
    .ws-stat-card .info .value { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .ws-stat-card .info .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; font-weight: 700; }

    /* -- event log (tab1) -- */
    .ws-event-entry {
        display: flex; align-items: flex-start; gap: 10px; padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9; font-size: 12px; font-family: 'Courier New', monospace;
        transition: background 0.15s ease;
    }
    .ws-event-entry:hover { background: #f8fafc; }
    .ws-event-entry .time { color: #64748b; white-space: nowrap; flex: 0 0 auto; min-width: 80px; }
    .ws-event-entry .type-badge { display: inline-block; padding: 1px 8px; border-radius: 999px; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; flex: 0 0 auto; min-width: 44px; text-align: center; }
    .ws-event-entry .type-badge.is-draw { background: #dbeafe; color: #1d4ed8; }
    .ws-event-entry .type-badge.is-update { background: #fef3c7; color: #b45309; }
    .ws-event-entry .type-badge.is-clear { background: #fce7f3; color: #be185d; }
    .ws-event-entry .type-badge.is-other { background: #e0e7ff; color: #4338ca; }
    .ws-event-entry .payload { color: #334155; word-break: break-all; flex: 1 1 auto; min-width: 0; max-height: 60px; overflow: hidden; text-overflow: ellipsis; }
    #ws-log-container { max-height: 520px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    #ws-log-container::-webkit-scrollbar { width: 8px; }
    #ws-log-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 10px 10px 0; }
    #ws-log-container::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; }

    .status-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; flex: 0 0 auto; }
    .status-dot.is-connected { background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); }
    .status-dot.is-disconnected { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }
    .status-dot.is-connecting { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); animation: pulse 1s infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }

    .ws-header-panel {
        border: 1px solid #e5e7eb; border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        padding: 14px 18px; margin-bottom: 12px;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .ws-header-info { display: flex; align-items: center; gap: 12px; }
    .ws-header-player { font-size: 16px; font-weight: 700; color: #0f172a; }

    /* -- container ws tab (tab2) -- */
    .ws-c-list-item {
        padding: 8px 12px; border-bottom: 1px solid #edf2f7; cursor: pointer;
        transition: background 0.12s ease, border-left 0.12s ease;
        border-left: 3px solid transparent;
    }
    .ws-c-list-item:hover { background: #f8fafc; }
    .ws-c-list-item.active { background: #eff6ff; border-left-color: #3b82f6; }

    .ws-c-log {
        max-height: 400px; overflow-y: auto;
        border: 1px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; font-family: 'Courier New', monospace; font-size: 12px;
    }
    .ws-c-log::-webkit-scrollbar { width: 8px; }
    .ws-c-log::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 10px 10px 0; }
    .ws-c-log::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; }

    .ws-c-entry {
        display: flex; align-items: flex-start; gap: 8px; padding: 5px 10px;
        border-bottom: 1px solid #edf2f7; line-height: 1.4;
    }
    .ws-c-entry:last-child { border-bottom: 0; }
    .ws-c-entry .dir {
        flex: 0 0 auto; font-weight: 700; font-size: 9px; padding: 1px 6px;
        border-radius: 4px; text-transform: uppercase;
    }
    .ws-c-entry .dir.sent { background: #dbeafe; color: #1d4ed8; }
    .ws-c-entry .dir.received { background: #fef3c7; color: #b45309; }
    .ws-c-entry .dir.system { background: #e0e7ff; color: #4338ca; }
    .ws-c-entry .dir.error { background: #fce7f3; color: #be185d; }
    .ws-c-entry .pl { color: #334155; word-break: break-all; flex: 1 1 auto; min-width: 0; white-space: pre-wrap; }
    .ws-c-entry .tm { color: #94a3b8; white-space: nowrap; flex: 0 0 auto; font-size: 10px; }

    .ws-c-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700;
    }
    .ws-c-badge.disconnected { background: #fef2f2; color: #dc2626; }
    .ws-c-badge.connecting { background: #fffbeb; color: #d97706; }
    .ws-c-badge.connected { background: #f0fdf4; color: #16a34a; }
    .ws-c-badge.error { background: #fdf2f8; color: #be185d; }

    .ws-c-preset {
        display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px;
    }
    .ws-c-preset .badge { cursor: pointer; font-size: 11px; padding: 4px 8px; transition: all 0.12s ease; }
    .ws-c-preset .badge:hover { filter: brightness(1.1); transform: translateY(-1px); }

    .ws-c-command-row { display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
    .ws-c-command-row input { flex: 1; min-width: 140px; }
</style>

<div class="ws-header-panel">
    <div class="ws-header-info">
        <div>
            <div class="ws-header-player">
                <i class="fas fa-user-circle text-primary"></i>
                Player #{{ $player->id }} — {{ $player->user?->name ?? 'N/A' }}
            </div>
            <div style="font-size: 11px; color: #64748b;">
                <span>📡 Broadcast: <code style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px;">player_{{ $player->id }}_channel</code></span>
                <span id="connectionStatus" style="margin-left: 6px; color: #94a3b8;"></span>
            </div>
        </div>
    </div>
    <div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearLog">
            <i class="fas fa-trash"></i> Pulisci Log
        </button>
    </div>
</div>

<ul class="nav nav-tabs" id="wsTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="tab-reverb" data-toggle="tab" href="#tab1" role="tab">
            <i class="fas fa-bolt"></i> WebSocket (Reverb)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="tab-containers" data-toggle="tab" href="#tab2" role="tab">
            <i class="fas fa-cubes"></i> Container WebSocket
        </a>
    </li>
</ul>

<div class="tab-content mt-2">
    {{-- ============ TAB 1: REVERB ============ --}}
    <div class="tab-pane fade show active" id="tab1" role="tabpanel">
        <div class="row">
            <div class="col-md-8">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Eventi in Tempo Reale</h5>
                        <div class="card-tools">
                            <span class="badge badge-info" id="eventCountBadge">0 eventi</span>
                        </div>
                    </div>
                    <div class="card-body" style="padding: 8px;">
                        <div id="ws-log-container">
                            <div class="text-center text-muted py-4" id="wsLogPlaceholder">
                                <i class="fas fa-plug fa-2x mb-2"></i><br>
                                In attesa di eventi WebSocket...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-simple"></i> Statistiche</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2" style="row-gap: 8px;">
                            <div class="col-6">
                                <div class="ws-stat-card">
                                    <div class="icon bg-blue"><i class="fas fa-bolt"></i></div>
                                    <div class="info">
                                        <div class="value" id="statTotal" title="Contati in tempo reale su questa pagina">0</div>
                                        <div class="label">Eventi Ricevuti</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ws-stat-card">
                                    <div class="icon bg-green"><i class="fas fa-clock"></i></div>
                                    <div class="info">
                                        <div class="value" id="statHour">0</div>
                                        <div class="label">Ultima Ora (locale)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ws-stat-card">
                                    <div class="icon bg-purple"><i class="fas fa-calendar-day"></i></div>
                                    <div class="info">
                                        <div class="value" id="stat24h">0</div>
                                        <div class="label">Questa Sessione</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="ws-stat-card">
                                    <div class="icon bg-orange"><i class="fas fa-gauge-high"></i></div>
                                    <div class="info">
                                        <div class="value" id="statPerMin">0</div>
                                        <div class="label">Eventi/min (media)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-circle-info"></i> Info Player</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><td style="width: 100px;"><strong>ID</strong></td><td>{{ $player->id }}</td></tr>
                            <tr><td><strong>Utente</strong></td><td>{{ $player->user?->name ?? 'N/A' }} (ID: {{ $player->user_id ?? 'N/A' }})</td></tr>
                            <tr><td><strong>Birth Region</strong></td><td>{{ $player->birthRegion?->name ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Session</strong></td><td><code>{{ $player->actual_session_id ?? 'N/A' }}</code></td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ TAB 2: CONTAINER WS ============ --}}
    <div class="tab-pane fade" id="tab2" role="tabpanel">
        @if (empty($containers))
            <div class="text-center text-muted py-5">
                <i class="fas fa-cubes fa-3x mb-3" style="opacity:0.4;"></i><br>
                Nessun container trovato per questo player.<br>
                <small>Avvia i container dalla pagina dedicata.</small>
            </div>
        @else
        <div class="row">
            <div class="col-md-5 col-xl-4 mb-3 mb-md-0">
                <div class="card card-outline card-secondary h-100">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0" style="font-size:13px;"><i class="fas fa-list"></i> Container</h5>
                        <div class="card-tools">
                            <input type="text" class="form-control form-control-sm" id="ws-c-search" placeholder="Filtra..." style="width:120px;">
                        </div>
                    </div>
                    <div class="card-body p-0" id="ws-c-list" style="max-height: 500px; overflow-y: auto;">
                        <div class="text-center text-muted py-3" id="ws-c-loading">
                            <i class="fas fa-spinner fa-spin"></i> Caricamento...
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 col-xl-8">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-plug"></i> <span id="ws-c-title">Seleziona un container</span></h5>
                        <div class="card-tools">
                            <span id="ws-c-badge" class="ws-c-badge disconnected"><i class="fas fa-circle"></i> Disconnesso</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center" style="gap:6px; flex-wrap:wrap; margin-bottom:8px;">
                            <button type="button" class="btn btn-success btn-sm" id="ws-c-connect" disabled><i class="fas fa-plug"></i> Connetti</button>
                            <button type="button" class="btn btn-danger btn-sm" id="ws-c-disconnect" disabled><i class="fas fa-unlink"></i> Disconnetti</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="ws-c-clear"><i class="fas fa-trash"></i> Clear</button>
                        </div>

                        <div class="ws-c-preset" id="ws-c-presets" style="display:none;">
                            <span class="text-muted small mr-1" style="line-height:24px;">Comandi:</span>
                            <span id="ws-c-preset-inner"></span>
                        </div>

                        <div class="ws-c-command-row" id="ws-c-cmd-row" style="display:none;">
                            <input type="text" class="form-control form-control-sm" id="ws-c-cmd" placeholder='{"command":"get_position"}' disabled>
                            <button type="button" class="btn btn-primary btn-sm" id="ws-c-send" disabled><i class="fas fa-paper-plane"></i> Invia</button>
                        </div>

                        <div class="ws-c-log" id="ws-c-log">
                            <div class="text-center text-muted py-4" id="ws-c-ph">
                                <i class="fas fa-plug fa-2x mb-2"></i><br>
                                Connettiti a un container per vedere i messaggi
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@stop

@section('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    $(document).ready(function () {
        // ===================== SHARED =====================
        const PLAYER_ID = {{ $player->id }};
        const DOCKER_HOST = '{{ $dockerHost ?? 'localhost' }}';
        const GATEWAY_PORT = {{ config('remote_docker.websocket_gateway_port') }};
        const SNAPSHOT_URL = '{{ route('containers.snapshot', $player) }}';
        const CONTAINERS = @json($containers);
        const CONTAINER_IDS = CONTAINERS.map(c => c.id);

        // ===================== TAB 1: REVERB =====================
        const REVERB_KEY = '{{ config('broadcasting.connections.reverb.key') }}';
        const REVERB_HOST = '{{ config('broadcasting.connections.reverb.options.host') }}';
        const REVERB_PORT = {{ config('broadcasting.connections.reverb.options.port') ?? 8081 }};
        const REVERB_SCHEME = '{{ config('broadcasting.connections.reverb.options.scheme') }}';

        const logContainer = document.getElementById('ws-log-container');
        const placeholder = document.getElementById('wsLogPlaceholder');
        const eventCountBadge = document.getElementById('eventCountBadge');
        const connectionStatus = document.getElementById('connectionStatus');
        let eventCount = 0;

        function setConnectionState(state) {
            if (state === 'connected') {
                connectionStatus.innerHTML = '<span style="color:#16a34a"><i class="fas fa-circle" style="font-size:8px;"></i> Connesso</span>';
            } else if (state === 'connecting') {
                connectionStatus.innerHTML = '<span style="color:#d97706"><i class="fas fa-spinner fa-spin" style="font-size:8px;"></i> Connessione...</span>';
            } else {
                connectionStatus.innerHTML = '<span style="color:#dc2626"><i class="fas fa-circle" style="font-size:8px;"></i> Disconnesso</span>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function addEventEntry(type, payload) {
            if (placeholder) placeholder.remove();
            const entry = document.createElement('div');
            entry.className = 'ws-event-entry';
            const time = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 });
            let typeClass = 'is-other';
            if (type === 'draw_interface' || type === 'draw') typeClass = 'is-draw';
            else if (type === 'update') typeClass = 'is-update';
            else if (type === 'clear') typeClass = 'is-clear';
            let payloadStr = typeof payload === 'string' ? payload : JSON.stringify(payload);
            if (payloadStr.length > 200) payloadStr = payloadStr.substring(0, 200) + '...';
            entry.innerHTML = `<span class="time">${time}</span><span class="type-badge ${typeClass}">${type.substring(0, 8)}</span><span class="payload">${escapeHtml(payloadStr)}</span>`;
            logContainer.insertBefore(entry, logContainer.firstChild);
            eventCount++;
            eventCountBadge.textContent = eventCount + ' eventi';
            while (logContainer.children.length > 200) logContainer.removeChild(logContainer.lastChild);
        }

        let localStats = { total: 0, timestamps: [], sessionStart: Date.now() };
        function updateLocalStats() {
            const now = Date.now(), oneHour = 3600000, oneDay = 86400000;
            localStats.timestamps = localStats.timestamps.filter(t => (now - t) < oneHour);
            const lastHour = localStats.timestamps.filter(t => (now - t) < oneHour).length;
            const last24h = localStats.timestamps.filter(t => (now - t) < oneDay).length;
            const perMin = lastHour > 0 ? (lastHour / 60).toFixed(1) : 0;
            document.getElementById('statTotal').textContent = localStats.total;
            document.getElementById('statHour').textContent = lastHour;
            document.getElementById('stat24h').textContent = last24h;
            document.getElementById('statPerMin').textContent = perMin;
        }
        function recordEvent() { localStats.total++; localStats.timestamps.push(Date.now()); updateLocalStats(); }

        function connectReverb() {
            setConnectionState('connecting');
            const pusher = new Pusher(REVERB_KEY, {
                cluster: 'mt1', wsHost: REVERB_HOST, wsPort: REVERB_PORT, wssPort: REVERB_PORT,
                forceTLS: REVERB_SCHEME === 'https', enabledTransports: ['ws', 'wss'], disableStats: true
            });
            pusher.connection.bind('connected', function () { setConnectionState('connected'); addEventEntry('system', 'Connesso a Reverb'); });
            pusher.connection.bind('disconnected', function () { setConnectionState('disconnected'); addEventEntry('system', 'Disconnesso da Reverb'); });
            pusher.connection.bind('error', function (error) { addEventEntry('system', 'Errore: ' + JSON.stringify(error)); });
            const channelName = 'player_' + PLAYER_ID + '_channel';
            const channel = pusher.subscribe(channelName);
            channel.bind('pusher:subscription_succeeded', function () { addEventEntry('system', 'Sottoscritto al canale ' + channelName); });
            channel.bind('pusher:subscription_error', function (data) { addEventEntry('system', 'Errore sottoscrizione: ' + (data?.error || 'unknown')); });
            channel.bind('draw_interface', function (data) { recordEvent(); addEventEntry('draw_interface', data); });
            channel.bind('.PlayerContainerReady', function (data) { recordEvent(); addEventEntry('container_ready', data); });
            return pusher;
        }

        let pusherInstance = connectReverb();
        updateLocalStats();
        setInterval(updateLocalStats, 5000);

        document.getElementById('btnClearLog').addEventListener('click', function () {
            while (logContainer.children.length > 0) logContainer.removeChild(logContainer.lastChild);
            eventCount = 0;
            eventCountBadge.textContent = '0 eventi';
            const ph = document.createElement('div');
            ph.className = 'text-center text-muted py-4'; ph.id = 'wsLogPlaceholder';
            ph.innerHTML = '<i class="fas fa-plug fa-2x mb-2"></i><br>Log pulito. In attesa di nuovi eventi...';
            logContainer.appendChild(ph);
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && (!pusherInstance || pusherInstance.connection.state === 'disconnected')) {
                addEventEntry('system', 'Riconnessione...');
                if (pusherInstance) pusherInstance.disconnect();
                pusherInstance = connectReverb();
            }
        });

        // ===================== TAB 2: CONTAINER WS =====================
        const containers = CONTAINERS;
        let selectedContainer = null;
        let wsConn = null;
        let wsConnected = false;
        let ctLog = [];

        const $ctList = $('#ws-c-list');
        const $ctLog = $('#ws-c-log');
        const $ctPh = $('#ws-c-ph');
        const $ctTitle = $('#ws-c-title');
        const $ctBadge = $('#ws-c-badge');
        const $ctConn = $('#ws-c-connect');
        const $ctDisc = $('#ws-c-disconnect');
        const $ctSend = $('#ws-c-send');
        const $ctCmd = $('#ws-c-cmd');
        const $ctPresets = $('#ws-c-presets');
        const $ctCmdRow = $('#ws-c-cmd-row');

        function esc(s) { return String(s || '').replace(/&/g,'&').replace(/</g,'<').replace(/>/g,'>').replace(/"/g,'"').replace(/'/g,'&#039;'); }

        function ctTypeBadge(type) {
            const m = {'Player':'primary','Map':'success','Entity':'warning','ElementHasPosition':'danger','Objective':'secondary','CacheSync':'info','ChimicalElement':'dark'};
            return '<span class="badge badge-' + (m[type]||'secondary') + '">' + esc(type) + '</span>';
        }

        function ctTypeOrder(type) {
            const order = {'Player':0,'Map':1,'Objective':2,'Entity':3,'ElementHasPosition':4,'CacheSync':5,'ChimicalElement':6};
            return order[type] !== undefined ? order[type] : 9;
        }

        function ctRenderList() {
            const q = ($('#ws-c-search').val()||'').toLowerCase().trim();
            const filtered = q ? containers.filter(function(c){
                return (c.name||'').toLowerCase().includes(q)||(c.container_id||'').toLowerCase().includes(q)||String(c.ws_port||'').includes(q)||(c.parent_type||'').toLowerCase().includes(q);
            }) : containers;

            if (filtered.length === 0) {
                $ctList.html('<div class="text-center text-muted py-3"><i class="fas fa-search fa-2x mb-2"></i><br>Nessun container trovato.</div>');
                return;
            }

            // Group by parent_type
            var groups = {};
            filtered.forEach(function(c){
                var t = c.parent_type || 'Other';
                if (!groups[t]) groups[t] = [];
                groups[t].push(c);
            });

            // Sort groups by order
            var sortedTypes = Object.keys(groups).sort(function(a,b){ return ctTypeOrder(a)-ctTypeOrder(b); });

            var html = '';
            sortedTypes.forEach(function(type){
                var items = groups[type];
                var typeLabel = type;
                // Use the label from first item if available
                if (items[0] && items[0].type_label) typeLabel = items[0].type_label;

                // Section header
                html += '<div style="padding: 6px 12px 2px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; background:#f8fafc; border-bottom:1px solid #edf2f7;">'
                    + esc(typeLabel) + ' <span class="text-muted" style="font-weight:400;">('+items.length+')</span>'
                    + '</div>';

                // Sort items by name within group
                items.sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });

                items.forEach(function(c){
                    var active = selectedContainer && selectedContainer.id === c.id;
                    var isConnected = wsConn && selectedContainer && selectedContainer.id === c.id && wsConnected;
                    var addr = DOCKER_HOST + ':' + c.ws_port;
                    html += '<div class="ws-c-list-item'+(active?' active':'')+'" data-id="'+c.id+'">'
                        + '<div class="d-flex justify-content-between align-items-center mb-1">'
                        + '<strong style="font-size:13px;">'+esc(c.name)+'</strong> '
                        + (isConnected ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#16a34a;box-shadow:0 0 0 2px rgba(22,163,74,0.2);margin-right:4px;"></span>' : '')
                        + ctTypeBadge(c.parent_type)
                        + '</div>'
                        + '<div style="font-size:11px;color:#64748b;">'
                        + (isConnected ? '<span style="color:#16a34a;font-weight:700;">●</span> ' : '')
                        + esc(c.container_id ? c.container_id.substring(0,12) : '-')
                        + ' <span class="mx-1">|</span> <i class="fas fa-plug"></i> '+esc(addr)
                        + '</div></div>';
                });
            });

            $ctList.html(html);
            $ctList.find('.ws-c-list-item').on('click',function(){
                const id = parseInt($(this).data('id'),10);
                const c = containers.find(function(x){return x.id===id;});
                if(c) ctSelect(c);
            });
        }

        // Per-type preset definitions
        function ctPresetsForType(type) {
            var all = {
                'Entity': [
                    { cmd: 'get_position',    label: '📍 get_position',     desc: 'Posizione corrente' },
                    { cmd: 'get_genes',       label: '🧬 get_genes',        desc: 'Geni correnti' },
                    { cmd: 'get_chimical_elements', label: '⚗️ chimical',  desc: 'Elementi chimici' },
                    { cmd: 'move',            label: '▲ move up',           desc: 'Sposta su',     more: 'up' },
                    { cmd: 'move',            label: '▼ move down',         desc: 'Sposta giù',   more: 'down' },
                    { cmd: 'move',            label: '◄ move left',         desc: 'Sposta sx',    more: 'left' },
                    { cmd: 'move',            label: '► move right',        desc: 'Sposta dx',    more: 'right' },
                ],
                'Player': [
                    { cmd: 'get_player_values', label: '📊 get_player_values', desc: 'Valori player' },
                ],
                'Map': [
                    { cmd: 'get_tile_info',             label: '🗺️ tile',              desc: 'Info tile (i,j)',     more: '{"tile_i":0,"tile_j":0}' },
                    { cmd: 'get_birth_region_details',  label: '🏞️ region detail',    desc: 'Dettaglio regione',   more: '{"tile_i":0,"tile_j":0}' },
                ],
                'ElementHasPosition': [
                    { cmd: 'get_status',      label: '📟 get_status',       desc: 'Stato elemento' },
                    { cmd: 'get_genes',       label: '🧬 get_genes',        desc: 'Geni correnti' },
                    { cmd: 'get_chimical_elements', label: '⚗️ chimical',  desc: 'Elementi chimici' },
                ],
            };
            return all[type] || [];
        }

        function ctUpdatePresets() {
            var container = selectedContainer;
            if (!container) { $('#ws-c-preset-inner').html(''); return; }
            var presets = ctPresetsForType(container.parent_type);
            if (presets.length === 0) {
                $('#ws-c-preset-inner').html('<span class="text-muted small">Nessun preset per questo tipo</span>');
                return;
            }
            var html = '';
            presets.forEach(function(p){
                var cmdObj = { command: p.cmd };
                if (p.more) {
                    if (p.cmd === 'move') {
                        cmdObj.params = { action: p.more };
                    } else {
                        try { cmdObj.params = JSON.parse(p.more); } catch(e) { cmdObj.params = {}; }
                    }
                }
                html += '<span class="badge badge-info js-ct-preset" title="' + esc(p.desc) + '" data-cmd=\'' + JSON.stringify(cmdObj) + '\'>' + esc(p.label) + '</span>';
            });
            $('#ws-c-preset-inner').html(html);
        }

        function ctSelect(c) {
            if(wsConn) ctDisconnect();
            selectedContainer = c;
            ctRenderList();
            const addr = DOCKER_HOST+':'+c.ws_port;
            $ctTitle.text(c.name+' ('+addr+')');
            ctSetBadge('disconnected','<i class="fas fa-circle"></i> Disconnesso');
            $ctConn.prop('disabled',false);
            $ctDisc.prop('disabled',true);
            $ctSend.prop('disabled',true);
            $ctCmd.prop('disabled',true);
            ctUpdatePresets();
            $ctPresets.show();
            $ctCmdRow.show();
            ctLog = [];
            ctRenderLog();
        }

        function ctSetBadge(cls,html){ $ctBadge.prop('class','ws-c-badge '+cls).html(html); }

        function ctAddLog(dir,payload){
            ctLog.push({
                dir:dir,
                payload: typeof payload==='string'?payload:JSON.stringify(payload,null,2),
                time: new Date().toLocaleTimeString('it-IT',{hour:'2-digit',minute:'2-digit',second:'2-digit'})
            });
            if(ctLog.length>1000) ctLog=ctLog.slice(-1000);
            ctRenderLog();
        }

        function ctRenderLog(){
            if($ctPh.length) $ctPh.hide();
            if(ctLog.length===0){
                $ctLog.html('<div class="text-center text-muted py-4" id="ws-c-ph"><i class="fas fa-plug fa-2x mb-2"></i><br>Nessun messaggio ancora.</div>');
                return;
            }
            let html='';
            ctLog.forEach(function(e){
                let pl=e.payload;
                if(pl.length>600) pl=pl.substring(0,600)+'...';
                html+='<div class="ws-c-entry"><span class="dir '+e.dir+'">'+e.dir.toUpperCase()+'</span><span class="tm">'+esc(e.time)+'</span><span class="pl">'+esc(pl)+'</span></div>';
            });
            $ctLog.html(html);
            $ctLog.scrollTop($ctLog[0].scrollHeight);
        }

        function ctConnect(){
            if(!selectedContainer||wsConn) return;
            // Use websocket gateway: ws://host:GATEWAY_PORT/?port=ws_port
            const port = selectedContainer.ws_port;
            if (!port) {
                ctAddLog('error', 'Questo container non ha una porta WS (ws_port mancante)');
                ctSetBadge('error', '<i class="fas fa-circle"></i> No WS');
                return;
            }
            const url='ws://'+DOCKER_HOST+':'+GATEWAY_PORT+'/?port='+port;
            ctAddLog('system','Connessione a '+url+' (container '+selectedContainer.name+')...');
            ctSetBadge('connecting','<i class="fas fa-spinner fa-spin"></i> Connessione...');
            $ctConn.prop('disabled',true); $ctDisc.prop('disabled',true);
            try{ wsConn=new WebSocket(url); }catch(e){
                ctAddLog('error','Errore: '+e.message);
                ctSetBadge('error','<i class="fas fa-circle"></i> Errore');
                $ctConn.prop('disabled',false); wsConn=null; return;
            }
            wsConn.onopen=function(){
                wsConnected=true; ctAddLog('system','Connesso!');
                ctSetBadge('connected','<i class="fas fa-circle"></i> Connesso');
                $ctConn.prop('disabled',true); $ctDisc.prop('disabled',false);
                $ctSend.prop('disabled',false); $ctCmd.prop('disabled',false); $ctCmd.focus();
                ctRenderList(); // Update green dot in list
            };
            wsConn.onmessage=function(ev){
                let data=ev.data;
                try{ const parsed=JSON.parse(data); data=JSON.stringify(parsed,null,2); }catch(_){}
                ctAddLog('received',data);
            };
            wsConn.onerror=function(){ ctAddLog('error','Errore WebSocket'); };
            wsConn.onclose=function(ev){
                wsConnected=false; ctAddLog('system','Disconnesso (codice '+ev.code+')');
                ctSetBadge('disconnected','<i class="fas fa-circle"></i> Disconnesso');
                $ctConn.prop('disabled',false); $ctDisc.prop('disabled',true);
                $ctSend.prop('disabled',true); $ctCmd.prop('disabled',true); wsConn=null;
                ctRenderList(); // Remove green dot
            };
        }

        function ctDisconnect(){
            if(!wsConn) return;
            try{ wsConn.close(); }catch(_){}
            wsConn=null; wsConnected=false;
            ctAddLog('system','Disconnessione manuale');
            ctSetBadge('disconnected','<i class="fas fa-circle"></i> Disconnesso');
            $ctConn.prop('disabled',false); $ctDisc.prop('disabled',true);
            $ctSend.prop('disabled',true); $ctCmd.prop('disabled',true);
        }

        function ctSend(cmdStr){
            if(!wsConn||!wsConnected||!cmdStr) return;
            try{
                const parsed=JSON.parse(cmdStr);
                wsConn.send(JSON.stringify(parsed));
                ctAddLog('sent',JSON.stringify(parsed,null,2));
                $ctCmd.val('');
            }catch(e){ ctAddLog('error','JSON non valido: '+e.message); }
        }

        // Init container list
        if (containers.length > 0) {
            selectedContainer = containers[0];
            ctRenderList();
            ctSelect(selectedContainer);
        } else {
            $ctList.html('<div class="text-center text-muted py-3"><i class="fas fa-cubes fa-2x mb-2"></i><br>Nessun container per questo player.</div>');
        }

        // Events tab2
        $('#ws-c-search').on('input', ctRenderList);
        $('#ws-c-connect').on('click', ctConnect);
        $('#ws-c-disconnect').on('click', ctDisconnect);
        $('#ws-c-send').on('click', function(){ ctSend($ctCmd.val()); });
        $('#ws-c-cmd').on('keydown', function(e){ if(e.key==='Enter') ctSend($ctCmd.val()); });
        $(document).on('click','.js-ct-preset', function(){
            var cmd = $(this).data('cmd');
            if(cmd){ $ctCmd.val(JSON.stringify(cmd)); ctSend($ctCmd.val()); }
        });
        $('#ws-c-clear').on('click', function(){ ctLog=[]; ctRenderLog(); });

        $(window).on('beforeunload', function(){
            if(wsConn) try{ wsConn.close(); }catch(_){}
        });
    });
</script>
@stop