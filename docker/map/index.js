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

function handleWebSocketCommand(data, ws) {
  const { command, params } = data || {};

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

const wss = new WebSocket.Server({ port: wsPort });
console.log(`WebSocket server listening on port ${wsPort}`);

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
