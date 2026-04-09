<?php

namespace App\Custom\Draw\Complex;

use App\Models\EntityChimicalElement;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\Line;
use App\Custom\Colors;

class BarChimicalElementDraw
{
    private const BAR_WIDTH = 300;
    private const BAR_HEIGHT = 40;
    private const BORDER_RADIUS = 20;

    private EntityChimicalElement $entityChimicalElement;
    private float $x = 0;
    private float $y = 0;
    private bool $renderable = true;

    private array $drawItems = [];

    public function __construct(EntityChimicalElement $entityChimicalElement)
    {
        $this->entityChimicalElement = $entityChimicalElement;
    }

    public function setOrigin(float $x, float $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->build();
    }

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function getDrawItems(): array
    {
        if (empty($this->drawItems)) {
            $this->build();
        }
        return $this->drawItems;
    }

    public function build(): void
    {
        $this->drawItems = [];

        $playerRuleChimicalElement = $this->entityChimicalElement->playerRuleChimicalElement;
        if (!$playerRuleChimicalElement) {
            return;
        }

        $title = $playerRuleChimicalElement->title ?? 'Elemento';
        $value = (int) $this->entityChimicalElement->value;
        $min = (int) $playerRuleChimicalElement->min;
        $max = (int) $playerRuleChimicalElement->max;
        $range = $max - $min;
        if ($range <= 0) {
            $range = 1;
        }

        $uid = 'bar_chimical_element_' . $this->entityChimicalElement->id;

        $titleText = new Text($uid . '_title');
        $titleText->setText($title);
        $titleText->setOrigin($this->x + (self::BAR_WIDTH / 2), $this->y - 18);
        $titleText->setFontSize(16);
        $titleText->setColor(Colors::BLACK);
        $titleText->setCenterAnchor(true);
        $titleText->setRenderable($this->renderable);
        $this->drawItems[] = $titleText;

        $glassBorder = new Rectangle($uid . '_glass_border');
        $glassBorder->setOrigin($this->x, $this->y);
        $glassBorder->setSize(self::BAR_WIDTH, self::BAR_HEIGHT);
        $glassBorder->setColor(Colors::WHITE);
        $glassBorder->setBorderColor(Colors::DARK_GRAY);
        $glassBorder->setThickness(3);
        $glassBorder->setBorderRadius(self::BORDER_RADIUS);
        $glassBorder->setRenderable($this->renderable);
        $this->drawItems[] = $glassBorder;

        $glassInner = new Rectangle($uid . '_glass_inner');
        $glassInner->setOrigin($this->x + 3, $this->y + 3);
        $glassInner->setSize(self::BAR_WIDTH - 6, self::BAR_HEIGHT - 6);
        $glassInner->setColor(Colors::SILVER);
        $glassInner->setBorderRadius(self::BORDER_RADIUS - 2);
        $glassInner->setRenderable($this->renderable);
        $this->drawItems[] = $glassInner;

        $details = $playerRuleChimicalElement->details()->orderBy('min')->get();

        foreach ($details as $detail) {
            $leftPercent = (($detail->min - $min) / $range) * 100;
            $widthPercent = (($detail->max - $detail->min) / $range) * 100;

            if ($leftPercent < 0) {
                $leftPercent = 0;
            }
            if ($widthPercent < 0) {
                $widthPercent = 0;
            }
            if ($leftPercent + $widthPercent > 100) {
                $widthPercent = 100 - $leftPercent;
            }

            $segmentX = $this->x + 3 + (($leftPercent / 100) * (self::BAR_WIDTH - 6));
            $segmentWidth = ($widthPercent / 100) * (self::BAR_WIDTH - 6);
            $segmentHeight = self::BAR_HEIGHT - 6;

            $segment = new Rectangle($uid . '_segment_' . $detail->id);
            $segment->setOrigin($segmentX, $this->y + 3);
            $segment->setSize(max(1, $segmentWidth), $segmentHeight);
            $segment->setColor($detail->color ? $this->hexToColor($detail->color) : Colors::LIGHT_GRAY);
            $segment->setBorderRadius(0);
            $segment->setRenderable($this->renderable);

            $tooltipText = $this->buildTooltipText($detail);
            $segment->addAttributes('tooltip_text', $tooltipText);

            $this->drawItems[] = $segment;

            if ($segmentWidth > 30) {
                $rangeText = new Text($uid . '_segment_text_' . $detail->id);
                $rangeText->setText("{$detail->min}-{$detail->max}");
                $rangeText->setOrigin($segmentX + ($segmentWidth / 2), $this->y + (self::BAR_HEIGHT / 2));
                $rangeText->setFontSize(10);
                $rangeText->setColor(Colors::WHITE);
                $rangeText->setCenterAnchor(true);
                $rangeText->setRenderable($this->renderable);
                $this->drawItems[] = $rangeText;
            }
        }

        $percent = $range > 0 ? ($value - $min) / $range : 0;
        $percent = max(0, min(1, $percent));

        $indicatorX = $this->x + 3 + (($percent) * (self::BAR_WIDTH - 6));
        $indicatorY1 = $this->y + 4;
        $indicatorY2 = $this->y + self::BAR_HEIGHT - 4;

        $line = new Rectangle($uid . '_line');
        $line->setOrigin($indicatorX - 1, $indicatorY1);
        $line->setSize(2, $indicatorY2 - $indicatorY1);
        $line->setColor(Colors::BLACK);
        $line->setRenderable($this->renderable);
        $line->addAttributes('tooltip_text', "Valore attuale: {$value}");
        $this->drawItems[] = $line;

        $valueText = new Text($uid . '_value');
        $valueText->setText((string) $value);
        $valueText->setOrigin($indicatorX, $this->y + self::BAR_HEIGHT + 10);
        $valueText->setFontSize(12);
        $valueText->setColor(Colors::BLACK);
        $valueText->setCenterAnchor(true);
        $valueText->setRenderable($this->renderable);
        $this->drawItems[] = $valueText;

        $minText = new Text($uid . '_min');
        $minText->setText((string) $min);
        $minText->setOrigin($this->x + 10, $this->y + self::BAR_HEIGHT + 10);
        $minText->setFontSize(12);
        $minText->setColor(Colors::DARK_GRAY);
        $minText->setRenderable($this->renderable);
        $this->drawItems[] = $minText;

        $maxText = new Text($uid . '_max');
        $maxText->setText((string) $max);
        $maxText->setOrigin($this->x + self::BAR_WIDTH - 10, $this->y + self::BAR_HEIGHT + 10);
        $maxText->setFontSize(12);
        $maxText->setColor(Colors::DARK_GRAY);
        $maxText->setCenterAnchor(true);
        $maxText->setRenderable($this->renderable);
        $this->drawItems[] = $maxText;
    }

    private function hexToColor(string $hex): int
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return ($r << 16) | ($g << 8) | $b;
        }
        return Colors::LIGHT_GRAY;
    }

    private function buildTooltipText($detail): string
    {
        $tooltip = "Range: {$detail->min} - {$detail->max}\n";

        $effects = $detail->effects;
        if ($effects->isNotEmpty()) {
            $tooltip .= "Effetti:\n";
            foreach ($effects as $effect) {
                $typeName = $effect->type === 1 ? 'Fisso' : 'A tempo';
                $tooltip .= "  - {$typeName}: {$effect->value}\n";
            }
        }

        return rtrim($tooltip);
    }
}
