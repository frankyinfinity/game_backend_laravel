import { createServer } from 'http';
import { Server } from 'socket.io';
import axios from 'axios';

// Configuration
const PORT = process.env.SOCKETIO_PORT || 3001;
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

// Create HTTP server
const httpServer = createServer();

// Create Socket.io server
const io = new Server(httpServer, {
    cors: {
        origin: LARAVEL_URL,
        methods: ['GET', 'POST'],
        credentials: true
    },
    transports: ['websocket', 'polling']
});

// Store connected clients
const clients = new Map();

// Authentication middleware
io.use(async (socket, next) => {
    try {
        const token = socket.handshake.auth.token || socket.handshake.headers.authorization;

        if (!token) {
            // For public channels, allow connection without token
            socket.data.user = { id: 'guest' };
            return next();
        }

        // Verify token with Laravel
        const response = await axios.get(`${LARAVEL_URL}/api/user`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (response.data) {
            socket.data.user = response.data;
            next();
        } else {
            next(new Error('Authentication error: Invalid token'));
        }
    } catch (error) {
        console.error('Authentication error:', error.message);
        // Allow connection as guest for public channels
        socket.data.user = { id: 'guest' };
        next();
    }
});

// Connection handler
io.on('connection', (socket) => {
    const userId = socket.data.user?.id;
    console.log(`Client connected: ${socket.id}, User ID: ${userId}`);

    // Store client
    clients.set(socket.id, {
        socket,
        userId,
        channels: new Set()
    });

    // Handle channel subscription
    socket.on('subscribe', async (data) => {
        try {
            const { channel, auth } = data;

            // Authorize channel with Laravel
            const response = await axios.post(`${LARAVEL_URL}/broadcasting/auth`, {
                socket_id: socket.id,
                channel_name: channel
            }, {
                headers: {
                    'Authorization': `Bearer ${socket.handshake.auth.token}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            if (response.data && (response.data.auth === true || response.data.auth)) {
                socket.join(channel);
                clients.get(socket.id).channels.add(channel);
                socket.emit('subscription_succeeded', { channel });
                console.log(`Socket ${socket.id} subscribed to channel: ${channel}`);
            } else {
                socket.emit('subscription_error', { channel, error: 'Authorization failed' });
            }
        } catch (error) {
            console.error('Subscription error:', error.message);
            // Allow subscription for public channels even if auth fails
            socket.join(data.channel);
            clients.get(socket.id).channels.add(data.channel);
            socket.emit('subscription_succeeded', { channel: data.channel });
        }
    });

    // Handle channel unsubscription
    socket.on('unsubscribe', (data) => {
        const { channel } = data;
        socket.leave(channel);
        clients.get(socket.id).channels.delete(channel);
        socket.emit('unsubscription_succeeded', { channel });
        console.log(`Socket ${socket.id} unsubscribed from channel: ${channel}`);
    });

    // Handle client events
    socket.on('client-event', (data) => {
        const { channel, event, payload } = data;
        // Broadcast to all clients in the channel except sender
        socket.to(channel).emit(event, payload);
    });

    // Handle disconnect
    socket.on('disconnect', () => {
        console.log(`Client disconnected: ${socket.id}`);
        clients.delete(socket.id);
    });

    // Handle errors
    socket.on('error', (error) => {
        console.error(`Socket error for ${socket.id}:`, error);
    });
});

// Broadcast endpoint for Laravel (HTTP POST)
httpServer.on('request', (req, res) => {
    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';

        req.on('data', (chunk) => {
            body += chunk.toString();
        });

        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                const { channel, event, payload } = data;

                // Broadcast to all clients in the channel
                io.to(channel).emit(event, payload);

                console.log(`Broadcasted event '${event}' to channel '${channel}'`);

                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: true }));
            } catch (error) {
                console.error('Broadcast error:', error);
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ success: false, error: error.message }));
            }
        });
    } else {
        // Handle other requests (404)
        res.writeHead(404, { 'Content-Type': 'text/plain' });
        res.end('Not Found');
    }
});

// Start server
httpServer.listen(PORT, () => {
    console.log(`Socket.io server running on port ${PORT}`);
    console.log(`Laravel URL: ${LARAVEL_URL}`);
    console.log(`Broadcast endpoint: http://localhost:${PORT}/broadcast`);
});

// Graceful shutdown
process.on('SIGTERM', () => {
    console.log('SIGTERM received, shutting down gracefully...');
    httpServer.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log('SIGINT received, shutting down gracefully...');
    httpServer.close(() => {
        console.log('Server closed');
        process.exit(0);
    });
});

export { io, httpServer };
