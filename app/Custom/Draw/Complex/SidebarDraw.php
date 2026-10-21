<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Colors;
use App\Helper\Helper;
use Illuminate\Support\Str;

class SidebarDraw
{
    private string $uid;
    private array $drawItems = [];
    private int $playerId;
    private Rectangle $background;

    // Sidebar dimensions
    private const WIDTH = 300;
    private const HEIGHT = 1080; // Full height
    private const BUTTON_WIDTH = 260;
    private const BUTTON_HEIGHT = 40;
    private const BUTTON_SPACING = 20;
    private const START_Y = 100;

    public function __construct(string $uid, int $playerId)
    {
        $this->uid = $uid;
        $this->playerId = $playerId;
        $this->drawItems = [];
        $this->build();
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function build(): void
    {
        // Create background
        $this->createBackground();

        // Add buttons
        $this->addObjectivesButton();
        $this->addLogoutButton();

        // Add background to draw items first (behind everything)
        array_unshift($this->drawItems, $this->background->buildJson());
    }

    public function getBackground(): Rectangle
    {
        return $this->background;
    }

    private function createBackground(): void
    {
        $this->background = new Rectangle($this->uid . '_background');
        $this->background->setOrigin(-300, 80); // Start off-screen to the left, below appbar (appbar height = 80)
        $this->background->setSize(self::WIDTH, self::HEIGHT);
        $this->background->setColor(0x2A2A2A); // Dark gray
        $this->background->setRenderable(false); // Hidden by default
        $this->background->addAttributes('z_index', 25000);
    }

    private function addObjectivesButton(): void
    {
        $objectivesButton = new ButtonDraw($this->uid . '_objectives_button');
        $objectivesButton->setOrigin(20, self::START_Y); // Relative to sidebar (left side)
        $objectivesButton->setSize(self::BUTTON_WIDTH, self::BUTTON_HEIGHT);
        $objectivesButton->setString('Obiettivi');
        $objectivesButton->setColorButton(Colors::BLUE);
        $objectivesButton->setColorString(Colors::WHITE);
        $objectivesButton->setTextFontSize(16);

        $modalUid = 'objective_modal_' . $this->playerId;
        $jsPathOnClickObjectives = resource_path('js/function/modal/click_open_modal.blade.php');
        $jsContentOnClickObjectives = file_get_contents($jsPathOnClickObjectives);
        $jsContentOnClickObjectives = str_replace('__MODAL_UID__', $modalUid, $jsContentOnClickObjectives);
        $jsContentOnClickObjectives = Helper::setCommonJsCode($jsContentOnClickObjectives, Str::random(20));
        $objectivesButton->setOnClick($jsContentOnClickObjectives);
        $objectivesButton->build();

        foreach ($objectivesButton->getDrawItems() as $drawItem) {
            $this->background->addChild($drawItem);
            $json = $drawItem->buildJson();
            // Set z-index higher than container and renderable false by default
            if (isset($json['attributes'])) {
                $json['attributes']['z_index'] = 26000;
                $json['attributes']['renderable'] = false;
            }
            $this->drawItems[] = $json;
        }
    }

    private function addLogoutButton(): void
    {
        $logoutButton = new ButtonDraw($this->uid . '_logout_button');
        $logoutButton->setOrigin(20, self::START_Y + self::BUTTON_HEIGHT + self::BUTTON_SPACING); // Relative to sidebar (left side)
        $logoutButton->setSize(self::BUTTON_WIDTH, self::BUTTON_HEIGHT);
        $logoutButton->setString('Logout');
        $logoutButton->setColorButton(Colors::RED);
        $logoutButton->setColorString(Colors::WHITE);
        $logoutButton->setTextFontSize(16);

        $jsPathOnClickLogout = resource_path('js/function/appbar/on_click_logout.blade.php');
        $jsContentOnClickLogout = file_get_contents($jsPathOnClickLogout);
        $jsContentOnClickLogout = Helper::setCommonJsCode($jsContentOnClickLogout, Str::random(20));
        $logoutButton->setOnClick($jsContentOnClickLogout);
        $logoutButton->build();

        foreach ($logoutButton->getDrawItems() as $drawItem) {
            $this->background->addChild($drawItem);
            $json = $drawItem->buildJson();
            // Set z-index higher than container and renderable false by default
            if (isset($json['attributes'])) {
                $json['attributes']['z_index'] = 26000;
                $json['attributes']['renderable'] = false;
            }
            $this->drawItems[] = $json;
        }
    }
}
