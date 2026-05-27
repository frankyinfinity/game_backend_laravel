<script>

(function() {
    var currentZoneName = null;
    var currentZonePixels = [];

    window['setZoneContext_' + '__MODAL_UID__'] = function(zoneName, pixels) {
        currentZoneName = zoneName;
        currentZonePixels = pixels;
    };

    window['updateZoneColor_' + '__MODAL_UID__'] = function() {
        if (!currentZoneName || currentZonePixels.length === 0) return;

        // Get slider values
        var redSlider = shapes['__MODAL_UID___slider_red_knob'];
        var greenSlider = shapes['__MODAL_UID___slider_green_knob'];
        var blueSlider = shapes['__MODAL_UID___slider_blue_knob'];
        var trackBgRed = shapes['__MODAL_UID___slider_red_track_bg'];
        var trackBgGreen = shapes['__MODAL_UID___slider_green_track_bg'];
        var trackBgBlue = shapes['__MODAL_UID___slider_blue_track_bg'];

        var r = 0, g = 0, b = 0;

        if (trackBgRed && redSlider) {
            var ratio = (redSlider.x - trackBgRed.x) / trackBgRed.width;
            r = Math.round(ratio * 255);
        }

        if (trackBgGreen && greenSlider) {
            var ratio = (greenSlider.x - trackBgGreen.x) / trackBgGreen.width;
            g = Math.round(ratio * 255);
        }

        if (trackBgBlue && blueSlider) {
            var ratio = (blueSlider.x - trackBgBlue.x) / trackBgBlue.width;
            b = Math.round(ratio * 255);
        }

        // Convert RGB to hex
        var hexColor = ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
        var pixiColor = parseInt('0x' + hexColor, 16);

        // Update ONLY pixels of the clicked zone (background color only, borders remain intact)
        currentZonePixels.forEach(function(pixel) {
            var cellUid = '__MODAL_UID___grid_cell_' + pixel.y + '_' + pixel.x;
            var cellShape = shapes[cellUid];
            if (cellShape) {
                cellShape.tint = pixiColor;
            }
        });
    };
})();

</script>
