<?php

namespace App\Custom\Draw\Complex\Table;

use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Colors;

class TableCellDraw {

    private string $uid;
    private $content;
    private int $width;
    private int $height;
    private int $backgroundColor;
    private int $borderColor;
    private int $borderThickness;
    private string $align;
    private int $textColor;
    private int $fontSize;
    private $formElement;

    public function __construct(string $uid) {
        $this->uid = $uid;
        $this->content = null;
        $this->width = 100;
        $this->height = 40;
        $this->backgroundColor = Colors::WHITE;
        $this->borderColor = Colors::BLACK;
        $this->borderThickness = 1;
        $this->align = TableDraw::ALIGN_LEFT;
        $this->textColor = Colors::BLACK;
        $this->fontSize = 16;
        $this->formElement = null;
    }

    public function setFormElement($element) {
        $this->formElement = $element;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function setSize(int $width, int $height) {
        $this->width = $width;
        $this->height = $height;
    }

    public function setBackgroundColor(int $color) {
        $this->backgroundColor = $color;
    }

    public function setBorderColor(int $color) {
        $this->borderColor = $color;
    }

    public function setAlign(string $align) {
        $this->align = $align;
    }
    
    public function setFontSize(int $size) {
        $this->fontSize = $size;
    }

    public function getWidth(): int {
        return $this->width;
    }

    public function getHeight(): int {
        return $this->height;
    }

    public function build(int $x, int $y): array {
        $drawItems = [];

        // Background
        $bg = new Rectangle($this->uid . '_bg');
        $bg->setOrigin($x, $y);
        $bg->setSize($this->width, $this->height);
        $bg->setColor($this->backgroundColor);
        $bg->setRenderable(true);
        $drawItems[] = $bg->buildJson();

        // Border
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

        // Content
        if ($this->content !== null) {
            if (is_scalar($this->content)) {
                $text = new Text($this->uid . '_text');
                
                $textX = $x + $this->width / 2;
                $textY = $y + ($this->height / 2) - ($this->fontSize / 2);
                $centerAnchor = true;

                if ($this->align === TableDraw::ALIGN_LEFT) {
                    $textX = $x + 10;
                    $centerAnchor = false;
                } elseif ($this->align === TableDraw::ALIGN_RIGHT) {
                    $textX = $x + $this->width - 10;
                    $centerAnchor = false;
                }

                $text->setOrigin($textX, $textY);
                $text->setText($this->content);
                $text->setColor($this->textColor);
                $text->setFontSize($this->fontSize);
                $text->setCenterAnchor($centerAnchor);
                $text->setRenderable(true);
                $drawItems[] = $text->buildJson();
            }
        }
        // Form Element
        if ($this->formElement !== null) {
            $this->formElement->setOrigin($x + 5, $y + 5);
            // Height needs to be adjusted because InputDraw adds a title and padding
            // In InputDraw.php: $y += 25; for the body.
            // So if height of cell is 50, and title takes 25, body height might be too small if we use ($height - 10).
            // But let's let the user decide the height of the cell.
            $this->formElement->setSize($this->width - 10, $this->height - 40);
            $this->formElement->build();
            foreach ($this->formElement->getDrawItems() as $item) {
                $drawItems[] = $item;
            }
        }

        return $drawItems;
    }
}
