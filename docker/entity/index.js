// entity.js

const http = require('http');
const WebSocket = require('ws');
const Pusher = require('pusher');
const fs = require('fs');

// Leggi i parametri dalle variabili d'ambiente
const entityUid = process.env.ENTITY_UID;
const entityTileI = process.env.ENTITY_TILE_I;
const entityTileJ = process.env.ENTITY_TILE_J;
const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const wsPort = process.env.WS_PORT || 8080;
const playerId = process.env.PLAYER_ID || 0;

// Detect if running inside Docker; if so, 'localhost' cannot reach the host.
function isRunningInDocker() {
  try {
    return fs.existsSync('/.dockerenv') || fs.readFileSync('/proc/1/cgroup', 'utf8').includes('docker');
  } catch (_) {
    return false;
  }
}

function resolveReverbHost(rawHost) {
  // When inside Docker without host networking, "localhost" means the container, not the host.
  // However, 127.0.0.1 is an explicit loopback IP — respect it (used with --network host).
  if (isRunningInDocker() && (rawHost === 'localhost' || rawHost === '0.0.0.0')) {
    const resolved = process.env.DOCKER_HOST_IP || 'host.docker.internal';
    console.log(`[Entity] Docker detected: remapping REVERB_HOST "${rawHost}" → "${resolved}"`);
    return resolved;
  }
  return rawHost;
}

// Configurazione Pusher (stesso pattern del container alert)
const reverbHost = resolveReverbHost(process.env.REVERB_HOST || 'localhost');
const reverbPort = process.env.REVERB_PORT || '8081';
const reverbScheme = process.env.REVERB_SCHEME || 'http';

const pusher = new Pusher({
  appId: process.env.REVERB_APP_ID || 'game',
  key: process.env.REVERB_APP_KEY || 'game-key',
  secret: process.env.REVERB_APP_SECRET || 'game-secret',
  host: reverbHost,
  port: reverbPort,
  scheme: reverbScheme,
  useTLS: reverbScheme === 'https',
});

console.log(`[Entity] Pusher initialized: ${reverbScheme}://${reverbHost}:${reverbPort}`);

const GENES_WAIT_SECONDS = 1;
const CHIMICAL_WAIT_SECONDS = 1;

console.log(`Entity service started.`);
console.log(`Entity UID: ${entityUid}`);
console.log(`Tile Position: (${entityTileI}, ${entityTileJ})`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);

// Variabili per tracciare la posizione attuale e i geni
let currentTileI = entityTileI;
let currentTileJ = entityTileJ;
let currentGenes = {};
let currentChimicalElements = {};

// Variabili per tracciare la posizione attuale in locale (aggiornate dopo ogni movimento)
let localCurrentTileI = null;
let localCurrentTileJ = null;
let isPositionInitialized = false;

// Configurazione per connettersi al container map
const mapWsHost = process.env.MAP_WS_HOST || 'map';
const mapWsPort = process.env.MAP_WS_PORT || 8080;

// function to handle login and session
let sessionCookie = null;
let xsrfToken = null;

function parseCookies(response) {
  const list = {};
  const rc = response.headers['set-cookie'];

  rc && rc.forEach(function (cookie) {
    const parts = cookie.split(';');
    const pair = parts[0].split('=');
    list[pair[0].trim()] = decodeURIComponent(pair[1]);
  });
  return list;
}

function getCookiesFromHeader(response) {
  return response.headers['set-cookie'] || [];
}

function updateSession(response) {
  const cookies = getCookiesFromHeader(response);
  if (cookies.length > 0) {
    // Simple cookie jar: just join all set-cookie headers
    sessionCookie = cookies.map(c => c.split(';')[0]).join('; ');

    // Extract XSRF-TOKEN if present
    const parsed = parseCookies(response);
    if (parsed['XSRF-TOKEN']) {
      xsrfToken = parsed['XSRF-TOKEN'];
    }
  }
}

