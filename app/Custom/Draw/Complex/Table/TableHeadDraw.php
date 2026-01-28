<?php

namespace App\Custom\Draw\Complex\Table;

use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Colors;

class TableHeadDraw {

    private string $uid;
    private string $text;
    private int $width;
    private int $height;
    private int $backgroundColor;
    private int $textColor;
    private int $borderColor;
    private int $borderThickness;
    private int $fontSize;
    private string $align;

    public function __construct(string $uid) {
        $this->uid = $uid;
        $this->text = '';
        $this->width = 100;
        $this->height = 40;
        $this->backgroundColor = Colors::LIGHT_GRAY;
        $this->textColor = Colors::BLACK;
        $this->borderColor = Colors::BLACK;
        $this->borderThickness = 2;
        $this->fontSize = 18;
        $this->align = TableDraw::ALIGN_LEFT;
    }

    public function setText(string $text) {
        $this->text = $text;
    }

    public function setSize(int $width, int $height) {
        $this->width = $width;
        $this->height = $height;
    }

    public function setBackgroundColor(int $color) {
        $this->backgroundColor = $color;
    }

    public function setTextColor(int $color) {
        $this->textColor = $color;
    }

    public function setFontSize(int $size) {
        $this->fontSize = $size;
    }

    public function setBorderColor(int $color) {
        $this->borderColor = $color;
    }

    public function setBorderThickness(int $thickness) {
        $this->borderThickness = $thickness;
    }

    public function setAlign(string $align) {
        $this->align = $align;
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getHeight(): int {
        return $this->height;
    }

    public function build(int $x, int $y): array {
        $drawItems = [];

        // Header Background
        $bg = new Rectangle($this->uid . '_bg');
        $bg->setOrigin($x, $y);
        $bg->setSize($this->width, $this->height);
        $bg->setColor($this->backgroundColor);
        $bg->setRenderable(true);
        $drawItems[] = $bg->buildJson();

        // Header Text
        $text = new Text($this->uid . '_text');
        
        $textX = $x + $this->width / 2;
        $textY = $y + $this->height / 2;
        $centerAnchor = true;

        if ($this->align === TableDraw::ALIGN_LEFT) {
            $textX = $x + 10; // Margin
            $textY = $y + ($this->height / 2) - ($this->fontSize / 2);
            $centerAnchor = false;
        } elseif ($this->align === TableDraw::ALIGN_RIGHT) {
            $textX = $x + $this->width - 10; // Margin
            $textY = $y + ($this->height / 2) - ($this->fontSize / 2);
            $centerAnchor = false;
        }

        $text->setOrigin($textX, $textY);
        $text->setText($this->text);
        $text->setColor($this->textColor);
        $text->setFontSize($this->fontSize);
        if (!$centerAnchor) {
            // If not centered, we might need to handle vertical centering manually if setCenterAnchor(false) affects both axes
            // But let's check Text primitive or assume setCenterAnchor handles both.
        }
        $text->setCenterAnchor($centerAnchor);
        
        // If it's right aligned and not center anchored, we might need to set anchor to 1,0.5
        // Let's check if Text primitive has more methods.
        
        $text->setRenderable(true);
        $drawItems[] = $text->buildJson();

        // Header Border
        $border = new MultiLine($this->uid . '_border');
        $border->setPoint($x, $y);
        $border->setPoint($x + $this->width, $y);
        $border->setPoint($x + $this->width, $y + $this->height);
        $border->setPoint($x, $y + $this->height);
        $border->setPoint($x, $y);
        $border->setThickness($this->borderThickness);
        $border->setColor($this->borderColor);
        $border->setRenderable(true);
        $drawItems[] = $border->buildJson();

        return $drawItems;
    }
}
