const fs = require('fs');
const http = require('http');
const path = require('path');

const backendUrl = process.env.BACKEND_URL;
const apiUserEmail = process.env.API_USER_EMAIL;
const apiUserPassword = process.env.API_USER_PASSWORD;
const playerId = process.env.PLAYER_ID;

console.log('Cache Sync service started.');
console.log(`Using Credentials: ${apiUserEmail} / ${apiUserPassword ? '******' : 'MISSING'}`);
console.log(`Player ID: ${playerId || 'MISSING'}`);

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

  const parsedUrl = new URL(backendUrl);
  const optionsGet = {
    hostname: parsedUrl.hostname,
    port: parsedUrl.port || 80,
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
      hostname: parsedUrl.hostname,
      port: parsedUrl.port || 80,
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
        console.log('Login successful, starting sync loop...');
        scheduleNextSync();
      } else {
        console.error(`Login failed with status: ${resPost.statusCode}`);
        resPost.on('data', (d) => console.error(d.toString()));
        setTimeout(performLogin, 5000);
      }
    });

    reqPost.on('error', (e) => {
      console.error(`Login POST error: ${e.message}`);
      setTimeout(performLogin, 5000);
    });
    reqPost.write(postData);
    reqPost.end();
  });

  reqGet.on('error', (e) => {
    console.error(`Initial GET error: ${e.message}`);
    setTimeout(performLogin, 5000);
  });
  reqGet.end();
}

function callSyncEndpoint() {
  if (!sessionCookie) {
    console.log('No session cookie, re-attempting login...');
    performLogin();
    return;
  }

  const syncPath = '/api/auth/game/sync_object_cache';
  const postData = JSON.stringify({
    player_id: playerId,
  });

  const parsedUrl = new URL(backendUrl);
  const options = {
    hostname: parsedUrl.hostname,
    port: parsedUrl.port || 80,
    path: syncPath,
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
          console.log(`[CacheSync player=${playerId}] sync completed.`);
        } else {
          console.error(`[CacheSync player=${playerId}] API error:`, response);
        }
      } catch (error) {
        console.error(`[CacheSync player=${playerId}] Parse error: ${error.message}`);
      } finally {
        scheduleNextSync();
      }
    });
  });

  req.on('error', (error) => {
    console.error(`[CacheSync player=${playerId}] Error calling sync endpoint: ${error.message}`);
    scheduleNextSync();
  });

  req.write(postData);
  req.end();
}

const SYNC_INTERVAL = 5;

function scheduleNextSync() {
  setTimeout(() => {
    callSyncEndpoint();
  }, SYNC_INTERVAL * 1000);
}

performLogin();