function performLogin() {
  console.log('Attempting login...');

  // Step 1: GET / to get initial cookies and CSRF token
  const optionsGet = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: '/login',
    method: 'GET',
  };

  const reqGet = http.request(optionsGet, (res) => {
    updateSession(res);

    // Prepare post data
    const postData = new URLSearchParams({
      'email': apiUserEmail,
      'password': apiUserPassword,
    }).toString();

    // Step 2: POST /login
    const optionsPost = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path: '/login',
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(postData),
        'Cookie': sessionCookie,
        'X-XSRF-TOKEN': xsrfToken
      }
    };

    const reqPost = http.request(optionsPost, (resPost) => {
      updateSession(resPost);

      if (resPost.statusCode === 302 || resPost.statusCode === 200 || resPost.statusCode === 204) {
        console.log('Login successful (or redirect received), starting creation loop...');
        // Avvia i cicli separati
        scheduleNextCycle();
        scheduleGenesFetch();
        scheduleChimicalElementsFetch();
        scheduleEntityDegradationCheck();
      } else {
        console.error(`Login failed with status: ${resPost.statusCode}`);
        // Try reading body for error
        resPost.on('data', d => console.error(d.toString()));
      }
    });

    reqPost.on('error', (e) => console.error(`Login POST error: ${e.message}`));
    reqPost.write(postData);
    reqPost.end();
  });

  reqGet.on('error', (e) => console.error(`Initial GET error: ${e.message}`));
  reqGet.end();
}

function fetchCurrentPosition() {
  if (!sessionCookie) {
    console.log('No session cookie, skipping fetch...');
    scheduleNextCycle(); // Riprogramma il prossimo ciclo anche se non c'è la sessione
    return;
  }

  const path = `/entities/position?uid=${entityUid}`;

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: path,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Cookie': sessionCookie
    },
  };

  const req = http.request(options, (res) => {
    let data = '';

    res.on('data', (chunk) => {
      data += chunk;
    });

    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          currentTileI = response.tile_i;
          currentTileJ = response.tile_j;
          console.log(`[Entity ${entityUid}] Still alive... Position: (${currentTileI}, ${currentTileJ})`);


          scheduleNextCycle();
        } else {
          console.error(`Status ${res.statusCode}: ${response.message || 'Unknown error'}`);
          scheduleNextCycle(); // Riprogramma anche in caso di errore
        }
      } catch (error) {
        if (res.statusCode === 401 || res.statusCode === 419) {
          console.error('Session expired or unauthorized, maybe re-login needed?');
        } else {
          console.error(`Error parsing response: ${error.message}. Status: ${res.statusCode}`);
        }
        scheduleNextCycle(); // Riprogramma anche in caso di errore
      }
    });
  });

  req.on('error', (error) => {
    console.error(`Error fetching position: ${error.message}`);
    scheduleNextCycle(); // Riprogramma anche in caso di errore di rete
  });

  req.end();
}


function fetchCurrentGenes() {
  if (!sessionCookie) return;

  const path = `/entities/genes?uid=${entityUid}`;

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: path,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Cookie': sessionCookie
    },
  };

  const req = http.request(options, (res) => {
    let data = '';
    res.on('data', (chunk) => { data += chunk; });
    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          currentGenes = response.genes;
          console.log(`[Entity ${entityUid}] Current Gene Values:`, JSON.stringify(currentGenes));
        }
      } catch (error) {
        console.error(`[Entity ${entityUid}] Error parsing gene values: ${error.message}`);
      }
    });
  });

  req.on('error', (error) => {
    console.error(`[Entity ${entityUid}] Error fetching genes: ${error.message}`);
  });

  req.end();
}

function fetchCurrentChimicalElements() {
  if (!sessionCookie) return;

  const path = `/entities/chimical-elements?uid=${entityUid}`;

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: path,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Cookie': sessionCookie
    },
  };

  const req = http.request(options, (res) => {
    let data = '';
    res.on('data', (chunk) => { data += chunk; });
    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          currentChimicalElements = response.chimical_elements;
          console.log(`[Entity ${entityUid}] Current Chimical Elements:`, JSON.stringify(currentChimicalElements));
        }
      } catch (error) {
        console.error(`[Entity ${entityUid}] Error parsing chimical elements: ${error.message}`);
      }
    });
  });

  req.on('error', (error) => {
    console.error(`[Entity ${entityUid}] Error fetching chimical elements: ${error.message}`);
  });

  req.end();
}


// Timer per i geni (separato)
let genesTimer = null;
function scheduleGenesFetch() {
  if (genesTimer) clearTimeout(genesTimer);
  genesTimer = setTimeout(() => {
    fetchCurrentGenes();
    scheduleGenesFetch();
  }, GENES_WAIT_SECONDS * 1000);
}

