const WebSocket = require('ws');

const wsPort = Number(process.env.WS_PORT || 0);

console.log(`Alert service started.`);
console.log(`WS_PORT: ${wsPort || 'MISSING'}`);

let wss = null;

function buildAlertDrawItems(title, body, alertType) {
  const typeMap = {
    'info': { border: '0x3B82F6', fill: '0xDBEAFE', text: '0x1E40AF' },
    'warning': { border: '0xF59E0B', fill: '0xFEF3C7', text: '0x92400E' },
    'error': { border: '0xEF4444', fill: '0xFEE2E2', text: '0x991B1B' },
    'success': { border: '0x10B981', fill: '0xD1FAE5', text: '0x065F46' },
  };
  const colors = typeMap[alertType] || typeMap['info'];

  const titleText = String(title || '');
  const bodyText = String(body || '');
  const titleFontSize = 18;
  const bodyFontSize = 14;
  const charWidth = 10;
  const padding = 16;
  const lineHeight = 22;

  const titleWidth = Math.max(100, titleText.length * charWidth);
  const bodyWidth = Math.max(100, bodyText.length * charWidth);
  const rectWidth = Math.max(titleWidth, bodyWidth) + (padding * 2);
  const rectHeight = (titleFontSize + lineHeight) + (padding * 2);

  const rectX = 1200 - rectWidth - 20;
  const rectY = 20;

  const alertId = 'alert_' + Date.now();
  const rectUid = alertId + '_rect';
  const titleUid = alertId + '_title';
  const bodyUid = alertId + '_body';

  return {
    type: 'draw_interface',
    request_id: alertId,
    player_id: 0,
    items: [
      {
        type: 'draw',
        object: {
          uid: rectUid,
          type: 'rectangle',
          x: rectX,
          y: rectY,
          width: rectWidth,
          height: rectHeight,
          color: colors.fill,
          borderColor: colors.border,
          thickness: 2,
          borderRadius: 8,
        },
      },
      {
        type: 'draw',
        object: {
          uid: titleUid,
          type: 'text',
          text: titleText,
          x: rectX + padding,
          y: rectY + padding,
          color: colors.text,
          fontSize: titleFontSize,
          fontFamily: 'Arial',
        },
      },
      {
        type: 'draw',
        object: {
          uid: bodyUid,
          type: 'text',
          text: bodyText,
          x: rectX + padding,
          y: rectY + padding + titleFontSize + 4,
          color: colors.text,
          fontSize: bodyFontSize,
          fontFamily: 'Arial',
        },
      },
      {
        type: 'update',
        uid: rectUid,
        sleep: 5000,
        attributes: { renderable: false },
      },
      {
        type: 'update',
        uid: titleUid,
        sleep: 5000,
        attributes: { renderable: false },
      },
      {
        type: 'update',
        uid: bodyUid,
        sleep: 5000,
        attributes: { renderable: false },
      },
    ],
  };
}

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
              const alertType = data.alert_type || data.type || 'info';
              const drawPayload = buildAlertDrawItems(data.title, data.body, alertType);
              console.log(`[Alert] Broadcasting draw_interface:`, drawPayload);
              wss.clients.forEach((client) => {
                if (client.readyState === WebSocket.OPEN) {
                  client.send(JSON.stringify(drawPayload));
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
