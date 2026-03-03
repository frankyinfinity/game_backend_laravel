<script>
const elementHasPositionId = __ELEMENT_HAS_POSITION_ID__;
const baseUrl = (window.BACK_URL || '').toString().replace(/\/+$/, '');
if (typeof $ !== 'undefined' && baseUrl !== '' && Number.isInteger(elementHasPositionId)) {
    $.ajax({
        url: `${baseUrl}/api/auth/game/brain_schedule/finish`,
        type: 'POST',
        data: {
            element_has_position_id: elementHasPositionId
        },
        success: function() {
            console.log(`BrainSchedule finish OK for element_has_position_id=${elementHasPositionId}`);
        },
        error: function(err) {
            console.error('BrainSchedule finish error', err);
        }
    });
} else {
    console.warn('BrainSchedule finish skipped: missing jQuery/BACK_URL/element_has_position_id');
}
</script>
