<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastingController extends Controller
{

    public function auth(Request $request): \Illuminate\Foundation\Application|\Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
    {

        if (! $request->user()) {
            abort(403, 'Non autorizzato.');
        }

        $socketId = $request->input('socket_id');
        $channelName = $request->input('channel_name');

        // Check if using Socket.io or Pusher
        $driver = config('broadcasting.default');

        if ($driver === 'socketio') {
            // Socket.io authorization - return success for public channels
            // For private/presence channels, you would implement additional logic
            return response()->json([
                'auth' => true,
                'socket_id' => $socketId,
                'channel_name' => $channelName
            ]);
        } else {
            // Fallback to Pusher
            $pusher = Broadcast::driver('pusher')->getPusher();
            $response = $pusher->authorizeChannel($channelName, $socketId);
            return response($response, 200)->header('Content-Type', 'application/json');
        }
    }

}
