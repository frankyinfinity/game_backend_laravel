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
    private $modifier = null;
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

    public function setModifier($modifier): void {
        $this->modifier = $modifier;
    }

    public function updateValue($newValue, $sessionId): array {
        $this->value = $newValue;
        
        // Load existing properties from cache
        $cachedBorder = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_border');
        if (!$cachedBorder) {
            return [];
        }
        $cachedText = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_text');
        
        // Extract properties from cached objects
        $this->x = $cachedBorder['x'];
        $this->y = $cachedBorder['y'];
        $this->width = $cachedBorder['width'];
        $this->height = $cachedBorder['height'];
        $cachedBar = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_bar');
        if (is_array($cachedBar) && isset($cachedBar['color'])) {
            $this->barColor = $cachedBar['color'];
        }
        
        $operations = [];
        
        // 1. Update the label text (name + value)
        $cachedAttributes = is_array($cachedBorder['attributes'] ?? null) ? $cachedBorder['attributes'] : [];
        $cachedName = $cachedAttributes['progress_name'] ?? null;
        if (is_string($cachedName) && trim($cachedName) !== '') {
            $this->name = trim($cachedName);
        } elseif (is_array($cachedText) && isset($cachedText['text']) && is_string($cachedText['text'])) {
            $cachedTextContent = trim($cachedText['text']);
            if (preg_match('/^(.+?)\s*\([^)]*\)\s*$/', $cachedTextContent, $matches)) {
                $this->name = trim($matches[1]);
            } elseif ($cachedTextContent !== '') {
                $this->name = $cachedTextContent;
            }
        }

        if ($this->name !== '') {
            $labelText = $this->name . " (" . $this->value . ")";
            $operations[] = [
                'type' => 'update',
                'uid' => $this->uid . '_text',
                'attributes' => [
                    'text' => $labelText
                ]
            ];

            if (is_array($cachedText)) {
                $cachedText['text'] = $labelText;
                \App\Custom\Manipulation\ObjectCache::put($sessionId, $cachedText);
            }
        }
        
        // 2. Calculate new bar dimensions
        // We need min/max - try to extract from range text or use defaults
        $cachedRange = \App\Custom\Manipulation\ObjectCache::find($sessionId, $this->uid . '_range');
        if (isset($cachedAttributes['progress_min']) && is_numeric($cachedAttributes['progress_min'])) {
            $this->min = (float) $cachedAttributes['progress_min'];
        }
        if (isset($cachedAttributes['progress_max']) && is_numeric($cachedAttributes['progress_max'])) {
            $this->max = (float) $cachedAttributes['progress_max'];
        }
        if ($cachedRange && preg_match('/\(([-]?\d+(?:\.\d+)?)\s*\/\s*([-]?\d+(?:\.\d+)?)\)/', $cachedRange['text'], $matches)) {
            $this->min = (int)$matches[1];
            $this->max = (int)$matches[2];
        }
        
        $range = $this->max - $this->min;
        $percent = $range > 0 ? ($this->value - $this->min) / $range : 0;
        $percent = max(0, min(1, $percent));
        $barWidth = ($this->width - 4) * $percent;
        $barHeight = $this->height - 4;
        
        // Update the existing bar's width instead of clearing and redrawing.
        // Avoid toggling renderable here: the client UI controls visibility.
        if (is_array($cachedBar)) {
            $operations[] = [
                'type' => 'update',
                'uid' => $this->uid . '_bar',
                'attributes' => [
                    'width' => max(0, $barWidth),
                    'height' => $barHeight
                ]
            ];

            $cachedBar['width'] = max(0, $barWidth);
            $cachedBar['height'] = $barHeight;
            \App\Custom\Manipulation\ObjectCache::put($sessionId, $cachedBar);
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
        $border->setBorderRadius(0);
        $border->setRenderable($this->renderable);
        $border->addAttributes('progress_name', $this->name);
        $border->addAttributes('progress_min', $this->min);
        $border->addAttributes('progress_max', $this->max);
        $this->drawItems[] = $border;

        // 2. The Progress Bar (Filled part)
        // Calculate width based on value, min, max
        $useModifiedRange = ($this->modifier !== null && $this->modifier > 0);
        
        if ($useModifiedRange) {
            $modifiedMax = $this->max + $this->modifier;
            $range = $modifiedMax - $this->min;
            $percent = $range > 0 ? ($this->value - $this->min) / $range : 0;
            $percent = max(0, min(1, $percent));
        } else {
            $range = $this->max - $this->min;
            if ($this->modifier !== null && $this->modifier < 0) {
                $valueToUse = min($this->value, $this->max);
            } else {
                $valueToUse = $this->value;
            }
            $percent = $range > 0 ? ($valueToUse - $this->min) / $range : 0;
            $percent = max(0, min(1, $percent));
        }

        $barWidth = ($this->width - 4) * $percent;
        $barHeight = $this->height - 4;

        $bar = new Primitive\Rectangle($this->uid . '_bar');
        $bar->setSize(max(0, $barWidth), $barHeight);
        $bar->setOrigin($this->x + 2, $this->y + 2);
        $bar->setColor($this->barColor);
        $bar->setBorderRadius(0);
        $bar->setRenderable($this->renderable);
        $bar->addAttributes('progress_name', $this->name);
        $bar->addAttributes('progress_min', $this->min);
        $bar->addAttributes('progress_max', $this->max);
        $this->drawItems[] = $bar;

        // 2b. Modifier Bar (inside the main bar)
        if ($this->modifier !== null && $this->modifier !== 0) {
            $modifierRange = abs($this->modifier);
            $baseRange = $useModifiedRange ? ($this->max + $this->modifier - $this->min) : ($this->max - $this->min);
            $percent = $baseRange > 0 ? $modifierRange / $baseRange : 0;
            $percent = max(0, min(1, $percent));
            
            $modifierBarWidth = ($this->width - 4) * $percent;
            $modifierBarHeight = $this->height - 6;
            $modifierBarX = $this->x + 2 + ($this->width - 4) - $modifierBarWidth;
            $modifierBarY = $this->y + 3;
            
            $modifierBorder = new Primitive\Rectangle($this->uid . '_modifier_bar');
            $modifierBorder->setSize(max(0, $modifierBarWidth), $modifierBarHeight);
            $modifierBorder->setOrigin($modifierBarX, $modifierBarY);
            $modifierBorder->setColor($this->modifier >= 0 ? Colors::FOREST_GREEN : Colors::ORANGE);
            $modifierBorder->setThickness(2);
            $modifierBorder->setBorderRadius(2);
            $modifierBorder->setRenderable($this->renderable);
            $this->drawItems[] = $modifierBorder;

            $modifierText = new Text($this->uid . '_modifier_text');
            $modifierText->setText($this->modifier >= 0 ? '+' : '-');
            $modifierText->setOrigin($modifierBarX + (max(0, $modifierBarWidth) / 2), $modifierBarY + ($modifierBarHeight / 2));
            $modifierText->setFontSize(14);
            $modifierText->setColor(Colors::WHITE);
            $modifierText->setCenterAnchor(true);
            $modifierText->setRenderable($this->renderable);
            $this->drawItems[] = $modifierText;
        }

        // 3. The Name (Label)
        if (!empty($this->name)) {
            $text = new Text($this->uid . '_text');
            $text->setText($this->name . " (" . $this->value . ")");
            $text->setOrigin($this->x, $this->y - 15); // Place it slightly above the bar
            $text->setFontSize(14);
            $text->setColor(Colors::BLACK);
            $text->setRenderable($this->renderable);
            $text->addAttributes('progress_name', $this->name);
            $this->drawItems[] = $text;
        }

        // 4. Min / Max (Centered below the bar)
        $rangeTextStr = "[" . $this->min . " / " . $this->max;
        if ($this->modifier !== null && $this->modifier !== 0) {
            $rangeTextStr .= " (" . ($this->modifier >= 0 ? '+' : '') . $this->modifier . ")";
        }
        $rangeTextStr .= "]";
        
        $rangeText = new Text($this->uid . '_range');
        $rangeText->setText($rangeTextStr);
        $rangeText->setOrigin($this->x + ($this->width / 2), $this->y + $this->height + 12);
        $rangeText->setFontSize(14);
        $rangeText->setColor(Colors::BLACK);
        $rangeText->setCenterAnchor(true);
        $rangeText->setRenderable($this->renderable);
        $rangeText->addAttributes('progress_min', $this->min);
        $rangeText->addAttributes('progress_max', $this->max);
        $this->drawItems[] = $rangeText;
    }
}
