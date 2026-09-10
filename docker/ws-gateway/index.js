const WebSocket = require('ws');
const { URL } = require('url');

const gatewayPort = parseInt(process.env.GATEWAY_PORT || '9001', 10);
// I container girano con --network host: il gateway inoltra a 127.0.0.1:<porta>
const targetHost = process.env.GATEWAY_TARGET_HOST || '127.0.0.1';
// Se l'upstream (es. il map container) non risponde entro questo tempo,
// chiudiamo il client invece di lasciarlo appeso in attesa per sempre.
const UPSTREAM_CONNECT_TIMEOUT_MS = parseInt(process.env.GATEWAY_UPSTREAM_TIMEOUT_MS || '8000', 10);

if (!Number.isInteger(gatewayPort) || gatewayPort <= 0) {
  throw new Error(`Invalid GATEWAY_PORT: ${process.env.GATEWAY_PORT}`);
}

const server = new WebSocket.Server({
  port: gatewayPort,
  host: '0.0.0.0',
  perMessageDeflate: false,
});

server.on('listening', () => {
  console.log(`[Gateway] WebSocket gateway listening on 0.0.0.0:${gatewayPort}`);
  console.log(`[Gateway] Routing upstream connections to ${targetHost}`);
});

server.on('connection', (client, req) => {
  const requestUrl = new URL(req.url || '/', 'http://localhost');
  const rawTargetPort = requestUrl.searchParams.get('port') || requestUrl.searchParams.get('target') || requestUrl.pathname.replace(/^\/+/, '');
  const targetPort = parseInt(rawTargetPort || '', 10);

  if (!Number.isInteger(targetPort) || targetPort <= 0) {
    client.close(1008, 'Missing or invalid target port');
    return;
  }

  const upstreamUrl = `ws://${targetHost}:${targetPort}`;
  console.log(`[Gateway] Client connected, proxying to ${upstreamUrl}`);

  const upstream = new WebSocket(upstreamUrl, {
    perMessageDeflate: false,
  });

  const pendingMessages = [];
  let upstreamReady = false;
  let clientClosed = false;

  // Timeout sull'apertura dell'upstream: se non si apre entro il limite
  // (es. map container non raggiungibile su questa porta), chiudiamo il
  // client così chi ha fatto la richiesta può riprovare o usare un fallback.
  const upstreamConnectTimer = setTimeout(() => {
    if (!upstreamReady && !clientClosed) {
      console.error(`[Gateway] Upstream ${upstreamUrl} did not open within ${UPSTREAM_CONNECT_TIMEOUT_MS}ms; closing client`);
      try {
        upstream.close();
      } catch (error) {
        // ignore
      }
      if (client.readyState === WebSocket.OPEN) {
        client.close(1011, 'Upstream connection timeout');
      }
    }
  }, UPSTREAM_CONNECT_TIMEOUT_MS);

  const clearUpstreamConnectTimer = () => clearTimeout(upstreamConnectTimer);

  const flushPendingMessages = () => {
    while (pendingMessages.length > 0 && upstream.readyState === WebSocket.OPEN) {
      const message = pendingMessages.shift();
      try {
        upstream.send(message);
      } catch (error) {
        console.error('[Gateway] Failed to flush pending message:', error.message);
        break;
      }
    }
  };

  client.on('message', (message, isBinary) => {
    if (upstreamReady && upstream.readyState === WebSocket.OPEN) {
      upstream.send(message, { binary: isBinary });
      return;
    }

    pendingMessages.push(message);
  });

  client.on('close', () => {
    clientClosed = true;
    clearUpstreamConnectTimer();
    try {
      upstream.close();
    } catch (error) {
      console.error('[Gateway] Error closing upstream on client close:', error.message);
    }
  });

  client.on('error', (error) => {
    console.error('[Gateway] Client error:', error.message);
  });

  upstream.on('open', () => {
    clearUpstreamConnectTimer();
    upstreamReady = true;
    flushPendingMessages();
  });

  upstream.on('message', (data, isBinary) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(data, { binary: isBinary });
    }
  });

  upstream.on('close', (code, reason) => {
    clearUpstreamConnectTimer();
    if (!clientClosed && client.readyState === WebSocket.OPEN) {
      client.close(code || 1000, reason ? reason.toString() : 'Upstream closed');
    }
  });

  upstream.on('error', (error) => {
    clearUpstreamConnectTimer();
    console.error('[Gateway] Upstream error:', error.message);
    if (client.readyState === WebSocket.OPEN) {
      client.close(1011, error.message || 'Upstream connection failed');
    }
  });
});

server.on('error', (error) => {
  console.error('[Gateway] Server error:', error.message);
});
