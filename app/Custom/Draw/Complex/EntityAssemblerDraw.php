<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Complex\GridDraw;
use App\Custom\Draw\Complex\TabDraw;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;
use Illuminate\Support\Str;

class EntityAssemblerDraw
{
    private $uid;
    private array $drawItems = [];
    private $borderRadius = 0;
    private $gridScrollInitJs = '';

    public function __construct($uid)
    {
        $this->uid = $uid;
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

    public function setBorderRadius($radius): void
    {
        $this->borderRadius = $radius;
    }

    public function build(): void
    {
        $marginLeft = 20;
        $marginTop = 20;

        // Rectangle with rounded corners (resized)
        $buttonWidth = 170;
        $buttonHeight = 50;
        $rect = new Rectangle($this->uid . '_rect');
        $rect->setSize($buttonWidth, $buttonHeight);
        $rect->setOrigin($marginLeft, $marginTop);
        $rect->setColor(0xD3D3D3);
        $rect->setBorderRadius($this->borderRadius);
        $rect->setBorderColor(0x0000FF);
        $rect->setThickness(2);
        $rect->setRenderable(true);

        // Text aligned left on button, centered vertically
        $textX = $marginLeft + 10;
        $textY = $marginTop + 17;
        $text = new Text($this->uid . '_text');
        $text->setCenterAnchor(false);
        $text->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $text->setFontSize(16);
        $text->setOrigin($textX, $textY);
        $text->setText('Assembler');
        $text->setColor(0x0000FF);
        $text->setRenderable(true);

        // White square with blue border inside button, centered vertically with margins from top/right/bottom
        $squareSize = 36;
        $squareX = $marginLeft + $buttonWidth - $squareSize - 6;
        $squareY = $marginTop + ($buttonHeight / 2) - ($squareSize / 2);
        $square = new Rectangle($this->uid . '_square');
        $square->setSize($squareSize, $squareSize);
        $square->setOrigin($squareX, $squareY);
        $square->setColor(0xFFFFFF);
        $square->setBorderRadius(2);
        $square->setBorderColor(0x0000FF);
        $square->setThickness(2);
        $square->setRenderable(true);

        // Build modal first (creates grid and generates scroll init JS)
        $modalUid = 'objective_modal_assembler_' . $this->uid;
        $this->buildModal($modalUid);

        // Add click handler to open modal (with grid scroll init appended)
        $jsOpen = file_get_contents(resource_path('js/function/modal/click_open_modal.blade.php'));
        $jsOpen = str_replace('__MODAL_UID__', $modalUid, $jsOpen);
        $jsOpen = str_replace('__name__', 'open_assembler_modal_' . $this->uid, $jsOpen);
        if ($this->gridScrollInitJs) {
            $jsOpen .= $this->gridScrollInitJs;
        }
        $jsOpen = Helper::setCommonJsCode($jsOpen, Str::random(20));
        $rect->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsOpen);
        $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsOpen);

