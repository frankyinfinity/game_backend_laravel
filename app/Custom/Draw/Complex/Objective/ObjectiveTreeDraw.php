<?php

namespace App\Custom\Draw\Complex\Objective;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Models\Player;
use App\Models\AgePlayer;
use App\Models\TargetLinkPlayer;
use App\Helper\Helper;

/**
 * ObjectiveTreeDraw - Main class to draw the entire objective tree for a player
 * Horizontal layout - all ages arranged in a row, visible together
 * Matrioska style - all containers have the same height based on max slots
 * Width is the sum of all age widths
 */
class ObjectiveTreeDraw
{
    private string $uid;
    private Player $player;
    private array $drawItems = [];
    private array $ageDraws = [];
    private array $linkDraws = [];
    
    // Position and size
    private int $x = 0;
    private int $y = 0;
    private int $ageSpacing = 15;
    private int $padding = 10;
    
    // Calculated height based on max phase slots
    private int $calculatedHeight = 0;
    
    private bool $renderable = true;
    private string $textFontFamily;
    private int $calculatedWidth = 0;
    
    public function __construct(string $uid, Player $player)
    {
        $this->uid = $uid;
        $this->player = $player;
        $this->textFontFamily = Helper::DEFAULT_FONT_FAMILY;
    }
    
    public function setOrigin(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }
    
    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }
    
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }
    
    public function getAgeDraws(): array
    {
        return $this->ageDraws;
    }
    
    public function getLinkDraws(): array
    {
        return $this->linkDraws;
    }
    
    /**
     * Calculate height based on max phase slots
     */
    public function getHeight(): int
    {
        if ($this->calculatedHeight === 0) {
            $this->calculateDimensions();
        }
        return $this->calculatedHeight + ($this->padding * 2);
    }
    
    public function getWidth(): int
    {
        return $this->calculatedWidth;
    }
    
    /**
     * Calculate dimensions before building
     */
    private function calculateDimensions(): void
    {
        $ages = $this->player->agePlayers()->orderBy('order')->get();
        
        // Find max phase height (number of slots) across all phases
        $maxPhaseHeight = 3;
        foreach ($ages as $age) {
            $phases = $age->phasePlayers;
            foreach ($phases as $phase) {
                if ($phase->height > $maxPhaseHeight) {
                    $maxPhaseHeight = $phase->height;
                }
            }
        }
        
        // Calculate height based on max slots
        $ageHeaderHeight = 50;
        $phaseHeaderHeight = 45;
        $targetHeight = 90;
        $targetSpacing = 8;
        $phasePadding = 10;
        $agePadding = 12;
        
        $targetsHeight = ($maxPhaseHeight * $targetHeight) + (($maxPhaseHeight - 1) * $targetSpacing);
        $phaseHeight = $phaseHeaderHeight + $targetsHeight + ($phasePadding * 2);
        
        $this->calculatedHeight = $ageHeaderHeight + $phaseHeight + ($agePadding * 2);
    }
    
    public function build(): void
    {
        // Clear stored target positions for link drawing
        TargetPlayerDraw::clearPositions();
        
        // Calculate dimensions first
        $this->calculateDimensions();
        
        // Draw ages (horizontal layout)
        $this->buildAges();
        
        // Draw links between targets (after all targets have been positioned)
        $this->buildLinks();
    }
    
    private function buildAges(): void
    {
        $ages = $this->player->agePlayers()->orderBy('order')->get();
        
        $currentX = $this->x + $this->padding;
        $currentY = $this->y + $this->padding;
        
        foreach ($ages as $age) {
            $ageDraw = new AgePlayerDraw(
                $this->uid . '_age_' . $age->id,
                $age
            );
            $ageDraw->setOrigin($currentX, $currentY);
            $ageDraw->setRenderable($this->renderable);
            $ageDraw->build();
            
            $this->ageDraws[] = $ageDraw;
            $this->drawItems = array_merge($this->drawItems, $ageDraw->getDrawItems());
            
            $currentX += $ageDraw->getWidth() + $this->ageSpacing;
        }
        
        // Calculate total width as sum of all age widths
        $this->calculatedWidth = $currentX - $this->x - $this->ageSpacing + $this->padding;
    }
    
    private function buildLinks(): void
    {
        // Get all target links for this player
        $links = TargetLinkPlayer::where('player_id', $this->player->id)->get();
        
        foreach ($links as $link) {
            $linkDraw = new TargetLinkPlayerDraw(
                $this->uid . '_link_' . $link->id,
                $link
            );
            $linkDraw->setRenderable($this->renderable);
            $linkDraw->build();
            
            $this->linkDraws[] = $linkDraw;
            $this->drawItems = array_merge($this->drawItems, $linkDraw->getDrawItems());
        }
    }
    
    /**
     * Get statistics about the objective tree
     */
    public function getStatistics(): array
    {
        $ages = $this->player->agePlayers()->orderBy('order')->get();
        
        $stats = [
            'total_ages' => $ages->count(),
            'total_phases' => 0,
            'total_targets' => 0,
            'total_links' => 0,
            'states' => [
                'locked' => 0,
                'unlocked' => 0,
                'in_progress' => 0,
                'completed' => 0,
            ],
        ];
        
        foreach ($ages as $age) {
            $phases = $age->phasePlayers;
            $stats['total_phases'] += $phases->count();
            
            foreach ($phases as $phase) {
                $phaseColumns = $phase->phaseColumnPlayers;
                
                foreach ($phaseColumns as $phaseColumn) {
                    $targets = $phaseColumn->targetPlayers;
                    $stats['total_targets'] += $targets->count();
                    
                    foreach ($targets as $target) {
                        $stats['states'][$target->state] = ($stats['states'][$target->state] ?? 0) + 1;
                    }
                }
            }
        }
        
        $stats['total_links'] = TargetLinkPlayer::where('player_id', $this->player->id)->count();
        
        return $stats;
    }
}
