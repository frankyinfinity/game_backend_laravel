const fs = require('fs');
const http = require('http');
const path = require('path');
const WebSocket = require('ws');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const elementHasPositionId = process.env.ELEMENT_HAS_POSITION_ID;
const wsPort = Number(process.env.WS_PORT || 0);

console.log('Element service started.');
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`ElementHasPosition ID: ${elementHasPositionId || 'MISSING'}`);

let sessionCookie = null;
let xsrfToken = null;

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
        console.log('Login successful, starting game/brain loop...');
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

function callGameBrain() {
  if (!sessionCookie) {
    console.log('No session cookie, skipping game/brain...');
    scheduleNextCycle();
    return;
  }

  const path = '/api/auth/game/brain';
  const postData = JSON.stringify({
    element_has_position_id: elementHasPositionId,
  });

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
        const response = data ? JSON.parse(data) : {};
        if (response.success) {
          console.log(`[Element ${elementHasPositionId}] brain refreshed.`);
        } else {
          console.error(`[Element ${elementHasPositionId}] API error:`, response);
        }
      } catch (error) {
        console.error(`[Element ${elementHasPositionId}] Parse error: ${error.message}`);
      } finally {
        scheduleNextCycle();
      }
    });
  });

  req.on('error', (error) => {
    console.error(`[Element ${elementHasPositionId}] Error calling game/brain: ${error.message}`);
    scheduleNextCycle();
  });

  req.write(postData);
  req.end();
}

let SECOND_TIMEOUT = 20;
function scheduleNextCycle() {
  setTimeout(() => {
    callGameBrain();
  }, SECOND_TIMEOUT * 1000);
}

function fetchNeuronBorderUid(neuronId) {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      return reject(new Error('No session cookie'));
    }

    const path = `/neurons/${neuronId}/border-uid`;
    const options = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path,
      method: 'GET',
      headers: {
        Accept: 'application/json',
        Cookie: sessionCookie,
        'X-XSRF-TOKEN': xsrfToken,
      },
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => {
        try {
          const response = data ? JSON.parse(data) : {};
          if (response.success) {
            resolve(response.border_uid);
          } else {
            console.error(`[Element ${elementHasPositionId}] fetchNeuronBorderUid API error:`, response);
            resolve(null);
          }
        } catch (e) {
          console.error(`[Element ${elementHasPositionId}] fetchNeuronBorderUid parse error:`, e.message);
          resolve(null);
        }
      });
    });

    req.on('error', (e) => {
      console.error(`[Element ${elementHasPositionId}] fetchNeuronBorderUid request error:`, e.message);
      resolve(null);
    });
    req.end();
  });
}

/**
 * Invia i dati del neurone al backend per il broadcast via Pusher
 */
function broadcastNeuronUpdate(borderUid, fileData, playerId) {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      return reject(new Error('No session cookie'));
    }

    const path = '/neurons/broadcast-update';
    const postData = JSON.stringify({
      border_uid: borderUid,
      file_data: fileData,
      player_id: playerId,
    });

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
      res.on('data', (chunk) => { data += chunk; });
      res.on('end', () => {
        try {
          const response = data ? JSON.parse(data) : {};
          if (response.success) {
            console.log(`[Element ${elementHasPositionId}] Neuron update broadcasted: border_uid=${borderUid}`);
            resolve(true);
          } else {
            console.error(`[Element ${elementHasPositionId}] broadcastNeuronUpdate API error:`, response);
            resolve(false);
          }
        } catch (e) {
          console.error(`[Element ${elementHasPositionId}] broadcastNeuronUpdate parse error:`, e.message);
          resolve(false);
        }
      });
    });

    req.on('error', (e) => {
      console.error(`[Element ${elementHasPositionId}] broadcastNeuronUpdate request error:`, e.message);
      resolve(false);
    });
    req.write(postData);
    req.end();
  });
}

