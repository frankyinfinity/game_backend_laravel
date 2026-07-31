const http = require('http');
const WebSocket = require('ws');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const playerId = process.env.PLAYER_ID;
const wsPort = Number(process.env.WS_PORT || 0);
const cycleInterval = process.env.CYCLE_INTERVAL || 10000; // Default 10 seconds (same as chimical-element)

console.log(`Score service started.`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Player ID: ${playerId || 'MISSING'}`);
console.log(`Cycle Interval: ${cycleInterval}ms`);

let sessionCookie = null;
let xsrfToken = null;
let responseJson = null;
let wss = null;

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
        console.log('Login successful, starting cycle...');
        runCycle();
        startWebSocketServer();
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

function callCalculateScore() {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      reject(new Error('No session cookie, skipping calculateScore'));
      return;
    }

    const payload = JSON.stringify({ player_id: playerId });
    const options = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path: '/api/auth/game/calculate_score',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
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
          const response = data ? JSON.parse(data) : {};
          console.log('[Score] calculate_score response:', response);
          resolve(response);
        } catch (error) {
          reject(new Error(`calculateScore invalid JSON: ${error.message}`));
        }
      });
    });

    req.on('error', (error) => {
      reject(new Error(`calculateScore request error: ${error.message}`));
    });

    req.write(payload);
    req.end();
  });
}

async function runCycle() {
  try {
    const response = await callCalculateScore();
    responseJson = response;
    console.log('[Score] Response saved to responseJson variable');
  } catch (error) {
    console.error(`[Score] Cycle error: ${error.message}`);
    responseJson = { error: error.message };
  }

  scheduleNextCycle();
}

function scheduleNextCycle() {
  setTimeout(() => {
    runCycle().catch((error) => {
      console.error(`[Score] Unexpected cycle error: ${error.message}`);
      scheduleNextCycle();
    });
  }, parseInt(cycleInterval));
}

function startWebSocketServer() {
  if (wsPort > 0) {
    wss = new WebSocket.Server({ port: wsPort });
    console.log(`[Score] WebSocket server listening on port ${wsPort}`);

    wss.on('connection', (ws) => {
      console.log(`[Score] WebSocket client connected`);

      ws.on('message', (message) => {
        try {
          const data = JSON.parse(message);
          console.log(`[Score] Received command:`, data);

          const command = data && data.command ? data.command : null;

          switch (command) {
            case 'get':
              ws.send(JSON.stringify({
                success: true,
                command: 'get',
                data: responseJson,
              }));
              break;
            default:
              ws.send(JSON.stringify({
                success: false,
                error: `Unknown command: ${command}`,
              }));
              break;
          }
        } catch (error) {
          console.error(`[Score] Error parsing message:`, error.message);
          ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
        }
      });

      ws.on('close', () => {
        console.log(`[Score] WebSocket client disconnected`);
      });

      ws.on('error', (error) => {
        console.error(`[Score] WebSocket error:`, error.message);
      });

      ws.send(JSON.stringify({
        success: true,
        message: 'Connected to score service',
        player_id: playerId,
      }));
    });
  } else {
    console.log('WS_PORT missing or invalid, websocket server disabled for score.');
  }
}

performLogin();
