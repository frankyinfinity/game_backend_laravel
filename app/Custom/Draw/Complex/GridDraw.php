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
            $element->setBorderColor(0x000000);
            $element->setThickness(1);
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

        // Create scroll viewport
        $scrollViewport = new Rectangle($uid . '_viewport');
        $scrollViewport->setOrigin($x, $y);
        $scrollViewport->setSize($width, $height);
        $scrollViewport->setColor(0xF4A460); // Sand yellow background
        $scrollViewport->setRenderable($this->renderable);
        $scrollViewport->addAttributes('z_index', $this->baseZIndex);
        $scrollViewport->addAttributes('scroll_enabled', true);
        $scrollViewport->addAttributes('scroll_direction', 'vertical');
        $this->drawItems[] = $scrollViewport;

        // Calculate total content height
        $totalElements = count($this->elements);
        $rows = ceil($totalElements / $elementsPerRow);
        $totalContentHeight = ($rows * $elementHeight) + (($rows - 1) * $elementSpacing);

        // Create content container
        $contentContainer = new Rectangle($uid . '_content');
        $contentContainer->setOrigin($x, $y);
        $contentContainer->setSize($width, max($totalContentHeight, $height));
        $contentContainer->setRenderable($this->renderable);
        $contentContainer->addAttributes('z_index', $this->baseZIndex - 1);
        $this->drawItems[] = $contentContainer;

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
                $element->setRenderable($this->renderable);
                $element->addAttributes('z_index', $this->baseZIndex + 1);
                $this->drawItems[] = $element;
                $elementUids[] = $element->getUid();
            }
        }

        // Add scroll attributes to viewport
        $scrollViewport->addAttributes('scroll_child_uids', $elementUids);
        $initialRenderables = [];
        foreach ($elementUids as $elementUid) {
            $initialRenderables[$elementUid] = true;
        }
        $scrollViewport->addAttributes('scroll_initial_renderables', $initialRenderables);
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