function safeVolumePath(relativePath) {
  const normalized = path.normalize(String(relativePath || '')).replace(/^([.][.][/\\])+/, '');
  const absolutePath = path.resolve('/data', normalized);
  if (!absolutePath.startsWith('/data' + path.sep) && absolutePath !== '/data') {
    throw new Error('Invalid volume path');
  }

  return absolutePath;
}

/**
 * Aggiorna lo stato di un neurone leggendo il file dal volume (storicamente logVolumeFile)
 */
async function updateNeuron(params, ws) {
  const relativePath = params ? params.path : null;
  const neuronId = params ? params.neuron_id : null;
  const playerId = params ? params.player_id : null;
  const color = params ? params.color : null;

  if (!relativePath) {
    console.error(`[Element ${elementHasPositionId}] Missing path for update_neuron`);
    ws.send(JSON.stringify({
      success: false,
      command: 'update_neuron',
      error: 'Missing path',
    }));
    return;
  }

  try {
    const absolutePath = safeVolumePath(relativePath);
    if (!fs.existsSync(absolutePath)) {
      ws.send(JSON.stringify({
        success: false,
        error: `File not found: ${relativePath}`,
      }));
      return;
    }

    const content = fs.readFileSync(absolutePath, 'utf8');
    console.log(`[Element ${elementHasPositionId}] update_neuron: ${neuronId} via file: ${relativePath}`);
    //console.log(content);

    const borderUid = neuronId ? await fetchNeuronBorderUid(neuronId) : null;
    console.log(`[Element ${elementHasPositionId}] border_uid for node: ${borderUid}`);

    let fileData = null;
    try {
      const parsed = JSON.parse(content);
      // Se borderUid è definito, cerchiamo quel nodo specifico nel contenuto
      if (borderUid && parsed && typeof parsed === 'object') {
        fileData = parsed[borderUid] !== undefined ? parsed[borderUid] : parsed;
      } else {
        fileData = parsed;
      }
    } catch (e) {
      fileData = content;
    }
    console.log(`[Element ${elementHasPositionId}] neuron data (node ${borderUid}):`, fileData);

    //Gestione Colore
    if (color) {
      console.log('Colore: ', color);
    }

    // Broadcast the neuron update to the frontend via Pusher
    if (borderUid && playerId) {
      await broadcastNeuronUpdate(borderUid, fileData, playerId);
    }

    ws.send(JSON.stringify({
      success: true,
      command: 'update_neuron',
      neuron_id: neuronId,
      border_uid: borderUid,
      data: fileData,
      path: relativePath,
      bytes: Buffer.byteLength(content, 'utf8'),
      color: color,
    }));

  } catch (error) {
    console.error(`[Element ${elementHasPositionId}] Error logging volume file ${relativePath}: ${error.message}`);
    ws.send(JSON.stringify({
      success: false,
      error: error.message,
    }));
  }
}

if (wsPort > 0) {
  const wss = new WebSocket.Server({ port: wsPort });
  console.log(`WebSocket server listening on port ${wsPort}`);

  wss.on('connection', (ws) => {
    console.log(`[WebSocket] Client connected to element ${elementHasPositionId}`);

    ws.on('message', (message) => {
      try {
        const data = JSON.parse(message);
        console.log(`[WebSocket] Received command:`, data);

        const command = data && data.command ? data.command : null;
        const params = data && data.params ? data.params : {};

        switch (command) {
          case 'update_neuron':
            updateNeuron(data.params, ws);
            break;
          default:
            ws.send(JSON.stringify({
              success: false,
              error: `Unknown command: ${command}`,
            }));
            break;
        }
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

    ws.send(JSON.stringify({
      success: true,
      message: 'Connected to element',
      element_has_position_id: elementHasPositionId,
    }));
  });
} else {
  console.log('WS_PORT missing or invalid, websocket server disabled for element.');
}

performLogin();
