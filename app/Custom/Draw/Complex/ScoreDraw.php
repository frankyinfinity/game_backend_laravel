<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectCache;
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
    private $backgroundColor;
    private $borderColor;
    private $textColor;
    private $borderRadius;
    private string $textFontFamily;
    private int $textFontSize;

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

    public function setBackgroundColor($color): void
    {
        $this->backgroundColor = $color;
    }

    public function setBorderColor($color): void
    {
        $this->borderColor = $color;
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

        // Rounded Rectangle as background
        $rect = new Primitive\Rectangle($uid . '_rect');
        $rect->setSize($width, $height);
        $rect->setOrigin($x, $y);
        $rect->setColor($backgroundColor);
        $rect->setBorderColor($borderColor);
        $rect->setBorderRadius($borderRadius);
        $rect->setRenderable($this->renderable);

        $this->drawItems[] = $rect;

        // Calculate horizontal layout: image on left, text on right
        $padding = 10;
        $imageSize = min($height - ($padding * 2), 40);
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

    public function updateValue($newValue, $sessionId): array
    {
        $this->scoreValue = $newValue;
        
        // Load existing properties from cache
        $cachedText = ObjectCache::find($sessionId, $this->uid . '_text');
        
        if (!$cachedText) {
            throw new \Exception("Score text not found in cache. Make sure to call build() first.");
        }
        
        $operations = [];
        
        // Update the text with new score value
        $operations[] = [
            'type' => 'update',
            'uid' => $this->uid . '_text',
            'attributes' => [
                'text' => (string)$this->scoreValue
            ]
        ];
        
        return $operations;
    }
}
