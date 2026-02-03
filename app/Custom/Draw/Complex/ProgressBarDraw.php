<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Colors;
use App\Helper\Helper;

class ProgressBarDraw {

    private $uid;
    private $min = 0;
    private $max = 100;
    private $value = 0;
    private $borderColor = Colors::BLACK;
    private $barColor = Colors::GREEN;
    private $name = '';
    
    private $width = 200;
    private $height = 20;
    private $x = 0;
    private $y = 0;
    private $renderable = true;

    private array $drawItems = [];

    public function __construct($uid) {
        $this->uid = $uid;
    }

    public function setMin($min): void {
        $this->min = $min;
    }

    public function setMax($max): void {
        $this->max = $max;
    }

    public function setValue($value): void {
        $this->value = $value;
    }

    public function updateValue($newValue, $sessionId): array {
        $this->value = $newValue;
        
        // Load existing properties from cache
        $cachedBorder = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_border');
        $cachedText = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_text');
        
        if (!$cachedBorder || !$cachedText) {
            throw new \Exception("ProgressBar not found in cache. Make sure to call build() first.");
        }
        
        // Extract properties from cached objects
        $this->x = $cachedBorder['x'];
        $this->y = $cachedBorder['y'];
        $this->width = $cachedBorder['width'];
        $this->height = $cachedBorder['height'];
        $this->barColor = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_bar')['color'] ?? $this->barColor;
        
        $operations = [];
        
        // 1. Update the label text (name + value)
        // Extract name from cached text (remove the old value part)
        $cachedTextContent = $cachedText['text'];
        if (preg_match('/^(.+?)\s*\(\d+\)$/', $cachedTextContent, $matches)) {
            $this->name = $matches[1];
        }
        
        $operations[] = [
            'type' => 'update',
            'uid' => $this->uid . '_text',
            'attributes' => [
                'text' => $this->name . " (" . $this->value . ")"
            ]
        ];
        
        // 2. Clear and redraw the bar (width changes based on value)
        $operations[] = [
            'type' => 'clear',
            'uid' => $this->uid . '_bar'
        ];
        
        // Calculate new bar dimensions
        // We need min/max - try to extract from range text or use defaults
        $cachedRange = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_range');
        if ($cachedRange && preg_match('/\((\d+)\s*\/\s*(\d+)\)/', $cachedRange['text'], $matches)) {
            $this->min = (int)$matches[1];
            $this->max = (int)$matches[2];
        }
        
        $range = $this->max - $this->min;
        $percent = $range > 0 ? ($this->value - $this->min) / $range : 0;
        $percent = max(0, min(1, $percent));
        $barWidth = ($this->width - 4) * $percent;
        $barHeight = $this->height - 4;
        
        if ($barWidth > 0) {
            $bar = new Primitive\Rectangle($this->uid . '_bar');
            $bar->setSize($barWidth, $barHeight);
            $bar->setOrigin($this->x + 2, $this->y + 2);
            $bar->setColor($this->barColor);
            $bar->setRenderable($this->renderable);
            
            $operations[] = [
                'type' => 'draw',
                'object' => $bar->buildJson()
            ];
        }
        
        return $operations;
    }

    public function setBorderColor($color): void {
        $this->borderColor = $color;
    }

    public function setBarColor($color): void {
        $this->barColor = $color;
    }

    public function setName($name): void {
        $this->name = $name;
    }

    public function setSize($width, $height): void {
        $this->width = $width;
        $this->height = $height;
    }

    public function setOrigin($x, $y): void {
        $this->x = $x;
        $this->y = $y;
    }

    public function setRenderable(bool $renderable): void {
        $this->renderable = $renderable;
    }

    public function getDrawItems(): array {
        return $this->drawItems;
    }

    public function build(): void {
        $this->drawItems = [];

        // 1. Background / Border Rectangle
        // We'll use a main rectangle as the container/border
        $border = new Primitive\Rectangle($this->uid . '_border');
        $border->setSize($this->width, $this->height);
        $border->setOrigin($this->x, $this->y);
        $border->setColor($this->borderColor);
        $border->setThickness(2); // Giving it some thickness to look like a border
        $border->setRenderable($this->renderable);
        $this->drawItems[] = $border;

        // 2. The Progress Bar (Filled part)
        // Calculate width based on value, min, max
        $range = $this->max - $this->min;
        $percent = $range > 0 ? ($this->value - $this->min) / $range : 0;
        $percent = max(0, min(1, $percent)); // Clamp between 0 and 1

        $barWidth = ($this->width - 4) * $percent; // Subtracting a bit for padding inside the border
        $barHeight = $this->height - 4;

        if ($barWidth > 0) {
            $bar = new Primitive\Rectangle($this->uid . '_bar');
            $bar->setSize($barWidth, $barHeight);
            $bar->setOrigin($this->x + 2, $this->y + 2);
            $bar->setColor($this->barColor);
            $bar->setRenderable($this->renderable);
            $this->drawItems[] = $bar;
        }

        // 3. The Name (Label)
        if (!empty($this->name)) {
            $text = new Text($this->uid . '_text');
            $text->setText($this->name . " (" . $this->value . ")");
            $text->setOrigin($this->x, $this->y - 15); // Place it slightly above the bar
            $text->setFontSize(14);
            $text->setColor(Colors::BLACK);
            $text->setRenderable($this->renderable);
            $this->drawItems[] = $text;
        }

        // 4. Min / Max (Centered below the bar)
        $rangeText = new Text($this->uid . '_range');
        $rangeText->setText("(" . $this->min . " / " . $this->max . ")");
        $rangeText->setOrigin($this->x + ($this->width / 2), $this->y + $this->height + 12);
        $rangeText->setFontSize(14);
        $rangeText->setColor(Colors::BLACK);
        $rangeText->setCenterAnchor(true);
        $rangeText->setRenderable($this->renderable);
        $this->drawItems[] = $rangeText;
    }
}