// Timer per gli elementi chimici (separato)
let chimicalElementsTimer = null;
function scheduleChimicalElementsFetch() {
  if (chimicalElementsTimer) clearTimeout(chimicalElementsTimer);
  chimicalElementsTimer = setTimeout(() => {
    fetchCurrentChimicalElements();
    scheduleChimicalElementsFetch();
  }, CHIMICAL_WAIT_SECONDS * 1000);
}

// Timer per la degradazione (10 secondi)
let degradationTimer = null;
function scheduleEntityDegradationCheck() {
  if (degradationTimer) clearTimeout(degradationTimer);
  degradationTimer = setTimeout(() => {
    checkEntityDegradation();
    scheduleEntityDegradationCheck();
  }, 10000);
}

function checkEntityDegradation() {
  if (!sessionCookie) return;

  const path = '/api/auth/game/entity/check_degradation';
  const postData = JSON.stringify({ entity_uid: entityUid });

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: path,
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Content-Length': Buffer.byteLength(postData),
      'Accept': 'application/json',
      'Cookie': sessionCookie,
      'X-XSRF-TOKEN': xsrfToken
    },
  };

  const req = http.request(options, (res) => {
    let data = '';
    res.on('data', (chunk) => { data += chunk; });
    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          console.log('[Entity ' + entityUid + '] Entity degradation check completed');
        } else {
          console.error('[Entity ' + entityUid + '] Entity degradation check failed: ' + (response.message || 'Unknown error'));
        }
      } catch (error) {
        console.error('[Entity ' + entityUid + '] Error parsing entity degradation response: ' + error.message);
      }
    });
  });

  req.on('error', (error) => {
    console.error('[Entity ' + entityUid + '] Error calling entity degradation API: ' + error.message);
  });

  req.write(postData);
  req.end();
}

// Funzione per programmare il prossimo ciclo (solo position)
function scheduleNextCycle() {
  setTimeout(() => {
    fetchCurrentPosition();
  }, 2000);
}

// ========== WebSocket Server ==========
const wss = new WebSocket.Server({ port: wsPort });

console.log(`WebSocket server listening on port ${wsPort}`);

wss.on('connection', (ws) => {
  console.log(`[WebSocket] Client connected to entity ${entityUid}`);

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      console.log(`[WebSocket] Received command:`, data);

      // Gestisci i comandi ricevuti
      handleWebSocketCommand(data, ws);
    } catch (error) {
      console.error(`[WebSocket] Error parsing message:`, error.message);
      ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
    }
  });

  ws.on('close', () => {
    console.log(`[WebSocket] Client disconnected`);
  });

  ws.on('error', (error) => {
    console.error(`[WebSocket] Error:`, error.message);
  });

  // Invia messaggio di benvenuto
  ws.send(JSON.stringify({
    success: true,
    message: 'Connected to entity',
    entity_uid: entityUid,
    position: { i: currentTileI, j: currentTileJ }
  }));
});

