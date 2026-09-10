// Integration simulation for entity.getWalkableFromMap().
//
// Spins up a fake "map" WebSocket server and a fake "ws-gateway" (a minimal
// port-proxy that mirrors backend/docker/ws-gateway/index.js behavior), then
// loads the real entity module and exercises getWalkableFromMap() against it.
//
// Run:  node test-walkable-sim.js
//
// The `ws` package must be resolvable from the entity folder (npm install).

const WebSocket = require('ws');

// ---------- test configuration ----------
const GATEWAY_PORT = 19001;
const MAP_PORT = 19002;

// Environment needed BEFORE the entity module is loaded
process.env.WS_GATEWAY_HOST = '127.0.0.1';
process.env.WS_GATEWAY_PORT = String(GATEWAY_PORT);
process.env.MAP_WS_PORT = String(MAP_PORT);
process.env.MAP_DIRECT_HOST = '127.0.0.1';
process.env.MAP_DIRECT_PORT = String(MAP_PORT);
process.env.WS_PORT = '0'; // ephemeral, unused in the test
process.env.ENTITY_UID = 'test-entity';
process.env.PLAYER_ID = '1';
process.env.WALKABLE_RESPONSE_TIMEOUT_MS = '800';
process.env.WALKABLE_RETRY_DELAY_MS = '200';

const entity = require('./index.js');

// ---------- tiny assertion helpers ----------
let failures = 0;
function check(condition, label, detail) {
  if (condition) {
    console.log(`  ✅ ${label}`);
  } else {
    failures++;
    console.log(`  ❌ ${label}${detail ? ' — ' + detail : ''}`);
  }
}

// ---------- fake map server ----------
// mode can be:
//   'ready'      -> always replies with the walkable array
//   'delayed'    -> replies null (not ready) on the first request, then the array
//   'silent'     -> only sends the greeting, never the real reply
function startMapServer(port, mode) {
  const hits = { requests: 0 };
  const server = new WebSocket.Server({ port, host: '127.0.0.1' });
  server.on('connection', (ws) => {
    // This is the map's handshake greeting, sent BEFORE any request is answered.
    ws.send(JSON.stringify({ success: true, message: 'Connected to map websocket', birth_region_id: 7 }));

    ws.on('message', (message) => {
      let data;
      try {
        data = JSON.parse(message.toString());
      } catch (e) {
        return;
      }
      if (data.command !== 'get_tile_walkable') return;
      hits.requests++;

      if (mode === 'ready') {
        ws.send(JSON.stringify({
          success: true,
          request_id: data.request_id,
          command: 'get_tile_walkable',
          tile_walkable: [[true, true, false], [true, true, true], [false, true, true]],
          dimensions: { rows: 3, cols: 3 },
        }));
      } else if (mode === 'delayed') {
        if (hits.requests === 1) {
          ws.send(JSON.stringify({
            success: true,
            request_id: data.request_id,
            command: 'get_tile_walkable',
            tile_walkable: null,
            dimensions: null,
          }));
        } else {
          ws.send(JSON.stringify({
            success: true,
            request_id: data.request_id,
            command: 'get_tile_walkable',
            tile_walkable: [[true], [true]],
            dimensions: { rows: 2, cols: 1 },
          }));
        }
      }
      // mode 'silent': never answer
    });
  });
  return { server, hits };
}

