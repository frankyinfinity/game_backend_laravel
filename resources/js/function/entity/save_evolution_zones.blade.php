<script>
window['__name__'] = function() {
    var modalUid = '__MODAL_UID__';
    var pixels = window['_evoPixels_' + modalUid] || [];
    var modified = window['_evoModifiedZones_' + modalUid] || {};

    // Build a zone info map (zone_id -> zone_name) from the pixel data
    var zoneInfo = {};
    for (var i = 0; i < pixels.length; i++) {
        var zid = pixels[i].zone_id;
        if (zid !== null && zid !== undefined && !zoneInfo[zid]) {
            zoneInfo[zid] = {
                zone_id: zid,
                zone_name: pixels[i].name || '',
            };
        }
    }

    // Build the JSON array of modified zones with the required structure
    var modifiedZones = [];
    for (var key in modified) {
        if (modified.hasOwnProperty(key)) {
            var zoneId   = parseInt(key, 10);
            var pc       = modified[key];

            // Ensure pc is treated as a number
            if (typeof pc !== 'number') {
                pc = parseInt(pc, 16);
            }

            var r = (pc >> 16) & 255;
            var g = (pc >> 8) & 255;
            var b = pc & 255;

            modifiedZones.push({
                zone_id:   zoneId,
                zone_name: zoneInfo[zoneId] ? zoneInfo[zoneId].zone_name : '',
                r: r,
                g: g,
                b: b
            });
        }
    }

    var jsonString = JSON.stringify(modifiedZones, null, 2);

    // Expose the generated JSON via a window variable for external inspection
    window['_evoSavedZones_' + modalUid] = jsonString;

    if (modifiedZones.length === 0) {
        console.log('[Evolution Save] Nessuna zona modificata');
        return;
    }

    console.log('[Evolution Save] Zonal modificate salvate:', jsonString);
};
window['__name__']();
</script>