// Funzione per gestire i comandi WebSocket
function handleWebSocketCommand(data, ws) {
  const { command, params } = data;

  switch (command) {
    case 'move':
      // Esegui un movimento con azione specifica (up, down, left, right) o coordinate target
      if (params && (params.action || (params.target_i !== undefined && params.target_j !== undefined))) {
        performMovement(params, (result) => {
          ws.send(JSON.stringify(result));
        });
      } else {
        ws.send(JSON.stringify({ success: false, error: 'Missing action or target coordinates' }));
      }
      break;

    case 'get_position':
      // Ritorna la posizione corrente (usa la posizione locale se disponibile, altrimenti quella dal env)
      const posI = localCurrentTileI !== null ? localCurrentTileI : currentTileI;
      const posJ = localCurrentTileJ !== null ? localCurrentTileJ : currentTileJ;
      ws.send(JSON.stringify({
        success: true,
        entity_uid: entityUid,
        position: { i: posI, j: posJ },
        source: isPositionInitialized ? 'local_cache' : 'environment'
      }));
      break;

    case 'get_position_api':
      // Ottiene la posizione attuale tramite API (endpoint dedicato)
      fetchCurrentPositionFromApi((result) => {
        if (result.success) {
          ws.send(JSON.stringify({
            success: true,
            entity_uid: entityUid,
            position: { i: result.tile_i, j: result.tile_j },
            source: 'api'
          }));
        } else {
          ws.send(JSON.stringify({
            success: false,
            error: 'Failed to fetch position from API'
          }));
        }
      });
      break;

    case 'get_genes':
      // Ritorna i geni correnti
      ws.send(JSON.stringify({
        success: true,
        command: 'get_genes',
        genes: currentGenes
      }));
      break;

    case 'get_chimical_elements':
      // Ritorna gli elementi chimici del tile specificato o della posizione corrente
      const tileI = data.params?.tile_i ?? currentTileI;
      const tileJ = data.params?.tile_j ?? currentTileJ;

      const pathChimical = `/entities/chimical-elements?uid=${entityUid}`;
      const optionsChimical = {
        hostname: new URL(backendUrl).hostname,
        port: new URL(backendUrl).port || 80,
        path: pathChimical,
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Cookie': sessionCookie
        },
      };

      const reqChimical = http.request(optionsChimical, (resChimical) => {
        let dataChimical = '';
        resChimical.on('data', (chunk) => { dataChimical += chunk; });
        resChimical.on('end', () => {
          try {
            const responseChimical = JSON.parse(dataChimical);
            ws.send(JSON.stringify({
              success: true,
              command: 'get_chimical_elements',
              chimical_elements: responseChimical.chimical_elements || [],
              tile_i: tileI,
              tile_j: tileJ
            }));
          } catch (error) {
            ws.send(JSON.stringify({
              success: false,
              command: 'get_chimical_elements',
              error: error.message
            }));
          }
        });
      });

      reqChimical.on('error', (error) => {
        ws.send(JSON.stringify({
          success: false,
          command: 'get_chimical_elements',
          error: error.message
        }));
      });

      reqChimical.end();
      break;

    default:
      ws.send(JSON.stringify({
        success: false,
        error: `Unknown command: ${command}`
      }));
  }
}

// Funzione per connettersi al map container via WebSocket e ottenere l'array walkable
function getWalkableFromMap(callback) {
  const mapWsUrl = `ws://${mapWsHost}:${mapWsPort}`;
  console.log(`[Entity ${entityUid}] Connecting to map WebSocket: ${mapWsUrl}`);

  const ws = new WebSocket(mapWsUrl);
  let isResolved = false;

  const timeout = setTimeout(() => {
    if (!isResolved) {
      isResolved = true;
      ws.close();
      callback({ success: false, error: 'Timeout connecting to map WebSocket' });
    }
  }, 5000);

  ws.on('open', () => {
    console.log(`[Entity ${entityUid}] Connected to map WebSocket, requesting walkable array...`);
    ws.send(JSON.stringify({ command: 'get_tile_walkable' }));
  });

  ws.on('message', (data) => {
    try {
      const response = JSON.parse(data.toString());
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        ws.close();
        if (response.success && response.tile_walkable) {
          console.log(`[Entity ${entityUid}] Received walkable array: ${response.dimensions.rows}x${response.dimensions.cols}`);
          callback({ success: true, tile_walkable: response.tile_walkable, dimensions: response.dimensions });
        } else {
          callback({ success: false, error: 'Invalid response from map', response });
        }
      }
    } catch (error) {
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        ws.close();
        callback({ success: false, error: `Parse error: ${error.message}` });
      }
    }
  });

  ws.on('error', (error) => {
    if (!isResolved) {
      isResolved = true;
      clearTimeout(timeout);
      callback({ success: false, error: `WebSocket error: ${error.message}` });
    }
  });

  ws.on('close', () => {
    if (!isResolved) {
      isResolved = true;
      clearTimeout(timeout);
      callback({ success: false, error: 'WebSocket closed unexpectedly' });
    }
  });
}

