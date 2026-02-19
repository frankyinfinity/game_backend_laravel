<?php

namespace App\Custom\Draw\Complex\Objective;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Models\PhasePlayer;
use App\Models\TargetPlayer;
use App\Helper\Helper;
use Illuminate\Support\Str;

/**
 * PhasePlayerDraw - Draws a phase container with targets in a grid layout
 * Layout like backend: columns (Fasce) horizontally, targets vertically in each column
 */
class PhasePlayerDraw
{
    private string $uid;
    private PhasePlayer $phasePlayer;
    private array $drawItems = [];
    private array $targetDraws = [];
    
    // Position and size
    private int $x = 0;
    private int $y = 0;
    private int $headerHeight = 45;
    private int $columnSpacing = 20;
    private int $targetSpacing = 20;
    private int $padding = 10;
    private int $targetWidth = 120;  // Wider for rectangular targets
    private int $targetHeight = 80;   // Shorter for rectangular targets
    
    // Colors - light gray containers with dark gray borders
    private array $stateColors = [
        PhasePlayer::STATE_LOCKED => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#666666',
            'accent' => '#a0a0a0',
        ],
        PhasePlayer::STATE_UNLOCKED => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#333333',
            'accent' => '#a0a0a0',
        ],
        PhasePlayer::STATE_IN_PROGRESS => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#000000',
            'accent' => '#a0a0a0',
        ],
        PhasePlayer::STATE_COMPLETED => [
            'background' => '#d0d0d0',
            'header' => '#c0c0c0',
            'border' => '#808080',
            'text' => '#000000',
            'accent' => '#a0a0a0',
        ],
    ];
    
    private bool $renderable = true;
    private string $textFontFamily;
    private int $textFontSize = 13;
    private int $calculatedWidth = 0;
    private int $calculatedHeight = 0;
    
    public function __construct(string $uid, PhasePlayer $phasePlayer)
    {
        $this->uid = $uid;
        $this->phasePlayer = $phasePlayer;
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
    
    /**
     * Set target dimensions from parent AgePlayerDraw
     */
    public function setTargetDimensions(int $width, int $height): void
    {
        $this->targetWidth = $width;
        $this->targetHeight = $height;
    }
    
    /**
     * Set spacing values from parent AgePlayerDraw
     */
    public function setSpacing(int $targetSpacing, int $columnSpacing): void
    {
        $this->targetSpacing = $targetSpacing;
        $this->columnSpacing = $columnSpacing;
    }
    
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }
    
    public function getTargetDraws(): array
    {
        return $this->targetDraws;
    }
    
    /**
     * Calculate height based on max height (number of slots from age)
     */
    public function getHeight(): int
    {
        return $this->headerHeight + $this->calculatedHeight + ($this->padding * 2);
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
        $state = $this->phasePlayer->state;
        $colors = $this->stateColors[$state] ?? $this->stateColors[PhasePlayer::STATE_LOCKED];
        
        // First calculate dimensions
        $this->calculateDimensions();
        
        $totalHeight = $this->getHeight();
        $totalWidth = $this->getWidth();
        
        // Main container background
        $container = new Rectangle($this->uid . '_container');
        $container->setOrigin($this->x, $this->y);
        $container->setSize($totalWidth, $totalHeight);
        $container->setColor($colors['background']);
        $container->setBorderColor($colors['border']);
        $container->setBorderRadius(6);
        $container->setRenderable($this->renderable);
        $this->drawItems[] = $container;
        
        // Header background
        $header = new Rectangle($this->uid . '_header');
        $header->setOrigin($this->x, $this->y);
        $header->setSize($totalWidth, $this->headerHeight);
        $header->setColor($colors['header']);
        $header->setBorderRadius(6);
        $header->setRenderable($this->renderable);
        $this->drawItems[] = $header;
        
        // Accent line under header
        $accentLine = new Rectangle($this->uid . '_accent');
        $accentLine->setOrigin($this->x, $this->y + $this->headerHeight - 2);
        $accentLine->setSize($totalWidth, 2);
        $accentLine->setColor($colors['accent']);
        $accentLine->setRenderable($this->renderable);
        $this->drawItems[] = $accentLine;
        
        // Phase name in header
        $phaseName = $this->phasePlayer->name ?? 'Fase';
        $nameText = new Text($this->uid . '_name');
        $nameText->setOrigin($this->x + ($totalWidth / 2), $this->y + ($this->headerHeight / 2));
        $nameText->setCenterAnchor(true);
        $nameText->setText($phaseName);
        $nameText->setColor($colors['text']);
        $nameText->setFontSize($this->textFontSize);
        $nameText->setFontFamily($this->textFontFamily);
        $nameText->setRenderable($this->renderable);
        $this->drawItems[] = $nameText;

        if ($state === PhasePlayer::STATE_LOCKED) {
            $lineWidth = min($totalWidth - 24, max(50, strlen($phaseName) * (int) floor($this->textFontSize * 0.8)));
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
        
        // Build targets in grid layout
        $this->buildTargets();
    }
    
    /**
     * Calculate dimensions based on columns and actual targets
     * Minimum width is based on the title text
     * Height is based on the maximum slot number that has a target
     */
    private function calculateDimensions(): void
    {
        $phaseColumns = $this->phasePlayer->phaseColumnPlayers;
        $numColumns = $phaseColumns->count();
        
        // Calculate minimum width based on title
        $titleText = $this->phasePlayer->name ?? 'Fase';
        $titleWidth = strlen($titleText) * 9 + ($this->padding * 2); // approx 9px per char
        
        // Width: columns * targetWidth + spacing
        $targetsWidth = ($numColumns * $this->targetWidth) 
            + (($numColumns - 1) * $this->columnSpacing) 
            + ($this->padding * 2);
        
        // Use the larger of title width or targets width
        $this->calculatedWidth = max($titleWidth, $targetsWidth);
        
        // Calculate actual max slot that has a target
        $maxSlot = 0;
        foreach ($phaseColumns as $phaseColumnPlayer) {
            $targets = $phaseColumnPlayer->targetPlayers;
            foreach ($targets as $target) {
                if ($target->slot > $maxSlot) {
                    $maxSlot = $target->slot;
                }
            }
        }
        
        // Height based on actual targets (maxSlot + 1 because slots start at 0)
        $actualHeight = $maxSlot + 1;
        if ($actualHeight < 1) {
            $actualHeight = 1; // Minimum height
        }
        
        $this->calculatedHeight = ($actualHeight * $this->targetHeight) 
            + (($actualHeight - 1) * $this->targetSpacing);
    }
    
    private function buildTargets(): void
    {
        $phaseColumns = $this->phasePlayer->phaseColumnPlayers;
        
        $columnX = $this->x + $this->padding;
        
        // Load the click handler JS file
        $jsPathClickTarget = resource_path('js/function/objective/click_target.blade.php');
        $jsContentClickTarget = file_get_contents($jsPathClickTarget);
        $jsContentClickTarget = Helper::setCommonJsCode($jsContentClickTarget, Str::random(20));
        
        foreach ($phaseColumns as $phaseColumnPlayer) {
            // Get all targets for this column and draw them at their slot positions
            $targets = $phaseColumnPlayer->targetPlayers;
            
            foreach ($targets as $target) {
                $targetY = $this->y + $this->headerHeight + $this->padding 
                    + ($target->slot * ($this->targetHeight + $this->targetSpacing));
                
                $targetDraw = new TargetPlayerDraw(
                    $this->uid . '_target_' . $target->id,
                    $target
                );
                $targetDraw->setOrigin($columnX, $targetY);
                $targetDraw->setSize($this->targetWidth, $this->targetHeight);
                $targetDraw->setRenderable($this->renderable);
                
                // Set click handler to show panel
                $targetDraw->setOnClick($jsContentClickTarget);
                
                $targetDraw->build();
                
                $this->targetDraws[] = $targetDraw;
                $this->drawItems = array_merge($this->drawItems, $targetDraw->getDrawItems());
            }
            
            $columnX += $this->targetWidth + $this->columnSpacing;
        }
    }
}