// ---------- fake ws-gateway (port proxy, mirrors docker/ws-gateway) ----------
function startGatewayServer(port) {
  const server = new WebSocket.Server({ port, host: '127.0.0.1' });
  server.on('connection', (client, req) => {
    const u = new URL(req.url || '/', 'http://localhost');
    const rawPort = u.searchParams.get('port') || u.pathname.replace(/^\/+/, '');
    const targetPort = parseInt(rawPort || '', 10);
    if (!Number.isInteger(targetPort) || targetPort <= 0) {
      client.close(1008, 'Missing or invalid target port');
      return;
    }
    const upstreamUrl = `ws://127.0.0.1:${targetPort}`;
    const upstream = new WebSocket(upstreamUrl, { perMessageDeflate: false });
    const pending = [];
    let upReady = false;

    client.on('message', (message, isBinary) => {
      if (upReady && upstream.readyState === WebSocket.OPEN) {
        upstream.send(message, { binary: isBinary });
      } else {
        pending.push(message);
      }
    });
    client.on('close', () => {
      try { upstream.close(); } catch (e) { /* ignore */ }
    });
    upstream.on('open', () => {
      upReady = true;
      while (pending.length > 0 && upstream.readyState === WebSocket.OPEN) {
        upstream.send(pending.shift());
      }
    });
    upstream.on('message', (data, isBinary) => {
      if (client.readyState === WebSocket.OPEN) {
        client.send(data, { binary: isBinary });
      }
    });
    upstream.on('close', (code, reason) => {
      if (client.readyState === WebSocket.OPEN) {
        client.close(code || 1000, reason ? reason.toString() : 'Upstream closed');
      }
    });
    upstream.on('error', (error) => {
      if (client.readyState === WebSocket.OPEN) {
        client.close(1011, error.message || 'Upstream connection failed');
      }
    });
  });
  return server;
}

// ---------- harness ----------
function runScenario(name, mapMode, expectSuccess) {
  return new Promise((resolve) => {
    const start = Date.now();
    const map = startMapServer(MAP_PORT, mapMode);
    const mapHits = () => map.hits.requests;

    const result = { called: 0, value: null };
    entity.getWalkableFromMap((res) => {
      result.called++;
      result.value = res;
    });

    // Give the scenario a hard deadline
    const deadline = setTimeout(() => {
      console.log(`\nScenario: ${name}`);
      check(false, 'callback fired within deadline', 'timed out');
      finish();
    }, 12000);

    const finish = () => {
      clearTimeout(deadline);
      try { map.server.close(); } catch (e) { /* ignore */ }
      resolve({ mapHits: mapHits() });
    };

    const poll = () => {
      if (result.called > 0) {
        check(result.called === 1, 'callback fired exactly once', `called ${result.called} times`);
        check(expectSuccess === !!result.value.success, `success === ${expectSuccess}`, JSON.stringify(result.value).slice(0, 300));
        if (expectSuccess) {
          check(Array.isArray(result.value.tile_walkable), 'tile_walkable is an array',
            Array.isArray(result.value.tile_walkable) ? `dims ${result.value.tile_walkable.length}x${result.value.tile_walkable[0]?.length}` : JSON.stringify(result.value));
        }
        console.log(`  ⌛ elapsed ~${Date.now() - start}ms`);
        finish();
      } else {
        setTimeout(poll, 50);
      }
    };
    poll();
  });
}

async function main() {
  const gateway = startGatewayServer(GATEWAY_PORT);
  const rest = (ms) => new Promise((r) => setTimeout(r, ms));

  console.log('== Scenario A: map ready → entity must succeed (greeting must be ignored) ==');
  const a = await runScenario('A', 'ready', true);
  check(a.mapHits === 1, `map received exactly 1 get_tile_walkable (got ${a.mapHits})`);
  await rest(300);

  console.log('\n== Scenario B: map not ready on first reply → entity must retry and succeed ==');
  const b = await runScenario('B', 'delayed', true);
  check(b.mapHits === 2, `map received 2 get_tile_walkable requests (got ${b.mapHits})`);
  await rest(300);

  console.log('\n== Scenario C: map only greets, never replies → entity must NOT hang (fails with feedback) ==');
  const c = await runScenario('C', 'silent', false);

  gateway.close();

  console.log('\n----------------------------------------');
  if (failures > 0) {
    console.log(`FAILED: ${failures} check(s) failed`);
    process.exit(1);
  } else {
    console.log('ALL CHECKS PASSED ✅');
    process.exit(0);
  }
}

main().catch((err) => {
  console.error('Test crashed:', err);
  process.exit(2);
});