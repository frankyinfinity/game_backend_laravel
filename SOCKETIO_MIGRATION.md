# Socket.io Migration Guide

This document describes the migration from Pusher to Socket.io for real-time communication in the project.

## Overview

The project has been migrated from Pusher to Socket.io for WebSocket communication. This change provides:
- Self-hosted WebSocket server (no external dependencies)
- Better control over the WebSocket infrastructure
- Cost savings (no Pusher subscription fees)
- Full customization of the WebSocket behavior

## Architecture

### Components

1. **Socket.io Server** (`socketio-server.js`)
   - Node.js server that handles WebSocket connections
   - Runs on port 3001 by default
   - Handles channel subscriptions and event broadcasting

2. **Socket.io Broadcaster** (`app/Broadcasting/SocketIoBroadcaster.php`)
   - Laravel broadcaster that integrates with Socket.io
   - Allows Laravel events to broadcast through Socket.io

3. **Socket.io Client** (`resources/js/socketio-client.js`)
   - JavaScript client for connecting to Socket.io
   - Provides Echo-compatible interface for existing code

4. **WebSocket Service** (`app/Services/WebSocketService.php`)
   - Updated to support both Socket.io and raw WebSocket connections
   - Provides broadcast functionality for channels

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Socket.io Configuration
SOCKETIO_URL=http://localhost:3001
SOCKETIO_HOST=localhost
SOCKETIO_PORT=3001
SOCKETIO_SCHEME=http
SOCKETIO_KEY=<your-generated-key>

# Vite (Frontend) Configuration
VITE_SOCKETIO_URL=http://localhost:3001

# Broadcasting Configuration
BROADCAST_CONNECTION=socketio
```

**Note:** Unlike Pusher, Socket.io is self-hosted and doesn't require an external API key. The `SOCKETIO_KEY` is a local secret key used for internal authentication. You can generate a secure random key using:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

The current project uses: `9a1b96ff34676daa344e7564af1c632fdb10505bf5fd1a1f2d993a32a7276d14`

### Broadcasting Configuration

The `config/broadcasting.php` file has been updated with a new `socketio` connection:

```php
'socketio' => [
    'driver' => 'socketio',
    'url' => env('SOCKETIO_URL', 'http://localhost:3001'),
    'key' => env('SOCKETIO_KEY', 'socketio-key'),
    'options' => [
        'host' => env('SOCKETIO_HOST', 'localhost'),
        'port' => env('SOCKETIO_PORT', 3001),
        'scheme' => env('SOCKETIO_SCHEME', 'http'),
    ],
],
```

## Usage

### Starting the Socket.io Server

Run the Socket.io server:

```bash
cd backend
npm run socketio
```

Or run both Vite and Socket.io together:

```bash
npm run dev:all
```

### Client-Side Usage

The Socket.io client provides an Echo-compatible interface:

```javascript
// Subscribe to a channel
const channel = Echo.channel('player_1_channel');

// Listen for events
channel.listen('draw_interface', (data) => {
    console.log('Event received:', data);
});

// Private channels
const privateChannel = Echo.private('player_1_channel');

// Presence channels
const presenceChannel = Echo.presence('chat');
```

### Server-Side Broadcasting

Events that implement `ShouldBroadcast` will automatically use Socket.io:

```php
class DrawInterfaceEvent implements ShouldBroadcast
{
    public function broadcastOn(): array
    {
        return [
            new Channel('player_' . $this->player->id . '_channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'draw_interface';
    }
}
```

### Manual Broadcasting

You can also broadcast manually using the WebSocketService:

```php
use App\Services\WebSocketService;

WebSocketService::broadcast(
    'player_1_channel',
    'custom_event',
    ['message' => 'Hello!']
);
```

## Files Modified

### Backend Files

1. `socketio-server.js` - New Socket.io server
2. `app/Broadcasting/SocketIoBroadcaster.php` - New broadcaster class
3. `app/Providers/AppServiceProvider.php` - Registered Socket.io broadcaster
4. `app/Http/Controllers/BroadcastingController.php` - Updated for Socket.io
5. `app/Services/WebSocketService.php` - Added Socket.io support
6. `config/broadcasting.php` - Added socketio connection
7. `.env` - Added Socket.io configuration

### Frontend Files

1. `resources/js/socketio-client.js` - New Socket.io client
2. `resources/js/echo.js` - Updated to use Socket.io
3. `resources/views/test.blade.php` - Updated to use Socket.io

### Package Files

1. `package.json` - Added Socket.io dependencies and scripts

## Dependencies

### New Dependencies

- `socket.io` - Socket.io server
- `socket.io-client` - Socket.io client

### Existing Dependencies (Still Used)

- `laravel-echo` - Echo interface (now using Socket.io)
- `pusher-js` - Can be removed if no longer needed

## Migration Notes

### Breaking Changes

1. **Channel Subscription**: The subscription flow has changed slightly. Socket.io uses explicit subscription events instead of Pusher's automatic subscription.

2. **Authentication**: Socket.io uses a different authentication mechanism. The `BroadcastingController` has been updated to handle both Pusher and Socket.io authentication.

3. **Event Names**: Event names remain the same, but the internal handling has changed.

### Compatibility

The Socket.io client provides an Echo-compatible interface, so most existing code should work without modification. However, direct Pusher API calls will need to be updated.

### Testing

To test the migration:

1. Start the Socket.io server: `npm run socketio`
2. Start the Laravel server: `php artisan serve`
3. Start Vite: `npm run dev`
4. Visit the test page and verify WebSocket connections

## Troubleshooting

### Connection Issues

If clients cannot connect to the Socket.io server:

1. Verify the Socket.io server is running: `npm run socketio`
2. Check the `SOCKETIO_URL` in `.env`
3. Ensure the port (3001) is not blocked by firewall
4. Check browser console for connection errors

### Authentication Issues

If channel subscription fails:

1. Verify the user is authenticated
2. Check the `BroadcastingController` for authentication logic
3. Ensure the token is being sent correctly

### Event Not Received

If events are not being received:

1. Verify the event is being dispatched on the server
2. Check the channel name matches between sender and receiver
3. Verify the Socket.io server logs for broadcast events

## Future Improvements

1. **Authentication**: Implement proper JWT authentication for Socket.io
2. **Private Channels**: Add full support for private and presence channels
3. **Scaling**: Add Redis adapter for horizontal scaling
4. **Monitoring**: Add metrics and monitoring for WebSocket connections
5. **Reconnection**: Improve reconnection logic for better reliability

## References

- [Socket.io Documentation](https://socket.io/docs/)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
- [Laravel Echo](https://laravel.com/docs/echo)
