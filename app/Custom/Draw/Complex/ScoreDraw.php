<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;

class ScoreDraw {

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

    private $width;
    private $height;
    private $x;
    private $y;
    private $scoreValue;
    private $scoreImage;
    private $scoreName;
    private $backgroundColor;
    private $borderColor;
    private $borderWidth;
    private $textColor;
    private $borderRadius;
    private string $textFontFamily;
    private int $textFontSize;
    private bool $transparentBackground = false;
    private ?int $imageSize = null;

    public function setSize($width, $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setScoreValue($value): void
    {
        $this->scoreValue = $value;
    }

    public function setScoreImage($imageSrc): void
    {
        $this->scoreImage = $imageSrc;
    }

    public function setScoreName($name): void
    {
        $this->scoreName = $name;
    }

    public function setBackgroundColor($color): void
    {
        $this->backgroundColor = $color;
    }

    public function setBorderColor($color): void
    {
        $this->borderColor = $color;
    }

    public function setBorderWidth($width): void
    {
        $this->borderWidth = $width;
    }

    public function setTextColor($color): void
    {
        $this->textColor = $color;
    }

    public function setBorderRadius($radius): void
    {
        $this->borderRadius = $radius;
    }

    public function setTextFontSize(int $value): void
    {
        $this->textFontSize = $value;
    }

    public function setTextFontFamily(string $value): void
    {
        $this->textFontFamily = $value;
    }

    public function setTransparentBackground(bool $value): void
    {
        $this->transparentBackground = $value;
    }

    public function setImageSize(?int $size): void
    {
        $this->imageSize = $size;
    }

    private $renderable;
    public function setRenderable($renderable) {
        $this->renderable = $renderable;
    }

    public function build() {

        $uid = $this->uid;
        $width = $this->width;
        $height = $this->height;
        $x = $this->x;
        $y = $this->y;
        $scoreValue = $this->scoreValue;
        $scoreImage = $this->scoreImage;
        $backgroundColor = $this->backgroundColor;
        $borderColor = $this->borderColor;
        $textColor = $this->textColor;
        $borderRadius = $this->borderRadius ?? 10;

        // Background rectangle (transparent or colored)
        $rect = new Primitive\Rectangle($uid . '_rect');
        $rect->setSize($width, $height);
        $rect->setOrigin($x, $y);
        $rect->setColor($backgroundColor);
        $rect->setRenderable($this->renderable);

        // Tooltip: just the score name
        if ($this->scoreName !== null) {
            $rect->addAttributes('tooltip_text', $this->scoreName);
        }

        $this->drawItems[] = $rect;

        // Border using 4 separate rectangles (top, bottom, left, right)
        $borderThickness = $this->borderWidth ?? 1;

        // Top border
        $borderTop = new Primitive\Rectangle($uid . '_border_top');
        $borderTop->setOrigin($x, $y);
        $borderTop->setSize($width, $borderThickness);
        $borderTop->setColor($borderColor);
        $borderTop->setRenderable($this->renderable);
        $this->drawItems[] = $borderTop;

        // Bottom border
        $borderBottom = new Primitive\Rectangle($uid . '_border_bottom');
        $borderBottom->setOrigin($x, $y + $height - $borderThickness);
        $borderBottom->setSize($width, $borderThickness);
        $borderBottom->setColor($borderColor);
        $borderBottom->setRenderable($this->renderable);
        $this->drawItems[] = $borderBottom;

        // Left border
        $borderLeft = new Primitive\Rectangle($uid . '_border_left');
        $borderLeft->setOrigin($x, $y);
        $borderLeft->setSize($borderThickness, $height);
        $borderLeft->setColor($borderColor);
        $borderLeft->setRenderable($this->renderable);
        $this->drawItems[] = $borderLeft;

        // Right border
        $borderRight = new Primitive\Rectangle($uid . '_border_right');
        $borderRight->setOrigin($x + $width - $borderThickness, $y);
        $borderRight->setSize($borderThickness, $height);
        $borderRight->setColor($borderColor);
        $borderRight->setRenderable($this->renderable);
        $this->drawItems[] = $borderRight;

        // Calculate horizontal layout: image on left, text on right
        $padding = 10;
        $imageSize = $this->imageSize ?? min($height - ($padding * 2), 40);
        $textHeight = $this->textFontSize;

        // Image
        $imageX = $x + $padding;
        $imageY = $y + ($height - $imageSize) / 2;
        
        $image = new Image($uid . '_image');
        $image->setSrc($scoreImage);
        $image->setSize($imageSize, $imageSize);
        $image->setOrigin($imageX, $imageY);
        $image->setRenderable($this->renderable);

        $this->drawItems[] = $image;

        // Text (score value) - centered vertically, positioned after image
        $textX = $imageX + $imageSize + $padding;
        $textY = $y + ($height / 2);
        
        $text = new Text($uid . '_text');
        $text->setCenterAnchor(true);
        $text->setFontFamily($this->textFontFamily);
        $text->setFontSize($this->textFontSize);
        $text->setOrigin($textX, $textY);
        $text->setText($scoreValue);
        $text->setColor($textColor);
        $text->setRenderable($this->renderable);

        $this->drawItems[] = $text;
    }
}
