// evolution/index.js
// Servizio Docker legato ad un player (PLAYER_ID).
// Espone un WebSocket che accetta il comando 'save' (come per alert).
// Quando riceve il comando 'save' con un JSON, il container fa partire
// la chiamata API evolution/save inoltrando il JSON ricevuto dal websocket.

const http = require('http');
const WebSocket = require('ws');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const playerId = process.env.PLAYER_ID;
const wsPort = Number(process.env.WS_PORT || 0);

console.log('Evolution service started.');
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Player ID: ${playerId || 'MISSING'}`);
console.log(`WS_PORT: ${wsPort || 'MISSING'}`);

let sessionCookie = null;
let xsrfToken = null;
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
  console.log('[Evolution] Attempting login...');

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
        'Cookie': sessionCookie,
        'X-XSRF-TOKEN': xsrfToken,
      },
    };

    const reqPost = http.request(optionsPost, (resPost) => {
      updateSession(resPost);

      if (resPost.statusCode === 302 || resPost.statusCode === 200 || resPost.statusCode === 204) {
        console.log('[Evolution] Login successful.');
      } else {
        console.error(`[Evolution] Login failed with status: ${resPost.statusCode}`);
        resPost.on('data', (d) => console.error(d.toString()));
      }
    });

    reqPost.on('error', (e) => console.error(`[Evolution] Login POST error: ${e.message}`));
    reqPost.write(postData);
    reqPost.end();
  });

  reqGet.on('error', (e) => console.error(`[Evolution] Initial GET error: ${e.message}`));
  reqGet.end();
}

// Chiama l'API evolution/save inoltrando il JSON passato dal websocket.
function callEvolutionSave(payload) {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      reject(new Error('No session cookie, skipping evolution/save'));
      return;
    }

    const postData = JSON.stringify(payload);
    const options = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path: '/api/auth/game/evolution/save',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(postData),
        'Accept': 'application/json',
        'Cookie': sessionCookie,
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
          const response = data ? JSON.parse(data) : {};
          console.log(`[Evolution] evolution/save response:`, response);
          resolve(response);
        } catch (error) {
          reject(new Error(`evolution/save invalid JSON: ${error.message}`));
        }
      });
    });

    req.on('error', (error) => {
      reject(new Error(`evolution/save request error: ${error.message}`));
    });

    req.write(postData);
    req.end();
  });
}

function startWebSocketServer() {
  if (wsPort > 0) {
    wss = new WebSocket.Server({ port: wsPort });
    console.log(`[Evolution] WebSocket server listening on port ${wsPort}`);

    wss.on('connection', (ws) => {
      console.log(`[Evolution] WebSocket client connected (player_id=${playerId})`);

      ws.on('message', (message) => {
        try {
          const data = JSON.parse(message);
          console.log(`[Evolution] Received message:`, data);

          const command = data && data.command ? data.command : null;

          switch (command) {
            case 'save': {
              // Il JSON passato dal websocket con il comando 'save' viene
              // inoltrato alla chiamata API evolution/save.
              const payload = { ...(data.data || data) };
              // Il campo 'command' è solo il protocollo websocket, non va inoltrato.
              delete payload.command;
              // Il container è legato al player tramite PLAYER_ID.
              if (!payload.player_id && playerId) {
                payload.player_id = playerId;
              }

              console.log(`[Evolution] Calling API evolution/save with JSON:`, JSON.stringify(payload));
              callEvolutionSave(payload)
                .then((response) => {
                  ws.send(JSON.stringify({ success: true, command: 'save', response }));
                })
                .catch((error) => {
                  console.error(`[Evolution] evolution/save error: ${error.message}`);
                  ws.send(JSON.stringify({ success: false, command: 'save', error: error.message }));
                });
              break;
            }
            default:
              ws.send(JSON.stringify({
                success: false,
                error: `Unknown command: ${command}`,
              }));
              break;
          }
        } catch (error) {
          console.error(`[Evolution] Error parsing message:`, error.message);
          ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
        }
      });

      ws.on('close', () => {
        console.log(`[Evolution] WebSocket client disconnected`);
      });

      ws.on('error', (error) => {
        console.error(`[Evolution] WebSocket error:`, error.message);
      });

      ws.send(JSON.stringify({
        success: true,
        message: 'Connected to evolution service',
        player_id: playerId,
      }));
    });
  } else {
    console.log('WS_PORT missing or invalid, websocket server disabled for evolution.');
  }
}

startWebSocketServer();
performLogin();