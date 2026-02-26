<script>
    window['__name__'] = function() {

        const deltaX = parseInt('__delta_x__', 10);
        const deltaY = parseInt('__delta_y__', 10);
        const excludedPrefixes = ['appbar', 'map_nav_', 'modal_'];

        const isScoreUid = (uid) => {
            return uid.indexOf('_score_') !== -1;
        };

        const shouldSkipUid = (uid) => {
            return isScoreUid(uid) || excludedPrefixes.some((prefix) => uid.indexOf(prefix) === 0);
        };

        const getObjectMinY = (obj) => {
            if (!obj) {
                return null;
            }

            if (obj.type === 'multi_line' && Array.isArray(obj.points) && obj.points.length > 0) {
                let minY = obj.points[0].y;
                for (let i = 1; i < obj.points.length; i++) {
                    if (obj.points[i].y < minY) {
                        minY = obj.points[i].y;
                    }
                }
                return minY;
            }

            if (typeof obj.y === 'number') {
                return obj.y;
            }

            return null;
        };

        Object.keys(objects).forEach((uid) => {
            if (shouldSkipUid(uid)) {
                return;
            }

            const object = objects[uid];
            const shape = shapes[uid];
            if (!object || !shape) {
                return;
            }

            const minY = getObjectMinY(object);
            if (minY === null) {
                return;
            }

            if (object.type === 'multi_line' && Array.isArray(object.points)) {
                object.points = object.points.map((point) => {
                    return {
                        x: point.x + deltaX,
                        y: point.y + deltaY
                    };
                });

                if (typeof shape.clear === 'function') {
                    shape.clear();
                    shape.lineStyle(object.thickness || 1, 0xFFFFFF);
                    shape.tint = object.color;
                    if (object.points.length > 0) {
                        shape.moveTo(object.points[0].x, object.points[0].y);
                        for (let i = 1; i < object.points.length; i++) {
                            shape.lineTo(object.points[i].x, object.points[i].y);
                        }
                    }
                }
                return;
            }

            if (typeof object.x === 'number') {
                object.x += deltaX;
                shape.x += deltaX;
            }
            if (typeof object.y === 'number') {
                object.y += deltaY;
                shape.y += deltaY;
            }
        });

        if (typeof refreshAllModalViewportMasks === 'function') {
            refreshAllModalViewportMasks();
        }

        if (app && app.stage) {
            app.stage.sortChildren();
        }

    }
    window['__name__']();
</script>
