<?php

namespace App\Custom\Draw\Complex\Appbar;

use App\Custom\Draw\Complex\AppbarDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\ScoreDraw;
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
        $playerName->setOrigin(20, 20); // Left padding, vertically centered
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
        $locationText->setOrigin(20, 45); // Left padding, vertically centered
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
     * Uses addRightElement() to add logout button
     */
    protected function addRightSection(): void
    {
        \Log::info('HomeAppbarDraw::addRightSection() called');
        
        // Logout button
        $logoutButton = new ButtonDraw($this->getUid() . '_logout_button');
        $logoutButton->setOrigin(1400, 25); // Right aligned, vertically centered
        $logoutButton->setSize(80, 30);
        $logoutButton->setString('Logout');
        $logoutButton->setColorButton(Colors::RED);
        $logoutButton->setColorString(Colors::WHITE);
        $logoutButton->setTextFontSize(14);
        
        // Set onClick function
        $jsPathOnClickLogout = resource_path('js/function/appbar/on_click_logout.blade.php');
        $jsContentOnClickLogout = file_get_contents($jsPathOnClickLogout);
        $jsContentOnClickLogout = Helper::setCommonJsCode($jsContentOnClickLogout, Str::random(20));

        $logoutButton->setOnClick($jsContentOnClickLogout);
        $logoutButton->build();
        
        // Add button elements using addRightElement()
        $drawItems = $logoutButton->getDrawItems();
        
        // Debug: Log the draw items
        \Log::info('Logout button draw items count: ' . count($drawItems));
        
        if (!empty($drawItems)) {
            foreach ($drawItems as $item) {
                $this->addRightElement($item);
            }
        }
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
        
        if ($playerScores->isEmpty()) {
            return;
        }
        
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
            
            $scoreDraw = new ScoreDraw($this->getUid() . '_score_' . $score->id);
            $scoreDraw->setOrigin($x, $centerY);
            $scoreDraw->setSize($scoreWidth, $scoreHeight);
            $scoreDraw->setBackgroundColor('#4169E1');
            $scoreDraw->setBorderColor('#5B7FE8');
            $scoreDraw->setBorderRadius(10);
            
            // Get image path for this score
            $imagePath = '/storage/scores/' . $score->id . '.png';
            $scoreDraw->setScoreImage($imagePath);
            
            // Use the value from player_score table if available, else 0
            $scoreValue = $playerScore->value ?? 0;
            $scoreDraw->setScoreValue((string) $scoreValue);
            
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
}
