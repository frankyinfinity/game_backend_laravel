// brain.js — esecuzione brain nel container element
// Invocato dal comando websocket 'execute-brain' di index.js.

const http = require('http');
const { URL } = require('url');
const Pusher = require('pusher');
const WebSocket = require('ws');
const fs = require('fs');

// Configurazione Pusher (stesso pattern del container entity)
function isRunningInDocker() {
  try { return fs.existsSync('/.dockerenv'); } catch (e) { return false; }
}
function resolveReverbHost(rawHost) {
  if (isRunningInDocker() && (rawHost === 'localhost' || rawHost === '0.0.0.0')) {
    return process.env.DOCKER_HOST_IP || '127.0.0.1';
  }
  return rawHost;
}
const reverbScheme = process.env.REVERB_SCHEME || 'http';
const pusher = new Pusher({
  appId: process.env.REVERB_APP_ID || 'game',
  key: process.env.REVERB_APP_KEY || 'game-key',
  secret: process.env.REVERB_APP_SECRET || 'game-secret',
  host: resolveReverbHost(process.env.REVERB_HOST || 'localhost'),
  port: process.env.REVERB_PORT || '8081',
  scheme: reverbScheme,
  useTLS: reverbScheme === 'https',
});

// Geometria mappa (allineata a Helper::TILE_SIZE / MAP_START_X/Y)
const TILE_SIZE = parseInt(process.env.TILE_SIZE || '40', 10);
const MAP_START_X = parseInt(process.env.MAP_START_X || '0', 10);
const MAP_START_Y = parseInt(process.env.MAP_START_Y || '80', 10);

// Accesso al map container via ws-gateway / direct (stesso pattern entity)
const wsGatewayHost = process.env.WS_GATEWAY_HOST || '127.0.0.1';
const wsGatewayPort = process.env.WS_GATEWAY_PORT || 9001;
const mapWsPort = process.env.MAP_WS_PORT || 8080;
const mapDirectHost = process.env.MAP_DIRECT_HOST || '127.0.0.1';
const mapDirectPort = process.env.MAP_DIRECT_PORT || 8080;
const WALKABLE_RESPONSE_TIMEOUT_MS = parseInt(process.env.WALKABLE_RESPONSE_TIMEOUT_MS || '5000', 10);
const WALKABLE_RETRY_DELAY_MS = parseInt(process.env.WALKABLE_RETRY_DELAY_MS || '1000', 10);
// Intervallo tra un tile e l'altro nell'animazione del movimento (entity: 400ms)
const MOVE_INTERVAL_MS = parseInt(process.env.MOVE_INTERVAL_MS || '400', 10);

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

    await executeNeuronSteps(pathsJson, context);

    await finishBrainSchedule(brainScheduleId, context);
}

/**
 * Gira tutti gli step di tutti i path: per ogni neurone
 * attiva -> esegue l'azione del suo tipo -> disattiva -> prossimo.
 */
