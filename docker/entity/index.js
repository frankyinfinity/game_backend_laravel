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
const playerId = process.env.ENTITY_PLAYER_ID || process.env.PLAYER_ID || 0;

// Detect if running inside Docker; if so, 'localhost' cannot reach the host.
function isRunningInDocker() {
  try {
    return fs.existsSync('/.dockerenv') || fs.readFileSync('/proc/1/cgroup', 'utf8').includes('docker');
  } catch (_) {
    return false;
  }
}

function resolveReverbHost(rawHost) {
  // I container girano con --network host (vedi DockerContainerService):
  // condividono la rete della VM, quindi 127.0.0.1 del container È il loopback
  // della VM stessa. I servizi host (backend :8085, Reverb :8081) sono esposti
  // sulla VM proprio tramite loopback con i tunnel invertiti di start.bat
  // (ssh -R 8085/8081:127.0.0.1:...). "host.docker.internal" invece risolve
  // al gateway del bridge Docker (172.17.x.x) dove i tunnel NON ascoltano.
  // 127.0.0.1 è l'IP esplicito di loopback: viene sempre rispettato.
  if (isRunningInDocker() && (rawHost === 'localhost' || rawHost === '0.0.0.0')) {
    const resolved = process.env.DOCKER_HOST_IP || '127.0.0.1';
    console.log(`[Entity ${entityUid}] Docker detected: remapping REVERB_HOST "${rawHost}" → "${resolved}"`);
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

const GENES_WAIT_SECONDS = 30;
const CHIMICAL_WAIT_SECONDS = 30;
const POSITION_WAIT_SECONDS = 10;
const DEGRADATION_WAIT_SECONDS = 60;

// Timeout per le richieste walkable al map (sovrascrivibili via env, es. nei test)
const WALKABLE_RESPONSE_TIMEOUT_MS = parseInt(process.env.WALKABLE_RESPONSE_TIMEOUT_MS || '5000', 10);
const WALKABLE_RETRY_DELAY_MS = parseInt(process.env.WALKABLE_RETRY_DELAY_MS || '1000', 10);

// Queue per serializzare le chiamate API e evitare socket hang up
let apiQueue = [];
let isApiCallInProgress = false;

function processApiQueue() {
  if (isApiCallInProgress || apiQueue.length === 0) return;
  
  const nextCall = apiQueue.shift();
  isApiCallInProgress = true;
  
  const callback = nextCall.callback;
  nextCall.fn(() => {
    isApiCallInProgress = false;
    if (callback) callback();
    processApiQueue();
  });
}

function enqueueApiCall(fn, callback) {
  apiQueue.push({ fn, callback });
  processApiQueue();
}

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

// Variabili per tracciare la posizione attuale e i geni
let currentTileI = entityTileI;
let currentTileJ = entityTileJ;
let currentGenes = {};
let currentChimicalElements = {};

// Variabili per tracciare la posizione attuale in locale (aggiornate dopo ogni movimento)
let localCurrentTileI = null;
let localCurrentTileJ = null;
let isPositionInitialized = false;

// Variabile per tracciare l'ultimo requestId del path disegnato (per poterlo cancellare)
let lastPathRequestId = null;

// Configurazione per connettersi al container map tramite ws-gateway
// IMPORTANTE: i container girano con --network host => niente DNS Docker,
// i nomi ("ws-gateway", "map") NON risolvono → va usato 127.0.0.1.
const wsGatewayHost = process.env.WS_GATEWAY_HOST || '127.0.0.1';
const wsGatewayPort = process.env.WS_GATEWAY_PORT || 9001;
const mapWsPort = process.env.MAP_WS_PORT || 8080;

// Configurazione per connessione diretta al map (fallback)
const mapDirectHost = process.env.MAP_DIRECT_HOST || '127.0.0.1';
const mapDirectPort = process.env.MAP_DIRECT_PORT || 8080;

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

// ========== Gestione sessione con auto-retry ==========
// Il login parte al boot e, se il backend o il tunnel SSH non è ancora
// raggiungibile, viene riprovato con backoff finché non riesce: il container
// si auto-ripara quando l'infrastruttura torna su.
let loginInProgress = false;
let loginAttempts = 0;
let loginRetryTimer = null;
let cyclesStarted = false;
const loginWaiters = [];

function notifyLoginWaiters() {
  const waiters = loginWaiters.splice(0);
  const ok = !!sessionCookie;
  waiters.forEach((w) => {
    try { w(ok); } catch (e) { /* ignore */ }
  });
}

function startCyclesOnce() {
  if (cyclesStarted) return;
  cyclesStarted = true;
  scheduleNextCycle();
  scheduleGenesFetch();
  scheduleChimicalElementsFetch();
  scheduleEntityDegradationCheck();
}

// Ripristina la sessione se il backend risponde 401/419 (sessione scaduta).
function handleAuthFailure(res, context) {
  if (res && (res.statusCode === 401 || res.statusCode === 419)) {
    console.error(`[Entity ${entityUid}] ${context}: session expired/unauthorized, re-logging in...`);
    sessionCookie = null;
    xsrfToken = null;
    performLogin();
    return true;
  }
  return false;
}

// Aspetta che la sessione sia disponibile (attivando il login se manca).
// Se entro timeoutMs non c'è ancora sessione, invoca callback(false).
function ensureSession(callback, timeoutMs = 8000) {
  if (sessionCookie) {
    if (callback) callback(true);
    return;
  }

  let settled = false;
  const timeoutTimer = setTimeout(() => {
    if (settled) return;
    settled = true;
    const idx = loginWaiters.indexOf(handler);
    if (idx >= 0) loginWaiters.splice(idx, 1);
    if (callback) callback(!!sessionCookie);
  }, timeoutMs);

  const handler = (ok) => {
    if (settled) return;
    settled = true;
    clearTimeout(timeoutTimer);
    if (callback) callback(ok);
  };

  loginWaiters.push(handler);
  performLogin();
}

function performLogin() {
  if (loginInProgress) return;
  if (sessionCookie) {
    notifyLoginWaiters();
    return;
  }

  loginInProgress = true;
  loginAttempts++;

  const finishLogin = (success) => {
    loginInProgress = false;

    if (success) {
      loginAttempts = 0;
      startCyclesOnce();
      console.log(`[Entity ${entityUid}] Login successful (session ready)`);
      notifyLoginWaiters();
    } else {
      // Backoff esponenziale (2s → max 30s), retry all'infinito per auto-riparazione.
      // NON notificare i loginWaiters: devono rimanere in attesa del retry.
      // Il timeout di ensureSession (8s) li liberà comunque se il login impiega troppo tempo.
      const delay = Math.min(30000, 2000 * Math.pow(2, Math.min(loginAttempts - 1, 5)));
      console.error(`[Entity ${entityUid}] Login failed (attempt ${loginAttempts}), retrying in ${Math.round(delay / 1000)}s...`);
      if (loginRetryTimer) clearTimeout(loginRetryTimer);
      loginRetryTimer = setTimeout(() => performLogin(), delay);
    }
  };

  // Step 1: GET /login → cookie e CSRF token
  const optionsGet = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: '/login',
    method: 'GET',
  };

  const reqGet = http.request(optionsGet, (res) => {
    updateSession(res);

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
        finishLogin(true);
      } else {
        console.error(`Login failed with status: ${resPost.statusCode}`);
        resPost.on('data', (d) => console.error(d.toString()));
        finishLogin(false);
      }
    });

    reqPost.on('error', (e) => {
      console.error(`Login POST error: ${e.message}`);
      finishLogin(false);
    });
    reqPost.write(postData);
    reqPost.end();
  });

  reqGet.on('error', (e) => {
    console.error(`Initial GET error: ${e.message}`);
    finishLogin(false);
  });
  reqGet.end();
}

