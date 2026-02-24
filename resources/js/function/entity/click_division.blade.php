<script>
    window['__name__'] = function() {
        const showBottomRightAlert = function(message) {
            if (!message) {
                return;
            }

            const containerId = 'game-toast-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.position = 'fixed';
                container.style.right = '16px';
                container.style.bottom = '16px';
                container.style.zIndex = '999999';
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '8px';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.background = '#d32f2f';
            toast.style.color = '#ffffff';
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '8px';
            toast.style.fontSize = '14px';
            toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.22)';
            toast.style.maxWidth = '320px';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 180ms ease';

            container.appendChild(toast);
            requestAnimationFrame(function() {
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';
            });

            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 180);
            }, 3000);
        };

        const entityUid = (typeof AppData !== 'undefined') ? AppData.actual_focus_uid_entity : null;
        if (!entityUid) {
            console.warn('Division click: no selected entity uid');
            return;
        }

        $.ajax({
            url: `${BACK_URL}/api/auth/game/entity/division`,
            type: 'POST',
            data: {
                entity_uid: entityUid
            },
            success: function(response) {
                console.log('Division response:', response);
                if (response && response.success === false && response.message) {
                    showBottomRightAlert(response.message);
                }
            },
            error: function(err) {
                console.error('Division API error:', err);
                const message = err && err.responseJSON && err.responseJSON.message
                    ? err.responseJSON.message
                    : 'Divisione non disponibile';
                showBottomRightAlert(message);
            }
        });
    }
    window['__name__']();
</script>
