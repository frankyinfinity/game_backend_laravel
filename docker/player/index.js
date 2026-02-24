const http = require('http');
const WebSocket = require('ws');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const playerId = process.env.PLAYER_ID;
const wsPort = process.env.WS_PORT || 8080;

console.log('Player service started.');
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Player ID: ${playerId || 'MISSING'}`);

let sessionCookie = null;
let xsrfToken = null;
let playerValuesCache = null;
let playerValuesUpdatedAt = null;

function parseCookies(response) {
  const list = {};
  const rc = response.headers['set-cookie'];

  rc && rc.forEach((cookie) => {
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
    sessionCookie = cookies.map((c) => c.split(';')[0]).join('; ');
    const parsed = parseCookies(response);
    if (parsed['XSRF-TOKEN']) {
      xsrfToken = parsed['XSRF-TOKEN'];
    }
  }
}

function performLogin() {
  console.log('Attempting login...');

  const optionsGet = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
    path: '/login',
    method: 'GET',
  };

  const reqGet = http.request(optionsGet, (res) => {
    updateSession(res);

    const postData = new URLSearchParams({
      email: apiUserEmail,
      password: apiUserPassword,
    }).toString();

    const optionsPost = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path: '/login',
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(postData),
        Cookie: sessionCookie,
        'X-XSRF-TOKEN': xsrfToken,
      },
    };

    const reqPost = http.request(optionsPost, (resPost) => {
      updateSession(resPost);

      if (resPost.statusCode === 302 || resPost.statusCode === 200 || resPost.statusCode === 204) {
        console.log('Login successful, starting player_values/get loop...');
        scheduleNextCycle();
      } else {
        console.error(`Login failed with status: ${resPost.statusCode}`);
        resPost.on('data', (d) => console.error(d.toString()));
      }
    });

    reqPost.on('error', (e) => console.error(`Login POST error: ${e.message}`));
    reqPost.write(postData);
    reqPost.end();
  });

  reqGet.on('error', (e) => console.error(`Initial GET error: ${e.message}`));
  reqGet.end();
}

function callGetPlayerValues() {
  if (!sessionCookie) {
    console.log('No session cookie, skipping game/player_values/get...');
    scheduleNextCycle();
    return;
  }

  const path = '/api/auth/game/player_values/get';
  const postData = JSON.stringify({ player_id: playerId });

  const options = {
    hostname: new URL(backendUrl).hostname,
    port: new URL(backendUrl).port || 80,
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

  const req = http.request(options, (res) => {
    let data = '';

    res.on('data', (chunk) => {
      data += chunk;
    });

    res.on('end', () => {
      try {
        const response = JSON.parse(data);
        if (response && response.success) {
          playerValuesCache = response;
          playerValuesUpdatedAt = new Date().toISOString();
          console.log(`[Player ${playerId}] Values refreshed at ${playerValuesUpdatedAt}`);
        } else {
          console.error(`[Player ${playerId}] API error:`, response);
        }
      } catch (error) {
        console.error(`[Player ${playerId}] Parse error: ${error.message}`);
      } finally {
        scheduleNextCycle();
      }
    });
  });

  req.on('error', (error) => {
    console.error(`[Player ${playerId}] Error calling game/player_values/get: ${error.message}`);
    scheduleNextCycle();
  });

  req.write(postData);
  req.end();
}

function scheduleNextCycle() {
  setTimeout(() => {
    callGetPlayerValues();
  }, 10000);
}

const wss = new WebSocket.Server({ port: wsPort });
console.log(`WebSocket server listening on port ${wsPort}`);

wss.on('connection', (ws) => {
  console.log(`[WebSocket] Client connected for player ${playerId}`);

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      handleWebSocketCommand(data, ws);
    } catch (error) {
      ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
    }
  });

  ws.on('close', () => {
    console.log('[WebSocket] Client disconnected');
  });

  ws.on('error', (error) => {
    console.error(`[WebSocket] Error: ${error.message}`);
  });

  ws.send(JSON.stringify({
    success: true,
    message: 'Connected to player service',
    player_id: playerId,
    updated_at: playerValuesUpdatedAt,
  }));
});

function handleWebSocketCommand(data, ws) {
  const command = data && data.command ? data.command : null;

  switch (command) {
    case 'get_player_values':
      ws.send(JSON.stringify({
        success: true,
        player_id: playerId,
        updated_at: playerValuesUpdatedAt,
        data: playerValuesCache,
      }));
      break;

    default:
      ws.send(JSON.stringify({
        success: false,
        error: `Unknown command: ${command}`,
      }));
      break;
  }
}

performLogin();