function fetchCurrentPosition() {
  if (!sessionCookie) {
    scheduleNextCycle(); // Riprogramma il prossimo ciclo anche se non c'è la sessione
    return;
  }

  enqueueApiCall((done) => {
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
          } else {
            console.error(`Status ${res.statusCode}: ${response.message || 'Unknown error'}`);
          }
        } catch (error) {
          if (handleAuthFailure(res, 'fetchCurrentPosition')) {
            // Sessione scaduta → il re-login è stato avviato
          } else {
            console.error(`Error parsing response: ${error.message}. Status: ${res.statusCode}`);
          }
        }
        scheduleNextCycle();
        done();
      });
    });

    req.on('error', (error) => {
      console.error(`Error fetching position: ${error.message}`);
      scheduleNextCycle();
      done();
    });

    req.end();
  });
}


function fetchCurrentGenes() {
  if (!sessionCookie) return;

  enqueueApiCall((done) => {
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
          } else if (handleAuthFailure(res, 'fetchCurrentGenes')) {
            // re-login avviato
          }
        } catch (error) {
          console.error(`[Entity ${entityUid}] Error parsing gene values: ${error.message}`);
        }
        done();
      });
    });

    req.on('error', (error) => {
      console.error(`[Entity ${entityUid}] Error fetching genes: ${error.message}`);
      done();
    });

    req.end();
  });
}

