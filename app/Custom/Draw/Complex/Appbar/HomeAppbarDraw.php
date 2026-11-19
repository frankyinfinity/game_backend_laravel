<?php

namespace App\Custom\Draw\Complex\Appbar;

use App\Custom\Draw\Complex\AppbarDraw;
use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Complex\GroupScoreDraw;
use App\Custom\Draw\Complex\ModalDraw;
use App\Custom\Draw\Complex\Objective\ObjectiveTreeDraw;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Custom\Draw\Complex\SidebarDraw;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Colors;
use App\Helper\Helper;
use App\Models\GroupScore;
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
        $jsContentOnClickMenu = str_replace('__PLAYER_ID__', (string) $this->player->id, $jsContentOnClickMenu);

        // Get player container for ws_port
        $playerContainer = \App\Models\Container::query()
            ->where('parent_type', \App\Models\Container::PARENT_TYPE_PLAYER)
            ->where('parent_id', $this->player->id)
            ->first();
        $playerWsPort = $playerContainer ? $playerContainer->ws_port : null;
        $jsContentOnClickMenu = str_replace('__PLAYER_WS_PORT__', $playerWsPort ?? 'null', $jsContentOnClickMenu);

        // Add gateway base URL
        $gatewayBaseUrl = 'ws://' . config('remote_docker.docker_host_ip') . ':' . config('remote_docker.websocket_gateway_port', 9001) . '/?port=';
        $jsContentOnClickMenu = str_replace('__gateway_base__', $gatewayBaseUrl, $jsContentOnClickMenu);

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
     * Shows a GroupScoreDraw (circle with the player's score count) that
     * opens a modal containing all the ScoreDraw grouped by GroupScore.
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

        $modalUid = 'group_scores_modal_' . $this->player->id;

        // JS che apre la modal degli score al click sul cerchio
        $jsPathOpenModal = resource_path('js/function/modal/click_open_modal.blade.php');
        $jsOpenModal = file_get_contents($jsPathOpenModal);
        $jsOpenModal = str_replace('__MODAL_UID__', $modalUid, $jsOpenModal);
        $jsOpenModal = Helper::setCommonJsCode($jsOpenModal, Str::random(20));

        // Cerchio con il numero degli score del player (centrato nella center section 400..1520)
        $groupScoreDraw = new GroupScoreDraw('player_' . $this->player->id . '_group_score');
        $groupScoreDraw->setOrigin(930, 10);
        $groupScoreDraw->setRadius(30);
        $groupScoreDraw->setCountValue($playerScores->sum('value'));
        $groupScoreDraw->setOnClick($jsOpenModal);
        $groupScoreDraw->build();

        foreach ($groupScoreDraw->getDrawItems() as $drawItem) {
            $this->addCenterElement($drawItem);
        }

        // Modal con tutti gli ScoreDraw raggruppati per gruppo
        $this->addGroupScoresModal($modalUid, $playerScores);

        // Add objectives modal (button is now in sidebar)
        $modalUid = 'objective_modal_' . $this->player->id;
        $this->addObjectiveModal($modalUid);
    }

    /**
     * Modal contenente gli ScoreDraw del player raggruppati per GroupScore.
     * Gli ScoreDraw usano gli stessi uid dell'appbar precedente
     * (player_{pid}_score_{sid}_*), quindi vengono aggiornati in tempo reale
     * dal polling websocket esistente.
     */
    private function addGroupScoresModal(string $modalUid, $playerScores): void
    {
        $modal = new ModalDraw($modalUid);
        $modal->setOrigin(20, 90);
        $modal->setSize(760, 560);
        $modal->setTitle('Punteggi');
        $modal->setRenderable(false);

        $scoreWidth = 120;
        $scoreHeight = 50;
        $colSpacing = 140;
        $rowSpacing = 66;
        $perRow = 4;
        $titleHeight = 34;
        $rowGap = 10;

        $addScoreItem = function ($playerScore, int $lx, int $ly) use ($modal, $scoreWidth, $scoreHeight) {
            $score = $playerScore->score;
            if (!$score) {
                return;
            }

            $scoreDraw = new ScoreDraw('player_' . $playerScore->player_id . '_score_' . $playerScore->score_id);
            $scoreDraw->setOrigin($lx, $ly);
            $scoreDraw->setSize($scoreWidth, $scoreHeight);
            $scoreDraw->setTransparentBackground(true);
            $scoreDraw->setBorderColor('#5B7FE8'); // Blue border
            $scoreDraw->setBorderWidth(3);
            $scoreDraw->setBorderRadius(10);
            $scoreDraw->setImageSize(35);
            $scoreDraw->setScoreImage('/storage/scores/' . $score->id . '.png');
            $scoreDraw->setScoreValue((string) ($playerScore->value ?? 0));
            $scoreDraw->setScoreName($score->name ?? 'Score ' . $score->id);
            $scoreDraw->setTextColor('#1A1A1A'); // Dark text on light modal background
            $scoreDraw->setTextFontSize(16);
            $scoreDraw->build();

            foreach ($scoreDraw->getDrawItems() as $drawItem) {
                $json = $drawItem->buildJson();
                $offsetX = isset($json['x']) ? (int) $json['x'] : 0;
                $offsetY = isset($json['y']) ? (int) $json['y'] : 0;
                $modal->addContentItem($drawItem, $offsetX, $offsetY);
            }
        };

        $cursorY = 0;

        $addGroup = function (string $title, string $groupKey, $items) use ($modal, $modalUid, $addScoreItem, &$cursorY, $perRow, $colSpacing, $rowSpacing, $titleHeight, $rowGap) {
            if ($items->isEmpty()) {
                return;
            }

            // Titolo del gruppo
            $titleDraw = new Text($modalUid . '_group_' . $groupKey . '_title');
            $titleDraw->setOrigin(0, $cursorY);
            $titleDraw->setText($title);
            $titleDraw->setColor(Colors::BLACK);
            $titleDraw->setFontSize(20);
            $titleDraw->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $json = $titleDraw->buildJson();
            $modal->addContentItem($titleDraw, (int) $json['x'], (int) $json['y']);

            $cursorY += $titleHeight;

            $i = 0;
            foreach ($items as $playerScore) {
                $lx = ($i % $perRow) * $colSpacing;
                $ly = $cursorY + intdiv($i, $perRow) * $rowSpacing;
                $addScoreItem($playerScore, $lx, $ly);
                $i++;
            }

            $cursorY += (int) (ceil($items->count() / $perRow) * $rowSpacing) + $rowGap;
        };

        // Una sezione per ogni GroupScore, poi gli score senza gruppo
        $groups = GroupScore::orderBy('name')->get();
        foreach ($groups as $group) {
            $items = $playerScores->filter(function ($playerScore) use ($group) {
                return $playerScore->score && $playerScore->score->group_score_id == $group->id;
            });
            $addGroup($group->name, (string) $group->id, $items);
        }

        $ungrouped = $playerScores->filter(function ($playerScore) {
            return $playerScore->score && $playerScore->score->group_score_id === null;
        });
        $addGroup('Senza gruppo', 'none', $ungrouped);

        $modal->build();

        foreach ($modal->getDrawItems() as $drawItem) {
            $this->addCenterElement($drawItem);
        }
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
