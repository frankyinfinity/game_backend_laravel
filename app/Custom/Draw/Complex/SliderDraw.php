<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Circle;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;

class SliderDraw
{
    private $uid;
    private array $drawItems = [];
    private $min = 0;
    private $max = 100;
    private $value = 50;
    private $color = 0x000000;
    private $title = '';
    private $x = 0;
    private $y = 0;
    private $width = 300;
    private $height = 60;
    private $onChange = "console.log(value);";

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function setMin($min): void
    {
        $this->min = $min;
    }

    public function setMax($max): void
    {
        $this->max = $max;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function setColor($color): void
    {
        $this->color = $color;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function setOnChange($jsFunction): void
    {
        $this->onChange = $jsFunction;
    }

    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setWidth($width): void
    {
        $this->width = $width;
    }

    public function setSize($width, $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getDrawItemsWithObjectDraw($sessionId): array
    {
        $drawItems = [];
        foreach ($this->drawItems as $item) {
            $objectDraw = new \App\Custom\Manipulation\ObjectDraw($item->buildJson(), $sessionId);
            $drawItems[] = $objectDraw->get();
        }
        return $drawItems;
    }

    public function build(): void
    {
        $this->drawItems = [];

        $trackX = $this->x;
        $trackWidth = $this->width;
        $trackHeight = 8;
        $trackY = $this->y + 20;
        $knobRadius = 10;
        $labelY = $trackY + $trackHeight + 14;

        $range = $this->max - $this->min;
        $ratio = $range > 0 ? ($this->value - $this->min) / $range : 0;
        $knobX = $trackX + (int) floor($ratio * $trackWidth);
        $knobY = $trackY + (int) floor($trackHeight / 2);

        // Title above bar (left aligned)
        if ($this->title !== '') {
            $titleText = new Text($this->uid . '_title');
            $titleText->setCenterAnchor(false);
            $titleText->setOrigin($this->x, $this->y);
            $titleText->setText($this->title);
            $titleText->setColor(0x000000);
            $titleText->setFontSize(14);
            $titleText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $titleText->setRenderable(true);
            $this->drawItems[] = $titleText;
        }

        // Track background
        $trackBg = new Rectangle($this->uid . '_track_bg');
        $trackBg->setOrigin($trackX, $trackY);
        $trackBg->setSize($trackWidth, $trackHeight);
        $trackBg->setColor(0xDDDDDD);
        $trackBg->setBorderRadius(4);
        $trackBg->setRenderable(true);
        $this->drawItems[] = $trackBg;

        // Track filled part
        $fillWidth = (int) floor($ratio * $trackWidth);
        if ($fillWidth > 0) {
            $trackFill = new Rectangle($this->uid . '_track_fill');
            $trackFill->setOrigin($trackX, $trackY);
            $trackFill->setSize($fillWidth, $trackHeight);
            $trackFill->setColor($this->color);
            $trackFill->setBorderRadius(4);
            $trackFill->setRenderable(true);
            $this->drawItems[] = $trackFill;
        }

        // Knob (circle)
        $knob = new Circle($this->uid . '_knob');
        $knob->setOrigin($knobX, $knobY);
        $knob->setRadius($knobRadius);
        $knob->setColor($this->color);
        $knob->setRenderable(true);

        $jsDrag = "window.__disableGlobalPan = true;" .
            "var knob = shape;" .
            "var trackBg = shapes['" . $this->uid . "_track_bg'];" .
            "var trackFill = shapes['" . $this->uid . "_track_fill'];" .
            "if (trackBg) {" .
            "    var trackX = trackBg.x;" .
            "    var trackWidth = trackBg.width;" .
            "    var startGlobalX = event.global.x;" .
            "    var knobStartX = knob.x;" .
            "    function onMove(ev) {" .
            "        var newX = knobStartX + (ev.global.x - startGlobalX);" .
            "        if (newX < trackX) newX = trackX;" .
            "        if (newX > trackX + trackWidth) newX = trackX + trackWidth;" .
            "        knob.x = newX;" .
            "        if (trackFill) { trackFill.width = Math.max(0, newX - trackX); }" .
            "        var ratio = (newX - trackX) / trackWidth;" .
            "        var value = " . $this->min . " + Math.round(ratio * " . ($this->max - $this->min) . ");" .
            "        " . $this->onChange . ";" .
            "    }" .
            "    function onUp() {" .
            "        app.stage.off('pointermove', onMove);" .
            "        app.stage.off('pointerup', onUp);" .
            "        app.stage.off('pointerupoutside', onUp);" .
            "        window.__disableGlobalPan = false;" .
            "    }" .
            "    app.stage.on('pointermove', onMove);" .
            "    app.stage.on('pointerup', onUp);" .
            "    app.stage.on('pointerupoutside', onUp);" .
            "} else {" .
            "    window.__disableGlobalPan = false;" .
            "}";

        $knob->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsDrag);
        $this->drawItems[] = $knob;

        // Min label below bar left
        $minText = new Text($this->uid . '_min');
        $minText->setCenterAnchor(true);
        $minText->setOrigin($this->x + 4, $labelY);
        $minText->setText((string) $this->min);
        $minText->setColor(0x000000);
        $minText->setFontSize(14);
        $minText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $minText->setRenderable(true);
        $this->drawItems[] = $minText;

        // Max label below bar right
        $maxText = new Text($this->uid . '_max');
        $maxText->setCenterAnchor(true);
        $maxText->setOrigin($this->x + $this->width - 4, $labelY);
        $maxText->setText((string) $this->max);
        $maxText->setColor(0x000000);
        $maxText->setFontSize(14);
        $maxText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $maxText->setRenderable(true);
        $this->drawItems[] = $maxText;
    }
}
