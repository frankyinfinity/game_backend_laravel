// brain.js — esecuzione brain nel container element
// Invocato dal comando websocket 'execute-brain' di index.js.

/**
 * Esegue il brain per lo schedule indicato.
 * WIP: per ora solo log su console.
 *
 * @param {number} brainScheduleId - id della brain_schedule da eseguire
 * @param {object} context - contesto del container (es. elementHasPositionId)
 */
function executeBrain(brainScheduleId, context = {}) {
    const ehpLabel = context.elementHasPositionId ? ` (ehp: ${context.elementHasPositionId})` : '';
    console.log(`[Brain] executeBrain — brain_schedule_id: ${brainScheduleId}${ehpLabel}`);
}

module.exports = { executeBrain };
