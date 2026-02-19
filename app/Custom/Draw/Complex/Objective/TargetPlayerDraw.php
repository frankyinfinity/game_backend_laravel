<?php

namespace App\Custom\Draw\Complex\Objective;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Complex\ScoreDraw;
use App\Models\TargetPlayer;
use App\Helper\Helper;
use Illuminate\Support\Str;

/**
 * TargetPlayerDraw - Draws a single target/objective node
 * Compact horizontal layout for objectives tree
 */
class TargetPlayerDraw
{
    private string $uid;
    private TargetPlayer $targetPlayer;
    private array $drawItems = [];
    
    // Position and size - rectangular (wider than tall)
    private int $x = 0;
    private int $y = 0;
    private int $width = 120;  // Wider for rectangular shape
    private int $height = 80;  // Shorter for rectangular shape
    
    // Colors based on state - dark colors for targets
    private array $stateColors = [
        TargetPlayer::STATE_LOCKED => [
            'background' => '#2a2a2a',
            'border' => '#1a1a1a',
            'text' => '#666666',
        ],
        TargetPlayer::STATE_UNLOCKED => [
            'background' => '#1a3a1a',
            'border' => '#2d5a27',
            'text' => '#7ed66f',
        ],
        TargetPlayer::STATE_IN_PROGRESS => [
            'background' => '#0a2a4a',
            'border' => '#1a5276',
            'text' => '#5dade2',
        ],
        TargetPlayer::STATE_COMPLETED => [
            'background' => '#0a2a4a',
            'border' => '#1a5276',
            'text' => '#5dade2',
        ],
    ];
    
    private bool $renderable = true;
    private string $textFontFamily;
    private int $textFontSize = 10;  // Increased from 9
    private $onClickFunction = null;
    
    // Store position for link drawing
    private static array $targetPositions = [];
    
    public function __construct(string $uid, TargetPlayer $targetPlayer)
    {
        $this->uid = $uid;
        $this->targetPlayer = $targetPlayer;
        $this->textFontFamily = Helper::DEFAULT_FONT_FAMILY;
    }
    
    public function setOrigin(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }
    
