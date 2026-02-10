<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Colors;
use App\Models\Player;

class AppbarDraw
{
    private $playerId;
    private $sessionId;
    private array $drawItems = [];
    private $uid;
    private $background;
    protected $player;

    // Appbar dimensions
    private const WIDTH = 1920;
    private const HEIGHT = 80;
    private const PADDING = 20;

    // Section widths
    private const LEFT_SECTION_WIDTH = 400;
    private const CENTER_SECTION_WIDTH = 1120;
    private const RIGHT_SECTION_WIDTH = 400;

    public function __construct($playerId, $sessionId)
    {
        \Log::info('AppbarDraw::__construct() called for playerId: ' . $playerId);
        
        $this->playerId = $playerId;
        $this->sessionId = $sessionId;
        $this->uid = 'appbar_' . $this->playerId;
        $this->drawItems = [];
        
        // Get player information
        $this->player = Player::with(['birthPlanet', 'birthRegion', 'user'])
            ->find($this->playerId);
        
        $this->build();
    }

    /**
     * Get the JSON draw items for the appbar
     * 
     * @return array
     */
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    /**
     * Build the drawing components for the appbar
     */
    private function build(): void
    {
        \Log::info('AppbarDraw::build() called');
        
        // Create background
        $this->createBackground();
        
        // Add left section elements
        $this->addLeftSection();
        
        // Add center section elements
        $this->addCenterSection();
        
        // Add right section elements
        $this->addRightSection();
        
        // Add background to draw items first (so it's behind everything)
        array_unshift($this->drawItems, $this->background->buildJson());
    }

    /**
     * Create the background rectangle
     */
    private function createBackground(): void
    {
        $this->background = new Rectangle($this->uid . '_background');
        $this->background->setOrigin(0, 0);
        $this->background->setSize(self::WIDTH, self::HEIGHT);
        $this->background->setColor(0x1A1A1A); // Dark gray (antigravity style)
    }

    /**
     * Add elements to the left section of the appbar
     * Override this method to customize left section
     */
    protected function addLeftSection(): void
    {
        // Left section is empty by default
        // Use addLeftElement() to add elements
    }

    /**
     * Add elements to the center section of the appbar
     * Override this method to customize center section
     */
    protected function addCenterSection(): void
    {
        // Center section is empty by default
        // Override this method to add elements in the center
    }

    /**
     * Add elements to the right section of the appbar
     * Override this method to customize right section
     */
    protected function addRightSection(): void
    {
        // Right section is empty by default
        // Override this method to add elements on the right
    }

    /**
     * Add an element to the left section
     * 
     * @param BasicDraw $element The element to add (Text, Rectangle, etc.)
     */
    protected function addLeftElement(BasicDraw $element): void
    {
        $this->background->addChild($element);
        $this->drawItems[] = $element->buildJson();
    }

    /**
     * Add an element to the center section
     * 
     * @param BasicDraw $element The element to add (Text, Rectangle, etc.)
     */
    protected function addCenterElement(BasicDraw $element): void
    {
        $this->background->addChild($element);
        $this->drawItems[] = $element->buildJson();
    }

    /**
     * Add an element to the right section
     * 
     * @param BasicDraw $element The element to add (Text, Rectangle, etc.)
     */
    protected function addRightElement(BasicDraw $element): void
    {
        $this->background->addChild($element);
        $this->drawItems[] = $element->buildJson();
    }

    /**
     * Helper method to get the X position for center-aligned text
     * 
     * @param int $textWidth Width of the text
     * @return int X position for center alignment
     */
    protected function getCenterX(int $textWidth): int
    {
        return self::LEFT_SECTION_WIDTH + (self::CENTER_SECTION_WIDTH - $textWidth) / 2;
    }

    /**
     * Helper method to get the X position for right-aligned text
     * 
     * @param int $textWidth Width of the text
     * @return int X position for right alignment
     */
    protected function getRightX(int $textWidth): int
    {
        return self::WIDTH - self::PADDING - $textWidth;
    }

    /**
     * Get the player object
     * 
     * @return Player|null
     */
    protected function getPlayer(): ?Player
    {
        return $this->player;
    }

    /**
     * Get the appbar UID
     * 
     * @return string
     */
    protected function getUid(): string
    {
        return $this->uid;
    }
}