        $this->drawItems[] = $rect;
        $this->drawItems[] = $text;
        $this->drawItems[] = $square;
    }

    private function buildModal($modalUid): void
    {
        $modalWidth = 1100;
        $modalHeight = 600;
        $screenWidth = 1280;
        $screenHeight = 720;

        $modalX = 20;
        $modalY = 20;

        // Modal body
        $body = new Rectangle($modalUid . '_body');
        $body->setOrigin($modalX, $modalY);
        $body->setSize($modalWidth, $modalHeight);
        $body->setColor(0xFFFFFF);
        $body->setBorderRadius(10);
        $body->setRenderable(false);
        $body->addAttributes('z_index', 20000);
        $this->drawItems[] = $body;

        // Border using 4 separate rectangles (top, bottom, left, right)
        $borderThickness = 2;
        // Top border
        $borderTop = new Rectangle($modalUid . '_border_top');
        $borderTop->setOrigin($modalX, $modalY);
        $borderTop->setSize($modalWidth, $borderThickness);
        $borderTop->setColor(0x000000);
        $borderTop->setRenderable(false);
        $borderTop->addAttributes('z_index', 20050);
        $this->drawItems[] = $borderTop;

        // Bottom border
        $borderBottom = new Rectangle($modalUid . '_border_bottom');
        $borderBottom->setOrigin($modalX, $modalY + $modalHeight - $borderThickness);
        $borderBottom->setSize($modalWidth, $borderThickness);
        $borderBottom->setColor(0x000000);
        $borderBottom->setRenderable(false);
        $borderBottom->addAttributes('z_index', 20050);
        $this->drawItems[] = $borderBottom;

        // Left border
        $borderLeft = new Rectangle($modalUid . '_border_left');
        $borderLeft->setOrigin($modalX, $modalY);
        $borderLeft->setSize($borderThickness, $modalHeight);
        $borderLeft->setColor(0x000000);
        $borderLeft->setRenderable(false);
        $borderLeft->addAttributes('z_index', 20050);
        $this->drawItems[] = $borderLeft;

        // Right border
        $borderRight = new Rectangle($modalUid . '_border_right');
        $borderRight->setOrigin($modalX + $modalWidth - $borderThickness, $modalY);
        $borderRight->setSize($borderThickness, $modalHeight);
        $borderRight->setColor(0x000000);
        $borderRight->setRenderable(false);
        $borderRight->addAttributes('z_index', 20050);
        $this->drawItems[] = $borderRight;

        // Modal header
        $headerHeight = 60;
        $header = new Rectangle($modalUid . '_header');
        $header->setOrigin($modalX, $modalY);
        $header->setSize($modalWidth, $headerHeight);
        $header->setColor(0xE0E0E0);
        $header->setRenderable(false);
        $header->addAttributes('z_index', 20001);
        $body->addChild($header);
        $this->drawItems[] = $header;

        // Modal title
        $title = new Text($modalUid . '_title');
        $title->setOrigin($modalX + 16, $modalY + 16);
        $title->setText('Assembler');
        $title->setColor(0x000000);
        $title->setFontSize(24);
        $title->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $title->setRenderable(false);
        $title->addAttributes('z_index', 20002);
        $body->addChild($title);
        $this->drawItems[] = $title;

        // Close button
        $closeSize = 28;
        $closeX = $modalX + $modalWidth - $closeSize - 12;
        $closeY = $modalY + 12;

        $closeButton = new Rectangle($modalUid . '_close_button');
        $closeButton->setOrigin($closeX, $closeY);
        $closeButton->setSize($closeSize, $closeSize);
        $closeButton->setColor(0x666666);
        $closeButton->setBorderRadius(4);
        $closeButton->setRenderable(false);
        $closeButton->addAttributes('z_index', 20003);
        $body->addChild($closeButton);

        $closeText = new Text($modalUid . '_close_text');
        $closeText->setCenterAnchor(true);
        $closeText->setOrigin($closeX + (int) floor($closeSize / 2), $closeY + (int) floor($closeSize / 2));
        $closeText->setText('X');
        $closeText->setFontSize(18);
        $closeText->setColor(0xFFFFFF);
        $closeText->setRenderable(false);
        $closeText->addAttributes('z_index', 20004);
        $body->addChild($closeText);

        // Close button click handler
        $jsClose = file_get_contents(resource_path('js/function/modal/click_close_modal.blade.php'));
        $jsClose = str_replace('__MODAL_UID__', $modalUid, $jsClose);
        $jsClose = Helper::setCommonJsCode($jsClose, Str::random(20));
        $closeButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose);
        $closeText->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose);

        $this->drawItems[] = $closeButton;
        $this->drawItems[] = $closeText;

        // Content area divided into two parts (6/4 grid)
        $contentPadding = 0;
        $contentX = $modalX + $contentPadding;
        $contentY = $modalY + $headerHeight + $contentPadding;
        $contentWidth = $modalWidth - ($contentPadding * 2);
        $contentHeight = $modalHeight - $headerHeight - ($contentPadding * 2);

        // Vertical separator (thin rectangle) at 60% position
        $leftWidth = (int) ($contentWidth * 0.6);
        $separatorX = $contentX + $leftWidth - 1;

        // Left side (60%) - 32x32 grid
        $gridSize = 32;
        $gridPadding = 20;
        $gridAreaWidth = $leftWidth - ($gridPadding * 2);
        $gridAreaHeight = $contentHeight - ($gridPadding * 2);
        
        // Calculate cell size as integer for uniform cells
        $cellSize = (int) floor(min($gridAreaWidth / $gridSize, $gridAreaHeight / $gridSize));
        
        // Center the grid in the left area
        $gridTotalWidth = $cellSize * $gridSize;
        $gridTotalHeight = $cellSize * $gridSize;
        $gridX = (int) ($contentX + ($leftWidth - $gridTotalWidth) / 2);
        $gridY = (int) ($contentY + ($contentHeight - $gridTotalHeight) / 2);

        // Dark gray background (acts as grid lines)
        $gridBg = new Rectangle($modalUid . '_grid_bg');
        $gridBg->setOrigin($gridX, $gridY);
        $gridBg->setSize($gridTotalWidth, $gridTotalHeight);
        $gridBg->setColor(0x404040);
        $gridBg->setRenderable(false);
        $gridBg->addAttributes('z_index', 20040);
        $this->drawItems[] = $gridBg;

        // 32x32 white cells with 1px gap (shows dark gray background as lines)
        $gridCellUids = [];
        $gridCellUids[] = $gridBg->getUid();
        $cellInnerSize = $cellSize - 1; // 1px gap for grid lines
        
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $cellX = $gridX + ($col * $cellSize) + 1;
                $cellY = $gridY + ($row * $cellSize) + 1;
                
                $cell = new Rectangle($modalUid . '_grid_cell_' . $row . '_' . $col);
                $cell->setOrigin($cellX, $cellY);
                $cell->setSize($cellInnerSize, $cellInnerSize);
                $cell->setColor(0xFFFFFF);
                $cell->setRenderable(false);
                $cell->addAttributes('z_index', 20041);
                $cell->addAttributes('grid_row', $row);
                $cell->addAttributes('grid_col', $col);
                $this->drawItems[] = $cell;
                $gridCellUids[] = $cell->getUid();
            }
        }
        
        // Black border around the entire grid
        $borderThick = 2;
        $gridBorderTop = new Rectangle($modalUid . '_grid_border_top');
        $gridBorderTop->setOrigin($gridX, $gridY);
        $gridBorderTop->setSize($gridTotalWidth, $borderThick);
        $gridBorderTop->setColor(0x000000);
        $gridBorderTop->setRenderable(false);
        $gridBorderTop->addAttributes('z_index', 20042);
        $this->drawItems[] = $gridBorderTop;
        $gridCellUids[] = $gridBorderTop->getUid();
        
        $gridBorderBottom = new Rectangle($modalUid . '_grid_border_bottom');
        $gridBorderBottom->setOrigin($gridX, $gridY + $gridTotalHeight - $borderThick);
        $gridBorderBottom->setSize($gridTotalWidth, $borderThick);
        $gridBorderBottom->setColor(0x000000);
        $gridBorderBottom->setRenderable(false);
        $gridBorderBottom->addAttributes('z_index', 20042);
        $this->drawItems[] = $gridBorderBottom;
        $gridCellUids[] = $gridBorderBottom->getUid();
        
        $gridBorderLeft = new Rectangle($modalUid . '_grid_border_left');
        $gridBorderLeft->setOrigin($gridX, $gridY);
        $gridBorderLeft->setSize($borderThick, $gridTotalHeight);
        $gridBorderLeft->setColor(0x000000);
        $gridBorderLeft->setRenderable(false);
        $gridBorderLeft->addAttributes('z_index', 20042);
        $this->drawItems[] = $gridBorderLeft;
        $gridCellUids[] = $gridBorderLeft->getUid();
        
        $gridBorderRight = new Rectangle($modalUid . '_grid_border_right');
        $gridBorderRight->setOrigin($gridX + $gridTotalWidth - $borderThick, $gridY);
        $gridBorderRight->setSize($borderThick, $gridTotalHeight);
        $gridBorderRight->setColor(0x000000);
        $gridBorderRight->setRenderable(false);
        $gridBorderRight->addAttributes('z_index', 20042);
        $this->drawItems[] = $gridBorderRight;
        $gridCellUids[] = $gridBorderRight->getUid();

        // Right side (40%) - TabDraw with 'Body' (primary) and 'Components' tabs
        $rightWidth = $contentWidth - $leftWidth;
        $rightX = $contentX + $leftWidth;

        // Create GridDraw for tab_body
        $gridDrawBody = new GridDraw($modalUid . '_grid_body');
        $gridDrawBody->setOrigin($rightX, $contentY + 40);
        $gridDrawBody->setSize($rightWidth, $contentHeight - 40);
        $gridDrawBody->setRenderable(false);
        $gridDrawBody->setBaseZIndex(20060);
        $gridDrawBody->setElementsPerRow(3);
        $gridDrawBody->setElementSpacing(2);

        $elementDataBody = \App\Models\EntityBody::with('zones.pixels')
            ->where('state', \App\Models\EntityBody::STATE_COMPLETED)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $pixels = [];

                // Build zone pixel lookup: maps "x,y" => zone_id and zone_id => color
                // Zone pixels are saved at 64x64 (editor grid), image is resized to 32x32
                $zonePixelToZoneId = [];
                $zoneIdToColor = [];
                foreach ($item->zones as $zone) {
                    $zoneIdToColor[$zone->id] = $zone->color ?? '#000000';
                    foreach ($zone->pixels as $pixel) {
                        $gx = (int) floor($pixel->x / 2);
                        $gy = (int) floor($pixel->y / 2);
                        $zonePixelToZoneId[$gx . ',' . $gy] = $zone->id;
                    }
                }
                
                // Read black pixels from the entity body image
                if (!empty($item->image)) {
                    $imagePath = \Storage::disk('entity_bodies')->path($item->image);
                    if (file_exists($imagePath)) {
                        $img = imagecreatefromstring(file_get_contents($imagePath));
                        if ($img) {
                            $origW = imagesx($img);
                            $origH = imagesy($img);
                            // Resize to 32x32 with white background
                            $resized = imagecreatetruecolor(32, 32);
                            $white = imagecolorallocate($resized, 255, 255, 255);
                            imagefill($resized, 0, 0, $white);
                            imagecopyresampled($resized, $img, 0, 0, 0, 0, 32, 32, $origW, $origH);
                            
                            for ($y = 0; $y < 32; $y++) {
                                for ($x = 0; $x < 32; $x++) {
                                    $rgb = imagecolorat($resized, $x, $y);
                                    $r = ($rgb >> 16) & 0xFF;
                                    $g = ($rgb >> 8) & 0xFF;
                                    $b = $rgb & 0xFF;
                                    if ($r < 50 && $g < 50 && $b < 50) {
                                        $key = $x . ',' . $y;
                                        $myZoneId = $zonePixelToZoneId[$key] ?? null;
                                        $hasZone = isset($zonePixelToZoneId[$x . ',' . $y]);

                                        $pixels[] = [
                                            'x' => $x,
                                            'y' => $y,
                                            'has_zone' => $hasZone,
                                            'zone_border_top' => $hasZone && (($zonePixelToZoneId[$x . ',' . ($y - 1)] ?? null) !== $myZoneId),
                                            'zone_border_bottom' => $hasZone && (($zonePixelToZoneId[$x . ',' . ($y + 1)] ?? null) !== $myZoneId),
                                            'zone_border_left' => $hasZone && (($zonePixelToZoneId[($x - 1) . ',' . $y] ?? null) !== $myZoneId),
                                            'zone_border_right' => $hasZone && (($zonePixelToZoneId[($x + 1) . ',' . $y] ?? null) !== $myZoneId),
                                            'zone_color' => $hasZone ? ($zoneIdToColor[$myZoneId] ?? '#000000') : null,
                                        ];
                                    }
                                }
                            }
                            imagedestroy($img);
                            imagedestroy($resized);
                        }
                    }
                }
                
                $data['pixels_json'] = json_encode($pixels);

                return $data;
            })->toArray();
        $gridDrawBody->setElementData($elementDataBody);
        $gridDrawBody->setOnClickJs('selectEntityBody_' . $modalUid);

        $this->buildGridTemplate($gridDrawBody, $modalUid, false);
        $gridDrawBody->build();
        $gridElementUidsBody = $gridDrawBody->getElementUids();
        $gridScrollUidsBody = $gridDrawBody->getScrollUids();
        $this->gridScrollInitJs = $gridDrawBody->getScrollInitJs();
        
        // Generate JS for entity body selection on grid
        $gridJs = file_get_contents(resource_path('js/function/entity_body/select_entity_body.blade.php'));
        $gridJs = str_replace('__MODAL_UID__', $modalUid, $gridJs);
        $gridJs = str_replace('__name__', 'selectEntityBody_' . $modalUid, $gridJs);
        $this->gridScrollInitJs .= $gridJs;

        // Create GridDraw for tab_component
        $gridDrawComponent = new GridDraw($modalUid . '_grid_component');
        $gridDrawComponent->setOrigin($rightX, $contentY + 40);
        $gridDrawComponent->setSize($rightWidth, $contentHeight - 40);
        $gridDrawComponent->setRenderable(false);
        $gridDrawComponent->setBaseZIndex(20080);
        $gridDrawComponent->setElementsPerRow(3);
        $gridDrawComponent->setElementSpacing(2);

        $elementDataComponent = \App\Models\EntityComponent::with(['entityTypeComponent', 'genes.gene', 'ruleChimicalElements.ruleChimicalElement.details.effects.gene'])
            ->where('state', \App\Models\EntityComponent::STATE_COMPLETED)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data['symbol'] = $item->entityTypeComponent ? \App\Helper\FontAwesome::unicode($item->entityTypeComponent->symbol) : '';
                
                // Build tooltip with genes and chemical elements details
                $tooltipParts = [];
                
                // Add genes
                if ($item->genes->isNotEmpty()) {
                    $tooltipParts[] = 'GENI:';
                    foreach ($item->genes as $geneRel) {
                        if ($geneRel->gene) {
                            $tooltipParts[] = "  - {$geneRel->gene->name} ({$geneRel->value})";
                        }
                    }
                }
                
                // Add chemical elements with ranges and effects
                if ($item->ruleChimicalElements->isNotEmpty()) {
                    $tooltipParts[] = 'ELEMENTI CHIMICI:';
                    foreach ($item->ruleChimicalElements as $elemRel) {
                        if ($elemRel->ruleChimicalElement) {
                            $rule = $elemRel->ruleChimicalElement;
                            $tooltipParts[] = "  - {$rule->name} ({$rule->title})";
                            if ($rule->details->isNotEmpty()) {
                                foreach ($rule->details as $detail) {
                                    // Only show bands that have effects
                                    if ($detail->effects->isNotEmpty()) {
                                        $tooltipParts[] = "    Fascia: [{$detail->min} / {$detail->max}]";
                                        foreach ($detail->effects as $effect) {
                                            $geneName = $effect->gene ? $effect->gene->name : 'N/A';
                                            $typeLabel = $effect->type === \App\Models\RuleChimicalElementDetailEffect::TYPE_FIXED ? 'Fisso' : 'A Tempo';
                                            $durationLabel = $effect->type === \App\Models\RuleChimicalElementDetailEffect::TYPE_TIMED 
                                                ? (\App\Models\RuleChimicalElementDetailEffect::DURATION_OPTIONS[$effect->duration] ?? $effect->duration . ' min')
                                                : '-';
                                            $tooltipParts[] = "      Effetto: {$geneName} (valore: {$effect->value}, tipo: {$typeLabel}, durata: {$durationLabel})";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $data['tooltip'] = implode("\n", $tooltipParts);
                return $data;
            })->toArray();
        $gridDrawComponent->setElementData($elementDataComponent);
        $gridDrawComponent->setImageDisk('entity_components');

        $this->buildGridTemplate($gridDrawComponent, $modalUid, true);
        $gridDrawComponent->build();
        $gridElementUidsComponent = $gridDrawComponent->getElementUids();
        $gridScrollUidsComponent = $gridDrawComponent->getScrollUids();

        // Main content viewport (gray container)
        $contentViewport = new Rectangle($modalUid . '_content_viewport');
        $contentViewport->setOrigin($contentX, $contentY);
        $contentViewport->setSize($contentWidth, $contentHeight);
        $contentViewport->setColor(0xD0D0D0);
        $contentViewport->setRenderable(false);
        $contentViewport->setBorderRadius(0);
        $contentViewport->addAttributes('z_index', 20005);
        $contentViewport->addAttributes('scroll_enabled', true);
        $contentViewport->addAttributes('scroll_direction', 'vertical');
        $contentViewport->addAttributes('scroll_child_uids', array_merge([
            $modalUid . '_separator_1',
            $modalUid . '_border_top',
            $modalUid . '_border_bottom',
            $modalUid . '_border_left',
            $modalUid . '_border_right',
            $modalUid . '_tabs_tab_tab_body',
            $modalUid . '_tabs_tab_text_tab_body',
            $modalUid . '_tabs_tab_tab_component',
            $modalUid . '_tabs_tab_text_tab_component',
            $modalUid . '_tabs_container_bg',
            $modalUid . '_grid_body_viewport',
            $modalUid . '_grid_body_panel',
            $modalUid . '_grid_component_viewport',
            $modalUid . '_grid_component_panel',
            $modalUid . '_tabs_tab_border_top_tab_body',
            $modalUid . '_tabs_tab_border_bottom_tab_body',
            $modalUid . '_tabs_tab_border_left_tab_body',
            $modalUid . '_tabs_tab_border_right_tab_body',
            $modalUid . '_tabs_tab_border_top_tab_component',
            $modalUid . '_tabs_tab_border_bottom_tab_component',
            $modalUid . '_tabs_tab_border_left_tab_component',
            $modalUid . '_tabs_tab_border_right_tab_component'
        ], $gridCellUids, $gridElementUidsBody, $gridScrollUidsBody, $gridElementUidsComponent, $gridScrollUidsComponent));
        $contentViewport->addAttributes('scroll_initial_renderables', array_merge([
            $modalUid . '_separator_1' => true,
            $modalUid . '_border_top' => true,
            $modalUid . '_border_bottom' => true,
            $modalUid . '_border_left' => true,
            $modalUid . '_border_right' => true,
            $modalUid . '_tabs_tab_tab_body' => true,
            $modalUid . '_tabs_tab_text_tab_body' => true,
            $modalUid . '_tabs_tab_tab_component' => true,
            $modalUid . '_tabs_tab_text_tab_component' => true,
            $modalUid . '_tabs_container_bg' => true,
            $modalUid . '_grid_body_viewport' => true,
            $modalUid . '_grid_body_panel' => true,
            $modalUid . '_grid_component_viewport' => false,
            $modalUid . '_grid_component_panel' => false,
            $modalUid . '_tabs_tab_border_top_tab_body' => true,
            $modalUid . '_tabs_tab_border_bottom_tab_body' => true,
            $modalUid . '_tabs_tab_border_left_tab_body' => true,
            $modalUid . '_tabs_tab_border_right_tab_body' => true,
            $modalUid . '_tabs_tab_border_top_tab_component' => false,
            $modalUid . '_tabs_tab_border_bottom_tab_component' => false,
            $modalUid . '_tabs_tab_border_left_tab_component' => false,
            $modalUid . '_tabs_tab_border_right_tab_component' => false
        ], array_fill_keys($gridCellUids, true), array_fill_keys($gridElementUidsBody, true), array_fill_keys($gridScrollUidsBody, true), array_fill_keys($gridElementUidsComponent, false), array_fill_keys($gridScrollUidsComponent, false)));
        $body->addChild($contentViewport);
        $this->drawItems[] = $contentViewport;

        // Vertical separator (thin rectangle) at 60% position
        $separator = new Rectangle($modalUid . '_separator_1');
        $separator->setOrigin($separatorX, $contentY);
        $separator->setSize(2, $contentHeight);
        $separator->setColor(0x000000);
        $separator->setRenderable(false);
        $separator->setBorderRadius(0);
        $separator->addAttributes('z_index', 20050);
        $contentViewport->addChild($separator);
        $this->drawItems[] = $separator;

        // Save grid element positions before TabDraw overrides them
        $gridPositions = [];
        foreach (array_merge($gridDrawBody->getDrawItems(), $gridDrawComponent->getDrawItems()) as $item) {
            $json = $item->buildJson();
            $gridPositions[$item->getUid()] = ['x' => $json['x'], 'y' => $json['y']];
        }

        // Create TabDraw
        $tabDraw = new TabDraw($modalUid . '_tabs');
        $tabDraw->setOrigin($rightX, $contentY);
        $tabDraw->setSize($rightWidth, $contentHeight);
        $tabDraw->setRenderable(false);
        $tabDraw->setBaseZIndex(20070);
        $tabDraw->addTab('Corpo', 'tab_body', $gridDrawBody->getDrawItems());
        $tabDraw->addTab('Componenti', 'tab_component', $gridDrawComponent->getDrawItems());
        $tabDraw->setPrimaryTab('tab_body');
        $tabDraw->disableTab('tab_component');
        $tabDraw->build();

        // Restore grid element positions overridden by TabDraw
        foreach ($tabDraw->getDrawItems() as $item) {
            if (isset($gridPositions[$item->getUid()])) {
                $item->setOrigin($gridPositions[$item->getUid()]['x'], $gridPositions[$item->getUid()]['y']);
            }
        }

        // Add tab draw items
        foreach ($tabDraw->getDrawItems() as $item) {
            $this->drawItems[] = $item;
        }
    }

    private function buildGridTemplate($gridDraw, $modalUid, bool $withSymbol = false): void
    {
        $templateContainer = new Rectangle('template_container');
        $templateContainer->setColor(0x87CEEB);
        $templateContainer->setBorderColor(0x000000);
        $templateContainer->setBorderRadius(5);

        $templateText = new Text('template_text');
        $templateText->setText('{label}');
        $templateText->setColor(0x000000);
        $templateText->setFontSize(14);
        $templateText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);

        $templateWhiteSquare = new Rectangle('template_white_square');
        $templateWhiteSquare->setColor(0xFFFFFF);
        $templateWhiteSquare->setBorderColor(0x000000);
        $templateWhiteSquare->setThickness(2);
        $templateWhiteSquare->setBorderRadius(2);

        $templateImage = new Image('template_image');
        $templateImage->setSrc('{image}');

        $templateGrid = new TemplateGridDraw($modalUid . '_template');
        $templateGrid->addTemplate($templateContainer);
        if ($withSymbol) {
            $templateSymbol = new Text('template_symbol');
            $templateSymbol->setText('{symbol}');
            $templateSymbol->setColor(0x000000);
            $templateSymbol->setFontSize(14);
            $templateSymbol->setFontFamily(\App\Helper\FontAwesome::fontFamily());
            $templateGrid->addTemplate($templateSymbol);
        }
        $templateGrid->addTemplate($templateText);
        $templateGrid->addTemplate($templateWhiteSquare);
        $templateGrid->addTemplate($templateImage);
        $templateGrid->addTemplateWithMapping('{label}', 'name');
        $templateGrid->addTemplateWithMapping('{image}', 'image');
        if ($withSymbol) {
            $templateGrid->addTemplateWithMapping('{symbol}', 'symbol');
        }
        $templateGrid->addTemplateWithMapping('{tooltip}', 'tooltip');
        $gridDraw->setTemplateGrid($templateGrid);
    }
}
