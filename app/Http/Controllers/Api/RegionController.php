<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use Illuminate\Support\Str;
use App\Models\Player;
use App\Custom\Manipulation\ObjectCache;
use App\Custom\Manipulation\ObjectClear;
use App\Models\DrawRequest;
use App\Custom\Draw\Complex\EntityAssemblerDraw;
use App\Custom\Draw\Complex\Form\SelectDraw;
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Colors;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Action\ActionForm;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{

    public function get(Request $request, $planet_id)
    {

        $x = doubleval($request->x);
        $y = doubleval($request->y);
        $width_input = doubleval($request->width_input);
        $height_input = doubleval($request->height_input);

        $name_input_uid = $request->name_input_uid;
        $email_input_uid = $request->email_input_uid;
        $password_input_uid = $request->password_input_uid;
        $name_specie_input_uid = $request->name_specie_input_uid;
        $tile_i_input_uid = $request->tile_i_input_uid;
        $tile_j_input_uid = $request->tile_j_input_uid;
        $planet_select_uid = $request->planet_select_uid;

        $regions = Region::query()
            ->where('planet_id', $planet_id)
            ->where('state', Region::STATE_COMPLETED)
            ->orderBy('name')
            ->whereNotNull('filename')
            ->whereNotNull('modified_image')
            ->get()->toArray();

        $playerId = 1;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = 'init_session_id';

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $drawItems = [];

        //Select Region
        $x += $width_input + ($width_input / 10);
        $selectRegion = new SelectDraw(Str::random(20), $sessionId);
        $selectRegion->setName('birth_region_id');
        $selectRegion->setRequired(true);
        $selectRegion->setTitle('Regione Natale');
        $selectRegion->setOptions($regions);
        $selectRegion->setOptionId('id');
        $selectRegion->setOptionText('name');
        $selectRegion->setOptionShowDisplay(2);

        $selectRegion->setOrigin($x, $y);
        $selectRegion->setSize($width_input, $height_input);
        $selectRegion->setBorderThickness(2);
        $selectRegion->setBorderColor(Colors::DARK_GRAY);
        $selectRegion->setTitleColor(Colors::BLACK);
        $selectRegion->setValueColor(Colors::BLACK);
        $selectRegion->setBackgroundColor(Colors::WHITE);
        $selectRegion->setBoxIconColor(Colors::LIGHT_GRAY);
        $selectRegion->setBoxIconTextColor(Colors::BLACK);
        $selectRegion->build();

        // EntityAssemblerDraw (al posto del multiselect elementi chimici)
        // Inviato solo se non già in cache (evita re-invio del payload da 2.7MB ad ogni cambio pianeta)
        $xAssembler = (int) ($x + $width_input + ($width_input / 10));
        $assemblerAlreadyInCache = ObjectCache::find($sessionId, 'register_assembler_button') !== null;
        $assembler = new EntityAssemblerDraw('register_assembler_button');
        $assembler->setOrigin($xAssembler, (int) $y);
        $assembler->setBorderRadius(8);
        $assembler->build();
        if (!$assemblerAlreadyInCache) {
            $drawItems = array_merge(
                $drawItems,
                $assembler->getDrawItemsWithObjectDraw($sessionId),
            );
        }

        // Ad ogni cambio pianeta resetta lo stato salvato dell'assembler:
        // riporta il quadratino a arancione e sblocca l'apertura della modal.
        $assemblerModalUid = 'objective_modal_assembler_register_assembler_button';
        $jsResetAssemblerState = "(function() {";
        $jsResetAssemblerState .= "\n  window['assemblerSaved_{$assemblerModalUid}'] = false;";
        $jsResetAssemblerState .= "\n  var sq = shapes['register_assembler_button_square'];";
        $jsResetAssemblerState .= "\n  if (sq) { sq.tint = 0xE07B00; }";
        $jsResetAssemblerState .= "\n  if (objects['register_assembler_button_square']) { objects['register_assembler_button_square'].color = 0xE07B00; }";
        $jsResetAssemblerState .= "\n  var resetAddStateFn = window['resetAddComponentState_{$assemblerModalUid}'];";
        $jsResetAssemblerState .= "\n  if (typeof resetAddStateFn === 'function') { resetAddStateFn(); }";
        $jsResetAssemblerState .= "\n})();";
        $drawItems[] = ['type' => 'code', 'code' => $jsResetAssemblerState];

        // Cancella il bottone "Torna al Login" della schermata precedente
        $objectClear = new ObjectClear('register_login_button', $sessionId);
        $drawItems[] = $objectClear->get();
        $objectClear = new ObjectClear('register_login_button_rect', $sessionId);
        $drawItems[] = $objectClear->get();
        $objectClear = new ObjectClear('register_login_button_text', $sessionId);
        $drawItems[] = $objectClear->get();

        //Button Registrazione (Blu)
        $x = 15;
        $y += 100;
        $submitButton = new ButtonDraw('register_submit_button');
        $submitButton->setSize(400, 50);
        $submitButton->setOrigin($x, $y);
        $submitButton->setString('Registrazione');
        $submitButton->setColorButton(Colors::BLUE);
        $submitButton->setColorString(Colors::WHITE);
        $submitButton->setTextFontSize(22);

        //Button Torna al Login (Rosso)
        $xBack = $x + 400 + 20; // 400 width + 20 gap
        $backButton = new ButtonDraw('register_back_button');
        $backButton->setSize(400, 50);
        $backButton->setOrigin($xBack, $y);
        $backButton->setString('Torna al Login');
        $backButton->setColorButton(Colors::RED);
        $backButton->setColorString(Colors::WHITE);
        $backButton->setTextFontSize(22);

        $jsPathOnClickLogin = resource_path('js/function/login/on_click_login.blade.php');
        if (file_exists($jsPathOnClickLogin)) {
            $jsContentOnClickLogin = file_get_contents($jsPathOnClickLogin);
            $jsContentOnClickLogin = \App\Helper\Helper::setCommonJsCode($jsContentOnClickLogin, Str::random(20));
            $backButton->setOnClick($jsContentOnClickLogin);
        }
        $backButton->build();

        //Action Form
        $actionForm = new ActionForm();
        $actionForm->setSubmitFunction(resource_path('js/function/register/on_submit_register.blade.php'));

        //Ghost Input & Select
        if ($name_input_uid) {
            $nameInput = new InputDraw($name_input_uid, $sessionId);
            $nameInput->setName('name');
            $actionForm->setInput($nameInput);
        }

        if ($email_input_uid) {
            $emailInput = new InputDraw($email_input_uid, $sessionId);
            $emailInput->setName('email');
            $actionForm->setInput($emailInput);
        }

        if ($password_input_uid) {
            $passwordInput = new InputDraw($password_input_uid, $sessionId);
            $passwordInput->setName('password');
            $actionForm->setInput($passwordInput);
        }

        if ($name_specie_input_uid) {
            $nameSpecieInput = new InputDraw($name_specie_input_uid, $sessionId);
            $nameSpecieInput->setName('name_specie');
            $actionForm->setInput($nameSpecieInput);
        }

        if ($tile_i_input_uid) {
            $tileIInput = new InputDraw($tile_i_input_uid, $sessionId);
            $tileIInput->setName('tile_i');
            $actionForm->setInput($tileIInput);
        }

        if ($tile_j_input_uid) {
            $tileJInput = new InputDraw($tile_j_input_uid, $sessionId);
            $tileJInput->setName('tile_j');
            $actionForm->setInput($tileJInput);
        }

        if ($planet_select_uid) {
            $planetSelect = new SelectDraw($planet_select_uid, $sessionId);
            $planetSelect->setName('birth_planet_id');
            $actionForm->setSelect($planetSelect);
        }

        $actionForm->setSelect($selectRegion);
        $actionForm->setInput($assembler->getActionFormInput($sessionId));
        $actionForm->setButton($submitButton);

        //Add Back Button to items
        $listItems = $backButton->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        //Get all
        $listItems = $selectRegion->getDrawItems();
        foreach ($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $submitButton->getDrawItems();
        foreach ($listItems as $listItem) {
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

        return response()->json(['success' => true]);

    }

}