async function executeNeuronSteps(pathsJson, context) {
    const circuits = Array.isArray(pathsJson && pathsJson.circuits) ? pathsJson.circuits : [];
    // Flat di tutti i path: serve per risolvere il salto verso un nuovo
    // circuito quando un path termina con END.
    const allPaths = circuits.flatMap((circuit) => Array.isArray(circuit.paths) ? circuit.paths : []);

    for (const circuit of circuits) {
        const circuitPaths = Array.isArray(circuit.paths) ? circuit.paths : [];
        for (const path of circuitPaths) {
            const steps = Array.isArray(path.steps) ? path.steps : [];

            // Stile interpreter: actualNeuron è lo step corrente; al punto 5 il
            // path viene "aggiornato" risolvendo a runtime lo step successivo
            // tramite il link uscente dal neurone appena eseguito.
            const visited = new Set();
            let currentSteps = steps;
            let actualNeuron = steps.find((s) => !s.condition) || steps[0] || null;
        while (actualNeuron) {
            const neuronId = actualNeuron.element_has_position_neuron_id;
            if (!neuronId || visited.has(neuronId)) {
                break;
            }
            visited.add(neuronId);

            // 1) attiva neurone (solo DB) + update border via pusher
            await postJson('/api/auth/game/brain_flow/neuron_state', {
                element_has_position_neuron_id: neuronId,
                active: true,
            }, context);
            await updateNeuronBorder(neuronId, '0x00FF00', context);

            // 2) azione per tipo di neurone
            const neuronType = actualNeuron.neuron && actualNeuron.neuron.information ? actualNeuron.neuron.information.type : null;
            switch (neuronType) {
                case 'movement': {
                    // 3) muovi di una cella a caso entro radius (scelta lato container)
                    const moveResult = await executeMovementNeuron(neuronId, context);
                    console.log(`[Brain] move (neuron ${neuronId}):`, JSON.stringify(moveResult));
                    break;
                }
                default:
                    break;
            }

            // 4) disattiva neurone (solo DB) + update border via pusher
            await postJson('/api/auth/game/brain_flow/neuron_state', {
                element_has_position_neuron_id: neuronId,
                active: false,
            }, context);
            await updateNeuronBorder(neuronId, '0xFFFFFF', context);

            // 5) prossimo neurone: segui il link 'trigger' uscente dal neurone
            // corrente. Se non esiste nel path corrente (END), il link può
            // puntare a un neurone di un altro circuito -> entra in quel path.
            const resolved = resolveNextNeuron(currentSteps, allPaths, neuronId, actualNeuron);
            if (!resolved) {
                break;
            }
            actualNeuron = resolved.step;
            currentSteps = resolved.steps;
            }
        }
    }
}

/**
 * Risolve lo step successivo di `actualNeuron`:
 * 1) dentro il path corrente: step raggiunto dal link trigger/main del neurone;
 * 2) cross-circuit (END): tra gli outgoing_links del neurone cerca un link
 *    trigger/main verso un neurone di un altro circuito e entra nello step
 *    corrispondente nel path di quel circuito.
 * @returns {{step:object, steps:Array}|null}
 */
function resolveNextNeuron(currentSteps, allPaths, neuronId, actualNeuron) {
    const next = findNextStepByLink(currentSteps, neuronId, 'trigger')
        || findNextStepByLink(currentSteps, neuronId, 'main');
    if (next) {
        return { step: next, steps: currentSteps };
    }

    const links = actualNeuron.neuron && Array.isArray(actualNeuron.neuron.outgoing_links)
        ? actualNeuron.neuron.outgoing_links
        : [];
    for (const link of links) {
        const condition = link.condition
            || (link.condition_order ? link.condition_order.condition : null);
        if (condition !== 'trigger' && condition !== 'main') {
            continue;
        }
        const toId = link.to_element_has_position_neuron_id;
        if (!toId) {
            continue;
        }
        const hit = findStepInPaths(allPaths, toId);
        if (hit) {
            return hit;
        }
    }
    return null;
}

/**
 * Cerca lo step di ingresso di un neurone in tutti i path: prima un root
 * (condition null) di un circuito, poi qualsiasi step con quel neuron id.
 */
function findStepInPaths(allPaths, neuronId) {
    for (const path of allPaths) {
        const steps = Array.isArray(path.steps) ? path.steps : [];
        const root = steps.find((s) => !s.condition && Number(s.element_has_position_neuron_id) === Number(neuronId));
        if (root) {
            return { step: root, steps };
        }
    }
    for (const path of allPaths) {
        const steps = Array.isArray(path.steps) ? path.steps : [];
        const step = steps.find((s) => Number(s.element_has_position_neuron_id) === Number(neuronId));
        if (step) {
            return { step, steps };
        }
    }
    return null;
}

/**
 * Trova lo step raggiunto dal link in uscita da `fromNeuronId` con la
 * `condition` data (es. 'trigger'): match su via_link.from_..._neuron_id
 * e sulla condition dello step.
 */
