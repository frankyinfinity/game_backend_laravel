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
const MAP_SCROLL_GROUP = process.env.MAP_SCROLL_GROUP || 'map_main';
// Z-index come entity: path rosso a 9000, elemento sopra il path (9500)
const PATH_Z_INDEX = parseInt(process.env.PATH_Z_INDEX || '9000', 10);
const ELEMENT_Z_INDEX = parseInt(process.env.ELEMENT_Z_INDEX || '9500', 10);

// requestId dell'ultimo path disegnato (per poterlo cancellare, come entity)
let lastPathRequestId = null;
// Ultima posizione persistita sul DB: evita chiamate ripetute (come entity)
let lastPersistedTileI = null;
let lastPersistedTileJ = null;

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
            await updateNeuronBorder(neuronId, '0x000000', context);

            // 5) prossimo neurone: segui SOLO il link con condition 'trigger'
            // uscente dal neurone corrente (start/begin: 'trigger'; movement:
            // 'trigger'). Se non esiste nel path corrente (END), può puntare a
            // un neurone di un altro circuito -> entra in quel path.
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
 * Risolve lo step successivo di `actualNeuron` con controlli stringenti:
 * il neurone corrente segue SOLO il link con la condition prevista per il
 * suo tipo (start/begin -> 'trigger'; movement -> 'trigger'), nessun fallback
 * su altre condition.
 * 1) dentro il path corrente: step raggiunto dal link con quella condition;
 * 2) cross-circuit (END): tra gli outgoing_links cerca un link con la stessa
 *    condition verso un neurone di un altro circuito e entra in quel path.
 * @returns {{step:object, steps:Array}|null}
 */
const REQUIRED_NEXT_CONDITION_BY_TYPE = {
    start: 'trigger',
    movement: 'trigger',
};

function resolveNextNeuron(currentSteps, allPaths, neuronId, actualNeuron) {
    const neuronType = actualNeuron.neuron && actualNeuron.neuron.information
        ? actualNeuron.neuron.information.type
        : null;
    // Tipo sconosciuto: stessa regola stringente ('trigger'), niente 'main'.
    const requiredCondition = REQUIRED_NEXT_CONDITION_BY_TYPE[neuronType] || 'trigger';

    const next = findNextStepByLink(currentSteps, neuronId, requiredCondition);
    if (next) {
        return { step: next, steps: currentSteps };
    }

    const links = actualNeuron.neuron && Array.isArray(actualNeuron.neuron.outgoing_links)
        ? actualNeuron.neuron.outgoing_links
        : [];
    for (const link of links) {
        const condition = link.condition
            || (link.condition_order ? link.condition_order.condition : null);
        if (condition !== requiredCondition) {
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
        await sendDrawInterface(buildMoveItems(context.elementHasPositionUid, null, target, null), 'brain_move_' + Date.now(), context);
        await updateElementPositionOnApi(target.i, target.j, context);
        return;
    }

    // Come entity: prima le coordinate pixel dei tile, poi la matrice walkable.
    const coordinatesResult = await getTileCoordinatesFromMap();
    const tileCoordinates = coordinatesResult && coordinatesResult.success ? coordinatesResult.tile_coordinates : null;

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
        await sendDrawInterface(buildMoveItems(context.elementHasPositionUid, from, target, tileCoordinates), 'brain_move_' + Date.now(), context);
        await updateElementPositionOnApi(target.i, target.j, context);
        return;
    }

    // Disegna il path rosso tramite Pusher (linea + punti al centro dei tile),
    // come drawPathViaPusher di entity.
    drawElementPathViaPusher(path, from.i, from.j, target.i, target.j, tileCoordinates, context);

    // Stessa firma di moveEntityAlongPath: callback chiamata a movimento finito.
    await new Promise((resolve) => {
        moveElementAlongPath(path, from.i, from.j, tileCoordinates, context, resolve);
    });
}

/**
 * Port di moveEntityAlongPath (entity/index.js): un trigger 'entity_movement'
 * con tutti gli step, poi un draw_interface per tile via setTimeout a
 * intervalli di MOVE_INTERVAL_MS (400ms). Alla fine chiama `callback`.
 */
