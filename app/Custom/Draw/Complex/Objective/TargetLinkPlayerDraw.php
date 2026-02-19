<?php

namespace App\Custom\Draw\Complex\Objective;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Models\TargetLinkPlayer;
use App\Models\TargetPlayer;

/**
 * TargetLinkPlayerDraw - Draws connection lines between targets
 * Inspired by Forge of Empires technology tree connections
 * Links connect from right edge of source to left edge of destination
 */
class TargetLinkPlayerDraw
{
    private string $uid;
    private TargetLinkPlayer $targetLinkPlayer;
    private array $drawItems = [];
    
    // Colors based on target states
    private array $linkColors = [
        'locked_locked' => '#3a3a3a',
        'locked_unlocked' => '#4a6a4a',
        'unlocked_unlocked' => '#4a9e3d',
        'unlocked_in_progress' => '#d4a017',
        'in_progress_completed' => '#2980b9',
        'completed_completed' => '#5dade2',
        'default' => '#666666',
    ];
    
    private bool $renderable = true;
    
    public function __construct(string $uid, TargetLinkPlayer $targetLinkPlayer)
    {
        $this->uid = $uid;
        $this->targetLinkPlayer = $targetLinkPlayer;
    }
    
    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }
    
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }
    
    /**
     * Get the appropriate link color based on connected target states
     */
    private function getLinkColor(): string
    {
        $fromTarget = $this->targetLinkPlayer->fromTargetPlayer;
        $toTarget = $this->targetLinkPlayer->toTargetPlayer;
        
        if (!$fromTarget || !$toTarget) {
            return $this->linkColors['default'];
        }
        
        $fromState = $fromTarget->state;
        $toState = $toTarget->state;
        
        $key = $fromState . '_' . $toState;
        return $this->linkColors[$key] ?? $this->linkColors['default'];
    }
    
    public function build(): void
    {
        $fromTarget = $this->targetLinkPlayer->fromTargetPlayer;
        $toTarget = $this->targetLinkPlayer->toTargetPlayer;
        
        if (!$fromTarget || !$toTarget) {
            return;
        }
        
        // Get positions from stored positions
        $fromPos = TargetPlayerDraw::getPosition($fromTarget->id);
        $toPos = TargetPlayerDraw::getPosition($toTarget->id);
        
        if (!$fromPos || !$toPos) {
            return;
        }
        
        // Use right edge of source and left edge of destination
        $startX = $fromPos['right']['x'];
        $startY = $fromPos['right']['y'];
        $endX = $toPos['left']['x'];
        $endY = $toPos['left']['y'];
        
        // Create the line connecting the two targets
        // For horizontal connections, draw a straight line
        // For diagonal connections, draw an L-shaped path
        $line = new MultiLine($this->uid . '_line');
        
        // Simple direct line
        $line->setPoint($startX, $startY);
        $line->setPoint($endX, $endY);
        
        $line->setColor($this->getLinkColor());
        $line->setRenderable($this->renderable);
        $this->drawItems[] = $line;
    }
}