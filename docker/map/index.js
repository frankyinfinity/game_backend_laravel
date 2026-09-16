// map.js

const http = require('http');
const WebSocket = require('ws');

// Leggi i parametri dalle variabili d'ambiente
const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const birthRegionId = process.env.BIRTH_REGION_ID;
const wsPort = process.env.WS_PORT || 8080;

console.log(`Map service started.`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Birth Region ID: ${birthRegionId || 'MISSING'}`);
console.log(`WebSocket Port: ${wsPort}`);

// function to handle login and session
let sessionCookie = null;
let xsrfToken = null;
let latestTilesByBirthRegion = null;
let latestBirthRegionDetails = null;
let tileWalkable = null;
let tileCoordinates = null;

function findTileInCache(tileI, tileJ) {
  if (!latestTilesByBirthRegion || !Array.isArray(latestTilesByBirthRegion.tiles)) {
    return null;
  }

  return latestTilesByBirthRegion.tiles.find((tile) => (
    Number(tile.i) === Number(tileI) && Number(tile.j) === Number(tileJ)
  )) || null;
}

function findDetailsInCache(tileI, tileJ) {
  if (!latestBirthRegionDetails || !Array.isArray(latestBirthRegionDetails.details)) {
    return null;
  }

  return latestBirthRegionDetails.details.find((detail) => (
    Number(detail.tile_i) === Number(tileI) && Number(detail.tile_j) === Number(tileJ)
  )) || null;
}

function parseTileWalkableFromApiResponse(response) {
  // La variabile viene popolata SOLO dalla risposta API
  if (response && Array.isArray(response.tile_walkable)) {
    console.log(`[Map] tileWalkable received from API: ${response.tile_walkable.length}x${response.tile_walkable[0]?.length || 0}`);
    return response.tile_walkable;
  }

  console.log('[Map] parseTileWalkableFromApiResponse: no tile_walkable array in API response');
  return null;
}

// L'array di coordinate (pixel) dei tile del birth region arriva dall'API
// /api/auth/game/get_tile_coordinates e ha la STESSA forma dell'array
// tile_walkable ([i][j]) così può essere consumato allo stesso modo:
// ogni cella contiene le coordinate del tile oppure null se non disponibili.
function parseTileCoordinatesFromApiResponse(response) {
  // La variabile viene popolata SOLO dalla risposta API
  if (response && Array.isArray(response.tile_coordinates)) {
    console.log(`[Map] tileCoordinates received from API: ${response.tile_coordinates.length}x${response.tile_coordinates[0]?.length || 0}`);
    return response.tile_coordinates;
  }

  console.log('[Map] parseTileCoordinatesFromApiResponse: no tile_coordinates array in API response');
  return null;
}

function handleWebSocketCommand(data, ws) {
  const { command, params } = data || {};

  if (command === 'get_tile_walkable') {
    const requestId = data.request_id ?? params?.request_id ?? null;
    ws.send(JSON.stringify({
      success: true,
      request_id: requestId,
      command: 'get_tile_walkable',
      tile_walkable: tileWalkable,
      dimensions: tileWalkable ? { rows: tileWalkable.length, cols: tileWalkable[0].length } : null,
    }));
    return;
  }

  // Come get_tile_walkable, ma restituisce le coordinate (pixel) di ogni tile
  // del birth region invece dei flag 0/1 di percorribilità.
  if (command === 'get_tile_coordinates') {
    const requestId = data.request_id ?? params?.request_id ?? null;
    ws.send(JSON.stringify({
      success: true,
      request_id: requestId,
      command: 'get_tile_coordinates',
      birth_region_id: birthRegionId,
      tile_coordinates: tileCoordinates,
      dimensions: tileCoordinates && tileCoordinates.length
        ? { rows: tileCoordinates.length, cols: tileCoordinates[0]?.length || 0 }
        : null,
    }));
    return;
  }

  if (command === 'get_tile_info') {
    const tileI = params ? params.tile_i : undefined;
    const tileJ = params ? params.tile_j : undefined;

    if (tileI === undefined || tileJ === undefined) {
      ws.send(JSON.stringify({
        success: false,
        error: 'Missing tile_i or tile_j',
      }));
      return;
    }

    const tile = findTileInCache(tileI, tileJ);
    if (!tile) {
      ws.send(JSON.stringify({
        success: false,
        error: 'Tile not found or cache not ready',
        tile_i: Number(tileI),
        tile_j: Number(tileJ),
      }));
      return;
    }

    ws.send(JSON.stringify({
      success: true,
      tile,
    }));
    return;
  }

  if (command === 'get_birth_region_details') {
    const tileI = params ? params.tile_i : undefined;
    const tileJ = params ? params.tile_j : undefined;

    if (tileI === undefined || tileJ === undefined) {
      ws.send(JSON.stringify({
        success: false,
        error: 'Missing tile_i or tile_j',
      }));
      return;
    }

    const detail = findDetailsInCache(tileI, tileJ);
    
    ws.send(JSON.stringify({
      success: true,
      detail,
      tile_i: Number(tileI),
      tile_j: Number(tileJ),
    }));
    return;
  }

  ws.send(JSON.stringify({
    success: false,
    error: `Unknown command: ${command}`,
  }));
}

const wss = new WebSocket.Server({ port: wsPort, host: '0.0.0.0' });
wss.on('listening', () => {
  console.log(`[Map] WebSocket server listening on 0.0.0.0:${wsPort}`);
});

wss.on('connection', (ws) => {
  ws.send(JSON.stringify({
    success: true,
    message: 'Connected to map websocket',
    birth_region_id: birthRegionId,
  }));

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      handleWebSocketCommand(data, ws);
    } catch (error) {
      ws.send(JSON.stringify({
        success: false,
        error: 'Invalid JSON',
      }));
    }
  });
});

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
    sessionCookie = cookies.map(c => c.split(';')[0]).join('; ');

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
        console.log('Login successful, running initial get_tiles_by_birth_region...');
        bootstrapAndStartLoop();
      } else {
        console.error(`Login failed with status: ${resPost.statusCode}`);
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

async function bootstrapAndStartLoop() {
  try {
    const initialTiles = await callGetTilesByBirthRegion();
    latestTilesByBirthRegion = initialTiles;
    console.log('[Map] Initial get_tiles_by_birth_region completed.');
  } catch (error) {
    console.error(`[Map] Initial get_tiles_by_birth_region error: ${error.message}`);
  }

  // Fetch walkable finché non è disponibile (retry automatico, si ferma quando
  // arrivano i dati)
  fetchTileWalkableUntilAvailable();

  // Fetch coordinate finché non sono disponibili (retry automatico, si ferma
  // quando arrivano i dati)
  fetchTileCoordinatesUntilAvailable();

  setTimeout(() => {
    callGetBirthRegionDetails()
      .then((details) => {
        latestBirthRegionDetails = details;
        console.log('[Map] Initial get_birth_region_details completed.');
      })
      .catch((error) => {
        console.error(`[Map] Initial get_birth_region_details error: ${error.message}`);
      })
      .finally(() => {
        runBirthRegionDetailsCycle();
      });
  }, 2000);

  scheduleNextCycle();
}

function callGameApi(path, payload, label) {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      reject(new Error(`No session cookie, skipping ${label}`));
      return;
    }

    const postData = JSON.stringify(payload);
    const options = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path,
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
          const response = data ? JSON.parse(data) : {};
          console.log(`[Map] ${label} response:`, response);
          resolve(response);
        } catch (error) {
          reject(new Error(`${label} invalid JSON response: ${error.message}`));
        }
      });
    });

    req.on('error', (error) => {
      reject(new Error(`${label} request error: ${error.message}`));
    });

    req.write(postData);
    req.end();
  });
}

function callSetElementInMap() {
  return callGameApi(
    '/api/auth/game/set_element_in_map',
    { birth_region_id: birthRegionId },
    'set_element_in_map'
  );
}

function callGetTilesByBirthRegion() {
  return callGameApi(
    '/api/auth/game/get_tiles_by_birth_region',
    { birth_region_id: birthRegionId },
    'get_tiles_by_birth_region'
  );
}

function callGetBirthRegionDetails() {
  return callGameApi(
    '/api/auth/game/get_birth_region_details',
    { birth_region_id: birthRegionId },
    'get_birth_region_details'
  );
}

function callGetTileWalkable() {
  return callGameApi(
    '/api/auth/game/get_tile_walkable',
    { birth_region_id: birthRegionId },
    'get_tile_walkable'
  );
}

function callGetTileCoordinates() {
  return callGameApi(
    '/api/auth/game/get_tile_coordinates',
    { birth_region_id: birthRegionId },
    'get_tile_coordinates'
  );
}

async function runCycle() {
  const results = await Promise.allSettled([
    callSetElementInMap(),
    callGetTilesByBirthRegion(),
  ]);

  const getTilesResult = results[1];
  if (getTilesResult && getTilesResult.status === 'fulfilled') {
    latestTilesByBirthRegion = getTilesResult.value;
  }

  for (const result of results) {
    if (result.status === 'rejected') {
      console.error(`[Map] Cycle error: ${result.reason.message}`);
    }
  }

  scheduleNextCycle();
}

// Intervallo tra un tentativo e l'altro quando i dati non sono ancora disponibili
const DATA_RETRY_INTERVAL_MS = 2000;

// Ripete la fetch del walkable FINCHÉ l'API non restituisce i dati (array non
// null e non vuoto); appena arrivano il ciclo di retry si ferma.
function fetchTileWalkableUntilAvailable() {
  callGetTileWalkable()
    .then((response) => {
      const walkable = parseTileWalkableFromApiResponse(response);

      if (walkable && walkable.length > 0) {
        tileWalkable = walkable;
        console.log(`[Map] tileWalkable loaded: ${walkable.length}x${walkable[0]?.length || 0} (retry stopped)`);
        return;
      }

      console.log(`[Map] tileWalkable not available yet, retry in ${DATA_RETRY_INTERVAL_MS}ms...`);
      setTimeout(fetchTileWalkableUntilAvailable, DATA_RETRY_INTERVAL_MS);
    })
    .catch((error) => {
      console.error(`[Map] fetchTileWalkableUntilAvailable error: ${error.message}, retry in ${DATA_RETRY_INTERVAL_MS}ms...`);
      setTimeout(fetchTileWalkableUntilAvailable, DATA_RETRY_INTERVAL_MS);
    });
}

// Stesso comportamento per le coordinate: si ripete la chiamata finché non si
// hanno i dati, poi si smette.
function fetchTileCoordinatesUntilAvailable() {
  callGetTileCoordinates()
    .then((response) => {
      const coordinates = parseTileCoordinatesFromApiResponse(response);

      if (coordinates && coordinates.length > 0) {
        tileCoordinates = coordinates;
        console.log(`[Map] tileCoordinates loaded: ${coordinates.length}x${coordinates[0]?.length || 0} (retry stopped)`);
        return;
      }

      console.log(`[Map] tileCoordinates not available yet, retry in ${DATA_RETRY_INTERVAL_MS}ms...`);
      setTimeout(fetchTileCoordinatesUntilAvailable, DATA_RETRY_INTERVAL_MS);
    })
    .catch((error) => {
      console.error(`[Map] fetchTileCoordinatesUntilAvailable error: ${error.message}, retry in ${DATA_RETRY_INTERVAL_MS}ms...`);
      setTimeout(fetchTileCoordinatesUntilAvailable, DATA_RETRY_INTERVAL_MS);
    });
}

function runBirthRegionDetailsCycle() {
  callGetBirthRegionDetails()
    .then((details) => {
      latestBirthRegionDetails = details;
      console.log('[Map] Latest Birth Region Details');
    })
    .catch((error) => {
      console.error(`[Map] Birth Region Details error: ${error.message}`);
    })
    .finally(() => {
      setTimeout(runBirthRegionDetailsCycle, 2000);
    });
}

// Funzione per programmare il prossimo ciclo (ogni 10 secondi)
function scheduleNextCycle() {
  setTimeout(() => {
    runCycle().catch((error) => {
      console.error(`[Map] Unexpected cycle error: ${error.message}`);
      scheduleNextCycle();
    });
  }, 10000);
}

// Start flow
performLogin();