function moveElementAlongPath(path, startI, startJ, tileCoordinates, context, callback) {
    const elementUid = context.elementHasPositionUid;
    if (!path || path.length === 0) {
        console.log('[Brain] No path to move along');
        if (callback) callback();
        return;
    }

    // Tile di partenza: serve per spostare l'elementDraw tra un tile e l'altro.
    let previousTileI = startI;
    let previousTileJ = startJ;
    // Posizione corrente locale: persistita sul DB una sola volta alla fine
    // (come localCurrentTileI/J + updateEntityPositionOnApi di entity).
    let localCurrentTileI = startI;
    let localCurrentTileJ = startJ;

    const totalSteps = path.length;
    const moveInterval = MOVE_INTERVAL_MS; // 400ms per tile

    console.log(`[Brain] Starting movement along ${totalSteps} tiles`);

    // Prepara tutti gli step del movimento
    const movementSteps = path.map((step, index) => ({
        tile_i: step.i,
        tile_j: step.j,
        step: index + 1,
        delay_ms: (index + 1) * moveInterval,
    }));

    // Invia tutti i dati del movimento in un'unica chiamata Pusher
    const movePayload = {
        type: 'entity_movement',
        entity_uid: elementUid,
        steps: movementSteps,
        total_steps: totalSteps,
        interval_ms: moveInterval,
        path_length: totalSteps,
    };

    sendPusherEvent('entity_movement', movePayload, context)
        .then((ok) => {
            if (ok) console.log('[Brain] Movement data sent successfully via Pusher');
        });

    // Aggiorna la posizione locale dopo l'invio
    let currentStep = 0;
    const updatePosition = () => {
        if (currentStep >= totalSteps) {
            console.log('[Brain] Movement completed!');
            // Movimento terminato: persisti la posizione finale con UNA SOLA
            // chiamata API e cancella il path disegnato (come entity).
            updateElementPositionOnApi(localCurrentTileI, localCurrentTileJ, context);
            clearPathViaPusher(totalSteps, context);
            if (callback) callback();
            return;
        }

        const step = path[currentStep];
        localCurrentTileI = step.i;
        localCurrentTileJ = step.j;

        // Sposta l'elementDraw dal tile precedente a quello appena raggiunto
        moveElementDrawBetweenTiles(previousTileI, previousTileJ, step.i, step.j, tileCoordinates, context);
        previousTileI = step.i;
        previousTileJ = step.j;

        currentStep++;

        setTimeout(updatePosition, moveInterval);
    };

    // Inizia l'aggiornamento della posizione locale
    setTimeout(updatePosition, moveInterval);
}

/**
 * Port di drawPathViaPusher di entity: disegna il path come linea rossa
 * (multi_line) con punti al centro dei tile. I centri arrivano da
 * tile_coordinates del map container; fallback griglia teorica.
 */
function drawElementPathViaPusher(path, currentI, currentJ, targetI, targetJ, tileCoordinates, context) {
    if (!path || path.length === 0) {
        console.log('[Brain] No path to draw');
        return;
    }

    const requestId = 'path_' + Date.now();
    lastPathRequestId = requestId; // Salva il requestId per poterlo cancellare dopo
    const pathColor = '0xEF4444'; // Rosso per il path

    // Il path fa parte dello scroll group della mappa: così resta allineato ai tile
    const pathAttributes = { scroll_group: MAP_SCROLL_GROUP, z_index: PATH_Z_INDEX };

    // Centri (pixel) di tutti i tile del path, incluso quello di partenza
    const centers = [getTileCenter(currentI, currentJ, tileCoordinates)];
    path.forEach((step) => {
        centers.push(getTileCenter(step.i, step.j, tileCoordinates));
    });

    const pathItems = [];

    // Disegna le linee che collegano i punti: un unico multi_line con tutti i centri
    pathItems.push({
        type: 'draw',
        object: {
            uid: `${requestId}_line`,
            type: 'multi_line',
            points: centers.map((center) => ({ x: center.x, y: center.y })),
            color: pathColor,
            thickness: 3,
            attributes: pathAttributes,
        },
    });

    // Disegna i punti (cerchi) al centro di ogni tile
    centers.forEach((center, index) => {
        const isStart = index === 0;
        pathItems.push({
            type: 'draw',
            object: {
                uid: `${requestId}_dot_${index}`,
                type: 'circle',
                x: center.x,
                y: center.y,
                radius: isStart ? 8 : 6,
                color: pathColor,
                attributes: pathAttributes,
            },
        });
    });

    console.log(`[Brain] Drawing path with ${centers.length} points`);
    sendDrawInterface(pathItems, requestId, context);
}

