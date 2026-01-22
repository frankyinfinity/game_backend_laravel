<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use Illuminate\Support\Str;
use App\Models\Player;
use App\Custom\Manipulation\ObjectCache;
use App\Events\DrawInterfaceEvent;
use App\Models\DrawRequest;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Colors;
use App\Custom\Draw\Complex\ButtonDraw;

class RegionController extends Controller
{

    public function get($planet_id){

        $regions = Region::query()
            ->where('planet_id', $planet_id)
            ->orderBy('name')
            ->whereNotNull('filename')
            ->get()->toArray();

        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';

        // Use player ID 1 for test
        $playerId = 1;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = 'test_session_fixed';

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $drawItems = [];
        
        $x = 50;
        $y = 150;

        //Select Region
        $selectRegion = new SelectDraw(Str::random(20), $sessionId);
        $selectRegion->setName('birth_region_id');
        $selectRegion->setRequired(true);
        $selectRegion->setTitle('Regione Natale');
        $selectRegion->setOptions($regions);
        $selectRegion->setOptionId('id');
        $selectRegion->setOptionText('name');
        $selectRegion->setOptionShowDisplay(2);

        $selectRegion->setOrigin($x, $y);
        $selectRegion->setSize(500, 50);
        $selectRegion->setBorderThickness(2);
        $selectRegion->setBorderColor(Colors::DARK_GRAY);
        $selectRegion->setTitleColor(Colors::BLACK);
        $selectRegion->setValueColor(Colors::BLACK);
        $selectRegion->setBackgroundColor(Colors::WHITE);
        $selectRegion->setBoxIconColor(Colors::LIGHT_GRAY);
        $selectRegion->setBoxIconTextColor(Colors::BLACK);
        $selectRegion->build();

        //Button
        $y += 100;

        $submitButton = new ButtonDraw(Str::random(20).'_submit_button');
        $submitButton->setSize(500, 50);
        $submitButton->setOrigin($x, $y);
        $submitButton->setString('Registrazione');
        $submitButton->setColorButton(Colors::BLUE);
        $submitButton->setColorString(Colors::WHITE);
        $submitButton->setTextFontSize(22);
        $submitButton->build();

        //Get all
        $listItems = $selectRegion->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $submitButton->getDrawItems(); 
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        // Flush to cache
        ObjectCache::flush($sessionId);

        // Dispatch event
        DrawRequest::query()->create([
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'player_id' => $playerId,
            'items' => json_encode($drawItems),
        ]);
        event(new DrawInterfaceEvent($player, $requestId));

        return response()->json(['success' => true]);

    }
    
}
