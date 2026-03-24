const WebSocket = require('ws');
const { URL } = require('url');

const gatewayPort = parseInt(process.env.GATEWAY_PORT || '9001', 10);
const targetHost = process.env.GATEWAY_TARGET_HOST || '127.0.0.1';

if (!Number.isInteger(gatewayPort) || gatewayPort <= 0) {
  throw new Error(`Invalid GATEWAY_PORT: ${process.env.GATEWAY_PORT}`);
}

const server = new WebSocket.Server({
  port: gatewayPort,
  perMessageDeflate: false,
});

console.log(`WebSocket gateway listening on port ${gatewayPort}`);
console.log(`Routing upstream connections to ${targetHost}`);

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
    upstreamReady = true;
    flushPendingMessages();
  });

  upstream.on('message', (data, isBinary) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(data, { binary: isBinary });
    }
  });

  upstream.on('close', (code, reason) => {
    if (!clientClosed && client.readyState === WebSocket.OPEN) {
      client.close(code || 1000, reason ? reason.toString() : 'Upstream closed');
    }
  });

  upstream.on('error', (error) => {
    console.error('[Gateway] Upstream error:', error.message);
    if (client.readyState === WebSocket.OPEN) {
      client.close(1011, error.message || 'Upstream connection failed');
    }
  });
});

server.on('error', (error) => {
  console.error('[Gateway] Server error:', error.message);
});
