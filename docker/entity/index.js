// entity.js

const http = require('http');
const WebSocket = require('ws');

// Leggi i parametri dalle variabili d'ambiente
const entityUid = process.env.ENTITY_UID;
const entityTileI = process.env.ENTITY_TILE_I;
const entityTileJ = process.env.ENTITY_TILE_J;
const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const wsPort = process.env.WS_PORT || 8080;

console.log(`Entity service started.`);
console.log(`Entity UID: ${entityUid}`);
console.log(`Tile Position: (${entityTileI}, ${entityTileJ})`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);

// Variabili per tracciare la posizione attuale
let currentTileI = entityTileI;
let currentTileJ = entityTileJ;

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
        // Avvia il primo ciclo
        scheduleNextCycle();
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


// Funzione per programmare il prossimo ciclo
function scheduleNextCycle() {
  setTimeout(() => {
    fetchCurrentPosition();
  }, 5000);
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
      // Ritorna la posizione corrente
      ws.send(JSON.stringify({
        success: true,
        entity_uid: entityUid,
        position: { i: currentTileI, j: currentTileJ }
      }));
      break;

    default:
      ws.send(JSON.stringify({
        success: false,
        error: `Unknown command: ${command}`
      }));
  }
}

// Funzione per eseguire un movimento specifico
function performMovement(params, callback) {
  if (!sessionCookie) {
    callback({ success: false, error: 'No session cookie' });
    return;
  }

  const payload = {
    entity_uid: entityUid
  };

  if (params.action) {
    payload.action = params.action;
  } else if (params.target_i !== undefined && params.target_j !== undefined) {
    payload.target_i = params.target_i;
    payload.target_j = params.target_j;
  }

  const postData = JSON.stringify(payload);

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: '/api/auth/game/entity/movement',
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

    res.on('data', (chunk) => {
      data += chunk;
    });

    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response.success) {
          console.log(`[Entity ${entityUid}] Movement performed:`, params);
          callback({ success: true, params: params, message: 'Movement executed' });
        } else {
          callback({ success: false, error: response.message || 'Movement failed' });
        }
      } catch (error) {
        callback({ success: false, error: `Parse error: ${error.message}` });
      }
    });
  });

  req.on('error', (error) => {
    callback({ success: false, error: error.message });
  });

  req.write(postData);
  req.end();
}

// Start flow

performLogin();