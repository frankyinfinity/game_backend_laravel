<script>
    window['__name__'] = function() {
        const targetUid = AppData.actual_focus_uid_target;
        if (!targetUid || !objects[targetUid]) {
            console.warn('No focused target found for start action');
            return;
        }

        const targetAttributes = objects[targetUid]['attributes'] || {};
        const targetPlayerId = targetAttributes['target_player_id'];
        if (!targetPlayerId) {
            console.warn('Missing target_player_id for start action');
            return;
        }

        let resolvedPlayerId = null;
        if (typeof playerId !== 'undefined') {
            resolvedPlayerId = playerId;
        } else if (typeof testPlayerId !== 'undefined') {
            resolvedPlayerId = testPlayerId;
        } else if (typeof window !== 'undefined' && typeof window.playerId !== 'undefined') {
            resolvedPlayerId = window.playerId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.player_id !== 'undefined') {
            resolvedPlayerId = AppData.player_id;
        }

        if (!resolvedPlayerId) {
            console.error('Player id variable not found');
            return;
        }

        let resolvedDrawPlayerId = resolvedPlayerId;
        if (typeof testPlayerId !== 'undefined') {
            resolvedDrawPlayerId = testPlayerId;
        }

        let resolvedSessionId = null;
        if (typeof sessionId !== 'undefined') {
            resolvedSessionId = sessionId;
        } else if (typeof AppData !== 'undefined' && typeof AppData.session_id !== 'undefined') {
            resolvedSessionId = AppData.session_id;
        }

        const panelUid = targetUid + '_panel';
        const startBtnUid = panelUid + '_start_btn';
        const startBtnTextUid = startBtnUid + '_text';

        const setStartButtonVisible = function(visible) {
            if (shapes[startBtnUid]) {
                shapes[startBtnUid].renderable = !!visible;
            }
            if (objects[startBtnUid] && objects[startBtnUid]['attributes']) {
                objects[startBtnUid]['attributes']['renderable'] = !!visible;
            }
            if (shapes[startBtnTextUid]) {
                shapes[startBtnTextUid].renderable = !!visible;
            }
            if (objects[startBtnTextUid] && objects[startBtnTextUid]['attributes']) {
                objects[startBtnTextUid]['attributes']['renderable'] = !!visible;
            }
        };
        const setAllStartButtonsVisible = function(visible) {
            const renderable = !!visible;
            if (typeof shapes !== 'undefined') {
                Object.keys(shapes).forEach(function(uid) {
                    if (uid.endsWith('_panel_start_btn') || uid.endsWith('_panel_start_btn_text')) {
                        shapes[uid].renderable = renderable;
                    }
                });
            }
            if (typeof objects !== 'undefined') {
                Object.keys(objects).forEach(function(uid) {
                    if (uid.endsWith('_panel_start_btn') || uid.endsWith('_panel_start_btn_text')) {
                        if (!objects[uid]['attributes']) {
                            objects[uid]['attributes'] = {};
                        }
                        objects[uid]['attributes']['renderable'] = renderable;
                    }
                });
            }
        };
        const hasAnyInProgressTarget = function() {
            if (typeof objects === 'undefined') return false;
            return Object.keys(objects).some(function(uid) {
                if (!uid.endsWith('_container')) return false;
                const attrs = (objects[uid] && objects[uid]['attributes']) ? objects[uid]['attributes'] : null;
                return attrs && attrs['target_state'] === 'in_progress';
            });
        };
        const syncStartButtonsWithState = function() {
            const hasActive = hasAnyInProgressTarget();
            if (typeof AppData !== 'undefined') {
                AppData.objective_has_active_in_progress = hasActive;
            }
            if (hasActive) {
                setAllStartButtonsVisible(false);
            }
        };
        const getStateLabel = function(state) {
            if (state === 'locked') return 'Bloccato';
            if (state === 'unlocked') return 'Sbloccato';
            if (state === 'in_progress') return 'In corso';
            if (state === 'completed') return 'Completato';
            return state || '';
        };
        const stateValueUid = targetUid + '_panel_state_value';

        const inProgressBg = 0x0a2a4a;
        const inProgressText = '#5dade2';
        const unlockedBg = 0x1a3a1a;
        const unlockedText = '#7ed66f';
        const titleUid = targetUid.replace('_container', '_title');

        // Hide immediately and mark as not unlocked, so the button does not reappear on panel reopen.
        setStartButtonVisible(false);
        if (objects[targetUid] && objects[targetUid]['attributes']) {
            objects[targetUid]['attributes']['target_state'] = 'in_progress';
        }
        if (typeof AppData !== 'undefined') {
            AppData.objective_has_active_in_progress = true;
        }
        setAllStartButtonsVisible(false);
        if (objects[targetUid]) {
            objects[targetUid]['color'] = inProgressBg;
        }
        if (shapes[targetUid]) {
            shapes[targetUid].tint = inProgressBg;
        }
        if (objects[titleUid]) {
            objects[titleUid]['color'] = inProgressText;
        }
        if (shapes[titleUid] && shapes[titleUid].style) {
            shapes[titleUid].style.fill = inProgressText;
        }
        if (shapes[stateValueUid]) {
            shapes[stateValueUid].text = getStateLabel('in_progress');
        }

        const requestData = {
            player_id: resolvedPlayerId,
            target_player_id: targetPlayerId,
            draw_player_id: resolvedDrawPlayerId
        };
        if (resolvedSessionId) {
            requestData.session_id = resolvedSessionId;
        }

        $.ajax({
            url: `${BACK_URL}/api/auth/game/objective/start`,
            type: 'POST',
            data: requestData,
            success: function(result) {
                if (!result.success) {
                    console.warn('Start objective failed', result);
                    if (objects[targetUid] && objects[targetUid]['attributes']) {
                        objects[targetUid]['attributes']['target_state'] = 'unlocked';
                    }
                    if (objects[targetUid]) {
                        objects[targetUid]['color'] = unlockedBg;
                    }
                    if (shapes[targetUid]) {
                        shapes[targetUid].tint = unlockedBg;
                    }
                    if (objects[titleUid]) {
                        objects[titleUid]['color'] = unlockedText;
                    }
                    if (shapes[titleUid] && shapes[titleUid].style) {
                        shapes[titleUid].style.fill = unlockedText;
                    }
                    if (shapes[stateValueUid]) {
                        shapes[stateValueUid].text = getStateLabel('unlocked');
                    }
                    syncStartButtonsWithState();
                    if (!hasAnyInProgressTarget()) {
                        setStartButtonVisible(true);
                    }
                    return;
                }
                syncStartButtonsWithState();
            },
            error: function(err) {
                console.error('Start objective API error', err);
                if (objects[targetUid] && objects[targetUid]['attributes']) {
                    objects[targetUid]['attributes']['target_state'] = 'unlocked';
                }
                if (objects[targetUid]) {
                    objects[targetUid]['color'] = unlockedBg;
                }
                if (shapes[targetUid]) {
                    shapes[targetUid].tint = unlockedBg;
                }
                if (objects[titleUid]) {
                    objects[titleUid]['color'] = unlockedText;
                }
                if (shapes[titleUid] && shapes[titleUid].style) {
                    shapes[titleUid].style.fill = unlockedText;
                }
                if (shapes[stateValueUid]) {
                    shapes[stateValueUid].text = getStateLabel('unlocked');
                }
                syncStartButtonsWithState();
                if (!hasAnyInProgressTarget()) {
                    setStartButtonVisible(true);
                }
            }
        });
    }
    window['__name__']();
</script>
