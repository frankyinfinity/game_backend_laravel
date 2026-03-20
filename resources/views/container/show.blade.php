@extends('adminlte::page')

@section('title', 'Container Player')

@section('content_header')@stop

@section('content')
<style>
    #container-pixi {
        width: 100%;
        height: clamp(520px, 72vh, 860px);
        border: 1px solid #d8e0ea;
        border-radius: 16px;
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
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }

    .player-summary-bar {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 12px 16px;
        margin-bottom: 16px;
    }

    .player-summary-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .player-summary-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #eef2f7;
        color: #0f172a;
        font-size: 12px;
        font-weight: 600;
    }

    .player-summary-item strong {
        color: #334155;
        font-weight: 700;
    }

    .container-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
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
        padding: 8px 12px;
        border-radius: 999px;
        font-size: 12px;
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
        gap: 12px;
    }

    .container-toolbar-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f8fafc;
    }

    .container-toolbar-group-title {
        width: 100%;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 2px;
    }

    .container-toolbar-group .btn {
        min-width: 94px;
    }

    .container-toolbar-group .btn.flex-grow {
        flex: 1 1 120px;
    }

    .exec-preset-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
    }

    @media (max-width: 991.98px) {
        #container-pixi {
            height: clamp(460px, 62vh, 760px);
        }
    }

    @media (max-width: 767.98px) {
        #container-pixi {
            height: clamp(420px, 56vh, 680px);
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

<div class="row">
    <div class="col-12 col-xl-4 mb-3 mb-xl-0">
        <div class="card container-info-panel mt-3">
            <div class="card-header pb-0">
                <h4 class="mb-0">Container selezionato</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="container-pill mb-2">Nome: <strong id="selected-container-name">-</strong></div>
                    <div class="container-pill mb-2">Tipo: <strong id="selected-container-type">-</strong></div>
                    <div class="container-pill mb-2">Scope: <strong id="selected-container-scope">-</strong></div>
                    <div class="container-pill mb-2">Container ID: <strong id="selected-container-id">-</strong></div>
                    <div class="container-pill mb-2">WS Port: <strong id="selected-container-port">-</strong></div>
                    <div class="status-chip is-unknown mb-2" id="selected-container-status-chip">
                        <span class="status-dot" id="selected-container-status-dot" style="background: #64748b;"></span>
                        <span id="selected-container-status">-</span>
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
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card container-info-panel">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Container Docker</h4>
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
            <div class="card-body">
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
        };
        const SNAPSHOT_URL = "{{ route('containers.snapshot', $player) }}";

        const containerHost = document.getElementById('container-pixi');
        let app = null;
        let selectedContainer = null;
        let selectedCard = null;
        let cardRegistry = [];
        let resizeTimer = null;
        let refreshTimer = null;
        let logsRefreshTimer = null;
        let containersState = INITIAL_CONTAINERS.slice();
        let lastUpdatedText = null;
        let emptyText = null;
        let logsModalContainerId = null;

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
            textObject.y = 34;
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
                document.querySelectorAll('.js-selected-action, .js-selected-delete, .js-selected-logs, .js-selected-inspect, .js-selected-exec').forEach((btn) => btn.disabled = true);
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
            document.querySelectorAll('.js-selected-action, .js-selected-delete, .js-selected-logs, .js-selected-inspect, .js-selected-exec').forEach((btn) => btn.disabled = false);
        }

        function drawCard(container, x, y, idx) {
            const { cardWidth: width, cardHeight: height } = getCardLayout();
            const compact = width < 220;
            const card = new PIXI.Container();
            card.x = x;
            card.y = y;
            card.eventMode = 'static';
            card.cursor = 'pointer';

            const bg = new PIXI.Graphics();
            bg.beginFill(0xffffff, 0.95);
            bg.drawRoundedRect(0, 0, width, height, 18);
            bg.endFill();
            bg.lineStyle(2, 0xdbe4f0, 1);
            bg.drawRoundedRect(0, 0, width, height, 18);
            card.addChild(bg);

            const accent = new PIXI.Graphics();
            accent.beginFill(container.color || 0x64748b, 1);
            accent.drawRoundedRect(0, 0, 12, height, 18);
            accent.endFill();
            card.addChild(accent);

            const badge = new PIXI.Text(typeLabel(container.parent_type), {
                fontFamily: 'Arial',
                fontSize: compact ? 10 : 11,
                fontWeight: '700',
                fill: container.color || 0x64748b,
            });
            const badgeBg = new PIXI.Graphics();
            const badgeWidth = Math.max(78, badge.width + 18);
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
            const statusWidth = Math.max(82, statusText.width + 24);
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
                    'ID: ' + shortId(container.container_id),
                ].join('\n'),
                {
                    fontFamily: 'Arial',
                    fontSize: compact ? 11 : 12,
                    fill: 0x475569,
                    lineHeight: compact ? 15 : 16,
                }
            );
            meta.x = 24;
            meta.y = 46;
            card.addChild(meta);

            if (!compact) {
                const footer = new PIXI.Text('Clicca per gestire', {
                    fontFamily: 'Arial',
                    fontSize: 11,
                    fill: 0x94a3b8,
                    fontStyle: 'italic',
                });
                footer.x = 24;
                footer.y = height - 22;
                card.addChild(footer);
            }

            const selectedStroke = new PIXI.Graphics();
            selectedStroke.lineStyle(0, 0x2563eb, 0);
            selectedStroke.drawRoundedRect(0, 0, width, height, 18);
            card.addChild(selectedStroke);

            card.on('pointertap', () => {
                selectedContainer = container;
                cardRegistry.forEach(({ selectedStroke: stroke, card: otherCard, data }) => {
                    const active = data.id === container.id;
                    stroke.clear();
                    stroke.lineStyle(active ? 4 : 0, active ? 0x2563eb : 0x000000, 1);
                    stroke.drawRoundedRect(0, 0, width, height, 18);
                    otherCard.scale.set(active ? 1.02 : 1);
                });
                updateSelectedDetails(container);
            });

            cardRegistry.push({ card, selectedStroke, data: container });
            app.stage.addChild(card);
        }

        function layoutCards() {
            if (!app) return;
            clearStage();

            const { cardWidth: width, cardHeight: height, gapX, gapY, columns } = getCardLayout();
            const startX = 12;
            const startY = 12;

            containersState.forEach((container, index) => {
                const col = index % columns;
                const row = Math.floor(index / columns);
                const x = startX + col * (width + gapX);
                const y = startY + row * (height + gapY);
                drawCard(container, x, y, index);
            });

            const rows = Math.max(1, Math.ceil(containersState.length / columns));
            const contentHeight = startY + rows * (height + gapY) + 20;
            resizeCanvas(contentHeight);
            app.stage.hitArea = new PIXI.Rectangle(0, 0, app.renderer.width, contentHeight);

            if (!selectedContainer && containersState.length > 0) {
                selectedContainer = containersState[0];
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
                emptyText.text = containersState.length === 0 ? 'Nessun container trovato' : (containerHost.clientWidth < 700 ? 'Tocca un container' : 'Seleziona un container');
                centerEmptyText(emptyText);
            }

            updateSelectedDetails(selectedContainer);
        }

        function setLastUpdated(text) {
            lastUpdatedText = text;
            const node = document.getElementById('container-last-updated');
            if (node) {
                node.textContent = text;
            }
        }

        function refreshCards(newContainers) {
            const selectedId = selectedContainer ? selectedContainer.id : null;
            containersState = Array.isArray(newContainers) ? newContainers : [];
            selectedContainer = selectedId ? containersState.find((item) => item.id === selectedId) || null : (containersState[0] || null);
            layoutCards();
        }

        function refreshContainers(silent) {
            return $.ajax({
                url: SNAPSHOT_URL,
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response && response.success) {
                        refreshCards(response.containers || []);
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
                }
            });
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

        function fetchLogsContent(containerId) {
            return $.ajax({
                url: "{{ route('containers.logs', ['container' => '_id_']) }}".replace('_id_', containerId),
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

                fetchLogsContent(containerId)
                    .done(function (response) {
                        if (response && response.success) {
                            updateLogsModal(response.logs || '(nessun log)', 'Logs: ' + (response.name || selectedContainer.name || 'Container'));
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
                html: '<textarea id="container-logs-content" readonly class="form-control" style="min-height: 420px; font-family: monospace; white-space: pre; overflow: auto;">Caricamento logs...</textarea>',
                width: 1100,
                showConfirmButton: true,
                confirmButtonText: 'Chiudi',
                confirmButtonClass: 'btn btn-primary',
                didOpen: function () {
                    startLogsPolling(containerId);
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
                        openTextModal(
                            'Inspect: ' + (response.name || selectedContainer.name || 'Container'),
                            JSON.stringify(response.inspect || {}, null, 2),
                            1100
                        );
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
                                            const text = textarea.value || '';
                                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                                navigator.clipboard.writeText(text);
                                            } else {
                                                textarea.select();
                                                document.execCommand('copy');
                                            }
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
                setLastUpdated('Nessun container disponibile');
            } else {
                layoutCards();
                setLastUpdated('Caricamento stato...');
                refreshContainers(true);
            }

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

            refreshTimer = setInterval(function () {
                refreshContainers(true);
            }, 10000);

            window.addEventListener('beforeunload', function () {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                }
                stopLogsRefresh();
            });
        });
    </script>
@stop