/**
 * Port di clearPathViaPusher di entity: cancella la linea e i punti del path
 * disegnato (update renderable=false con lo stesso requestId del draw).
 */
function clearPathViaPusher(pathLength, context) {
    if (!lastPathRequestId) {
        console.log('[Brain] No path to clear');
        return;
    }

    const requestId = lastPathRequestId;
    const clearItems = [];

    clearItems.push({
        type: 'update',
        uid: `${requestId}_line`,
        attributes: { renderable: false },
    });

    const totalDots = pathLength + 1;
    for (let i = 0; i < totalDots; i++) {
        clearItems.push({
            type: 'update',
            uid: `${requestId}_dot_${i}`,
            attributes: { renderable: false },
        });
    }

    sendDrawInterface(clearItems, requestId, context);
}

/**
 * Port di updateEntityPositionOnApi di entity: persiste sul DB la posizione
 * finale dell'elemento (POST brain_flow/update_position), una sola volta a
 * fine movimento. Fire-and-forget con log.
 */
function updateElementPositionOnApi(tileI, tileJ, context) {
    if (tileI === lastPersistedTileI && tileJ === lastPersistedTileJ) {
        return Promise.resolve(true);
    }
    if (!context.elementHasPositionId) {
        console.error('[Brain] update_position: elementHasPositionId mancante nel context');
        return Promise.resolve(false);
    }
    return postJson('/api/auth/game/brain_flow/update_position', {
        element_has_position_id: context.elementHasPositionId,
        tile_i: tileI,
        tile_j: tileJ,
    }, context).then((result) => {
        if (result && result.success) {
            lastPersistedTileI = tileI;
            lastPersistedTileJ = tileJ;
            console.log(`[Brain] Posizione persistita: (${tileI}, ${tileJ})`);
            return true;
        }
        console.error('[Brain] update_position FAILED:', JSON.stringify(result));
        return false;
    }).catch((err) => {
        console.error('[Brain] update_position FAILED:', err.message);
        return false;
    });
}

/**
 * Port di moveEntityDrawBetweenTiles: invia al frontend lo spostamento
 * dell'elementDraw dal tile (fromI,fromJ) al tile (toI,toJ).
 */
function moveElementDrawBetweenTiles(fromI, fromJ, toI, toJ, tileCoordinates, context) {
    const requestId = 'element_move_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
    const items = buildMoveItems(context.elementHasPositionUid, { i: fromI, j: fromJ }, { i: toI, j: toJ }, tileCoordinates);
    sendDrawInterface(items, requestId, context)
        .then((ok) => {
            if (ok) console.log(`[Brain] ElementDraw moved to tile (${toI}, ${toJ})`);
        });
}

/**
 * Coordinate pixel dei tile dal map container (command get_tile_coordinates,
 * come getTileCoordinatesFromMap di entity).
 */
function getTileCoordinatesFromMap() {
    return requestMapData({
        requestIdPrefix: 'coordinates',
        command: 'get_tile_coordinates',
        responseKey: 'tile_coordinates',
    });
}

/**
 * Centro (pixel) del tile (i, j) letto dalla matrice tile_coordinates del map
 * container (port di getTileCenter di entity: fallback griglia teorica).
 */
function getTileCenter(i, j, tileCoordinates) {
    const coordinates = (tileCoordinates && tileCoordinates[i]) ? tileCoordinates[i][j] : null;
    const center = coordinates ? coordinates.center : null;

    if (center && typeof center.x === 'number' && typeof center.y === 'number') {
        return { x: center.x, y: center.y, fromCoordinates: true };
    }

    return {
        x: MAP_START_X + (j * TILE_SIZE) + (TILE_SIZE / 2),
        y: MAP_START_Y + (i * TILE_SIZE) + (TILE_SIZE / 2),
        fromCoordinates: false,
    };
}

