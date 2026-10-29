<?php

namespace App\Console\Commands;

use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\EntityDraw;
use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Custom\Manipulation\ObjectDraw;
use App\Helper\Helper;
use App\Models\DrawRequest;
use App\Models\Entity;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestDrawCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "test:draw {--player-id=1 : Player ID to send draw events to}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send test draw events to the test page - EntityDraw only";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $requestId = Str::uuid()->toString();
        $sessionId = "test_session_fixed";

        $eventPlayerId = (int) $this->option('player-id');

        ObjectCache::buffer($sessionId);

        $drawItems = [];

        // Cancella gli oggetti precedenti
        $existingObjects = ObjectCache::all($sessionId);
        foreach ($existingObjects as $uid => $object) {
            $objectClear = new ObjectClear($uid, $sessionId);
            $drawItems[] = $objectClear->get();
        }

        ObjectCache::clear($sessionId);

        // Create ModalDraw
        $modalUid = 'objective_modal_test';
        $modal = new ModalDraw($modalUid);
        $modal->setTitle('Test Modal');
        $modal->setScreenSize(1280, 720);
        $modal->setSize(600, 400);
        $modal->setOrigin(220, 50); // Slightly to the right of button (button ends at x=200), same height
        $modal->setRenderable(false); // Initially hidden
        $modal->build();

        foreach ($modal->getDrawItems() as $modalItem) {
            $drawItems[] = (new ObjectDraw($modalItem->buildJson(), $sessionId))->get();
        }

        // Create button to open modal
        $buttonUid = 'open_modal_button';
        $button = new ButtonDraw($buttonUid);
        $button->setOrigin(50, 50);
        $button->setSize(150, 40);
        $button->setString('Apri Modal');
        $button->setColorButton(0x3b82f6);
        $button->setColorString(0xffffff);
        $button->setTextFontSize(16);
        $button->setTextFontFamily(Helper::DEFAULT_FONT_FAMILY);

        // JavaScript to open modal
        $jsOpenModal = file_get_contents(resource_path('js/function/modal/click_open_modal.blade.php'));
        $jsOpenModal = str_replace('__MODAL_UID__', $modalUid, $jsOpenModal);
        $jsOpenModal = Helper::setCommonJsCode($jsOpenModal, Str::random(20));
        $button->setOnClick($jsOpenModal);
        $button->build();

        foreach ($button->getDrawItems() as $buttonItem) {
            $drawItems[] = (new ObjectDraw($buttonItem->buildJson(), $sessionId))->get();
        }

        // Create button to add random elements to modal
        $addElementsButtonUid = 'add_elements_button';
        $addElementsButton = new ButtonDraw($addElementsButtonUid);
        $addElementsButton->setOrigin(50, 100); // Below the first button
        $addElementsButton->setSize(180, 40);
        $addElementsButton->setString('Aggiungi Casuali');
        $addElementsButton->setColorButton(0x22c55e);
        $addElementsButton->setColorString(0xffffff);
        $addElementsButton->setTextFontSize(16);
        $addElementsButton->setTextFontFamily(Helper::DEFAULT_FONT_FAMILY);

        // JavaScript to add random elements via websocket evolution container
        $jsAddElements = "
            const modalUid = '{$modalUid}';
            const playerId = {$eventPlayerId};
            
            console.log('Button clicked');
            console.log('modalUid:', modalUid);
            console.log('playerId:', playerId);
            console.log('window.evolutionWsPort:', window.evolutionWsPort);
            
            // Get evolution container port via API if not already set
            const fetchEvolutionPort = function(callback) {
                const portKey = 'evolution_port_' + modalUid;
                console.log('portKey:', portKey);
                console.log('window[portKey]:', window[portKey]);
                
                if (window.evolutionWsPort) {
                    console.log('Using cached window.evolutionWsPort:', window.evolutionWsPort);
                    callback(window.evolutionWsPort);
                    return;
                }
                
                if (window[portKey]) {
                    console.log('Using cached window[portKey]:', window[portKey]);
                    callback(window[portKey]);
                    return;
                }
                
                console.log('Fetching evolution port from API...');
                console.log('API URL:', window.BACK_URL + '/api/containers/evolution-port/' + playerId);
                
                $.ajax({
                    url: window.BACK_URL + '/api/containers/evolution-port/' + playerId,
                    method: 'GET',
                    success: function(response) {
                        console.log('API response:', response);
                        if (response.success && response.port) {
                            window[portKey] = response.port;
                            console.log('Port set to:', response.port);
                            callback(response.port);
                        } else {
                            console.error('Failed to get evolution port:', response.message);
                            callback(null);
                        }
                    },
                    error: function(error) {
                        console.error('Error fetching evolution port:', error);
                        callback(null);
                    }
                });
            };
            
            fetchEvolutionPort(function(evolutionPort) {
                console.log('fetchEvolutionPort callback called with:', evolutionPort);
                
                if (!evolutionPort) {
                    console.error('Evolution container port not found');
                    return;
                }
                
                console.log('Evolution port:', evolutionPort);
                
                const gatewayBaseUrl = window.gatewayBaseUrl || 'ws://localhost:9001/?port=';
                const wsUrl = gatewayBaseUrl + evolutionPort;
                
                console.log('WS URL:', wsUrl);
                
                // Use cached websocket or create new one
                window.gameWebSockets = window.gameWebSockets || {};
                let ws = window.gameWebSockets[evolutionPort];
                
                const sendCommand = function() {
                    ws.send(JSON.stringify({ command: 'get_timeline' }));
                };
                
                const onMessage = function(event) {
                    let response;
                    try {
                        response = JSON.parse(event.data);
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    
                    console.log('Timeline response:', response);
                    
                    // Ignore connection messages, only process get_timeline responses
                    if (!response.command || response.command !== 'get_timeline') {
                        console.log('Ignoring non-timeline message:', response);
                        return;
                    }
                    
                    if (response.success && response.elements) {
                        console.log('Calling addElementsToModal with', response.elements.length, 'elements');
                        
                        // Call the reusable function to add elements
                        if (typeof window.addElementsToModal === 'function') {
                            window.addElementsToModal(modalUid, response.elements);
                        } else {
                            console.error('addElementsToModal function not found');
                        }
                    } else {
                        console.error('Invalid response:', response);
                    }
                };
                
                if (!ws || ws.readyState === WebSocket.CLOSED || ws.readyState === WebSocket.CLOSING) {
                    ws = new WebSocket(wsUrl);
                    window.gameWebSockets[evolutionPort] = ws;
                    
                    ws.onopen = function() {
                        console.log('WS Connected to ' + wsUrl);
                        sendCommand();
                    };
                    ws.onmessage = function(event) {
                        onMessage(event);
                    };
                    ws.onerror = function(error) {
                        console.error('WS Error:', error);
                    };
                } else {
                    if (ws.readyState === WebSocket.OPEN) {
                        sendCommand();
                    } else if (ws.readyState === WebSocket.CONNECTING) {
                        ws.addEventListener('open', sendCommand, { once: true });
                    }
                    // Use { once: true } to automatically remove listener after first message
                    ws.addEventListener('message', onMessage, { once: true });
                }
            });
        ";
        $jsAddElements = Helper::setCommonJsCode($jsAddElements, Str::random(20));
        $addElementsButton->setOnClick($jsAddElements);
        $addElementsButton->build();

        foreach ($addElementsButton->getDrawItems() as $addElementsButtonItem) {
            $drawItems[] = (new ObjectDraw($addElementsButtonItem->buildJson(), $sessionId))->get();
        }

        $this->info("Generated " . count($drawItems) . " test draw items (modal and buttons).");

        ObjectCache::flush($sessionId);

        $this->info("Total draw items: " . count($drawItems));

        DrawRequest::query()->create([
            "session_id" => $sessionId,
            "request_id" => $requestId,
            "player_id" => $eventPlayerId,
            "items" => json_encode($drawItems),
        ]);

        $this->info("Test draw event sent. Check the /test page.");
    }
}
