import { io } from 'socket.io-client';

class SocketIoClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || 'http://localhost:3001',
            token: options.token || null,
            transports: options.transports || ['websocket', 'polling'],
            autoConnect: options.autoConnect !== false,
            reconnection: options.reconnection !== false,
            reconnectionDelay: options.reconnectionDelay || 1000,
            reconnectionAttempts: options.reconnectionAttempts || Infinity,
            ...options
        };

        this.socket = null;
        this.channels = new Map();
        this.listeners = new Map();
    }

    connect() {
        if (this.socket && this.socket.connected) {
            return Promise.resolve(this.socket);
        }

        return new Promise((resolve, reject) => {
            const socketOptions = {
                transports: this.options.transports,
                autoConnect: this.options.autoConnect,
                reconnection: this.options.reconnection,
                reconnectionDelay: this.options.reconnectionDelay,
                reconnectionAttempts: this.options.reconnectionAttempts,
                auth: {
                    token: this.options.token
                }
            };

            this.socket = io(this.options.url, socketOptions);

            this.socket.on('connect', () => {
                console.log('Socket.io connected:', this.socket.id);
                resolve(this.socket);
            });

            this.socket.on('connect_error', (error) => {
                console.error('Socket.io connection error:', error);
                reject(error);
            });

            this.socket.on('disconnect', (reason) => {
                console.log('Socket.io disconnected:', reason);
            });

            this.socket.on('error', (error) => {
                console.error('Socket.io error:', error);
            });
        });
    }

    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
            this.channels.clear();
            this.listeners.clear();
        }
    }

    channel(channelName) {
        if (!this.channels.has(channelName)) {
            this.channels.set(channelName, new SocketIoChannel(this, channelName));
        }
        return this.channels.get(channelName);
    }

    private(channelName) {
        return this.channel('private-' + channelName);
    }

    presence(channelName) {
        return this.channel('presence-' + channelName);
    }

    leave(channelName) {
        if (this.socket) {
            this.socket.emit('unsubscribe', { channel: channelName });
        }
        this.channels.delete(channelName);
    }

    leaveAll() {
        this.channels.forEach((channel, name) => {
            this.leave(name);
        });
    }

    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);

        if (this.socket) {
            this.socket.on(event, callback);
        }
    }

    off(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }

        if (this.socket) {
            this.socket.off(event, callback);
        }
    }
}

class SocketIoChannel {
    constructor(client, name) {
        this.client = client;
        this.name = name;
        this.listeners = new Map();
        this.subscribed = false;
    }

    subscribe() {
        return new Promise((resolve, reject) => {
            if (this.subscribed) {
                resolve();
                return;
            }

            if (!this.client.socket) {
                reject(new Error('Socket not connected'));
                return;
            }

            this.client.socket.emit('subscribe', {
                channel: this.name,
                auth: {
                    token: this.client.options.token
                }
            });

            this.client.socket.on('subscription_succeeded', (data) => {
                if (data.channel === this.name) {
                    this.subscribed = true;
                    console.log(`Subscribed to channel: ${this.name}`);
                    resolve();
                }
            });

            this.client.socket.on('subscription_error', (data) => {
                if (data.channel === this.name) {
                    console.error(`Subscription error for ${this.name}:`, data.error);
                    reject(new Error(data.error));
                }
            });
        });
    }

    unsubscribe() {
        if (this.client.socket) {
            this.client.socket.emit('unsubscribe', { channel: this.name });
        }
        this.subscribed = false;
        this.listeners.clear();
    }

    listen(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);

        if (this.client.socket) {
            this.client.socket.on(event, (data) => {
                callback(data);
            });
        }

        return this;
    }

    stopListening(event, callback) {
        if (this.listeners.has(event)) {
            const callbacks = this.listeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }

        if (this.client.socket) {
            this.client.socket.off(event, callback);
        }

        return this;
    }

    on(event, callback) {
        return this.listen(event, callback);
    }

    off(event, callback) {
        return this.stopListening(event, callback);
    }
}

// Create global Echo-like interface
const socketIoClient = new SocketIoClient({
    url: import.meta.env.VITE_SOCKETIO_URL || 'http://localhost:3001',
    token: localStorage.getItem('auth_token') || null
});

// Auto-connect
socketIoClient.connect().catch(console.error);

// Export for use in other files
window.SocketIoClient = SocketIoClient;
window.socketIoClient = socketIoClient;

// Create Echo-like interface for compatibility
window.Echo = {
    channel: (name) => socketIoClient.channel(name),
    private: (name) => socketIoClient.private(name),
    presence: (name) => socketIoClient.presence(name),
    leave: (name) => socketIoClient.leave(name),
    leaveAll: () => socketIoClient.leaveAll(),
    connector: socketIoClient,
    connect: () => socketIoClient.connect(),
    disconnect: () => socketIoClient.disconnect(),
    on: (event, callback) => socketIoClient.on(event, callback),
    off: (event, callback) => socketIoClient.off(event, callback)
};

export default socketIoClient;
export { SocketIoClient };