// Algoritmo BFS per trovare il percorso più breve tra due punti su una griglia walkable
function findPathBFS(tileWalkable, startI, startJ, targetI, targetJ) {
  const rows = tileWalkable.length;
  const cols = tileWalkable[0]?.length || 0;

  // Verifica che le coordinate siano valide
  if (startI < 0 || startI >= rows || startJ < 0 || startJ >= cols ||
      targetI < 0 || targetI >= rows || targetJ < 0 || targetJ >= cols) {
    return { success: false, error: 'Coordinates out of bounds' };
  }

  // Verifica che start e target siano walkable
  if (!tileWalkable[startI][startJ] || !tileWalkable[targetI][targetJ]) {
    return { success: false, error: 'Start or target position is not walkable' };
  }

  // Se start === target, ritorna un percorso vuoto
  if (startI === targetI && startJ === targetJ) {
    return { success: true, path: [], distance: 0 };
  }

  // Direzioni: su, giù, destra, sinistra
  const directions = [
    { di: -1, dj: 0, name: 'up' },
    { di: 1, dj: 0, name: 'down' },
    { di: 0, dj: 1, name: 'right' },
    { di: 0, dj: -1, name: 'left' }
  ];

  // BFS
  const visited = Array.from({ length: rows }, () => Array(cols).fill(false));
  const queue = [{ i: startI, j: startJ, path: [] }];
  visited[startI][startJ] = true;

  while (queue.length > 0) {
    const current = queue.shift();

    for (const dir of directions) {
      const newI = current.i + dir.di;
      const newJ = current.j + dir.dj;

      // Verifica bounds e se è walkable e non visitato
      if (newI >= 0 && newI < rows && newJ >= 0 && newJ < cols &&
          tileWalkable[newI][newJ] && !visited[newI][newJ]) {

        const newPath = [...current.path, { action: dir.name, i: newI, j: newJ }];

        // Se abbiamo raggiunto il target
        if (newI === targetI && newJ === targetJ) {
          return {
            success: true,
            path: newPath,
            distance: newPath.length
          };
        }

        visited[newI][newJ] = true;
        queue.push({ i: newI, j: newJ, path: newPath });
      }
    }
  }

  return { success: false, error: 'No path found to target' };
}

// Funzione per disegnare il path come linea rossa con punti al centro dei tile
function drawPathViaPusher(path, currentI, currentJ, targetI, targetJ) {
  if (!path || path.length === 0) {
    console.log(`[Entity ${entityUid}] No path to draw`);
    return;
  }

  const requestId = 'path_' + Date.now();
  const tileSize = 32;
  const pathColor = '0xEF4444'; // Rosso per il path

  // Costruisci gli elementi del path da disegnare
  const pathItems = [];

  // Calcola i centri di tutti i tile (incluso quello iniziale)
  const centers = [];

  // Posizione iniziale
  const startCenterX = currentJ * tileSize + tileSize / 2;
  const startCenterY = currentI * tileSize + tileSize / 2;
  centers.push({ x: startCenterX, y: startCenterY });

  // Centri di ogni tile nel path
  path.forEach((step) => {
    const centerX = step.j * tileSize + tileSize / 2;
    const centerY = step.i * tileSize + tileSize / 2;
    centers.push({ x: centerX, y: centerY });
  });

  // Disegna le linee che collegano i punti
  for (let i = 0; i < centers.length - 1; i++) {
    pathItems.push({
      type: 'draw',
      object: {
        uid: `${requestId}_line_${i}`,
        type: 'line',
        x1: centers[i].x,
        y1: centers[i].y,
        x2: centers[i + 1].x,
        y2: centers[i + 1].y,
        color: pathColor,
        thickness: 3,
      },
    });
  }

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
        borderColor: pathColor,
        thickness: isStart ? 3 : 2,
      },
    });
  });

  const drawPayload = {
    type: 'draw_interface',
    request_id: requestId,
    player_id: playerId,
    items: pathItems,
  };

  // Invia tramite Pusher (stesso pattern del container alert)
  const channelName = 'player_' + playerId + '_channel';
  console.log(`[Entity ${entityUid}] Drawing path via Pusher on channel: ${channelName}`);

  pusher.trigger(channelName, 'draw_interface', drawPayload)
    .then(() => {
      console.log(`[Entity ${entityUid}] Path drawn successfully via Pusher`);
    })
    .catch((err) => {
      console.error(`[Entity ${entityUid}] ⛔ Pusher draw FAILED`);
      console.error(`[Entity ${entityUid}]   Channel : ${channelName}`);
      console.error(`[Entity ${entityUid}]   Target  : ${reverbScheme}://${reverbHost}:${reverbPort}`);
      console.error(`[Entity ${entityUid}]   Message : ${err.message || '(no message)'}`);
    });
}

