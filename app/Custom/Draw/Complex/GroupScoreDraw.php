<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;

class GroupScoreDraw {

    private $uid;
    public function __construct($uid) {
        $this->uid = $uid;
        $this->textFontFamily = Helper::DEFAULT_FONT_FAMILY;
        $this->textFontSize = Helper::DEFAULT_FONT_SIZE;
        $this->renderable = true;
        $this->textColor = '#FFFFFF'; // White text by default
    }

    private array $drawItems = [];
    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    private $x;
    private $y;
    private int $radius = 30;
    private $countValue = 0;
    private $circleColor = 0x5B7FE8;
    private $textColor;
    private string $tooltipText = 'Punteggi';
    private ?array $tooltipScores = null;
    private ?string $onClickJs = null;
    private string $textFontFamily;
    private int $textFontSize;
    private $renderable;

    /**
     * Origin is the top-left corner of the circle bounding box.
     */
    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setRadius(int $radius): void
    {
        $this->radius = $radius;
    }

    public function setCountValue($value): void
    {
        $this->countValue = $value;
    }

    public function setCircleColor($color): void
    {
        $this->circleColor = $color;
    }

    public function setTextColor($color): void
    {
        $this->textColor = $color;
    }

    public function setTooltipText(string $text): void
    {
        $this->tooltipText = $text;
    }

    /**
     * Scores shown in the tooltip as "[group] name: value".
     * Each item: ['score_id' => int, 'name' => string, 'group' => string, 'value' => int|string]
     * The frontend rebuilds the text live using the latest polled values.
     */
    public function setTooltipScores(array $items): void
    {
        $this->tooltipScores = $items;
    }

    public function setTextFontSize(int $value): void
    {
        $this->textFontSize = $value;
    }

    public function setTextFontFamily(string $value): void
    {
        $this->textFontFamily = $value;
    }

    public function setOnClick(?string $js): void
    {
        $this->onClickJs = $js;
    }

    public function setRenderable($renderable) {
        $this->renderable = $renderable;
    }

    public function build() {

        $cx = $this->x + $this->radius;
        $cy = $this->y + $this->radius;

        // Circle
        $circle = new Circle($this->uid . '_circle');
        $circle->setOrigin($cx, $cy);
        $circle->setRadius($this->radius);
        $circle->setColor($this->circleColor);
        $circle->setRenderable($this->renderable);
        $tooltipText = $this->tooltipText;
        if (!empty($this->tooltipScores)) {
            $circle->addAttributes('tooltip_scores', $this->tooltipScores);
            $lines = array_map(function ($s) {
                $group = $s['group'] ?? '';
                $prefix = $group !== '' ? '[' . $group . '] ' : '';
                return $prefix . ($s['name'] ?? '') . ': ' . ($s['value'] ?? 0);
            }, $this->tooltipScores);
            $tooltipText = implode("\n", $lines);
        }
        $circle->addAttributes('tooltip_text', $tooltipText);
        if ($this->onClickJs !== null) {
            $circle->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $this->onClickJs);
        }
        $this->drawItems[] = $circle;

        // Count text, centered in the circle
        $text = new Text($this->uid . '_text');
        $text->setCenterAnchor(true);
        $text->setFontFamily($this->textFontFamily);
        $text->setFontSize($this->textFontSize);
        $text->setOrigin($cx, $cy);
        $text->setText((string) $this->countValue);
        $text->setColor($this->textColor);
        $text->setRenderable($this->renderable);
        if ($this->onClickJs !== null) {
            $text->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $this->onClickJs);
        }
        $this->drawItems[] = $text;
    }
}
