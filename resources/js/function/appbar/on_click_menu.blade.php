<script>
    window['__name__'] = function() {
        const sidebarBackgroundUid = 'appbar_' + playerId + '_sidebar_background';
        const sidebarButtonUids = [
            'appbar_' + playerId + '_sidebar_objectives_button_rect',
            'appbar_' + playerId + '_sidebar_objectives_button_text',
            'appbar_' + playerId + '_sidebar_timeline_button_rect',
            'appbar_' + playerId + '_sidebar_timeline_button_text',
            'appbar_' + playerId + '_sidebar_logout_button_rect',
            'appbar_' + playerId + '_sidebar_logout_button_text'
        ];

        const sidebarBackground = shapes[sidebarBackgroundUid];

        if (!sidebarBackground) {
            return;
        }

        const isCurrentlyVisible = sidebarBackground.renderable;
        const targetX = isCurrentlyVisible ? -300 : 0;
        const startX = sidebarBackground.x;
        const duration = 300;
        const startTime = performance.now();

        // Make sidebar and buttons visible immediately when opening
        if (!isCurrentlyVisible) {
            sidebarBackground.renderable = true;
            sidebarButtonUids.forEach(uid => {
                if (shapes[uid]) {
                    shapes[uid].renderable = true;
                }
            });
        }

        const animateSidebar = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const currentX = startX + (targetX - startX) * easeProgress;
            sidebarBackground.x = currentX;

            if (progress < 1) {
                requestAnimationFrame(animateSidebar);
            } else {
                if (isCurrentlyVisible) {
                    sidebarBackground.renderable = false;
                    sidebarButtonUids.forEach(uid => {
                        if (shapes[uid]) {
                            shapes[uid].renderable = false;
                        }
                    });
                }
                sidebarBackground.x = targetX;
            }
        };

        requestAnimationFrame(animateSidebar);

        // PlayerValue polling for Timeline button visibility
        const timelineButtonRectUid = 'appbar_' + playerId + '_sidebar_timeline_button_rect';
        const timelineButtonTextUid = 'appbar_' + playerId + '_sidebar_timeline_button_text';

        const resolveEvolutionEnabled = () => {
            const wsResponse = (typeof AppData !== 'undefined') ? AppData.player_values_ws_response : null;
            if (!wsResponse || typeof wsResponse !== 'object') return false;

            const valuesFromDocker = wsResponse.data && wsResponse.data.values;
            if (valuesFromDocker && typeof valuesFromDocker === 'object') {
                return !!valuesFromDocker.evolution;
            }

            const directValues = wsResponse.values;
            if (directValues && typeof directValues === 'object') {
                return !!directValues.evolution;
            }

            return false;
        };

        const applyTimelineButtonVisibility = () => {
            const evolutionEnabled = resolveEvolutionEnabled();
            [timelineButtonRectUid, timelineButtonTextUid].forEach((uid) => {
                if (shapes[uid]) {
                    shapes[uid].renderable = evolutionEnabled;
                }
            });
        };

        // Start polling when opening sidebar
        if (!isCurrentlyVisible) {
            const resolvedPlayerWsPort = __PLAYER_WS_PORT__;

            if (!resolvedPlayerWsPort) {
                console.log('[Sidebar] No playerWsPort available, skipping PlayerValue polling');
                return;
            }

            const wsUrl = '__gateway_base__' + resolvedPlayerWsPort;

            const requestPlayerValues = () => {
                let wsKey = 'player_' + resolvedPlayerWsPort;
                let ws = window.gameWebSockets[wsKey];
                if (ws && ws.readyState === 1) {
                    ws.send(JSON.stringify({
                        command: 'get_player_values'
                    }));
                }
            };

            const bindPlayerMessageHandler = () => {
                let wsKey = 'player_' + resolvedPlayerWsPort;
                let ws = window.gameWebSockets[wsKey];
                ws.onmessage = function(event) {
                    try {
                        let response = JSON.parse(event.data);
                        if (typeof AppData !== 'undefined') {
                            AppData.player_values_ws_response = response;
                        }
                        window.playerValuesWsResponse = response;
                        applyTimelineButtonVisibility();
                    } catch (e) {
                        console.error('Player WS Parse Error:', e);
                    }
                };
            };

            const startPlayerValuePolling = (ws) => {
                console.log('[PlayerValue Polling] Starting cycle for sidebar');
                setTimeout(() => requestPlayerValues(), 50);
                AppData._playerValuePollingIntervals = AppData._playerValuePollingIntervals || {};
                AppData._playerValuePollingIntervals['sidebar_' + playerId] = setInterval(() => requestPlayerValues(), 2000);
            };

            let wsKey = 'player_' + resolvedPlayerWsPort;
            let ws = window.gameWebSockets[wsKey];

            if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
                ws = new WebSocket(wsUrl);
                window.gameWebSockets[wsKey] = ws;

                ws.onopen = function() {
                    bindPlayerMessageHandler();
                    startPlayerValuePolling(ws);
                };

                ws.onerror = function(error) {
                    console.error('Player WS Error:', error);
                };
            } else if (ws.readyState === WebSocket.OPEN) {
                bindPlayerMessageHandler();
                startPlayerValuePolling(ws);
            } else if (ws.readyState === WebSocket.CONNECTING) {
                ws.addEventListener('open', function() {
                    bindPlayerMessageHandler();
                    startPlayerValuePolling(ws);
                }, { once: true });
            }
        } else {
            // Stop polling when closing sidebar
            if (AppData && AppData._playerValuePollingIntervals && AppData._playerValuePollingIntervals['sidebar_' + playerId]) {
                clearInterval(AppData._playerValuePollingIntervals['sidebar_' + playerId]);
                delete AppData._playerValuePollingIntervals['sidebar_' + playerId];
                console.log('[PlayerValue Polling] Stopped for sidebar');
            }
        }
    }
    window['__name__']();
</script>
