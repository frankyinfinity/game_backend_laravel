<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;

class GridDraw
{
    private string $uid;
    private int $x;
    private int $y;
    private int $width;
    private int $height;
    private int $elementsPerRow;
    private array $elements;
    private bool $renderable = true;
    private array $drawItems = [];
    private int $elementSpacing = 5;
    private int $baseZIndex = 0;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
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

    public function setElementsPerRow(int $count): void
    {
        $this->elementsPerRow = $count;
    }

    public function setElements(array $elements): void
    {
        $this->elements = $elements;
    }

    public function generateElements(int $count, string $uidPrefix): void
    {
        $this->elements = [];
        for ($i = 0; $i < $count; $i++) {
            $element = new Rectangle($uidPrefix . '_element_' . $i);
            $element->setColor(0xFFFFFF); // White background
            $element->setBorderColor(0x000000);
            $element->setThickness(2);
            $this->elements[] = $element;
        }
    }

    public function setElementSpacing(int $spacing): void
    {
        $this->elementSpacing = $spacing;
    }

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function setBaseZIndex(int $zIndex): void
    {
        $this->baseZIndex = $zIndex;
    }

    public function build(): void
    {
        $uid = $this->uid;
        $x = $this->x;
        $y = $this->y;
        $width = $this->width;
        $height = $this->height;
        $elementsPerRow = $this->elementsPerRow;
        $elementSpacing = $this->elementSpacing;

        // Calculate element size
        $totalSpacingX = ($elementsPerRow - 1) * $elementSpacing;
        $elementWidth = ($width - $totalSpacingX) / $elementsPerRow;
        $elementHeight = $elementWidth; // Square elements

        // Calculate total rows and content height
        $totalElements = count($this->elements);
        $rows = ceil($totalElements / $elementsPerRow);
        $totalContentHeight = ($rows * $elementHeight) + (($rows - 1) * $elementSpacing);

        // Create viewport (visible area)
        $viewport = new Rectangle($uid . '_viewport');
        $viewport->setOrigin($x, $y);
        $viewport->setSize($width, $height);
        $viewport->setColor(0xF4A460); // Sand yellow background
        $viewport->setRenderable(true); // Always visible when modal is open
        $viewport->addAttributes('z_index', $this->baseZIndex);
        $this->drawItems[] = $viewport;

        // Create content panel (scrollable area)
        $panel = new Rectangle($uid . '_panel');
        $panel->setOrigin($x, $y);
        $panel->setSize($width, max($totalContentHeight, $height));
        $panel->setColor(0xF4A460);
        $panel->setRenderable(true); // Always visible when modal is open
        $panel->addAttributes('z_index', $this->baseZIndex - 1);
        $this->drawItems[] = $panel;

        // Position elements in grid
        $elementUids = [];
        foreach ($this->elements as $index => $element) {
            if ($element instanceof BasicDraw) {
                $row = floor($index / $elementsPerRow);
                $col = $index % $elementsPerRow;

                $elementX = $x + ($col * ($elementWidth + $elementSpacing));
                $elementY = $y + ($row * ($elementHeight + $elementSpacing));

                $element->setOrigin($elementX, $elementY);
                $element->setSize($elementWidth, $elementHeight);
                $element->setRenderable(true); // Always visible when modal is open
                $element->addAttributes('z_index', $this->baseZIndex + 1);
                $element->addAttributes('grid_uid', $uid); // Add grid_uid for identification
                $this->drawItems[] = $element;
                $elementUids[] = $element->getUid();
            }
        }
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getElementUids(): array
    {
        $uids = [];
        foreach ($this->elements as $element) {
            if ($element instanceof BasicDraw) {
                $uids[] = $element->getUid();
            }
        }
        return $uids;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
