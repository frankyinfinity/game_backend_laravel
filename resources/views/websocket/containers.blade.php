@extends('adminlte::page')

@section('title', 'Container WebSocket Monitor')

@section('content_header')@stop

@section('content')
<style>
    .ws-container-list-item {
        padding: 10px 14px;
        border-bottom: 1px solid #edf2f7;
        cursor: pointer;
        transition: background 0.12s ease, border-left 0.12s ease;
        border-left: 3px solid transparent;
    }
    .ws-container-list-item:hover { background: #f8fafc; }
    .ws-container-list-item.active {
        background: #eff6ff;
        border-left-color: #3b82f6;
    }

    .ws-header-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        padding: 12px 18px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ws-detail-panel {
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .ws-detail-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        flex-wrap: wrap;
    }
    .ws-detail-header h5 { margin: 0; font-size: 14px; font-weight: 800; }
    .ws-detail-body { padding: 10px 14px; }

    .ws-command-row {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }
    .ws-command-row input { flex: 1; min-width: 140px; }
    .ws-command-row select { min-width: 160px; }

    .preset-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-bottom: 8px;
    }
    .preset-badges .badge {
        cursor: pointer;
        font-size: 11px;
        padding: 4px 8px;
        transition: all 0.12s ease;
    }
    .preset-badges .badge:hover { filter: brightness(1.1); transform: translateY(-1px); }

    .ws-container-log {
        max-height: 420px;
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        font-family: 'Courier New', monospace;
        font-size: 12px;
    }
    .ws-container-log::-webkit-scrollbar { width: 8px; }
    .ws-container-log::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 0 10px 10px 0; }
    .ws-container-log::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 999px; }

    .ws-log-entry {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        padding: 5px 10px;
        border-bottom: 1px solid #edf2f7;
        line-height: 1.4;
    }
    .ws-log-entry:last-child { border-bottom: 0; }
    .ws-log-entry .dir {
        flex: 0 0 auto;
        font-weight: 700;
        font-size: 9px;
        padding: 1px 6px;
        border-radius: 4px;
        text-transform: uppercase;
    }
    .ws-log-entry .dir.sent { background: #dbeafe; color: #1d4ed8; }
    .ws-log-entry .dir.received { background: #fef3c7; color: #b45309; }
    .ws-log-entry .dir.system { background: #e0e7ff; color: #4338ca; }
    .ws-log-entry .dir.error { background: #fce7f3; color: #be185d; }
    .ws-log-entry .pl { color: #334155; word-break: break-all; flex: 1 1 auto; min-width: 0; white-space: pre-wrap; }
    .ws-log-entry .tm { color: #94a3b8; white-space: nowrap; flex: 0 0 auto; font-size: 10px; }

    .conn-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .conn-badge.disconnected { background: #fef2f2; color: #dc2626; }
    .conn-badge.connecting { background: #fffbeb; color: #d97706; }
    .conn-badge.connected { background: #f0fdf4; color: #16a34a; }
    .conn-badge.error { background: #fdf2f8; color: #be185d; }

    .no-selection-box {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }
    .no-selection-box i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }

    .stat-summary {
        display: flex;
        gap: 16px;
        align-items: center;
        font-size: 12px;
        color: #64748b;
    }
    .stat-summary span { white-space: nowrap; }
    .stat-summary strong { color: #0f172a; }
</style>

<div class="ws-header-panel">
    <div>
        <h4 class="mb-1" style="font-weight: 800; font-size: 18px;">
            <i class="fas fa-cubes"></i> Container WebSocket Monitor
        </h4>
        <div class="stat-summary">
            <span>Host: <code>{{ config('remote_docker.docker_host_ip') ?? 'localhost' }}</code></span>
            <span id="stat-total">Container: <strong>0</strong></span>
            <span id="stat-connected" style="display:none;">Connessi: <strong id="stat-connected-count">0</strong></span>
        </div>
    </div>
    <div class="d-flex" style="gap: 8px;">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh" title="Ricarica lista container">
            <i class="fas fa-redo"></i> Ricarica
        </button>
        <a href="{{ route('websocket.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-arrow-left"></i> Players
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-5 col-xl-4 mb-3 mb-md-0">
        <div class="card card-outline card-secondary h-100">
            <div class="card-header py-2">
                <h5 class="card-title mb-0" style="font-size: 13px;">
                    <i class="fas fa-list"></i> Container
                </h5>
                <div class="card-tools">
                    <input type="text" class="form-control form-control-sm" id="search-input" placeholder="Filtra..." style="width: 130px;">
                </div>
            </div>
            <div class="card-body p-0" id="container-list" style="max-height: 640px; overflow-y: auto;">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>
                    Caricamento...
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 col-xl-8">
        <div class="ws-detail-panel">
            <div class="ws-detail-header">
                <h5>
                    <i class="fas fa-plug"></i>
                    <span id="detail-title">Seleziona un container</span>
                </h5>
                <div class="d-flex align-items-center" style="gap: 6px; flex-wrap: wrap;">
                    <span id="detail-badge" class="conn-badge disconnected">
                        <i class="fas fa-circle"></i> Disconnesso
                    </span>
                    <button type="button" class="btn btn-success btn-sm" id="btn-connect" disabled>
                        <i class="fas fa-plug"></i> Connetti
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="btn-disconnect" disabled>
                        <i class="fas fa-unlink"></i> Disconnetti
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
            </div>
            <div class="ws-detail-body">
                <div class="preset-badges" id="preset-area" style="display:none;">
                    <span class="text-muted small" style="line-height: 24px; margin-right: 4px;">Comandi:</span>
                    <span class="badge badge-info js-preset" data-cmd='{"command":"get_position"}'>get_position</span>
                    <span class="badge badge-info js-preset" data-cmd='{"command":"move","params":{"action":"up"}}'>▲ up</span>
                    <span class="badge badge-info js-preset" data-cmd='{"command":"move","params":{"action":"down"}}'>▼ down</span>
                    <span class="badge badge-info js-preset" data-cmd='{"command":"move","params":{"action":"left"}}'>◄ left</span>
                    <span class="badge badge-info js-preset" data-cmd='{"command":"move","params":{"action":"right"}}'>► right</span>
                </div>
                <div class="ws-command-row" id="cmd-area" style="display:none;">
                    <input type="text" class="form-control form-control-sm" id="cmd-input" placeholder='{"command":"get_position"}' disabled>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-send" disabled>
                        <i class="fas fa-paper-plane"></i> Invia
                    </button>
                </div>
                <div class="ws-container-log" id="ws-log">
                    <div class="text-center text-muted py-4" id="ws-ph">
                        <i class="fas fa-plug fa-2x mb-2"></i><br>
                        Connettiti a un container per vedere i messaggi WebSocket
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
    $(document).ready(function () {
        const DOCKER_HOST = '{{ config('remote_docker.docker_host_ip') ?? 'localhost' }}';
        const LIST_URL = '{{ route('websocket.containers.list') }}';

        let containers = [];
        let selected = null;     // currently selected container object
        let ws = null;
        let connected = false;
        let log = [];

        const $list = $('#container-list');
        const $log = $('#ws-log');
        const $ph = $('#ws-ph');
        const $detailTitle = $('#detail-title');
        const $badge = $('#detail-badge');
        const $btnConn = $('#btn-connect');
        const $btnDisc = $('#btn-disconnect');
        const $btnSend = $('#btn-send');
        const $cmdInp = $('#cmd-input');
        const $presetArea = $('#preset-area');
        const $cmdArea = $('#cmd-area');
        const $statTotal = $('#stat-total');
        const $statConnected = $('#stat-connected');
        const $statConnectedCount = $('#stat-connected-count');

        function esc(s) {
            return String(s || '')
                .replace(/&/g, '&')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '"')
                .replace(/'/g, '&#039;');
        }

        function typeBadge(type) {
            const m = {
                'Player': 'badge-primary',
                'Map': 'badge-success',
                'Entity': 'badge-warning',
                'ElementHasPosition': 'badge-danger',
                'Objective': 'badge-secondary',
                'CacheSync': 'badge-info',
                'ChimicalElement': 'badge-dark',
            };
            return '<span class="badge ' + (m[type] || 'badge-secondary') + '">' + esc(type) + '</span>';
        }

        function renderList() {
            const q = ($('#search-input').val() || '').toLowerCase().trim();
            const filtered = q ? containers.filter(function (c) {
                return (c.name || '').toLowerCase().includes(q)
                    || (c.container_id || '').toLowerCase().includes(q)
                    || String(c.ws_port || '').includes(q)
                    || (c.scope || '').toLowerCase().includes(q)
                    || (c.parent_type || '').toLowerCase().includes(q);
            }) : containers;

            if (filtered.length === 0) {
                $list.html('<div class="text-center text-muted py-4"><i class="fas fa-search fa-2x mb-2"></i><br>Nessun container trovato.</div>');
                $statTotal.html('Container: <strong>0</strong>');
                return;
            }

            let html = '';
            filtered.forEach(function (c) {
                const active = selected && selected.id === c.id;
                const addr = DOCKER_HOST + ':' + c.ws_port;
                html += '<div class="ws-container-list-item' + (active ? ' active' : '') + '" data-id="' + c.id + '">'
                    + '<div class="d-flex justify-content-between align-items-center mb-1">'
                    + '<strong style="font-size:13px;">' + esc(c.name) + '</strong> '
                    + typeBadge(c.parent_type)
                    + '</div>'
                    + '<div style="font-size:11px;color:#64748b;">'
                    + esc(c.container_id ? c.container_id.substring(0, 12) : '-')
                    + ' <span class="mx-1">|</span> '
                    + '<i class="fas fa-plug"></i> ' + esc(addr)
                    + ' <span class="mx-1">|</span> '
                    + esc(c.scope || '')
                    + ' <span class="mx-1">|</span> '
                    + '<span class="text-muted">' + esc(c.player_name) + '</span>'
                    + '</div>'
                    + '</div>';
            });

            $list.html(html);
            $statTotal.html('Container: <strong>' + filtered.length + '</strong>');

            $list.find('.ws-container-list-item').on('click', function () {
                const id = parseInt($(this).data('id'), 10);
                const c = containers.find(function (x) { return x.id === id; });
                if (c) selectContainer(c);
            });
        }

        function selectContainer(c) {
            if (ws) disconnect();
            selected = c;
            renderList();
            const addr = DOCKER_HOST + ':' + c.ws_port;
            $detailTitle.text(c.name + ' (' + addr + ')');
            setBadge('disconnected', '<i class="fas fa-circle"></i> Disconnesso');
            $btnConn.prop('disabled', false);
            $btnDisc.prop('disabled', true);
            $btnSend.prop('disabled', true);
            $cmdInp.prop('disabled', true);
            $presetArea.show();
            $cmdArea.show();
            log = [];
            renderLog();
        }

        function setBadge(cls, html) {
            $badge.prop('class', 'conn-badge ' + cls).html(html);
        }

        function addLog(dir, payload) {
            log.push({
                dir: dir,
                payload: typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2),
                time: new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
            });
            if (log.length > 1000) log = log.slice(-1000);
            renderLog();
        }

        function renderLog() {
            if ($ph.length) $ph.hide();

            if (log.length === 0) {
                $log.html('<div class="text-center text-muted py-4" id="ws-ph"><i class="fas fa-plug fa-2x mb-2"></i><br>Nessun messaggio ancora.</div>');
                return;
            }

            let html = '';
            log.forEach(function (e) {
                let pl = e.payload;
                if (pl.length > 600) pl = pl.substring(0, 600) + '...';
                html += '<div class="ws-log-entry">'
                    + '<span class="dir ' + e.dir + '">' + e.dir.toUpperCase() + '</span>'
                    + '<span class="tm">' + esc(e.time) + '</span>'
                    + '<span class="pl">' + esc(pl) + '</span>'
                    + '</div>';
            });
            $log.html(html);
            $log.scrollTop($log[0].scrollHeight);
        }

        function connect() {
            if (!selected || ws) return;
            const url = 'ws://' + DOCKER_HOST + ':' + selected.ws_port;
            addLog('system', 'Connessione a ' + url + '...');
            setBadge('connecting', '<i class="fas fa-spinner fa-spin"></i> Connessione...');
            $btnConn.prop('disabled', true);
            $btnDisc.prop('disabled', true);

            try {
                ws = new WebSocket(url);
            } catch (e) {
                addLog('error', 'Errore di connessione: ' + e.message);
                setBadge('error', '<i class="fas fa-circle"></i> Errore');
                $btnConn.prop('disabled', false);
                ws = null;
                return;
            }

            ws.onopen = function () {
                connected = true;
                addLog('system', 'Connesso!');
                setBadge('connected', '<i class="fas fa-circle"></i> Connesso');
                $btnConn.prop('disabled', true);
                $btnDisc.prop('disabled', false);
                $btnSend.prop('disabled', false);
                $cmdInp.prop('disabled', false);
                $cmdInp.focus();
                updateConnectedStat();
            };

            ws.onmessage = function (ev) {
                let data = ev.data;
                try {
                    const parsed = JSON.parse(data);
                    data = JSON.stringify(parsed, null, 2);
                } catch (_) { /* keep raw */ }
                addLog('received', data);
            };

            ws.onerror = function () {
                addLog('error', 'Errore WebSocket');
            };

            ws.onclose = function (ev) {
                connected = false;
                addLog('system', 'Disconnesso (codice ' + ev.code + ')');
                setBadge('disconnected', '<i class="fas fa-circle"></i> Disconnesso');
                $btnConn.prop('disabled', false);
                $btnDisc.prop('disabled', true);
                $btnSend.prop('disabled', true);
                $cmdInp.prop('disabled', true);
                ws = null;
                updateConnectedStat();
            };
        }

        function disconnect() {
            if (!ws) return;
            try { ws.close(); } catch (_) {}
            ws = null;
            connected = false;
            addLog('system', 'Disconnessione manuale');
            setBadge('disconnected', '<i class="fas fa-circle"></i> Disconnesso');
            $btnConn.prop('disabled', false);
            $btnDisc.prop('disabled', true);
            $btnSend.prop('disabled', true);
            $cmdInp.prop('disabled', true);
            updateConnectedStat();
        }

        function sendCommand(cmdStr) {
            if (!ws || !connected || !cmdStr) return;
            try {
                const parsed = JSON.parse(cmdStr);
                ws.send(JSON.stringify(parsed));
                addLog('sent', JSON.stringify(parsed, null, 2));
                $cmdInp.val('');
            } catch (e) {
                addLog('error', 'JSON non valido: ' + e.message);
            }
        }

        function updateConnectedStat() {
            const count = connected ? 1 : 0;
            $statConnected.toggle(count > 0);
            $statConnectedCount.text(count);
        }

        function loadContainers() {
            $list.html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Caricamento...</div>');

            $.ajax({
                url: LIST_URL,
                type: 'GET',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (!res || !res.success) return;
                    containers = Array.isArray(res.containers) ? res.containers : [];
                    const prevId = selected ? selected.id : null;
                    selected = prevId ? containers.find(function (c) { return c.id === prevId; }) : null;
                    if (!selected && containers.length > 0) {
                        selected = containers[0];
                    }
                    renderList();
                    if (selected) selectContainer(selected);
                    else {
                        $detailTitle.text('Seleziona un container');
                        $btnConn.prop('disabled', true);
                        $presetArea.hide();
                        $cmdArea.hide();
                    }
                },
                error: function () {
                    $list.html('<div class="text-center text-muted py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Errore di caricamento.</div>');
                }
            });
        }

        // Initially load
        loadContainers();

        // Refresh
        $('#btn-refresh').on('click', function () {
            if (ws) disconnect();
            loadContainers();
        });

        // Search
        $('#search-input').on('input', function () {
            renderList();
        });

        // Connect
        $('#btn-connect').on('click', connect);
        $('#btn-disconnect').on('click', disconnect);

        // Send
        $('#btn-send').on('click', function () {
            sendCommand($cmdInp.val());
        });
        $cmdInp.on('keydown', function (e) {
            if (e.key === 'Enter') sendCommand($cmdInp.val());
        });

        // Preset commands
        $(document).on('click', '.js-preset', function () {
            const cmd = $(this).data('cmd');
            if (cmd) {
                $cmdInp.val(JSON.stringify(cmd));
                sendCommand($cmdInp.val());
            }
        });

        // Clear log
        $('#btn-clear').on('click', function () {
            log = [];
            renderLog();
        });

        // Confirm before page leave
        $(window).on('beforeunload', function () {
            if (ws) try { ws.close(); } catch (_) {}
        });
    });
</script>
@stop