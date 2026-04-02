const http = require('http');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const birthRegionId = process.env.BIRTH_REGION_ID;

console.log(`ChimicalElement service started.`);
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Birth Region ID: ${birthRegionId || 'MISSING'}`);

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
        scheduleNextCycle();
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

function callCalculateChimicalElement() {
  return new Promise((resolve, reject) => {
    if (!sessionCookie) {
      reject(new Error('No session cookie, skipping calculateChimicalElement'));
      return;
    }

    const payload = JSON.stringify({ birth_region_id: birthRegionId });
    const options = {
      hostname: new URL(backendUrl).hostname,
      port: new URL(backendUrl).port || 80,
      path: '/api/auth/game/calculate_chimical_element',
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
          console.log('[ChimicalElement] calculate_chimical_element response:', response);
          resolve(response);
        } catch (error) {
          reject(new Error(`calculateChimicalElement invalid JSON: ${error.message}`));
        }
      });
    });

    req.on('error', (error) => {
      reject(new Error(`calculateChimicalElement request error: ${error.message}`));
    });

    req.write(payload);
    req.end();
  });
}

async function runCycle() {
  try {
    await callCalculateChimicalElement();
  } catch (error) {
    console.error(`[ChimicalElement] Cycle error: ${error.message}`);
  }

  scheduleNextCycle();
}

function scheduleNextCycle() {
  setTimeout(() => {
    runCycle().catch((error) => {
      console.error(`[ChimicalElement] Unexpected cycle error: ${error.message}`);
      scheduleNextCycle();
    });
  }, 10000);
}

performLogin();
