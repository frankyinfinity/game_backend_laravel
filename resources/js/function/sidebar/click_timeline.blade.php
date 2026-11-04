<script>
    window['__name__'] = function() {
        console.log('Timeline button clicked - closing sidebar and opening timeline modal');

        // 1) Close the sidebar
        if (AppData && AppData._playerId) {
            const playerId = AppData._playerId;
            const sidBarBg = 'appbar_' + playerId + '_sidebar_background';
            if (shapes[sidBarBg] && shapes[sidBarBg].renderable) shapes[sidBarBg].renderable = false;
            const btns = ['appbar_'+playerId+'_sidebar_objectives_button_rect','appbar_'+playerId+'_sidebar_objectives_button_text','appbar_'+playerId+'_sidebar_timeline_button_rect','appbar_'+playerId+'_sidebar_timeline_button_text','appbar_'+playerId+'_sidebar_logout_button_rect','appbar_'+playerId+'_sidebar_logout_button_text'];
            btns.forEach(uid => { if (shapes[uid] && shapes[uid].renderable) shapes[uid].renderable = false; });
            if (AppData._playerValuePollingIntervals && AppData._playerValuePollingIntervals['sidebar_'+playerId]) { clearInterval(AppData._playerValuePollingIntervals['sidebar_'+playerId]); delete AppData._playerValuePollingIntervals['sidebar_'+playerId]; }
        }

        // 2) Open the Timeline Evolutiva modal FIRST
        if (AppData && AppData._playerId) {
            const playerId = AppData._playerId;
            const modalUid = 'timeline_evolutiva_modal';
            // Open the modal (this sets up the viewport and makes it visible)
            if (typeof window.openModal === 'function') {
                window.openModal(modalUid);
            }
        }

        // 3) Make WebSocket call to evolution container get_timeline
        if (AppData && AppData._playerId) {
            const playerId = AppData._playerId;
            const modalUid = 'timeline_evolutiva_modal';
            const ws = new WebSocket('ws://'+window.config.REVERB_HOST+':'+window.config.REVERB_PORT+'/?port='+playerId);
            ws.onopen = () => ws.send(JSON.stringify({type:'get_timeline',player_id:playerId}));
            ws.onmessage = e => {
                const d = JSON.parse(e.data);
                if (d.type==='timeline' && d.elements) {
                    // 4) Draw the response using addElementsToModal.js function
                    window.addElementsToModal_(modalUid, d.elements);
                    if (typeof window.refreshModalViewport==='function') window.refreshModalViewport(modalUid);
                }
            };
            ws.onerror = () => { /* Modal already opened in step 2 */ };
        }
    };
    window['__name__']();
</script>
