<?php

namespace App\Custom\Draw\Complex\Objective;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Models\AgePlayer;
use App\Helper\Helper;

/**
 * AgePlayerDraw - Draws an age container with phases arranged horizontally
 * Matrioska style - all phases have the same height based on max slots
 * Width is the sum of all phase widths
 */
class AgePlayerDraw
{
    private string $uid;
    private AgePlayer $agePlayer;
    private array $drawItems = [];
    private array $phaseDraws = [];
    
    // Position and size
    private int $x = 0;
    private int $y = 0;
    private int $headerHeight = 50;
    private int $phaseSpacing = 30;  // Spacing between phases
    private int $padding = 12;
    
    // Target dimensions (must match PhasePlayerDraw) - rectangular
    private int $targetWidth = 120;  // Wider for rectangular targets
    private int $targetHeight = 80;   // Shorter for rectangular targets
    private int $targetSpacing = 20;
private int $columnSpacing = 20;
    
    // Colors - light gray containers with dark gray borders
    private array $stateColors = [
        AgePlayer::STATE_LOCKED => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#666666',
            'accent' => '#a0a0a0',
        ],
        AgePlayer::STATE_UNLOCKED => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#333333',
            'accent' => '#a0a0a0',
        ],
        AgePlayer::STATE_COMPLETED => [
            'background' => '#9dad9f',
            'header' => '#8f9f91',
            'border' => '#6f7f72',
            'text' => '#1f2d23',
            'accent' => '#7f8f82',
        ],
    ];
    
    private bool $renderable = true;
    private string $textFontFamily;
    private int $textFontSize = 14;
    private int $calculatedWidth = 0;
    private int $maxPhaseHeight = 3;
    
    public function __construct(string $uid, AgePlayer $agePlayer)
    {
        $this->uid = $uid;
        $this->agePlayer = $agePlayer;
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
    
    public function getPhaseDraws(): array
    {
        return $this->phaseDraws;
    }
    
    /**
     * Calculate height based on actual targets (max slot across all phases)
     */
    public function getHeight(): int
    {
        // Phase header + targets area + padding
        $phaseHeaderHeight = 45;
        $phasePadding = 10;
        
        // Calculate actual max slot from targets
        $maxSlot = 0;
        $phases = $this->agePlayer->phasePlayers;
        foreach ($phases as $phase) {
            foreach ($phase->phaseColumnPlayers as $column) {
                foreach ($column->targetPlayers as $target) {
                    if ($target->slot > $maxSlot) {
                        $maxSlot = $target->slot;
                    }
                }
            }
        }
        
        // Height based on actual targets (maxSlot + 1 because slots start at 0)
        $actualHeight = $maxSlot + 1;
        if ($actualHeight < 1) {
            $actualHeight = 1; // Minimum height
        }
        
        $targetsHeight = ($actualHeight * $this->targetHeight) 
            + (($actualHeight - 1) * $this->targetSpacing);
        
        $phaseTotalHeight = $phaseHeaderHeight + $targetsHeight + ($phasePadding * 2);
        
        return $this->headerHeight + $phaseTotalHeight + ($this->padding * 2);
    }
    
    public function getWidth(): int
    {
        return $this->calculatedWidth;
    }
    
    public function getX(): int
    {
        return $this->x;
    }
    
    public function getY(): int
    {
        return $this->y;
    }
    
    public function build(): void
    {
        $state = $this->agePlayer->state;
        $colors = $this->stateColors[$state] ?? $this->stateColors[AgePlayer::STATE_LOCKED];
        
        // First calculate phases dimensions (without adding to drawItems)
        $this->calculatePhasesDimensions();
        
        $totalHeight = $this->getHeight();
        $totalWidth = $this->getWidth();
        
        // Main container background
        $container = new Rectangle($this->uid . '_container');
        $container->setOrigin($this->x, $this->y);
        $container->setSize($totalWidth, $totalHeight);
        $container->setColor($colors['background']);
        $container->setBorderColor($colors['border']);
        $container->setBorderRadius(8);
        $container->setRenderable($this->renderable);
        $this->drawItems[] = $container;
        
        // Header background
        $headerBg = new Rectangle($this->uid . '_header_bg');
        $headerBg->setOrigin($this->x, $this->y);
        $headerBg->setSize($totalWidth, $this->headerHeight);
        $headerBg->setColor($colors['header']);
        $headerBg->setBorderRadius(8);
        $headerBg->setRenderable($this->renderable);
        $this->drawItems[] = $headerBg;
        
        // Accent line under header
        $accentLine = new Rectangle($this->uid . '_accent');
        $accentLine->setOrigin($this->x, $this->y + $this->headerHeight - 3);
        $accentLine->setSize($totalWidth, 3);
        $accentLine->setColor($colors['accent']);
        $accentLine->setRenderable($this->renderable);
        $this->drawItems[] = $accentLine;
        
        // Age name in header
        $ageName = $this->agePlayer->name ?? 'Era ' . $this->agePlayer->order;
        $nameText = new Text($this->uid . '_name');
        $nameText->setOrigin($this->x + ($totalWidth / 2), $this->y + ($this->headerHeight / 2));
        $nameText->setCenterAnchor(true);
        $nameText->setText($ageName);
        $nameText->setColor($colors['text']);
        $nameText->setFontSize($this->textFontSize);
        $nameText->setFontFamily($this->textFontFamily);
        $nameText->setRenderable($this->renderable);
        $this->drawItems[] = $nameText;

        if ($state === AgePlayer::STATE_LOCKED) {
            $lineWidth = min($totalWidth - 30, max(60, strlen($ageName) * (int) floor($this->textFontSize * 0.8)));
            $lineY = $this->y + ($this->headerHeight / 2);
            $lineXCenter = $this->x + ($totalWidth / 2);

            $strikeLine = new MultiLine($this->uid . '_name_strike');
            $strikeLine->setPoint($lineXCenter - ($lineWidth / 2), $lineY);
            $strikeLine->setPoint($lineXCenter + ($lineWidth / 2), $lineY);
            $strikeLine->setColor($colors['text']);
            $strikeLine->setThickness(2);
            $strikeLine->setRenderable($this->renderable);
            $this->drawItems[] = $strikeLine;
        }
        
        // Now build phases (after container is drawn)
        $this->buildPhases();
    }
    
    /**
     * Calculate phases width without drawing them
     * Width is the sum of all phase widths
     */
    private function calculatePhasesDimensions(): void
    {
        $phases = $this->agePlayer->phasePlayers
            ->sortBy('order')
            ->values();
        
        // Calculate total width as sum of all phase widths
        $totalPhasesWidth = 0;
        
        foreach ($phases as $phase) {
            $numColumns = $phase->phaseColumnPlayers->count();
            
            // Calculate phase width based on columns
            $titleText = $phase->name ?? 'Fase';
            $titleWidth = strlen($titleText) * 9 + ($this->padding * 2);
            $targetsWidth = ($numColumns * $this->targetWidth) 
                + (($numColumns - 1) * $this->columnSpacing) 
                + ($this->padding * 2);
            $phaseWidth = max($titleWidth, $targetsWidth);
            
            $totalPhasesWidth += $phaseWidth;
        }
        
        // Add spacing between phases
        $totalPhasesWidth += ($phases->count() - 1) * $this->phaseSpacing;
        
        // Total width = padding + phases + padding
        $this->calculatedWidth = $totalPhasesWidth + ($this->padding * 2);
        
        // Also ensure minimum width based on age title
        $ageTitleText = $this->agePlayer->name ?? 'Era ' . $this->agePlayer->order;
        $ageTitleWidth = strlen($ageTitleText) * 10 + ($this->padding * 2);
        $this->calculatedWidth = max($this->calculatedWidth, $ageTitleWidth);
    }
    
    private function buildPhases(): void
    {
        $phases = $this->agePlayer->phasePlayers
            ->sortBy('order')
            ->values();
        
        $phaseX = $this->x + $this->padding;
        $phaseY = $this->y + $this->headerHeight + $this->padding;
        
        foreach ($phases as $phase) {
            $phaseDraw = new PhasePlayerDraw(
                $this->uid . '_phase_' . $phase->id,
                $phase
            );
            
            // Set target dimensions to match
            $phaseDraw->setTargetDimensions($this->targetWidth, $this->targetHeight);
            $phaseDraw->setSpacing($this->targetSpacing, $this->columnSpacing);
            
            $phaseDraw->setOrigin($phaseX, $phaseY);
            $phaseDraw->setRenderable($this->renderable);
            $phaseDraw->build();
            
            $this->phaseDraws[] = $phaseDraw;
            $this->drawItems = array_merge($this->drawItems, $phaseDraw->getDrawItems());
            
            $phaseX += $phaseDraw->getWidth() + $this->phaseSpacing;
        }
    }
}