// Funzione per cancellare il path disegnato (linee e punti)
function clearPathViaPusher(pathLength) {
  const requestId = 'path_clear_' + Date.now();

  // Costruisci gli elementi da rimuovere (linee e punti)
  const clearItems = [];

  // Cancella le linee (sono pathLength - 1 linee tra pathLength punti)
  // Ma abbiamo pathLength + 1 punti (incluso start), quindi pathLength linee
  const totalLines = pathLength;
  for (let i = 0; i < totalLines; i++) {
    clearItems.push({
      type: 'update',
      uid: `path_${requestId}_line_${i}`,
      attributes: { renderable: false },
    });
  }

  // Cancella i punti (pathLength + 1 punti, incluso start)
  const totalDots = pathLength + 1;
  for (let i = 0; i < totalDots; i++) {
    clearItems.push({
      type: 'update',
      uid: `path_${requestId}_dot_${i}`,
      attributes: { renderable: false },
    });
  }

  const clearPayload = {
    type: 'draw_interface',
    request_id: requestId,
    player_id: playerId,
    items: clearItems,
  };

  const channelName = 'player_' + playerId + '_channel';
  console.log(`[Entity ${entityUid}] Clearing path via Pusher on channel: ${channelName}`);

  pusher.trigger(channelName, 'draw_interface', clearPayload)
    .then(() => {
      console.log(`[Entity ${entityUid}] Path cleared successfully via Pusher`);
    })
    .catch((err) => {
      console.error(`[Entity ${entityUid}] ⛔ Pusher clear FAILED: ${err.message}`);
    });
}

// Funzione per muovere l'entity tile per tile con animazione
// Invia tutti i dati del movimento in un'unica chiamata Pusher
function moveEntityAlongPath(path, callback) {
  if (!path || path.length === 0) {
    console.log(`[Entity ${entityUid}] No path to move along`);
    if (callback) callback();
    return;
  }

  const totalSteps = path.length;
  const moveInterval = 400; // 400ms per tile

  console.log(`[Entity ${entityUid}] Starting movement along ${totalSteps} tiles`);

  // Prepara tutti i step del movimento
  const movementSteps = path.map((step, index) => ({
    tile_i: step.i,
    tile_j: step.j,
    step: index + 1,
    delay_ms: (index + 1) * moveInterval,
  }));

  // Invia tutti i dati del movimento in un'unica chiamata Pusher
  const movePayload = {
    type: 'entity_movement',
    entity_uid: entityUid,
    steps: movementSteps,
    total_steps: totalSteps,
    interval_ms: moveInterval,
    path_length: totalSteps,
  };

  const channelName = 'player_' + playerId + '_channel';
  console.log(`[Entity ${entityUid}] Sending movement data via Pusher on channel: ${channelName}`);

  pusher.trigger(channelName, 'entity_movement', movePayload)
    .then(() => {
      console.log(`[Entity ${entityUid}] Movement data sent successfully via Pusher`);
    })
    .catch((err) => {
      console.error(`[Entity ${entityUid}] ⛔ Movement data FAILED: ${err.message}`);
    });

  // Aggiorna la posizione locale dopo l'invio
  let currentStep = 0;
  const updatePosition = () => {
    if (currentStep >= totalSteps) {
      console.log(`[Entity ${entityUid}] Movement completed!`);
      // Cancella il path una volta arrivati alla fine
      clearPathViaPusher(totalSteps);
      if (callback) callback();
      return;
    }

    const step = path[currentStep];
    localCurrentTileI = step.i;
    localCurrentTileJ = step.j;
    currentStep++;

    setTimeout(updatePosition, moveInterval);
  };

  // Inizia l'aggiornamento della posizione locale
  setTimeout(updatePosition, moveInterval);
}

// Funzione per ottenere la posizione attuale dal backend (solo la prima volta)
function fetchCurrentPositionFromApi(callback) {
  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: `/api/auth/game/entity/get_position?entity_uid=${encodeURIComponent(entityUid)}`,
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Cookie': sessionCookie,
      'X-XSRF-TOKEN': xsrfToken
    },
  };

  const req = http.request(options, (res) => {
    let data = '';
    res.on('data', (chunk) => { data += chunk; });
    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success && response.tile_i !== undefined && response.tile_j !== undefined) {
          callback({
            success: true,
            tile_i: Number(response.tile_i),
            tile_j: Number(response.tile_j)
          });
        } else {
          callback({ success: false, error: 'Position not found in response' });
        }
      } catch (error) {
        callback({ success: false, error: `Parse error: ${error.message}` });
      }
    });
  });

  req.on('error', (error) => {
    callback({ success: false, error: error.message });
  });

  req.end();
}

