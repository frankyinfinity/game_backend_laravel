<script>
    window['__name__'] = function() {

        const deltaX = parseInt('__delta_x__', 10);
        const deltaY = parseInt('__delta_y__', 10);
        const scrollGroup = '__scroll_group__';
        if (typeof window.ensureScrollGroupState === 'function') {
            window.ensureScrollGroupState(AppData, scrollGroup);
        }

        if (!window.ScrollGroupManager) {
            window.ScrollGroupManager = class {
                constructor(objectsRef, shapesRef) {
                    this.objects = objectsRef;
                    this.shapes = shapesRef;
                }

                isUiUid(uid) {
                    return uid.indexOf('appbar') === 0
                        || uid.indexOf('map_nav_') === 0
                        || uid.indexOf('modal_') === 0
                        || uid.indexOf('_score_') !== -1;
                }

                isInGroup(uid, obj, groupName) {
                    const objGroup = obj?.attributes?.scroll_group ?? null;
                    if (this.isUiUid(uid)) return false;
                    return objGroup === groupName;
                }

                move(groupName, dx, dy) {
                    Object.keys(this.objects).forEach((uid) => {
                        const object = this.objects[uid];
                        const shape = this.shapes[uid];
                        if (!object || !shape) return;
                        if (!this.isInGroup(uid, object, groupName)) return;

                        if (object.type === 'multi_line' && Array.isArray(object.points)) {
                            object.points = object.points.map((point) => ({
                                x: point.x + dx,
                                y: point.y + dy
                            }));

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
                            object.x += dx;
                            shape.x += dx;
                        }
                        if (typeof object.y === 'number') {
                            object.y += dy;
                            shape.y += dy;
                        }
                    });
                }
            };
        }

        const scrollGroupManager = new window.ScrollGroupManager(objects, shapes);
        scrollGroupManager.move(scrollGroup, deltaX, deltaY);

        if (typeof window.incrementScrollGroupOffset === 'function') {
            window.incrementScrollGroupOffset(AppData, scrollGroup, deltaX, deltaY);
        }

        if (typeof refreshAllModalViewportMasks === 'function') {
            refreshAllModalViewportMasks();
        }
        if (typeof runScrollClipping === 'function') {
            runScrollClipping();
        }

        if (app && app.stage) {
            app.stage.sortChildren();
        }

    }
    window['__name__']();
</script>
