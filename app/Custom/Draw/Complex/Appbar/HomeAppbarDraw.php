<?php

namespace App\Custom\Draw\Complex\Appbar;

use App\Custom\Draw\Complex\AppbarDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Draw\Complex\SidebarDraw;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Colors;
use App\Helper\Helper;
use App\Models\PlayerHasScore;
use Illuminate\Support\Str;

class HomeAppbarDraw extends AppbarDraw
{
    /**
     * Add elements to the left section of the appbar
     * Uses addLeftElement() to add player name and location
     */
    protected function addLeftSection(): void
    {
        // Player name text (centered vertically)
        $playerName = new Text($this->getUid() . '_player_name');
        $playerName->setOrigin(120, 20); // Shifted right to avoid menu button
        $playerName->setFontSize(24);
        $playerName->setFontFamily('Outfit');
        $playerName->setColor(Colors::WHITE);

        $displayName = 'Giocatore';
        if ($this->player) {
            $displayName = $this->player->name ?? ($this->player->user->name ?? 'Giocatore');
        }
        $playerName->setText($displayName);

        // Location text (planet - region) (centered vertically)
        $locationText = new Text($this->getUid() . '_location');
        $locationText->setOrigin(120, 45); // Shifted right to avoid menu button
        $locationText->setFontSize(18);
        $locationText->setFontFamily('Outfit');
        $locationText->setColor(0xCCCCCC); // Light gray

        $locationString = '(Nessun pianeta selezionato)';
        if ($this->player && $this->player->birthPlanet && $this->player->birthRegion) {
            $locationString = "({$this->player->birthPlanet->name} - {$this->player->birthRegion->name})";
        }
        $locationText->setText($locationString);

        // Add elements using addLeftElement()
        $this->addLeftElement($playerName);
        $this->addLeftElement($locationText);
    }

    /**
     * Add elements to the right section of the appbar
     * Uses addRightElement() to add menu button
     */
    protected function addRightSection(): void
    {
        \Log::info('HomeAppbarDraw::addRightSection() called');

        // Menu button
        $menuButton = new ButtonDraw($this->getUid() . '_menu_button');
        $menuButton->setOrigin(20, 25); // Left side of appbar
        $menuButton->setSize(80, 30);
        $menuButton->setString('Menu');
        $menuButton->setColorButton(Colors::BLUE);
        $menuButton->setColorString(Colors::WHITE);
        $menuButton->setTextFontSize(14);

        // Set onClick function to toggle sidebar
        $jsPathOnClickMenu = resource_path('js/function/appbar/on_click_menu.blade.php');
        $jsContentOnClickMenu = file_get_contents($jsPathOnClickMenu);
        $jsContentOnClickMenu = Helper::setCommonJsCode($jsContentOnClickMenu, Str::random(20));

        $menuButton->setOnClick($jsContentOnClickMenu);
        $menuButton->build();

        // Add button elements using addRightElement()
        $drawItems = $menuButton->getDrawItems();

        \Log::info('Menu button draw items count: ' . count($drawItems));

        if (!empty($drawItems)) {
            foreach ($drawItems as $item) {
                $this->addRightElement($item);
            }
        }

        // Add sidebar
        $this->addSidebar();
    }

    private function addSidebar(): void
    {
        if (!$this->player) {
            return;
        }

        $sidebar = new SidebarDraw($this->getUid() . '_sidebar', $this->player->id);
        $sidebar->build();

        // Store sidebar for later use in GenerateMapJob
        $this->sidebar = $sidebar;
    }

    public function getSidebar(): ?SidebarDraw
    {
        return $this->sidebar ?? null;
    }

    /**
     * Add elements to the center section of the appbar
     * Shows three ScoreDraw elements for the player's scores
     */
    protected function addCenterSection(): void
    {
        \Log::info('HomeAppbarDraw::addCenterSection() called for player: ' . ($this->player ? $this->player->id : 'null'));
        
        if (!$this->player) {
            return;
        }
        
        // Get player's scores from PlayerHasScore
        $playerScores = PlayerHasScore::query()
            ->where('player_id', $this->player->id)
            ->with('score')
            ->get();
        
        \Log::info('Found ' . $playerScores->count() . ' scores for player');
        
        if (!$playerScores->isEmpty()) {
            // Center section dimensions
            $startX = 400; // LEFT_SECTION_WIDTH
            $centerY = 15; // Vertically centered in appbar (height 80)
            $scoreWidth = 120;
            $scoreHeight = 50;
            $spacing = 130;
            
            $index = 0;
            foreach ($playerScores as $playerScore) {
                
                $score = $playerScore->score;
                if (!$score) {
                    continue;
                }
                
                $x = $startX + ($index * $spacing);
                
                $scoreDraw = new ScoreDraw('player_'.$playerScore->player_id.'_score_' . $playerScore->score_id);
                $scoreDraw->setOrigin($x, $centerY);
                $scoreDraw->setSize($scoreWidth, $scoreHeight);
                $scoreDraw->setBackgroundColor(0x00000000); // Transparent background
                $scoreDraw->setBorderColor('#5B7FE8'); // Blue border
                $scoreDraw->setBorderWidth(3); // Thicker border for visibility
                $scoreDraw->setBorderRadius(10);
                $scoreDraw->setImageSize(35); // Slightly smaller image

                // Get image path for this score
                $imagePath = '/storage/scores/' . $score->id . '.png';
                $scoreDraw->setScoreImage($imagePath);

                // Use the value from player_score table if available, else 0
                $scoreValue = $playerScore->value ?? 0;
                $scoreDraw->setScoreValue((string) $scoreValue);

                // Score name for tooltip
                $scoreDraw->setScoreName($score->name ?? 'Score ' . $score->id);

                // White text
                $scoreDraw->setTextColor('#FFFFFF');
                $scoreDraw->setTextFontSize(16);
                $scoreDraw->build();
                
                // Add all draw items to center section
                foreach ($scoreDraw->getDrawItems() as $drawItem) {
                    $this->addCenterElement($drawItem);
                }
                
                $index++;
            }
        }

        // Add objectives modal (button is now in sidebar)
        $modalUid = 'objective_modal_' . $this->player->id;
        $this->addObjectiveModal($modalUid);
    }

    private function addObjectiveModal(string $modalUid): void
    {
        if (!$this->player) {
            return;
        }

        $objectiveTreeUidPrefix = 'objective_tree_' . $this->player->id;
        $objectiveTree = new ObjectiveTreeDraw($objectiveTreeUidPrefix, $this->player->fresh());
        $objectiveTree->setOrigin(0, 0);
        $objectiveTree->build();

        $modal = new ModalDraw($modalUid);
        $modal->setOrigin(20, 90);
        $modal->setSize(1120, 680);
        $modal->setTitle('Obiettivi');
        $modal->setRenderable(false);

        foreach ($objectiveTree->getDrawItems() as $drawItem) {
            $json = $drawItem->buildJson();
            $offsetX = isset($json['x']) ? (int) $json['x'] : 0;
            $offsetY = isset($json['y']) ? (int) $json['y'] : 0;
            $modal->addContentItem($drawItem, $offsetX, $offsetY);
        }

        $modal->build();

        foreach ($modal->getDrawItems() as $drawItem) {
            $this->addCenterElement($drawItem);
        }
    }
}
