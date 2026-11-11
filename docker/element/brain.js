// brain.js — esecuzione brain nel container element
// Invocato dal comando websocket 'execute-brain' di index.js.

const http = require('http');
const { URL } = require('url');

/**
 * Esegue il brain per lo schedule indicato.
 * WIP: log + marcatura dello BrainSchedule come terminato via API.
 *
 * @param {number} brainScheduleId - id della brain_schedule da eseguire
 * @param {object} context - contesto del container (elementHasPositionId,
 *                           backendUrl, getSessionCookie, getXsrfToken)
 */
function executeBrain(brainScheduleId, context = {}) {
    const ehpLabel = context.elementHasPositionId ? ` (ehp: ${context.elementHasPositionId})` : '';
    console.log(`[Brain] executeBrain — brain_schedule_id: ${brainScheduleId}${ehpLabel}`);

    finishBrainSchedule(brainScheduleId, context);
}

/**
 * Chiama l'API che porta lo BrainSchedule allo stato finale.
 */
function finishBrainSchedule(brainScheduleId, context) {
    const backendUrl = context.backendUrl;
    const sessionCookie = context.getSessionCookie ? context.getSessionCookie() : null;
    const xsrfToken = context.getXsrfToken ? context.getXsrfToken() : null;

    if (!backendUrl || !sessionCookie) {
        console.warn(`[Brain] finishBrainSchedule skipped — no backend session available (brain_schedule_id: ${brainScheduleId})`);
        return;
    }

    const url = new URL(backendUrl);
    const postData = JSON.stringify({ brain_schedule_id: brainScheduleId });

    const options = {
        hostname: url.hostname,
        port: url.port || 80,
        path: '/api/auth/game/brain_flow/finish',
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(postData),
            Accept: 'application/json',
            Cookie: sessionCookie,
            'X-XSRF-TOKEN': xsrfToken,
        },
    };

    const req = http.request(options, (res) => {
        let data = '';
        res.on('data', (chunk) => { data += chunk; });
        res.on('end', () => {
            try {
                const response = data ? JSON.parse(data) : {};
                console.log(`[Brain] brain_flow/finish response (brain_schedule_id: ${brainScheduleId}):`, JSON.stringify(response));
            } catch (error) {
                console.error(`[Brain] brain_flow/finish parse error: ${error.message}`);
            }
        });
    });

    req.on('error', (error) => {
        console.error(`[Brain] brain_flow/finish request error: ${error.message}`);
    });

    req.write(postData);
    req.end();
}

module.exports = { executeBrain };