    public function setSize(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }
    
    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }
    
    /**
     * Set the onClick function for the target
     * @param callable $onClickFunction JavaScript function to execute on click
     */
    public function setOnClick($onClickFunction): void
    {
        $this->onClickFunction = $onClickFunction;
    }
    
    /**
     * Build the panel UI for showing target details
     * This creates a panel that appears when the target is clicked
     */
    public function buildPanel(): void
    {
        $panelWidth = 400;
        $costRows = $this->targetPlayer->targetHasScorePlayers()->with('score')->get();
        $costRowHeight = 46;
        $panelHeight = 300 + ($costRows->count() * ($costRowHeight + 8));
        // Position panel relative to the target, offset to the right
        $panelX = $this->x + $this->width + 20; // 20px gap from target
        $panelY = $this->y; // Same vertical position as target
        
        // Panel background - use container uid + _panel so click handler can find it
        $panel = new Rectangle($this->uid . '_container_panel');
        $panel->setOrigin($panelX, $panelY);
        $panel->setSize($panelWidth, $panelHeight);
        $panel->setColor('#ffffff');
        $panel->setBorderColor('#333333');
        $panel->setBorderRadius(8);
        $panel->setRenderable(false); // Hidden by default
        
        // Panel title - add as child of panel
        // UID must end with _panel_title for the JavaScript to find it
        $title = new Text($this->uid . '_container_panel_title');
        $title->setOrigin($panelX + 20, $panelY + 30);
        $title->setText($this->targetPlayer->title ?? 'Obiettivo');
        $title->setFontFamily($this->textFontFamily);
        $title->setFontSize(18);
        $title->setColor('#000000');
        $title->setRenderable(false);
        $panel->addChild($title);
        
        // Panel description - add as child of panel
        // UID must end with _panel_description for the JavaScript to find it
        $description = new Text($this->uid . '_container_panel_description');
        $description->setOrigin($panelX + 20, $panelY + 70);
        $description->setText($this->targetPlayer->description ?? 'Nessuna descrizione disponibile');
        $description->setFontFamily($this->textFontFamily);
        $description->setFontSize(14);
        $description->setColor('#333333');
        $description->setRenderable(false);
        $panel->addChild($description);

        // Cost section label
        $costLabel = new Text($this->uid . '_container_panel_cost_label');
        $costLabel->setOrigin($panelX + 20, $panelY + 115);
        $costLabel->setText('Costo:');
        $costLabel->setFontFamily($this->textFontFamily);
        $costLabel->setFontSize(16);
        $costLabel->setColor('#000000');
        $costLabel->setRenderable(false);
        $panel->addChild($costLabel);

        // Cost rows (ScoreDraw style) from target_has_score_player
        $costStartY = $panelY + 135;
        foreach ($costRows as $index => $costRow) {
            if (!$costRow->score) {
                continue;
            }

            $scoreDraw = new ScoreDraw($this->uid . '_container_panel_cost_score_' . $costRow->score_id);
            $scoreDraw->setOrigin($panelX + 20, $costStartY + ($index * ($costRowHeight + 8)));
            $scoreDraw->setSize($panelWidth - 40, $costRowHeight);
            $scoreDraw->setBackgroundColor('#4169E1');
            $scoreDraw->setBorderColor('#5B7FE8');
            $scoreDraw->setBorderRadius(8);
            $scoreDraw->setScoreImage('/storage/scores/' . $costRow->score_id . '.png');
            $scoreDraw->setScoreValue((string) ($costRow->value ?? 0));
            $scoreDraw->setTextColor('#FFFFFF');
            $scoreDraw->setTextFontSize(14);
            $scoreDraw->setRenderable(false);
            $scoreDraw->build();

            foreach ($scoreDraw->getDrawItems() as $scoreItem) {
                $panel->addChild($scoreItem);
                $this->drawItems[] = $scoreItem;
            }
        }

        if ($this->targetPlayer->state === TargetPlayer::STATE_UNLOCKED) {
            $jsPathStartTarget = resource_path('js/function/objective/start_target.blade.php');
            $jsContentStartTarget = file_get_contents($jsPathStartTarget);
            $jsContentStartTarget = Helper::setCommonJsCode($jsContentStartTarget, Str::random(20));

            $startButton = new Rectangle($this->uid . '_container_panel_start_btn');
            $startButton->setOrigin($panelX + $panelWidth - 56, $panelY + 12);
            $startButton->setSize(34, 34);
            $startButton->setColor('#1E90FF');
            $startButton->setBorderColor('#0B63CE');
            $startButton->setBorderRadius(17);
            $startButton->setRenderable(false);
            $startButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsContentStartTarget);
            $panel->addChild($startButton);
            $this->drawItems[] = $startButton;

            $startIcon = new Text($this->uid . '_container_panel_start_btn_text');
            $startIcon->setOrigin($panelX + $panelWidth - 39, $panelY + 29);
            $startIcon->setCenterAnchor(true);
            $startIcon->setText('>');
            $startIcon->setFontFamily($this->textFontFamily);
            $startIcon->setFontSize(22);
            $startIcon->setColor('#FFFFFF');
            $startIcon->setRenderable(false);
            $panel->addChild($startIcon);
            $this->drawItems[] = $startIcon;
        }
        
        // Add panel and child text objects to draw items.
        // Children must also be sent as standalone draw objects so the frontend can resolve child UIDs.
        $this->drawItems[] = $panel;
        $this->drawItems[] = $title;
        $this->drawItems[] = $description;
        $this->drawItems[] = $costLabel;
    }
    
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }
    
    public function getCenterPosition(): array
    {
        return [
            'x' => $this->x + ($this->width / 2),
            'y' => $this->y + ($this->height / 2),
        ];
    }
    
    public function getUid(): string
    {
        return $this->uid;
    }
    
    public function getTargetPlayer(): TargetPlayer
    {
        return $this->targetPlayer;
    }
    
    public function getWidth(): int
    {
        return $this->width;
    }
    
    public function getHeight(): int
    {
        return $this->height;
    }
    
    public function getX(): int
    {
        return $this->x;
    }
    
    public function getY(): int
    {
        return $this->y;
    }
    
    /**
     * Get the right edge position (for links)
     */
    public function getRightPosition(): array
    {
        return [
            'x' => $this->x + $this->width,
            'y' => $this->y + ($this->height / 2),
        ];
    }
    
    /**
     * Get the left edge position (for links)
     */
    public function getLeftPosition(): array
    {
        return [
            'x' => $this->x,
            'y' => $this->y + ($this->height / 2),
        ];
    }
    
    /**
     * Store position for link drawing
     */
    public static function storePosition(int $targetPlayerId, array $position): void
    {
        self::$targetPositions[$targetPlayerId] = $position;
    }
    
    /**
     * Get stored position for link drawing
     */
    public static function getPosition(int $targetPlayerId): ?array
    {
        return self::$targetPositions[$targetPlayerId] ?? null;
    }
    
    /**
     * Clear stored positions
     */
    public static function clearPositions(): void
    {
        self::$targetPositions = [];
    }
    
    public function build(): void
    {
        // Build the panel first (hidden by default)
        $this->buildPanel();
        
        $state = $this->targetPlayer->state;
        $colors = $this->stateColors[$state] ?? $this->stateColors[TargetPlayer::STATE_LOCKED];
        
        // Main container (rounded square)
        $container = new Rectangle($this->uid . '_container');
        $container->setOrigin($this->x, $this->y);
        $container->setSize($this->width, $this->height);
        $container->setColor($colors['background']);
        $container->setBorderColor($colors['border']);
        $container->setBorderRadius(6);  // Increased from 4
        $container->setRenderable($this->renderable);
        
        // Store target data in attributes for the click handler
        $container->addAttributes('target_title', $this->targetPlayer->title ?? 'Obiettivo');
        $container->addAttributes('target_description', $this->targetPlayer->description ?? 'Nessuna descrizione disponibile');
        $container->addAttributes('target_player_id', $this->targetPlayer->id);
        $container->addAttributes('target_state', $state);
        
        // Add click handler only if set and target is not locked
        if ($this->onClickFunction !== null && $state !== TargetPlayer::STATE_LOCKED) {
            $onClickFunction = $this->onClickFunction;
            $container->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $onClickFunction);
        }
        
        $this->drawItems[] = $container;
        
        // Target title (centered, with padding to stay inside)
        $padding = 6;  // Increased from 4
        $title = new Text($this->uid . '_title');
        $title->setOrigin($this->x + ($this->width / 2), $this->y + ($this->height / 2));
        $title->setCenterAnchor(true);
        // Truncate text to fit within the container with padding
        $maxChars = floor(($this->width - ($padding * 2)) / 7); // approx 7px per char
        $title->setText($this->truncateText($this->targetPlayer->title ?? 'T' . $this->targetPlayer->slot, max(3, $maxChars)));
        $title->setColor($colors['text']);
        $title->setFontSize($this->textFontSize);
        $title->setFontFamily($this->textFontFamily);
        $title->setRenderable($this->renderable);
        $this->drawItems[] = $title;
        
        // Store position for link drawing
        self::storePosition($this->targetPlayer->id, [
            'center' => $this->getCenterPosition(),
            'left' => $this->getLeftPosition(),
            'right' => $this->getRightPosition(),
            'targetPlayer' => $this->targetPlayer,
        ]);
    }
    
    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 1) . '.';
    }
}
