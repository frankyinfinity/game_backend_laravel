// brain.js — esecuzione brain nel container element
// Invocato dal comando websocket 'execute-brain' di index.js.

const http = require('http');
const { URL } = require('url');

/**
 * Esegue il brain per lo schedule indicato.
 * WIP: log -> fetch percorsi possibili -> marcatura finale via API.
 *
 * @param {number} brainScheduleId - id della brain_schedule da eseguire
 * @param {object} context - contesto del container (elementHasPositionId,
 *                           backendUrl, getSessionCookie, getXsrfToken)
 */
async function executeBrain(brainScheduleId, context = {}) {
    const ehpLabel = context.elementHasPositionId ? ` (ehp: ${context.elementHasPositionId})` : '';
    console.log(`[Brain] executeBrain — brain_schedule_id: ${brainScheduleId}${ehpLabel}`);

    const pathsJson = await fetchBrainPaths(brainScheduleId, context);
    console.log(`[Brain] brain_flow/paths (brain_schedule_id: ${brainScheduleId}):`, JSON.stringify(pathsJson));

    await finishBrainSchedule(brainScheduleId, context);
}

/**
 * Scarica i percorsi possibili dello BrainSchedule dall'API brain_flow/paths.
 * @returns {Promise<object|null>} il JSON della risposta, null se non disponibile
 */
function fetchBrainPaths(brainScheduleId, context) {
    return postJson('/api/auth/game/brain_flow/paths', { brain_schedule_id: brainScheduleId }, context);
}

/**
 * Chiama l'API che porta lo BrainSchedule allo stato finale.
 */
function finishBrainSchedule(brainScheduleId, context) {
    return postJson('/api/auth/game/brain_flow/finish', { brain_schedule_id: brainScheduleId }, context);
}

/**
 * POST JSON verso il backend con session cookie + XSRF.
 * @returns {Promise<object|null>} risposta decodificata, null su errore/sessione assente
 */
function postJson(path, payload, context) {
    const backendUrl = context.backendUrl;
    const sessionCookie = context.getSessionCookie ? context.getSessionCookie() : null;
    const xsrfToken = context.getXsrfToken ? context.getXsrfToken() : null;

    if (!backendUrl || !sessionCookie) {
        console.warn(`[Brain] ${path} skipped — no backend session available`);
        return Promise.resolve(null);
    }

    const url = new URL(backendUrl);
    const postData = JSON.stringify(payload);

    const options = {
        hostname: url.hostname,
        port: url.port || 80,
        path,
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(postData),
            Accept: 'application/json',
            Cookie: sessionCookie,
            'X-XSRF-TOKEN': xsrfToken,
        },
    };

    return new Promise((resolve) => {
        const req = http.request(options, (res) => {
            let data = '';
            res.on('data', (chunk) => { data += chunk; });
            res.on('end', () => {
                try {
                    resolve(data ? JSON.parse(data) : {});
                } catch (error) {
                    console.error(`[Brain] ${path} parse error: ${error.message}`);
                    resolve(null);
                }
            });
        });

        req.on('error', (error) => {
            console.error(`[Brain] ${path} request error: ${error.message}`);
            resolve(null);
        });

        req.write(postData);
        req.end();
    });
}

module.exports = { executeBrain };
