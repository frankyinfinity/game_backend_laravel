@extends('adminlte::page')

@section('title', 'WebSocket Player Dashboard')

@section('content_header')@stop

@section('content')
<style>
    .ws-stat-card {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        padding: 12px 16px;
        min-height: 68px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .ws-stat-card .icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
        flex: 0 0 auto;
    }
    .ws-stat-card .icon.bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .ws-stat-card .icon.bg-green { background: linear-gradient(135deg, #22c55e, #16a34a); }
    .ws-stat-card .icon.bg-purple { background: linear-gradient(135deg, #a855f7, #7c3aed); }
    .ws-stat-card .icon.bg-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
    .ws-stat-card .info { display: flex; flex-direction: column; }
    .ws-stat-card .info .value { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .ws-stat-card .info .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; font-weight: 700; }

    .ws-event-entry {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12px;
        font-family: 'Courier New', monospace;
        transition: background 0.15s ease;
    }
    .ws-event-entry:hover { background: #f8fafc; }
    .ws-event-entry .time { color: #64748b; white-space: nowrap; flex: 0 0 auto; min-width: 80px; }
    .ws-event-entry .type-badge {
        display: inline-block;
        padding: 1px 8px;
        border-radius: 999px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        flex: 0 0 auto;
        min-width: 44px;
        text-align: center;
    }
    .ws-event-entry .type-badge.is-draw { background: #dbeafe; color: #1d4ed8; }
    .ws-event-entry .type-badge.is-update { background: #fef3c7; color: #b45309; }
    .ws-event-entry .type-badge.is-clear { background: #fce7f3; color: #be185d; }
    .ws-event-entry .type-badge.is-other { background: #e0e7ff; color: #4338ca; }
    .ws-event-entry .payload {
        color: #334155;
        word-break: break-all;
        flex: 1 1 auto;
        min-width: 0;
        max-height: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #ws-log-container {
        max-height: 520px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    #ws-log-container::-webkit-scrollbar { width: 8px; }
    #ws-log-container::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 10px 10px 0; }
    #ws-log-container::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        display: inline-block;
        flex: 0 0 auto;
    }
    .status-dot.is-connected { background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.2); }
    .status-dot.is-disconnected { background: #ef4444; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2); }
    .status-dot.is-connecting { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2); animation: pulse 1s infinite; }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .ws-header-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        padding: 14px 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .ws-header-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .ws-header-player {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
    }
</style>

<div class="ws-header-panel">
    <div class="ws-header-info">
        <div class="status-dot is-disconnected" id="connectionDot"></div>
        <div>
            <div class="ws-header-player">Player #{{ $player->id }} — {{ $player->user?->name ?? 'N/A' }}</div>
            <div style="font-size: 11px; color: #64748b;">
                Canale: <code style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px;">player_{{ $player->id }}_channel</code>
                <span id="connectionStatus" style="margin-left: 8px; color: #94a3b8;">Disconnesso</span>
            </div>
        </div>
    </div>
    <div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearLog">
            <i class="fas fa-trash"></i> Pulisci Log
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-bolt"></i> Eventi WebSocket in Tempo Reale</h5>
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
@stop

@section('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    $(document).ready(function () {
        const PLAYER_ID = {{ $player->id }};
        const REVERB_KEY = '{{ config('broadcasting.connections.reverb.key') }}';
        const REVERB_HOST = '{{ config('broadcasting.connections.reverb.options.host') }}';
        const REVERB_PORT = {{ config('broadcasting.connections.reverb.options.port') ?? 8081 }};
        const REVERB_SCHEME = '{{ config('broadcasting.connections.reverb.options.scheme') }}';

        const logContainer = document.getElementById('ws-log-container');
        const placeholder = document.getElementById('wsLogPlaceholder');
        const eventCountBadge = document.getElementById('eventCountBadge');
        const connectionDot = document.getElementById('connectionDot');
        const connectionStatus = document.getElementById('connectionStatus');
        let eventCount = 0;

        function setConnectionState(state) {
            connectionDot.className = 'status-dot';
            if (state === 'connected') {
                connectionDot.classList.add('is-connected');
                connectionStatus.textContent = 'Connesso';
                connectionStatus.style.color = '#16a34a';
            } else if (state === 'connecting') {
                connectionDot.classList.add('is-connecting');
                connectionStatus.textContent = 'Connessione...';
                connectionStatus.style.color = '#d97706';
            } else {
                connectionDot.classList.add('is-disconnected');
                connectionStatus.textContent = 'Disconnesso';
                connectionStatus.style.color = '#dc2626';
            }
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

            entry.innerHTML = `
                <span class="time">${time}</span>
                <span class="type-badge ${typeClass}">${type.substring(0, 8)}</span>
                <span class="payload">${escapeHtml(payloadStr)}</span>
            `;

            logContainer.insertBefore(entry, logContainer.firstChild);

            eventCount++;
            eventCountBadge.textContent = eventCount + ' eventi';

            // Keep max 200 entries
            while (logContainer.children.length > 200) {
                logContainer.removeChild(logContainer.lastChild);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Local stats tracking
        let localStats = {
            total: 0,
            timestamps: [],
            sessionStart: Date.now()
        };

        function updateLocalStats() {
            const now = Date.now();
            const oneHour = 60 * 60 * 1000;
            const oneDay = 24 * 60 * 60 * 1000;

            // Remove timestamps older than 1 hour for per-minute calculation
            localStats.timestamps = localStats.timestamps.filter(t => (now - t) < oneHour);

            const lastHour = localStats.timestamps.filter(t => (now - t) < oneHour).length;
            const last24h = localStats.timestamps.filter(t => (now - t) < oneDay).length;
            const perMin = lastHour > 0 ? (lastHour / 60).toFixed(1) : 0;

            document.getElementById('statTotal').textContent = localStats.total;
            document.getElementById('statHour').textContent = lastHour;
            document.getElementById('stat24h').textContent = last24h;
            document.getElementById('statPerMin').textContent = perMin;
        }

        function recordEvent() {
            localStats.total++;
            localStats.timestamps.push(Date.now());
            updateLocalStats();
        }

        // Connect to Reverb
        function connect() {
            setConnectionState('connecting');

            const pusher = new Pusher(REVERB_KEY, {
                cluster: 'mt1',
                wsHost: REVERB_HOST,
                wsPort: REVERB_PORT,
                wssPort: REVERB_PORT,
                forceTLS: REVERB_SCHEME === 'https',
                enabledTransports: ['ws', 'wss'],
                disableStats: true
            });

            pusher.connection.bind('connected', function () {
                setConnectionState('connected');
                addEventEntry('system', 'Connesso a Reverb');
            });

            pusher.connection.bind('disconnected', function () {
                setConnectionState('disconnected');
                addEventEntry('system', 'Disconnesso da Reverb');
            });

            pusher.connection.bind('error', function (error) {
                addEventEntry('system', 'Errore: ' + JSON.stringify(error));
            });

            const channelName = 'player_' + PLAYER_ID + '_channel';
            const channel = pusher.subscribe(channelName);

            channel.bind('pusher:subscription_succeeded', function () {
                addEventEntry('system', 'Sottoscritto al canale ' + channelName);
            });

            channel.bind('pusher:subscription_error', function (data) {
                addEventEntry('system', 'Errore sottoscrizione: ' + (data?.error || 'unknown'));
            });

            // Listen for draw_interface events
            channel.bind('draw_interface', function (data) {
                recordEvent();
                addEventEntry('draw_interface', data);
            });

            // Listen for container_ready events
            channel.bind('.PlayerContainerReady', function (data) {
                recordEvent();
                addEventEntry('container_ready', data);
            });

            return pusher;
        }

        let pusherInstance = connect();

        // Initialize stats
        updateLocalStats();
        setInterval(updateLocalStats, 5000);

        // Clear log
        document.getElementById('btnClearLog').addEventListener('click', function () {
            while (logContainer.children.length > 0) {
                logContainer.removeChild(logContainer.lastChild);
            }
            eventCount = 0;
            eventCountBadge.textContent = '0 eventi';
            // Re-add placeholder
            const ph = document.createElement('div');
            ph.className = 'text-center text-muted py-4';
            ph.id = 'wsLogPlaceholder';
            ph.innerHTML = '<i class="fas fa-plug fa-2x mb-2"></i><br>Log pulito. In attesa di nuovi eventi...';
            logContainer.appendChild(ph);
        });

        // Reconnect on page visibility change
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden && (!pusherInstance || pusherInstance.connection.state === 'disconnected')) {
                addEventEntry('system', 'Riconnessione...');
                if (pusherInstance) pusherInstance.disconnect();
                pusherInstance = connect();
            }
        });
    });
</script>
@stop