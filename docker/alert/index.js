const WebSocket = require('ws');

const wsPort = Number(process.env.WS_PORT || 0);

console.log(`Alert service started.`);
console.log(`WS_PORT: ${wsPort || 'MISSING'}`);

let wss = null;

function startWebSocketServer() {
  if (wsPort > 0) {
    wss = new WebSocket.Server({ port: wsPort });
    console.log(`[Alert] WebSocket server listening on port ${wsPort}`);

    wss.on('connection', (ws) => {
      console.log(`[Alert] WebSocket client connected`);

      ws.on('message', (message) => {
        try {
          const data = JSON.parse(message);
          console.log(`[Alert] Received message:`, data);

          const command = data && data.command ? data.command : null;

          switch (command) {
            case 'alert':
              // Broadcast alert to all connected clients
              const alertPayload = {
                type: 'alert',
                title: data.title || '',
                body: data.body || '',
                alert_type: data.alert_type || data.type || 'info',
              };
              console.log(`[Alert] Broadcasting alert:`, alertPayload);
              wss.clients.forEach((client) => {
                if (client.readyState === WebSocket.OPEN) {
                  client.send(JSON.stringify(alertPayload));
                }
              });
              ws.send(JSON.stringify({ success: true, message: 'Alert sent' }));
              break;
            default:
              ws.send(JSON.stringify({
                success: false,
                error: `Unknown command: ${command}`,
              }));
              break;
          }
        } catch (error) {
          console.error(`[Alert] Error parsing message:`, error.message);
          ws.send(JSON.stringify({ success: false, error: 'Invalid JSON' }));
        }
      });

      ws.on('close', () => {
        console.log(`[Alert] WebSocket client disconnected`);
      });

      ws.on('error', (error) => {
        console.error(`[Alert] WebSocket error:`, error.message);
      });

      ws.send(JSON.stringify({
        success: true,
        message: 'Connected to alert service',
      }));
    });
  } else {
    console.log('WS_PORT missing or invalid, websocket server disabled for alert.');
  }
}

startWebSocketServer();