// Funzione per eseguire un movimento specifico
// Utilizza WebSocket per ottenere l'array walkable dal map container
// e calcola il percorso con BFS dalla posizione attuale al target
function performMovement(params, callback) {
  if (!sessionCookie) {
    callback({ success: false, error: 'No session cookie' });
    return;
  }

  // Determina le coordinate target
  let targetI, targetJ;

  if (params.target_i !== undefined && params.target_j !== undefined) {
    targetI = Number(params.target_i);
    targetJ = Number(params.target_j);
  } else {
    callback({ success: false, error: 'Target coordinates (target_i, target_j) are required' });
    return;
  }

  // Funzione per inizializzare la posizione (solo la prima volta tramite API)
  const initializePosition = (onInitialized) => {
    if (isPositionInitialized && localCurrentTileI !== null && localCurrentTileJ !== null) {
      console.log(`[Entity ${entityUid}] Using cached position: (${localCurrentTileI}, ${localCurrentTileJ})`);
      onInitialized({ success: true, tile_i: localCurrentTileI, tile_j: localCurrentTileJ });
    } else {
      console.log(`[Entity ${entityUid}] First time: fetching position from API...`);
      fetchCurrentPositionFromApi((result) => {
        if (result.success) {
          localCurrentTileI = result.tile_i;
          localCurrentTileJ = result.tile_j;
          isPositionInitialized = true;
          console.log(`[Entity ${entityUid}] Position initialized from API: (${localCurrentTileI}, ${localCurrentTileJ})`);
          onInitialized(result);
        } else {
          callback({ success: false, error: 'Failed to initialize position' });
        }
      });
    }
  };

  // Funzione per eseguire il movimento una volta ottenuto l'array walkable
  const executeMovementWithWalkable = (currentI, currentJ, tileWalkable, dimensions) => {
    // Calcola il percorso con BFS
    const pathResult = findPathBFS(tileWalkable, currentI, currentJ, targetI, targetJ);

    // Prepara il risultato JSON
    const movementResult = {
      entity_uid: entityUid,
      timestamp: new Date().toISOString(),
      current_position: {
        tile_i: currentI,
        tile_j: currentJ
      },
      target_position: {
        tile_i: targetI,
        tile_j: targetJ
      },
      path: pathResult.success ? pathResult.path : [],
      path_found: pathResult.success,
      distance: pathResult.success ? pathResult.distance : null,
      walkable_dimensions: dimensions,
      error: pathResult.success ? null : pathResult.error
    };

    // Log del risultato in formato JSON
    console.log(`[Entity ${entityUid}] Movement Result:`);
    console.log(JSON.stringify(movementResult, null, 2));

    // Disegna il path tramite Pusher (linea rossa con punti)
    if (pathResult.success && pathResult.path.length > 0) {
      drawPathViaPusher(pathResult.path, currentI, currentJ, targetI, targetJ);

      // Avvia il movimento dell'entity lungo il path (400ms per tile)
      moveEntityAlongPath(pathResult.path, () => {
        // Questo viene chiamato quando il movimento è completato
        console.log(`[Entity ${entityUid}] Movement along path completed`);
      });
    }

    callback({
      success: pathResult.success,
      movement: movementResult,
      message: pathResult.success ? 'Path calculated successfully' : pathResult.error
    });
  };

  // Flusso principale: 1) Inizializza posizione -> 2) Ottieni walkable -> 3) Calcola percorso
  initializePosition((posResult) => {
    if (!posResult.success) {
      callback({ success: false, error: 'Position initialization failed' });
      return;
    }

    console.log(`[Entity ${entityUid}] Requesting walkable array from map container...`);

    // Ottieni l'array walkable dal map container via WebSocket
    getWalkableFromMap((walkableResult) => {
      if (!walkableResult.success) {
        callback({ success: false, error: `Failed to get walkable array: ${walkableResult.error}` });
        return;
      }

      // Esegui il movimento con l'array walkable ottenuto
      executeMovementWithWalkable(
        posResult.tile_i,
        posResult.tile_j,
        walkableResult.tile_walkable,
        walkableResult.dimensions
      );
    });
  });
}

// Start flow

performLogin();