function fetchCurrentChimicalElements() {
  if (!sessionCookie) return;

  enqueueApiCall((done) => {
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
          } else if (handleAuthFailure(res, 'fetchCurrentChimicalElements')) {
            // re-login avviato
          }
        } catch (error) {
          console.error(`[Entity ${entityUid}] Error parsing chimical elements: ${error.message}`);
        }
        done();
      });
    });

    req.on('error', (error) => {
      console.error(`[Entity ${entityUid}] Error fetching chimical elements: ${error.message}`);
      done();
    });

    req.end();
  });
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

// Timer per la degradazione
let degradationTimer = null;
function scheduleEntityDegradationCheck() {
  if (degradationTimer) clearTimeout(degradationTimer);
  degradationTimer = setTimeout(() => {
    checkEntityDegradation();
    scheduleEntityDegradationCheck();
  }, DEGRADATION_WAIT_SECONDS * 1000);
}

function checkEntityDegradation() {
  if (!sessionCookie) return;

  enqueueApiCall((done) => {
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
          if (!response.success) {
            if (handleAuthFailure(res, 'checkEntityDegradation')) {
              // re-login avviato
            } else {
              console.error('[Entity ' + entityUid + '] Entity degradation check failed: ' + (response.message || 'Unknown error'));
            }
          }
        } catch (error) {
          console.error('[Entity ' + entityUid + '] Error parsing entity degradation response: ' + error.message);
        }
        done();
      });
    });

    req.on('error', (error) => {
      console.error('[Entity ' + entityUid + '] Error calling entity degradation API: ' + error.message);
      done();
    });

    req.write(postData);
    req.end();
  });
}

// Funzione per programmare il prossimo ciclo (solo position)
function scheduleNextCycle() {
  setTimeout(() => {
    fetchCurrentPosition();
  }, POSITION_WAIT_SECONDS * 1000);
}

// ========== WebSocket Server ==========
const wss = new WebSocket.Server({ port: wsPort, host: '0.0.0.0' });

wss.on('listening', () => {
  console.log(`[Entity ${entityUid}] WebSocket server listening on 0.0.0.0:${wsPort}`);
});

