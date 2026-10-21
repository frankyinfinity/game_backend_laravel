<script>
    window['__name__'] = function() {
        const sidebarBackgroundUid = 'appbar_' + playerId + '_sidebar_background';
        const sidebarButtonUids = [
            'appbar_' + playerId + '_sidebar_objectives_button_rect',
            'appbar_' + playerId + '_sidebar_objectives_button_text',
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
    }
    window['__name__']();
</script>
