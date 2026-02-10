<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SocketIoBroadcaster extends Broadcaster
{
    /**
     * The Socket.io server URL.
     *
     * @var string
     */
    protected $socketIoUrl;

    /**
     * The Guzzle HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \GuzzleHttp\Client  $client
     * @param  string  $socketIoUrl
     * @return void
     */
    public function __construct(Client $client, $socketIoUrl)
    {
        $this->client = $client;
        $this->socketIoUrl = $socketIoUrl;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        $channelName = $request->channel_name;
        $socketId = $request->socket_id;

        if (Str::startsWith($channelName, ['private-', 'presence-'])) {
            return $this->verifyUserCanAccessChannel(
                $request, $channelName
            );
        }

        return true;
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return json_encode(['auth' => $result]);
        }

        return json_encode([
            'auth' => $result,
        ]);
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $socketResponse = [];

        foreach ($this->formatChannels($channels) as $channel) {
            $socketResponse[] = $this->broadcastToChannel($channel, $event, $payload);
        }

        return $socketResponse;
    }

    /**
     * Broadcast to a specific channel.
     *
     * @param  string  $channel
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    protected function broadcastToChannel($channel, $event, $payload)
    {
        try {
            $this->client->post($this->socketIoUrl . '/broadcast', [
                'json' => [
                    'channel' => $channel,
                    'event' => $event,
                    'payload' => $payload,
                ],
                'timeout' => 5,
            ]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                logger()->error('Socket.io broadcast error: ' . $e->getResponse()->getBody());
            } else {
                logger()->error('Socket.io broadcast error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get the Socket.io API key.
     *
     * @return string
     */
    public function getSocketIoKey()
    {
        return $this->socketIoKey;
    }

    /**
     * Format the channel array into an array of strings.
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel) {
            return (string) $channel;
        }, $channels);
    }
}
