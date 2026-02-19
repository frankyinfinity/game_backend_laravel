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

        $.ajax({
            url: `${BACK_URL}/api/auth/game/objective/start`,
            type: 'POST',
            data: {
                player_id: resolvedPlayerId,
                target_player_id: targetPlayerId
            },
            success: function(result) {
                if (!result.success) {
                    console.warn('Start objective failed', result);
                    return;
                }

                if (objects[targetUid]['attributes']) {
                    objects[targetUid]['attributes']['target_state'] = 'in_progress';
                }

                const inProgressBg = 0x0a2a4a;
                const inProgressText = '#5dade2';

                if (objects[targetUid]) {
                    objects[targetUid]['color'] = inProgressBg;
                }

                if (shapes[targetUid]) {
                    shapes[targetUid].tint = inProgressBg;
                }

                const titleUid = targetUid.replace('_container', '_title');
                if (objects[titleUid]) {
                    objects[titleUid]['color'] = inProgressText;
                }
                if (shapes[titleUid]) {
                    if (shapes[titleUid].style) {
                        shapes[titleUid].style.fill = inProgressText;
                    }
                }

                const panelUid = targetUid + '_panel';
                const startBtnUid = panelUid + '_start_btn';
                const startBtnTextUid = startBtnUid + '_text';
                if (shapes[startBtnUid]) {
                    shapes[startBtnUid].renderable = false;
                }
                if (shapes[startBtnTextUid]) {
                    shapes[startBtnTextUid].renderable = false;
                }
            },
            error: function(err) {
                console.error('Start objective API error', err);
            }
        });
    }
    window['__name__']();
</script>
