<script>
    window['__name__'] = function () {

        // Guard to prevent duplicate polling instances on syncObjectCache re-execution
        if (window.__scorePollingInitialized) {
            console.log('[Score] Polling already initialized, skipping duplicate');
            return;
        }
        window.__scorePollingInitialized = true;

        let playerId = '__PLAYER_ID__';
        let scoreWs = null;
        let scoreWsUrl = null;
        let scorePollTimer = null;

        // Find score container websocket from websocket_info response
        const findScoreWsUrl = (containers) => {
            if (!Array.isArray(containers)) return null;
            const scoreContainer = containers.find((c) =>
                c && (c.type === 'Score' || (c.name && c.name.indexOf('score_') === 0))
            );
            if (scoreContainer && scoreContainer.ws_gateway_url) {
                return scoreContainer.ws_gateway_url;
            }
            if (scoreContainer && scoreContainer.ws_url) {
                return scoreContainer.ws_url;
            }
            return null;
        };

        // Refresh the score websocket connection and start polling
        const refreshScoreWebSocket = (url) => {
            if (scoreWs && scoreWs.readyState === WebSocket.OPEN) {
                scoreWs.close();
            }
            scoreWsUrl = url;

            scoreWs = new WebSocket(url);
            scoreWs.onopen = function () {
                console.log('[Score] WebSocket connected');
            };
            scoreWs.onmessage = function (event) {
                try {
                    const response = JSON.parse(event.data);
                    handleScoreResponse(response);
                } catch (e) {
                    console.error('[Score] Error parsing WS response:', e);
                }
            };
            scoreWs.onerror = function (err) {
                console.error('[Score] WebSocket error:', err);
            };
            scoreWs.onclose = function () {
                console.log('[Score] WebSocket closed');
            };
        };

        // Update score text objects in the UI
        const updateScoreText = (scoreId, newValue) => {
            const textUid = 'player_' + playerId + '_score_' + scoreId + '_text';
            const rectUid = 'player_' + playerId + '_score_' + scoreId + '_rect';
            const valueStr = String(newValue);
            
            // Update visible text
            if (objects[textUid]) {
                objects[textUid].text = valueStr;
                if (shapes[textUid]) {
                    shapes[textUid].text = valueStr;
                }
            }

            // Update tooltip on the rect (formato "nome: value")
            if (objects[rectUid] && objects[rectUid].attributes && objects[rectUid].attributes.tooltip_text) {
                const oldTooltip = objects[rectUid].attributes.tooltip_text;
                const colonIndex = oldTooltip.lastIndexOf(': ');
                if (colonIndex !== -1) {
                    const name = oldTooltip.substring(0, colonIndex);
                    objects[rectUid].attributes.tooltip_text = name + ': ' + valueStr;
                }
            }
        };

        // Handle score data coming from the websocket
        const handleScoreResponse = (response) => {
            console.log('[Score] WS Response:', response);

            // Support both direct "scores" array and nested data
            const scores = (response && response.scores)
                ? response.scores
                : (response && response.data && response.data.scores)
                    ? response.data.scores
                    : null;
            if (!scores) return;

            if (Array.isArray(scores)) {
                scores.forEach(function (score) {
                    if (score && score.score_id !== undefined && score.value !== undefined) {
                        updateScoreText(score.score_id, score.value);
                    }
                });
            } else if (typeof scores === 'object') {
                Object.keys(scores).forEach(function (scoreId) {
                    const value = scores[scoreId];
                    if (value !== null && value !== undefined) {
                        updateScoreText(scoreId, value);
                    }
                });
            }
        };

        // Poll the score websocket every 1 second
        const startScorePolling = () => {
            if (scorePollTimer) {
                clearInterval(scorePollTimer);
            }

            scorePollTimer = setInterval(function () {
                if (!scoreWs || scoreWs.readyState !== WebSocket.OPEN) return;

                scoreWs.send(JSON.stringify({
                    command: 'get',
                    params: {
                        player_id: playerId
                    }
                }));
            }, 1000);
        };

        // Stop the score polling and close the websocket
        const stopScorePolling = () => {
            if (scorePollTimer) {
                clearInterval(scorePollTimer);
                scorePollTimer = null;
            }
            if (scoreWs) {
                try { scoreWs.close(); } catch (e) { }
                scoreWs = null;
            }
            console.log('[Score] Polling stopped');
        };

        // Expose stop function globally so logout can call it
        window.stopScorePolling = stopScorePolling;

        // Fetch websocket info and connect to score container
        const initScorePolling = () => {
            if (typeof $ === 'undefined' || typeof BACK_URL === 'undefined') {
                console.warn('[Score] Missing $ or BACK_URL');
                return;
            }

            $.ajax({
                url: BACK_URL + '/api/game/websocket_info',
                type: 'POST',
                data: { player_id: playerId }
            }).then(function (response) {
                if (!response || !response.success || !response.containers) {
                    console.warn('[Score] Invalid websocket_info response');
                    return;
                }

                const wsUrl = findScoreWsUrl(response.containers);
                if (!wsUrl) {
                    console.warn('[Score] Score container websocket URL not found');
                    return;
                }

                refreshScoreWebSocket(wsUrl);
                startScorePolling();
            }).catch(function (err) {
                console.error('[Score] Failed to fetch websocket_info:', err);
            });
        };

        initScorePolling();
    }
    window['__name__']();
</script>