function findNextStepByLink(steps, fromNeuronId, condition) {
    return steps.find((s) => {
        const link = s.via_link || null;
        if (!link) {
            return false;
        }
        return Number(link.from_element_has_position_neuron_id) === Number(fromNeuronId)
            && (s.condition || null) === condition;
    }) || null;
}

/**
 * Neurone movement (stessa logica del container entity):
 * move_candidates -> scelta random -> walkable dal map -> BFS path ->
 * movimento tile per tile lungo il path (entity_movement + update per tile).
 */
async function executeMovementNeuron(neuronId, context) {
    const candidatesJson = await postJson('/api/auth/game/brain_flow/move_candidates', {
        element_has_position_neuron_id: neuronId,
    }, context);

    const candidates = candidatesJson && Array.isArray(candidatesJson.candidates) ? candidatesJson.candidates : [];
    if (candidates.length === 0) {
        console.log(`[Brain] move (neuron ${neuronId}): nessuna cella disponibile`);
        return null;
    }

    const target = candidates[Math.floor(Math.random() * candidates.length)];
    const from = candidatesJson && candidatesJson.from ? candidatesJson.from : null;
    console.log(`[Brain] move (neuron ${neuronId}): ${candidates.length} celle, scelta (${target.i},${target.j})`);

    if (!from) {
        // Posizione corrente sconosciuta: salto diretto (un solo update).
        await sendDrawInterface(buildMoveItems(context.elementHasPositionUid, null, target), 'brain_move_' + Date.now(), context);
        return;
    }

    const walkableResult = await getWalkableFromMap();
    const tileWalkable = walkableResult && walkableResult.success ? walkableResult.tile_walkable : null;

    let path = null;
    if (tileWalkable) {
        const pathResult = findPathBFS(tileWalkable, from.i, from.j, target.i, target.j);
        if (pathResult && pathResult.success && pathResult.path.length > 0) {
            path = pathResult.path;
        }
    }

    if (!path) {
        // Map non raggiungibile o nessun path: fallback salto diretto.
        await sendDrawInterface(buildMoveItems(context.elementHasPositionUid, from, target), 'brain_move_' + Date.now(), context);
        return;
    }

    await moveElementAlongPath(path, from.i, from.j, context);
}

/**
 * Muove l'elemento tile per tile lungo il path (come moveEntityAlongPath):
 * un evento 'entity_movement' con tutti gli step, poi un draw_interface
 * per ogni tile a intervalli di MOVE_INTERVAL_MS.
 */
async function moveElementAlongPath(path, startI, startJ, context) {
    const elementUid = context.elementHasPositionUid;
    const totalSteps = path.length;
    const requestId = 'brain_move_' + Date.now() + '_' + Math.floor(Math.random() * 100000);

    const movementSteps = path.map((step, index) => ({
        tile_i: step.i,
        tile_j: step.j,
        step: index + 1,
        delay_ms: (index + 1) * MOVE_INTERVAL_MS,
    }));

    await sendPusherEvent('entity_movement', {
        type: 'entity_movement',
        entity_uid: elementUid,
        steps: movementSteps,
        total_steps: totalSteps,
        interval_ms: MOVE_INTERVAL_MS,
        path_length: totalSteps,
    }, context);

    // Sub-for lungo il path: un trigger per tile (elemento + square eventi).
    let previous = { i: startI, j: startJ };
    for (const step of path) {
        await sleep(MOVE_INTERVAL_MS);
        await sendDrawInterface(buildMoveItems(elementUid, previous, step), requestId, context);
        previous = step;
    }
}

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Array walkable [i][j] dal map container (stesso request/response di entity).
 * @returns {Promise<{success:boolean, tile_walkable?:Array}>}
 */
function getWalkableFromMap() {
    return requestMapData({
        requestIdPrefix: 'walkable',
        command: 'get_tile_walkable',
        responseKey: 'tile_walkable',
    });
}