wss.on('connection', (ws) => {
  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);

      // Gestisci i comandi ricevuti
      handleWebSocketCommand(data, ws);
    } catch (error) {
      console.error(`[WebSocket] Error parsing message:`, error.message);
      ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
    }
  });

  ws.on('close', () => {
    // Client disconnected
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

  // Supporta sia params.target_i che data.target_i direttamente
  const moveParams = params || data;

  switch (command) {
    case 'move':
      // Esegui un movimento con azione specifica (up, down, left, right) o coordinate target
      if (moveParams && (moveParams.action || (moveParams.target_i !== undefined && moveParams.target_j !== undefined))) {
        performMovement(moveParams, (result) => {
          if (!result || !result.success) {
            console.error(`[Entity ${entityUid}] ⛔ Move failed: ${JSON.stringify(result)}`);
          }
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

// Funzione per connettersi al map container e ottenere l'array walkable
// Prova automaticamente: 1) via ws-gateway, 2) connessione diretta al map
// Ogni richiesta usa un request_id univoco: vengono accettate SOLO le risposte
// con lo stesso request_id (il messaggio di benvenuto del map viene ignorato).
function getWalkableFromMap(callback) {
  const requestId = 'walkable_' + Date.now() + '_' + Math.floor(Math.random() * 100000);
  const maxRetriesPerMethod = 2;

  // Metodo 1: Connessione via ws-gateway
  const tryViaGateway = (retryCount = 0) => {
    const mapWsUrl = `ws://${wsGatewayHost}:${wsGatewayPort}?port=${mapWsPort}`;
    console.log(`[Entity ${entityUid}] Trying ws-gateway: ${mapWsUrl} (attempt ${retryCount + 1}/${maxRetriesPerMethod + 1})`);

    const ws = new WebSocket(mapWsUrl);
    let isResolved = false;

    const timeout = setTimeout(() => {
      if (!isResolved) {
        isResolved = true;
        ws.close();
        if (retryCount < maxRetriesPerMethod) {
          tryViaGateway(retryCount + 1);
        } else {
          // Fallback: prova connessione diretta
          tryDirectConnection();
        }
      }
    }, WALKABLE_RESPONSE_TIMEOUT_MS);

    ws.on('open', () => {
      console.log(`[Entity ${entityUid}] Connected via ws-gateway, requesting walkable array...`);
      ws.send(JSON.stringify({ request_id: requestId, command: 'get_tile_walkable' }));
    });

    ws.on('message', (data) => {
      let response;
      try {
        response = JSON.parse(data.toString());
      } catch (error) {
        // Frame non JSON: non è la risposta che cerchiamo
        return;
      }

      // Ignora i messaggi che non sono la risposta alla nostra richiesta
      // (es. il messaggio di benvenuto del map) → non risolviamo più il
      // movimento con un falso errore.
      if (String(response.request_id || '') !== String(requestId)) {
        return;
      }

      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        ws.close();

        if (response.success && Array.isArray(response.tile_walkable)) {
          console.log(`[Entity ${entityUid}] Received walkable array: ${response.dimensions.rows}x${response.dimensions.cols}`);
          callback({ success: true, tile_walkable: response.tile_walkable, dimensions: response.dimensions });
        } else if (response.success && response.tile_walkable === null) {
          // Il map non ha ancora caricato l'array walkable: riprova
          console.error(`[Entity ${entityUid}] Map walkable array not ready yet (attempt ${retryCount + 1}/${maxRetriesPerMethod + 1})`);
          if (retryCount < maxRetriesPerMethod) {
            setTimeout(() => tryViaGateway(retryCount + 1), WALKABLE_RETRY_DELAY_MS);
          } else {
            // Fallback: prova connessione diretta
            tryDirectConnection();
          }
        } else {
          console.error(`[Entity ${entityUid}] Invalid response from map: ${JSON.stringify(response)}`);
          if (retryCount < maxRetriesPerMethod) {
            setTimeout(() => tryViaGateway(retryCount + 1), WALKABLE_RETRY_DELAY_MS);
          } else {
            // Fallback: prova connessione diretta
            tryDirectConnection();
          }
        }
      }
    });

    ws.on('error', (error) => {
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        console.log(`[Entity ${entityUid}] ws-gateway error: ${error.message}`);
        if (retryCount < maxRetriesPerMethod) {
          setTimeout(() => tryViaGateway(retryCount + 1), 500);
        } else {
          // Fallback: prova connessione diretta
          tryDirectConnection();
        }
      }
    });

    ws.on('close', () => {
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        tryDirectConnection();
      }
    });
  };

  // Metodo 2: Connessione diretta al map container
  const tryDirectConnection = (retryCount = 0) => {
    const directUrl = `ws://${mapDirectHost}:${mapDirectPort}`;
    console.log(`[Entity ${entityUid}] Trying direct connection: ${directUrl} (attempt ${retryCount + 1}/${maxRetriesPerMethod + 1})`);

    const ws = new WebSocket(directUrl);
    let isResolved = false;

    const timeout = setTimeout(() => {
      if (!isResolved) {
        isResolved = true;
        ws.close();
        if (retryCount < maxRetriesPerMethod) {
          setTimeout(() => tryDirectConnection(retryCount + 1), 500);
        } else {
          console.error(`[Entity ${entityUid}] ⛔ All connection methods failed (requestId ${requestId})`);
          callback({ success: false, error: 'All connection methods failed', request_id: requestId });
        }
      }
    }, WALKABLE_RESPONSE_TIMEOUT_MS);

    ws.on('open', () => {
      console.log(`[Entity ${entityUid}] Connected directly to map, requesting walkable array...`);
      ws.send(JSON.stringify({ request_id: requestId, command: 'get_tile_walkable' }));
    });

    ws.on('message', (data) => {
      let response;
      try {
        response = JSON.parse(data.toString());
      } catch (error) {
        // Frame non JSON: non è la risposta che cerchiamo
        return;
      }

      // Ignora i messaggi che non sono la risposta alla nostra richiesta
      // (es. il messaggio di benvenuto del map).
      if (String(response.request_id || '') !== String(requestId)) {
        return;
      }

      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        ws.close();

        if (response.success && Array.isArray(response.tile_walkable)) {
          console.log(`[Entity ${entityUid}] Received walkable array: ${response.dimensions.rows}x${response.dimensions.cols}`);
          callback({ success: true, tile_walkable: response.tile_walkable, dimensions: response.dimensions });
        } else if (response.success && response.tile_walkable === null) {
          // Il map non ha ancora caricato l'array walkable: riprova
          console.error(`[Entity ${entityUid}] Map walkable array not ready yet (attempt ${retryCount + 1}/${maxRetriesPerMethod + 1})`);
          if (retryCount < maxRetriesPerMethod) {
            setTimeout(() => tryDirectConnection(retryCount + 1), WALKABLE_RETRY_DELAY_MS);
          } else {
            console.error(`[Entity ${entityUid}] ⛔ Map never provided the walkable array (requestId ${requestId})`);
            callback({ success: false, error: 'Walkable array not available from map', request_id: requestId });
          }
        } else {
          console.error(`[Entity ${entityUid}] Invalid response from map: ${JSON.stringify(response)}`);
          if (retryCount < maxRetriesPerMethod) {
            setTimeout(() => tryDirectConnection(retryCount + 1), WALKABLE_RETRY_DELAY_MS);
          } else {
            console.error(`[Entity ${entityUid}] ⛔ All connection methods failed (requestId ${requestId})`);
            callback({ success: false, error: 'All connection methods failed', request_id: requestId });
          }
        }
      }
    });

    ws.on('error', (error) => {
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        console.log(`[Entity ${entityUid}] Direct connection error: ${error.message}`);
        if (retryCount < maxRetriesPerMethod) {
          setTimeout(() => tryDirectConnection(retryCount + 1), 500);
        } else {
          callback({ success: false, error: 'All connection methods failed' });
        }
      }
    });

    ws.on('close', () => {
      if (!isResolved) {
        isResolved = true;
        clearTimeout(timeout);
        if (retryCount < maxRetriesPerMethod) {
          setTimeout(() => tryDirectConnection(retryCount + 1), 500);
        } else {
          callback({ success: false, error: 'All connection methods failed' });
        }
      }
    });
  };

  // Inizia con il primo metodo (ws-gateway)
  tryViaGateway();
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
  lastPathRequestId = requestId; // Salva il requestId per poterlo cancellare dopo
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
  if (!lastPathRequestId) {
    console.log(`[Entity ${entityUid}] No path to clear`);
    return;
  }

  // Usa lo stesso requestId usato per disegnare il path
  const requestId = lastPathRequestId;

  // Costruisci gli elementi da rimuovere (linee e punti)
  const clearItems = [];

  // Cancella le linee (sono pathLength - 1 linee tra pathLength punti)
  // Ma abbiamo pathLength + 1 punti (incluso start), quindi pathLength linee
  const totalLines = pathLength;
  for (let i = 0; i < totalLines; i++) {
    clearItems.push({
      type: 'update',
      uid: `${requestId}_line_${i}`,
      attributes: { renderable: false },
    });
  }

  // Cancella i punti (pathLength + 1 punti, incluso start)
  const totalDots = pathLength + 1;
  for (let i = 0; i < totalDots; i++) {
    clearItems.push({
      type: 'update',
      uid: `${requestId}_dot_${i}`,
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
// Supporta sia coordinate target (target_i, target_j) che azioni (up, down, left, right)
function performMovement(params, callback) {
  if (!sessionCookie) {
    // Sessione non ancora pronta (login al boot in corso o riprovato):
    // aspetta (attivando il login) prima di rinunciare.
    ensureSession((ok) => {
      if (!ok || !sessionCookie) {
        callback({ success: false, error: 'No session cookie (login non riuscito)' });
        return;
      }
      performMovement(params, callback);
    }, 10000);
    return;
  }

  // Determina le coordinate target
  let targetI, targetJ;

  if (params.target_i !== undefined && params.target_j !== undefined) {
    // Modalità coordinate: clicco su un tile
    targetI = Number(params.target_i);
    targetJ = Number(params.target_j);
  } else if (params.action) {
    // Modalità azione: uso le freccette (up, down, left, right)
    const action = params.action.toLowerCase();
    const actionMap = {
      'up': { di: -1, dj: 0 },
      'down': { di: 1, dj: 0 },
      'left': { di: 0, dj: -1 },
      'right': { di: 0, dj: 1 },
    };

    if (!actionMap[action]) {
      callback({ success: false, error: `Invalid action: ${params.action}` });
      return;
    }

    // Per le azioni, abbiamo bisogno della posizione corrente prima di calcolare il target
    const getPositionForAction = () => {
      if (isPositionInitialized && localCurrentTileI !== null && localCurrentTileJ !== null) {
        return { i: localCurrentTileI, j: localCurrentTileJ };
      }
      return null;
    };

    const currentPos = getPositionForAction();
    if (!currentPos) {
      // Se non abbiamo la posizione, la otteniamo dall'API
      fetchCurrentPositionFromApi((result) => {
        if (result.success) {
          localCurrentTileI = result.tile_i;
          localCurrentTileJ = result.tile_j;
          isPositionInitialized = true;
          targetI = result.tile_i + actionMap[action].di;
          targetJ = result.tile_j + actionMap[action].dj;
          startMovement(targetI, targetJ, callback);
        } else {
          callback({ success: false, error: 'Failed to get current position for action' });
        }
      });
      return;
    }

    targetI = currentPos.i + actionMap[action].di;
    targetJ = currentPos.j + actionMap[action].dj;
  } else {
    callback({ success: false, error: 'Target coordinates (target_i, target_j) or action is required' });
    return;
  }

  // Avvia il movimento con le coordinate target calcolate
  startMovement(targetI, targetJ, callback);
}

// Funzione condivisa per avviare il movimento (usata sia da coordinate che da azioni)
function startMovement(targetI, targetJ, callback) {
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
        console.error(`[Entity ${entityUid}] ⛔ Failed to get walkable array: ${walkableResult.error}`);
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

// Start flow: eseguito solo quando lo script viene avviato direttamente
// (node index.js dentro il container). Importandolo come modulo (es. nei
// test) il login non parte automaticamente.
if (require.main === module) {
  performLogin();
}

module.exports = {
  getWalkableFromMap,
  findPathBFS,
  performMovement,
  performLogin,
  ensureSession,
  resolveReverbHost,
  playerId,
};