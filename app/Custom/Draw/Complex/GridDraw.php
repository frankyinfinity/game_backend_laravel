<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;
use Illuminate\Support\Str;

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
    private int $scrollbarWidth = 30;
    private int $currentScrollRow = 0;
    private array $scrollUids = [];
    private string $scrollInitJs = '';
    private array $allElementUids = [];
    private array $templates = [];
    private array $elementData = [];
    private array $placeholderMappings = [];
    private string $imageDisk = 'entity_bodies';

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

    public function setElementData(array $data): void
    {
        $this->elementData = $data;
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function setTemplateGrid(TemplateGridDraw $templateGrid): void
    {
        $this->templates = $templateGrid->getTemplates();
        $this->placeholderMappings = $templateGrid->getPlaceholderMappings();
    }

    public function setImageDisk(string $disk): void
    {
        $this->imageDisk = $disk;
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
        $scrollbarWidth = $this->scrollbarWidth;

        // Calculate element size (leave space for scrollbar)
        $gridWidth = $width - $scrollbarWidth;
        $totalSpacingX = ($elementsPerRow - 1) * $elementSpacing;
        $elementWidth = ($gridWidth - $totalSpacingX) / $elementsPerRow;
        $elementHeight = $elementWidth; // Square elements

        // Calculate total rows and content height
        $totalElements = count($this->elementData);
        $rows = (int) ceil($totalElements / $elementsPerRow);
        $totalContentHeight = ($rows * $elementHeight) + (($rows - 1) * $elementSpacing);

        // Calculate how many rows fit in viewport
        $visibleRows = 0;
        $accumulatedHeight = 0;
        for ($r = 0; $r < $rows; $r++) {
            $rowHeight = $elementHeight + ($r < $rows - 1 ? $elementSpacing : 0);
            if ($accumulatedHeight + $rowHeight <= $height) {
                $visibleRows++;
                $accumulatedHeight += $rowHeight;
            } else {
                break;
            }
        }
        if ($visibleRows === 0) $visibleRows = 1;

        // Create viewport (visible area)
        $viewport = new Rectangle($uid . '_viewport');
        $viewport->setOrigin($x, $y);
        $viewport->setSize($width, $height);
        $viewport->setColor(0xD0D0D0);
        $viewport->setRenderable($this->renderable); // Controlled by parent
        $viewport->addAttributes('z_index', $this->baseZIndex);
        $viewport->addAttributes('currentScrollRow', 0);
        $viewport->addAttributes('totalRows', $rows);
        $viewport->addAttributes('visibleRows', $visibleRows);
        $viewport->addAttributes('rowHeight', $elementHeight + $elementSpacing);
        $this->drawItems[] = $viewport;

        // Create content panel (scrollable area)
        $panel = new Rectangle($uid . '_panel');
        $panel->setOrigin($x, $y);
        $panel->setSize($gridWidth, max($totalContentHeight, $height));
        $panel->setColor(0xD0D0D0);
        $panel->setRenderable($this->renderable); // Controlled by parent
        $panel->addAttributes('z_index', $this->baseZIndex - 1);
        $this->drawItems[] = $panel;

        // Position elements in grid
        $elementUids = [];
        $this->allElementUids = [];
        
        foreach ($this->elementData as $index => $cellData) {
            $row = (int) floor($index / $elementsPerRow);
            $col = $index % $elementsPerRow;

            $cellX = $x + ($col * ($elementWidth + $elementSpacing));
            $cellY = $y + ($row * ($elementHeight + $elementSpacing));

            // Create base rectangle (always)
            $rect = new Rectangle($uid . '_element_' . $index);
            $rect->setOrigin($cellX, $cellY);
            $rect->setSize($elementWidth, $elementHeight);
            $rect->setColor(0xD0D0D0);
            $rect->setBorderColor(0x000000);
            $rect->setThickness(2);
            $rect->setRenderable($this->renderable);
            $rect->addAttributes('z_index', $this->baseZIndex + 1);
            $rect->addAttributes('grid_uid', $uid);
            $this->drawItems[] = $rect;
            $elementUids[] = $rect->getUid();
            $this->allElementUids[] = $rect->getUid();

            // Clone template elements on top
            if (!empty($this->templates)) {
                $cellData = isset($this->elementData[$index]) ? $this->elementData[$index] : [];
                $margin = 4;
                $containerW = $elementWidth - ($margin * 2);
                $containerH = $elementHeight - ($margin * 2);
                
                foreach ($this->templates as $templateIndex => $template) {
                    $clonedElement = clone $template;
                    $clonedElement->setUid($uid . '_cell_' . $index . '_template_' . $templateIndex);
                    
                    $innerPadding = 4;
                    $textAreaHeight = 22;

                    if ($templateIndex === 0 && $clonedElement instanceof Rectangle) {
                        $clonedElement->setOrigin((int)($cellX + $margin), (int)($cellY + $margin));
                        $clonedElement->setSize((int)$containerW, (int)$containerH);
                    } elseif ($templateIndex === 2 && $clonedElement instanceof Rectangle) {
                        $wsX = (int)($cellX + $margin + $innerPadding);
                        $wsY = (int)($cellY + $margin + $innerPadding);
                        $wsW = (int)($containerW - 2 * $innerPadding);
                        $wsH = (int)($containerH - $textAreaHeight - 2 * $innerPadding);
                        $clonedElement->setOrigin($wsX, $wsY);
                        $clonedElement->setSize($wsW, $wsH);
                    } elseif ($templateIndex === 3 && $clonedElement instanceof Image) {
                        $imgPadding = 4;
                        $wsW = (int)($containerW - 2 * $innerPadding);
                        $wsH = (int)($containerH - $textAreaHeight - 2 * $innerPadding);
                        $imgX = (int)($cellX + $margin + $innerPadding + $imgPadding);
                        $imgY = (int)($cellY + $margin + $innerPadding + $imgPadding);
                        $imgW = (int)($wsW - 2 * $imgPadding);
                        $imgH = (int)($wsH - 2 * $imgPadding);
                        $clonedElement->setOrigin($imgX, $imgY);
                        $clonedElement->setSize($imgW, $imgH);
                    } elseif ($clonedElement instanceof Text) {
                        $clonedElement->setOrigin(
                            (int)($cellX + $margin + 9),
                            (int)($cellY + $margin + $containerH - 19)
                        );
                        $clonedElement->setCenterAnchor(false);
                    } else {
                        $clonedElement->setOrigin(
                            (int)($cellX + $clonedElement->getOriginX()),
                            (int)($cellY + $clonedElement->getOriginY())
                        );
                    }
                    $clonedElement->setRenderable($this->renderable);
                    $clonedElement->addAttributes('z_index', $this->baseZIndex + 2 + $templateIndex);
                    $clonedElement->addAttributes('grid_uid', $uid);
                    $clonedElement->addAttributes('cell_index', $index);
                    $clonedElement->addAttributes('cell_value', (string)($index + 1));
                    
                    // Replace placeholders in Text elements
                    if ($clonedElement instanceof Text) {
                        $originalText = $clonedElement->getText();
                        foreach ($this->placeholderMappings as $mapping) {
                            if (isset($mapping['placeholder']) && isset($mapping['dataKey'])) {
                                $originalText = TemplateGridDraw::replacePlaceholdersWithMapping(
                                    $originalText, 
                                    $cellData, 
                                    $mapping['placeholder'], 
                                    $mapping['dataKey']
                                );
                            }
                        }
                        $maxChars = max(1, (int)(($containerW - 8) / 7));
                        if (mb_strlen($originalText) > $maxChars) {
                            $originalText = mb_substr($originalText, 0, $maxChars - 1) . '...';
                        }
                        $clonedElement->setText($originalText);
                    }

                    // Replace placeholders in Image elements
                    if ($clonedElement instanceof Image) {
                        $src = $clonedElement->getSrc();
                        foreach ($this->placeholderMappings as $mapping) {
                            if (isset($mapping['placeholder'], $mapping['dataKey'], $cellData[$mapping['dataKey']])) {
                                $imageFile = $cellData[$mapping['dataKey']];
                                if ($imageFile && \Storage::disk($this->imageDisk)->exists($imageFile)) {
                                    $imageUrl = \Storage::disk($this->imageDisk)->url($imageFile);
                                    $src = str_replace($mapping['placeholder'], $imageUrl, $src);
                                }
                            }
                        }
                        $clonedElement->setSrc($src);
                    }
                    
                    $rect->addChild($clonedElement);
                    $this->drawItems[] = $clonedElement;
                    $elementUids[] = $clonedElement->getUid();
                    $this->allElementUids[] = $clonedElement->getUid();
                }
            } else {
                // Default: text centered
                $text = new Text($uid . '_text_' . $index);
                $text->setCenterAnchor(true);
                $text->setOrigin(
                    (int)($cellX + $elementWidth / 2),
                    (int)($cellY + $elementHeight / 2)
                );
                $text->setText((string)($index + 1));
                $text->setColor(0x000000);
                $text->setFontSize(max(10, (int)($elementWidth / 4)));
                $text->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
                $text->setRenderable($this->renderable);
                $text->addAttributes('z_index', $this->baseZIndex + 2);
                $text->addAttributes('grid_uid', $uid);
                $this->drawItems[] = $text;
                $elementUids[] = $text->getUid();
                $this->allElementUids[] = $text->getUid();
            }
        }

        // Build scrollbar
        $this->buildScrollbar($uid, $x, $y, $width, $height, $gridWidth, $elementHeight, $elementSpacing, $rows, $visibleRows, $elementUids);
    }

    private function buildScrollbar(string $uid, int $x, int $y, int $width, int $height, int $gridWidth, int $elementHeight, int $elementSpacing, int $totalRows, int $visibleRows, array $elementUids): void
    {
        $scrollbarWidth = $this->scrollbarWidth;
        $scrollX = $x + $gridWidth;
        $scrollY = $y;
        $rowStep = $elementHeight + $elementSpacing;

        // Scrollbar strip background
        $scrollbarStrip = new Rectangle($uid . '_scrollbar_strip');
        $scrollbarStrip->setOrigin($scrollX, $scrollY);
        $scrollbarStrip->setSize($scrollbarWidth, $height);
        $scrollbarStrip->setColor(0xD0D0D0);
        $scrollbarStrip->setRenderable($this->renderable);
        $scrollbarStrip->addAttributes('z_index', $this->baseZIndex + 10);
        $this->drawItems[] = $scrollbarStrip;
        $this->scrollUids[] = $scrollbarStrip->getUid();

        // Scrollbar strip left border
        $stripLeftBorder = new Rectangle($uid . '_scrollbar_strip_left_border');
        $stripLeftBorder->setOrigin($scrollX, $scrollY);
        $stripLeftBorder->setSize(2, $height);
        $stripLeftBorder->setColor(0x000000);
        $stripLeftBorder->setRenderable($this->renderable);
        $stripLeftBorder->addAttributes('z_index', $this->baseZIndex + 11);
        $this->drawItems[] = $stripLeftBorder;
        $this->scrollUids[] = $stripLeftBorder->getUid();

        // Up button
        $upButton = new Rectangle($uid . '_scroll_up');
        $upButton->setOrigin($scrollX, $scrollY);
        $upButton->setSize($scrollbarWidth, $scrollbarWidth);
        $upButton->setColor(0xBBBBBB);
        $upButton->setRenderable($this->renderable);
        $upButton->addAttributes('z_index', $this->baseZIndex + 20);
        $upButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_up_" . $uid . "']();");
        $this->drawItems[] = $upButton;
        $this->scrollUids[] = $upButton->getUid();

        // Up button left border
        $upLeftBorder = new Rectangle($uid . '_scroll_up_left_border');
        $upLeftBorder->setOrigin($scrollX, $scrollY);
        $upLeftBorder->setSize(2, $scrollbarWidth);
        $upLeftBorder->setColor(0x000000);
        $upLeftBorder->setRenderable($this->renderable);
        $upLeftBorder->addAttributes('z_index', $this->baseZIndex + 21);
        $this->drawItems[] = $upLeftBorder;
        $this->scrollUids[] = $upLeftBorder->getUid();

        // Up text
        $upText = new Text($uid . '_scroll_up_text');
        $upText->setCenterAnchor(true);
        $upText->setFontSize(18);
        $upText->setOrigin($scrollX + (int)($scrollbarWidth / 2), $scrollY + (int)($scrollbarWidth / 2));
        $upText->setColor(0x333333);
        $upText->setText('^');
        $upText->setRenderable($this->renderable);
        $upText->addAttributes('z_index', $this->baseZIndex + 21);
        $this->drawItems[] = $upText;
        $this->scrollUids[] = $upText->getUid();

        // Down button
        $downButton = new Rectangle($uid . '_scroll_down');
        $downButton->setOrigin($scrollX, $scrollY + $height - $scrollbarWidth);
        $downButton->setSize($scrollbarWidth, $scrollbarWidth);
        $downButton->setColor(0xBBBBBB);
        $downButton->setRenderable($this->renderable);
        $downButton->addAttributes('z_index', $this->baseZIndex + 20);
        $downButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_down_" . $uid . "']();");
        $this->drawItems[] = $downButton;
        $this->scrollUids[] = $downButton->getUid();

        // Down button left border
        $downLeftBorder = new Rectangle($uid . '_scroll_down_left_border');
        $downLeftBorder->setOrigin($scrollX, $scrollY + $height - $scrollbarWidth);
        $downLeftBorder->setSize(2, $scrollbarWidth);
        $downLeftBorder->setColor(0x000000);
        $downLeftBorder->setRenderable($this->renderable);
        $downLeftBorder->addAttributes('z_index', $this->baseZIndex + 21);
        $this->drawItems[] = $downLeftBorder;
        $this->scrollUids[] = $downLeftBorder->getUid();

        // Down text
        $downText = new Text($uid . '_scroll_down_text');
        $downText->setCenterAnchor(true);
        $downText->setFontSize(18);
        $downText->setOrigin($scrollX + (int)($scrollbarWidth / 2), $scrollY + $height - (int)($scrollbarWidth / 2));
        $downText->setColor(0x333333);
        $downText->setText('V');
        $downText->setRenderable($this->renderable);
        $downText->addAttributes('z_index', $this->baseZIndex + 21);
        $this->drawItems[] = $downText;
        $this->scrollUids[] = $downText->getUid();

        // Generate scroll JS
        $this->generateScrollJs($uid, $elementUids, $rowStep, $totalRows, $visibleRows);
    }

    private function generateScrollJs(string $uid, array $elementUids, int $rowStep, int $totalRows, int $visibleRows): void
    {
        $elementUidsJs = '[' . implode(', ', array_map(function($u) { return "'" . $u . "'"; }, $elementUids)) . ']';
        $panelUid = $uid . '_panel';

        $this->scrollInitJs = "(function() {
    window['moveGridShapes_" . $uid . "'] = function(deltaY, elementUids) {
        var panel = shapes['" . $panelUid . "'];
        if (panel) panel.y += deltaY;
        elementUids.forEach(function(uid) {
            var shape = shapes[uid];
            if (shape) shape.y += deltaY;
        });
    };

    window['scroll_up_" . $uid . "'] = function() {
        var viewport = shapes['" . $uid . "_viewport'];
        if (!viewport) return;
        var currentRow = viewport.currentScrollRow || 0;
        if (currentRow > 0) {
            currentRow--;
            viewport.currentScrollRow = currentRow;
            window['moveGridShapes_" . $uid . "'](" . $rowStep . ", " . $elementUidsJs . ");
        }
    };

    window['scroll_down_" . $uid . "'] = function() {
        var viewport = shapes['" . $uid . "_viewport'];
        if (!viewport) return;
        var currentRow = viewport.currentScrollRow || 0;
        var totalRows = " . $totalRows . ";
        var visibleRows = " . $visibleRows . ";
        if (currentRow + visibleRows < totalRows) {
            currentRow++;
            viewport.currentScrollRow = currentRow;
            window['moveGridShapes_" . $uid . "'](-" . $rowStep . ", " . $elementUidsJs . ");
        }
    };
})();";
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getElementUids(): array
    {
        return $this->allElementUids;
    }

    public function getScrollUids(): array
    {
        return $this->scrollUids;
    }

    public function getScrollInitJs(): string
    {
        return $this->scrollInitJs;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