/**
 * Richiesta dati al map container via WebSocket: prova ws-gateway poi
 * connessione diretta, max 3 tentativi per trasporto (port di requestMapData).
 */
function requestMapData(config) {
    const { requestIdPrefix, command, responseKey } = config;
    const requestId = requestIdPrefix + '_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
    const maxAttempts = 3;

    const transports = [
        { label: 'ws-gateway', buildUrl: () => `ws://${wsGatewayHost}:${wsGatewayPort}?port=${mapWsPort}` },
        { label: 'direct', buildUrl: () => `ws://${mapDirectHost}:${mapDirectPort}` },
    ];

    return new Promise((resolve) => {
        let isSettled = false;
        const finish = (payload) => {
            if (isSettled) return;
            isSettled = true;
            resolve(payload);
        };
        const failAll = () => {
            console.error(`[Brain] Map never provided ${responseKey} (requestId ${requestId})`);
            finish({ success: false, error: `${responseKey} not available from map` });
        };
        const nextAttempt = (transportIndex, attemptIndex) => {
            if (isSettled) return;
            if (attemptIndex + 1 < maxAttempts) {
                setTimeout(() => attemptTransport(transportIndex, attemptIndex + 1), WALKABLE_RETRY_DELAY_MS);
                return;
            }
            if (transportIndex + 1 < transports.length) {
                attemptTransport(transportIndex + 1, 0);
                return;
            }
            failAll();
        };
        const attemptTransport = (transportIndex, attemptIndex) => {
            if (isSettled) return;
            const transport = transports[transportIndex];
            const mapWsUrl = transport.buildUrl();
            const ws = new WebSocket(mapWsUrl);
            let isAttemptSettled = false;
            const settleAttempt = (handler) => {
                if (isAttemptSettled) return false;
                isAttemptSettled = true;
                clearTimeout(timeout);
                try { ws.close(); } catch (e) { /* già chiuso */ }
                handler();
                return true;
            };
            const timeout = setTimeout(() => {
                settleAttempt(() => nextAttempt(transportIndex, attemptIndex));
            }, WALKABLE_RESPONSE_TIMEOUT_MS);
            ws.on('open', () => {
                ws.send(JSON.stringify({ request_id: requestId, command }));
            });
            ws.on('message', (data) => {
                let response;
                try { response = JSON.parse(data.toString()); } catch (e) { return; }
                if (String(response.request_id || '') !== String(requestId)) return;
                settleAttempt(() => {
                    if (response.success && Array.isArray(response[responseKey])) {
                        finish({ success: true, [responseKey]: response[responseKey] });
                        return;
                    }
                    nextAttempt(transportIndex, attemptIndex);
                });
            });
            ws.on('error', () => settleAttempt(() => nextAttempt(transportIndex, attemptIndex)));
            ws.on('close', () => settleAttempt(() => nextAttempt(transportIndex, attemptIndex)));
        };
        attemptTransport(0, 0);
    });
}

/**
 * BFS per il path più breve su griglia walkable (port di findPathBFS entity).
 * @returns {{success:boolean, path?:Array<{action:string,i:number,j:number}>, error?:string}}
 */