/**
 * Port di buildResetTilesOverColorCode di entity: ripristina il colore
 * originale dei tile di origine e arrivo dopo un movimento (toglie
 * l'over-color lasciato da un hover precedente).
 */
function buildResetTilesOverColorCode(fromTileUid, toTileUid) {
    return [
        '(function () {',
        `  var tileUids = ${JSON.stringify([fromTileUid, toTileUid])};`,
        '  tileUids.forEach(function (uid) {',
        '    var shape = (typeof shapes !== "undefined") ? shapes[uid] : null;',
        '    var object = (typeof objects !== "undefined") ? objects[uid] : null;',
        '    if (shape && object && object.color !== undefined && object.color !== null) {',
        '      shape.tint = object.color;',
        '    }',
        '  });',
        '})();',
    ].join('\n');
}

/**
 * Port di buildMoveEntityDrawCode di entity: sposta di (deltaX, deltaY) tutti
 * gli oggetti collegati al draw dell'elemento (attributes.uids), esclusa
 * l'immagine root che viene posizionata dall'item update assoluto.
 */
function buildMoveElementDrawCode(elementUid, fromI, fromJ, toI, toJ, tileCoordinates) {
    const fromCenter = getTileCenter(fromI, fromJ, tileCoordinates);
    const toCenter = getTileCenter(toI, toJ, tileCoordinates);
    const deltaX = toCenter.x - fromCenter.x;
    const deltaY = toCenter.y - fromCenter.y;

    return [
        '(function () {',
        `  var rootUid = ${JSON.stringify(elementUid)};`,
        `  var deltaX = ${deltaX};`,
        `  var deltaY = ${deltaY};`,
        '  var rootObject = (typeof objects !== "undefined") ? objects[rootUid] : null;',
        '  if (!rootObject) { console.warn("[ElementDraw] immagine element non trovata:", rootUid); return; }',
        '  var uids = (rootObject.attributes && Array.isArray(rootObject.attributes.uids)) ? rootObject.attributes.uids : [];',
        '  uids.forEach(function (uid) {',
        '    if (uid === rootUid) return; // immagine aggiornata dall\'item update (x/y assoluti)',
        '    var elementObject = objects[uid];',
        '    if (!elementObject) return;',
        '    if (typeof elementObject.x === "number") elementObject.x += deltaX;',
        '    if (typeof elementObject.y === "number") elementObject.y += deltaY;',
        '    if (Array.isArray(elementObject.points)) {',
        '      elementObject.points = elementObject.points.map(function (point) { return { x: point.x + deltaX, y: point.y + deltaY }; });',
        '    }',
        '    if (typeof redrawShapeFromObject === "function") redrawShapeFromObject(uid);',
        '  });',
        '})();',
    ].join('\n');
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
 * Items 'draw_interface' per lo spostamento dell'elemento (port di
 * buildEntityDrawMoveItems di entity): update posizione (origin top-left,
 * come ElementDraw), interactive_events dei tile di partenza/arrivo, reset
 * over-color e spostamento degli oggetti collegati al draw (uids).
 */
function buildMoveItems(elementUid, from, target, tileCoordinates) {
    const items = [{
        type: 'update',
        uid: elementUid,
        attributes: {
            x: TILE_SIZE * target.j + MAP_START_X,
            y: TILE_SIZE * target.i + MAP_START_Y,
            z_index: ELEMENT_Z_INDEX,
        },
    }];

    const toTileUid = 'square_' + target.i + '_' + target.j;

    if (from) {
        const fromTileUid = 'square_' + from.i + '_' + from.j;
        // Tile di partenza: l'elemento lo ha lasciato -> riaggancia over
        items.push({
            type: 'update',
            uid: fromTileUid,
            attributes: { interactive_events: { add: ['pointerover', 'pointerout'] } },
        });
        items.push({
            type: 'code',
            code: buildResetTilesOverColorCode(fromTileUid, toTileUid),
        });
        items.push({
            type: 'code',
            code: buildMoveElementDrawCode(elementUid, from.i, from.j, target.i, target.j, tileCoordinates),
        });
    }

    // Tile di arrivo: ora occupato -> rimuove gli eventi over
    items.push({
        type: 'update',
        uid: toTileUid,
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
