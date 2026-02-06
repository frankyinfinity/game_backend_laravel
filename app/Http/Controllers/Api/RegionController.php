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
use App\Custom\Draw\Complex\Form\InputDraw;
use App\Custom\Manipulation\ObjectDraw;
use App\Custom\Colors;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Action\ActionForm;
use App\Custom\Draw\Complex\Table\TableDraw;
use App\Custom\Draw\Complex\Table\TableHeadDraw;
use App\Custom\Draw\Complex\Table\TableCellDraw;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{

    public function get(Request $request, $planet_id){
        
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
            ->orderBy('name')
            ->whereNotNull('filename')
            ->get()->toArray();

        $playerId = 1;
        $player = Player::find($playerId);

        $requestId = Str::uuid()->toString();
        $sessionId = 'init_session_id';

        // Use the cache system
        ObjectCache::buffer($sessionId);

        $drawItems = [];

        //Select Region
        $x += $width_input + ($width_input/10);
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

        //Table
        $x = 15;
        $y += 100;

        $requestGene = \Illuminate\Http\Request::create('/api/registration_genes', 'GET');
        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
        $response = $kernel->handle($requestGene);
        $responseBody = json_decode($response->getContent(), true);
        $genes = $responseBody['genes'] ?? [];

        $table = new TableDraw(Str::random(20));
        $table->setOrigin($x, $y);
        $table->setWidth(1380);
        $table->setRowHeight(50);

        $head1 = new TableHeadDraw(Str::random(20));
        $head1->setText('Nome Gene');
        $head1->setSize(250, 50);
        $table->addHead($head1);

        $head2 = new TableHeadDraw(Str::random(20));
        $head2->setText('Min');
        $head2->setSize(100, 50);
        $table->addHead($head2);

        $head3 = new TableHeadDraw(Str::random(20));
        $head3->setText('Range Max (Da-A)');
        $head3->setSize(250, 50);
        $table->addHead($head3);

        $head4 = new TableHeadDraw(Str::random(20));
        $head4->setText('Iniziale');
        $head4->setSize(150, 50);
        $table->addHead($head4);

        $tableInputs = [];
        foreach ($genes as $gene) {

            $row = [];
            
            // Col 1: Nome Gene
            $cell1 = new TableCellDraw(Str::random(20));
            $cell1->setContent($gene['name']);
            $row[] = $cell1;

            // Col 2
            $cell2 = new TableCellDraw(Str::random(20));
            $cell2->setContent($gene['min']);
            $row[] = $cell2;

            // Col 3
            $cell3 = new TableCellDraw(Str::random(20));
            if($gene['max'] !== null) {
                $cell3->setContent('Fisso: ' . $gene['max']);
            } else {
                $cell3->setContent($gene['max_from'] . ' / ' . $gene['max_to']);
            }
            $row[] = $cell3;

            // Col 4
            $cell4 = new TableCellDraw(Str::random(20));
            $cell4->setSize(150, 50);
            $inputGene = new InputDraw(Str::random(20), $sessionId);
            $inputGene->setName('gene_value_' . $gene['id']);
            $inputGene->setTitle('');
            $inputGene->setType(InputDraw::TYPE_NUMBER);
            $inputGene->setBorderThickness(1);
            $inputGene->setBorderColor(Colors::DARK_GRAY);
            $inputGene->setBackgroundColor(Colors::WHITE);
            $inputGene->setSize(130, 68);
            $inputGene->setValue($gene['max'] !== null ? $gene['min'] : $gene['max_from']);
            if($gene['max'] !== null) {
                $inputGene->setMin($gene['min']);
                $inputGene->setMax($gene['max']);
            } else {
                $inputGene->setMin($gene['max_from']);
                $inputGene->setMax($gene['max_to']);
            }
            $cell4->setFormElement($inputGene);
            $row[] = $cell4;

            // Set consistent height for all cells in row
            $cell1->setSize(250, 50);
            $cell2->setSize(100, 50);
            $cell3->setSize(250, 50);

            $table->addRow($row);
            
            $tableInputs[] = $inputGene;

        }

        $table->build();

        //Button Registrazione (Blu)
        $x = 15;
        $y = $table->getBottomY() + 50;
        $submitButton = new ButtonDraw(Str::random(20).'_submit_button');
        $submitButton->setSize(400, 50);
        $submitButton->setOrigin($x, $y);
        $submitButton->setString('Registrazione');
        $submitButton->setColorButton(Colors::BLUE);
        $submitButton->setColorString(Colors::WHITE);
        $submitButton->setTextFontSize(22);

        //Button Torna al Login (Rosso)
        $xBack = $x + 400 + 20; // 400 width + 20 gap
        $backButton = new ButtonDraw(Str::random(20).'_back_button');
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


        
        //Gost Input & Select
        if($name_input_uid){
            $nameInput = new InputDraw($name_input_uid, $sessionId);
            $nameInput->setName('name');
            $actionForm->setInput($nameInput);
        }

        if($email_input_uid){
            $emailInput = new InputDraw($email_input_uid, $sessionId);
            $emailInput->setName('email');
            $actionForm->setInput($emailInput);
        }

        if($password_input_uid){
            $passwordInput = new InputDraw($password_input_uid, $sessionId);
            $passwordInput->setName('password');
            $actionForm->setInput($passwordInput);
        }

        if($name_specie_input_uid){
            $nameSpecieInput = new InputDraw($name_specie_input_uid, $sessionId);
            $nameSpecieInput->setName('name_specie');
            $actionForm->setInput($nameSpecieInput);
        }
    
        if($tile_i_input_uid){
            $tileIInput = new InputDraw($tile_i_input_uid, $sessionId);
            $tileIInput->setName('tile_i');
            $actionForm->setInput($tileIInput);
        }

        if($tile_j_input_uid){
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

        //Table Inputs
        $geneIds = [];
        foreach ($genes as $gene) {
            $geneIds[] = $gene['id'];
            $actionForm->setExtraData('gene_min_'.$gene['id'], $gene['min']);
        }
        $actionForm->setExtraData('gene_ids', implode(',', $geneIds));

        foreach ($tableInputs as $tableInput) {
            $actionForm->setInput($tableInput);
        }
        $actionForm->setButton($submitButton);

        //Add Back Button to items
        $listItems = $backButton->getDrawItems(); 
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }


        //Get all
        $listItems = $selectRegion->getDrawItems();
        foreach($listItems as $listItem) {
            $objectDraw = new ObjectDraw($listItem, $sessionId);
            $drawItems[] = $objectDraw->get();
        }

        $listItems = $table->getDrawItems();
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