function findPathBFS(tileWalkable, startI, startJ, targetI, targetJ) {
    const rows = tileWalkable.length;
    const cols = tileWalkable[0] ? tileWalkable[0].length : 0;
    if (startI < 0 || startI >= rows || startJ < 0 || startJ >= cols ||
        targetI < 0 || targetI >= rows || targetJ < 0 || targetJ >= cols) {
        return { success: false, error: 'Coordinates out of bounds' };
    }
    if (!tileWalkable[startI][startJ] || !tileWalkable[targetI][targetJ]) {
        return { success: false, error: 'Start or target position is not walkable' };
    }
    if (startI === targetI && startJ === targetJ) {
        return { success: true, path: [] };
    }

    const directions = [
        { di: -1, dj: 0, name: 'up' },
        { di: 1, dj: 0, name: 'down' },
        { di: 0, dj: 1, name: 'right' },
        { di: 0, dj: -1, name: 'left' },
    ];

    const visited = Array.from({ length: rows }, () => Array(cols).fill(false));
    const queue = [{ i: startI, j: startJ, path: [] }];
    visited[startI][startJ] = true;

    while (queue.length > 0) {
        const current = queue.shift();
        for (const dir of directions) {
            const newI = current.i + dir.di;
            const newJ = current.j + dir.dj;
            if (newI >= 0 && newI < rows && newJ >= 0 && newJ < cols &&
                tileWalkable[newI][newJ] && !visited[newI][newJ]) {
                const newPath = [...current.path, { action: dir.name, i: newI, j: newJ }];
                if (newI === targetI && newJ === targetJ) {
                    return { success: true, path: newPath };
                }
                visited[newI][newJ] = true;
                queue.push({ i: newI, j: newJ, path: newPath });
            }
        }
    }

    return { success: false, error: 'No path found to target' };
}

/**
 * Update del border del neurone via pusher evento 'update_draw'
 * (uid risolto con l'API border-uid esposta dal container).
 */
async function updateNeuronBorder(neuronId, borderColor, context) {
    if (typeof context.fetchNeuronBorderUid !== 'function') {
        return;
    }
    const borderUid = await context.fetchNeuronBorderUid(neuronId);
    if (!borderUid) {
        return;
    }
    await sendUpdateDraw(borderUid, { borderColor }, null, context);
}

/**
 * Items 'draw_interface' per lo spostamento dell'elemento da `from` a `target`:
 * update posizione (origin top-left, come ElementDraw) + interactive_events
 * dei tile di partenza/arrivo (come moveEntityAlongPath del container entity).
 */
function buildMoveItems(elementUid, from, target) {
    const items = [{
        type: 'update',
        uid: elementUid,
        attributes: {
            x: TILE_SIZE * target.j + MAP_START_X,
            y: TILE_SIZE * target.i + MAP_START_Y,
            z_index: 100,
        },
    }];

    if (from) {
        items.push({
            type: 'update',
            uid: 'square_' + from.i + '_' + from.j,
            attributes: { interactive_events: { add: ['pointerover', 'pointerout'] } },
        });
    }
    items.push({
        type: 'update',
        uid: 'square_' + target.i + '_' + target.j,
        attributes: { interactive_events: { remove: ['pointerover', 'pointerout'] } },
    });

    return items;
}

function resolvePlayerId(context) {
    return context.playerId || Number(process.env.ELEMENT_PLAYER_ID || process.env.PLAYER_ID || 0);
}

function sendPusherEvent(event, payload, context) {
    const playerId = resolvePlayerId(context);
    if (!playerId) {
        return Promise.resolve(false);
    }
    return pusher.trigger('player_' + playerId + '_channel', event, payload)
        .then(() => true)
        .catch((error) => {
            console.error(`[Brain] ${event} FAILED: ${error.message}`);
            return false;
        });
}

function sendUpdateDraw(borderUid, attributes, object, context) {
    const playerId = resolvePlayerId(context);
    if (!playerId || !borderUid) {
        return Promise.resolve(false);
    }
    return pusher.trigger('player_' + playerId + '_channel', 'update_draw', {
        player_id: playerId,
        border_uid: borderUid,
        attributes,
        object: object || null,
    }).then(() => true).catch((error) => {
        console.error(`[Brain] update_draw FAILED (${borderUid}): ${error.message}`);
        return false;
    });
}

function sendDrawInterface(items, requestId, context) {
    const playerId = resolvePlayerId(context);
    if (!playerId) {
        return Promise.resolve(false);
    }
    return pusher.trigger('player_' + playerId + '_channel', 'draw_interface', {
        type: 'draw_interface',
        request_id: requestId,
        player_id: playerId,
        items,
    }).then(() => true).catch((error) => {
        console.error(`[Brain] draw_interface FAILED (${requestId}): ${error.message}`);
        return false;
    });
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
