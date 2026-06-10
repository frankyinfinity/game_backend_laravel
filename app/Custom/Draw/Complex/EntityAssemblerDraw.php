<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Complex\GridDraw;
use App\Custom\Draw\Complex\TabDraw;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Image;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Complex\SliderDraw;
use App\Helper\Helper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntityAssemblerDraw
{
    private $uid;
    private array $drawItems = [];
    private $borderRadius = 0;
    private $gridScrollInitJs = "";

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
            $objectDraw = new \App\Custom\Manipulation\ObjectDraw(
                $item->buildJson(),
                $sessionId,
            );
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
        $rect = new Rectangle($this->uid . "_rect");
        $rect->setSize($buttonWidth, $buttonHeight);
        $rect->setOrigin($marginLeft, $marginTop);
        $rect->setColor(0xd3d3d3);
        $rect->setBorderRadius($this->borderRadius);
        $rect->setBorderColor(0x0000ff);
        $rect->setThickness(2);
        $rect->setRenderable(true);

        // Text aligned left on button, centered vertically
        $textX = $marginLeft + 10;
        $textY = $marginTop + 17;
        $text = new Text($this->uid . "_text");
        $text->setCenterAnchor(false);
        $text->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $text->setFontSize(16);
        $text->setOrigin($textX, $textY);
        $text->setText("Assembler");
        $text->setColor(0x0000ff);
        $text->setRenderable(true);

        // White square with blue border inside button, centered vertically with margins from top/right/bottom
        $squareSize = 36;
        $squareX = $marginLeft + $buttonWidth - $squareSize - 6;
        $squareY = $marginTop + $buttonHeight / 2 - $squareSize / 2;
        $square = new Rectangle($this->uid . "_square");
        $square->setSize($squareSize, $squareSize);
        $square->setOrigin($squareX, $squareY);
        $square->setColor(0xffffff);
        $square->setBorderRadius(2);
        $square->setBorderColor(0x0000ff);
        $square->setThickness(2);
        $square->setRenderable(true);

        // Build modal first (creates grid and generates scroll init JS)
        $modalUid = "objective_modal_assembler_" . $this->uid;
        $this->buildModal($modalUid);

        // Add click handler to open modal (with grid scroll init appended)
        $jsOpen = file_get_contents(
            resource_path("js/function/modal/click_open_modal.blade.php"),
        );
        $jsOpen = str_replace("__MODAL_UID__", $modalUid, $jsOpen);
        $jsOpen = str_replace(
            "__name__",
            "open_assembler_modal_" . $this->uid,
            $jsOpen,
        );
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
        $modalWidth = 1300;
        $modalHeight = 680;
        $screenWidth = 1280;
        $screenHeight = 720;

        $modalX = 20;
        $modalY = 20;

        // Modal body
        $body = new Rectangle($modalUid . "_body");
        $body->setOrigin($modalX, $modalY);
        $body->setSize($modalWidth, $modalHeight);
        $body->setColor(0xffffff);
        $body->setBorderRadius(10);
        $body->setRenderable(false);
        $body->addAttributes("z_index", 20000);
        $this->drawItems[] = $body;

        // Border using 4 separate rectangles (top, bottom, left, right)
        $borderThickness = 2;
        // Top border
        $borderTop = new Rectangle($modalUid . "_border_top");
        $borderTop->setOrigin($modalX, $modalY);
        $borderTop->setSize($modalWidth, $borderThickness);
        $borderTop->setColor(0x000000);
        $borderTop->setRenderable(false);
        $borderTop->addAttributes("z_index", 20050);
        $this->drawItems[] = $borderTop;

        // Bottom border
        $borderBottom = new Rectangle($modalUid . "_border_bottom");
        $borderBottom->setOrigin(
            $modalX,
            $modalY + $modalHeight - $borderThickness,
        );
        $borderBottom->setSize($modalWidth, $borderThickness);
        $borderBottom->setColor(0x000000);
        $borderBottom->setRenderable(false);
        $borderBottom->addAttributes("z_index", 20050);
        $this->drawItems[] = $borderBottom;

        // Left border
        $borderLeft = new Rectangle($modalUid . "_border_left");
        $borderLeft->setOrigin($modalX, $modalY);
        $borderLeft->setSize($borderThickness, $modalHeight);
        $borderLeft->setColor(0x000000);
        $borderLeft->setRenderable(false);
        $borderLeft->addAttributes("z_index", 20050);
        $this->drawItems[] = $borderLeft;

        // Right border
        $borderRight = new Rectangle($modalUid . "_border_right");
        $borderRight->setOrigin(
            $modalX + $modalWidth - $borderThickness,
            $modalY,
        );
        $borderRight->setSize($borderThickness, $modalHeight);
        $borderRight->setColor(0x000000);
        $borderRight->setRenderable(false);
        $borderRight->addAttributes("z_index", 20050);
        $this->drawItems[] = $borderRight;

        // Modal header
        $headerHeight = 60;
        $header = new Rectangle($modalUid . "_header");
        $header->setOrigin($modalX, $modalY);
        $header->setSize($modalWidth, $headerHeight);
        $header->setColor(0xe0e0e0);
        $header->setRenderable(false);
        $header->addAttributes("z_index", 20001);
        $body->addChild($header);
        $this->drawItems[] = $header;

        // Modal title
        $title = new Text($modalUid . "_title");
        $title->setOrigin($modalX + 16, $modalY + 16);
        $title->setText("Assembler");
        $title->setColor(0x000000);
        $title->setFontSize(24);
        $title->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $title->setRenderable(false);
        $title->addAttributes("z_index", 20002);
        $body->addChild($title);
        $this->drawItems[] = $title;

        // Close button
        $closeSize = 28;
        $closeX = $modalX + $modalWidth - $closeSize - 12;
        $closeY = $modalY + 12;

        $closeButton = new Rectangle($modalUid . "_close_button");
        $closeButton->setOrigin($closeX, $closeY);
        $closeButton->setSize($closeSize, $closeSize);
        $closeButton->setColor(0x666666);
        $closeButton->setBorderRadius(4);
        $closeButton->setRenderable(false);
        $closeButton->addAttributes("z_index", 20003);
        $body->addChild($closeButton);

        $closeText = new Text($modalUid . "_close_text");
        $closeText->setCenterAnchor(true);
        $closeText->setOrigin(
            $closeX + (int) floor($closeSize / 2),
            $closeY + (int) floor($closeSize / 2),
        );
        $closeText->setText("X");
        $closeText->setFontSize(18);
        $closeText->setColor(0xffffff);
        $closeText->setRenderable(false);
        $closeText->addAttributes("z_index", 20004);
        $body->addChild($closeText);

        // Close button click handler
        $jsClose = file_get_contents(
            resource_path("js/function/modal/click_close_modal.blade.php"),
        );
        $jsClose = str_replace("__MODAL_UID__", $modalUid, $jsClose);
        $jsClose = Helper::setCommonJsCode($jsClose, Str::random(20));
        $closeButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsClose,
        );
        $closeText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsClose,
        );

        $this->drawItems[] = $closeButton;
        $this->drawItems[] = $closeText;

        // Content area divided into two parts (6/4 grid)
        $contentPadding = 0;
        $contentX = $modalX + $contentPadding;
        $contentY = $modalY + $headerHeight + $contentPadding;
        $contentWidth = $modalWidth - $contentPadding * 2;
        $contentHeight = $modalHeight - $headerHeight - $contentPadding * 2;

        // Vertical separator (thin rectangle) at 60% position
        $leftWidth = (int) ($contentWidth * 0.6);
        $separatorX = $contentX + $leftWidth - 1;

        // Left side (60%) - 32x32 grid
        $gridSize = 32;
        $gridPadding = 20;
        $gridAreaWidth = $leftWidth - $gridPadding * 2;
        $gridAreaHeight = $contentHeight - $gridPadding * 2;

        // Fixed cell size to preserve grid dimensions
        $cellSize = 15;

        // Position grid near the top of the left area
        $gridTotalWidth = $cellSize * $gridSize;
        $gridTotalHeight = $cellSize * $gridSize;
        $gridX = (int) ($contentX + $gridPadding);
        $gridY = (int) ($contentY + $gridPadding);

        // Dark gray background (acts as grid lines)
        $gridBg = new Rectangle($modalUid . "_grid_bg");
        $gridBg->setOrigin($gridX, $gridY);
        $gridBg->setSize($gridTotalWidth, $gridTotalHeight);
        $gridBg->setColor(0x404040);
        $gridBg->setRenderable(false);
        $gridBg->addAttributes("z_index", 20040);
        $this->drawItems[] = $gridBg;

        // 32x32 white cells with 1px gap (shows dark gray background as lines)
        $gridCellUids = [];
        $gridCellUids[] = $gridBg->getUid();
        $cellInnerSize = $cellSize - 1; // 1px gap for grid lines

        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $cellX = $gridX + $col * $cellSize + 1;
                $cellY = $gridY + $row * $cellSize + 1;

                $cell = new Rectangle(
                    $modalUid . "_grid_cell_" . $row . "_" . $col,
                );
                $cell->setOrigin($cellX, $cellY);
                $cell->setSize($cellInnerSize, $cellInnerSize);
                $cell->setColor(0xffffff);
                $cell->setRenderable(false);
                $cell->addAttributes("z_index", 20041);
                $cell->addAttributes("grid_row", $row);
                $cell->addAttributes("grid_col", $col);

                // Add click handler for zone info panel
                $jsGridCellClick = "window['clickGridCell_{$modalUid}']('{$cell->getUid()}');";
                $cell->setInteractive(
                    BasicDraw::INTERACTIVE_POINTER_DOWN,
                    $jsGridCellClick,
                );

                $this->drawItems[] = $cell;
                $gridCellUids[] = $cell->getUid();
            }
        }

        // Black border around the entire grid
        $borderThick = 2;
        $gridBorderTop = new Rectangle($modalUid . "_grid_border_top");
        $gridBorderTop->setOrigin($gridX, $gridY);
        $gridBorderTop->setSize($gridTotalWidth, $borderThick);
        $gridBorderTop->setColor(0x000000);
        $gridBorderTop->setRenderable(false);
        $gridBorderTop->addAttributes("z_index", 20042);
        $this->drawItems[] = $gridBorderTop;
        $gridCellUids[] = $gridBorderTop->getUid();

        $gridBorderBottom = new Rectangle($modalUid . "_grid_border_bottom");
        $gridBorderBottom->setOrigin(
            $gridX,
            $gridY + $gridTotalHeight - $borderThick,
        );
        $gridBorderBottom->setSize($gridTotalWidth, $borderThick);
        $gridBorderBottom->setColor(0x000000);
        $gridBorderBottom->setRenderable(false);
        $gridBorderBottom->addAttributes("z_index", 20042);
        $this->drawItems[] = $gridBorderBottom;
        $gridCellUids[] = $gridBorderBottom->getUid();

        $gridBorderLeft = new Rectangle($modalUid . "_grid_border_left");
        $gridBorderLeft->setOrigin($gridX, $gridY);
        $gridBorderLeft->setSize($borderThick, $gridTotalHeight);
        $gridBorderLeft->setColor(0x000000);
        $gridBorderLeft->setRenderable(false);
        $gridBorderLeft->addAttributes("z_index", 20042);
        $this->drawItems[] = $gridBorderLeft;
        $gridCellUids[] = $gridBorderLeft->getUid();

        $gridBorderRight = new Rectangle($modalUid . "_grid_border_right");
        $gridBorderRight->setOrigin(
            $gridX + $gridTotalWidth - $borderThick,
            $gridY,
        );
        $gridBorderRight->setSize($borderThick, $gridTotalHeight);
        $gridBorderRight->setColor(0x000000);
        $gridBorderRight->setRenderable(false);
        $gridBorderRight->addAttributes("z_index", 20042);
        $this->drawItems[] = $gridBorderRight;
        $gridCellUids[] = $gridBorderRight->getUid();

        // Direction buttons for moving EntityBody pixels (positioned to the right of the grid)
        $dirButtonSize = 34;
        $dirButtonGap = 6;
        $dirButtonPadding = 10;
        $containerPadding = 8;
        $dirButtonsX = $gridX + $gridTotalWidth + $dirButtonPadding;
        $dirButtonsY = $gridY + $dirButtonPadding;

        // Light gray container with black border (simulating transparency)
        $containerWidth =
            $dirButtonSize * 3 + $dirButtonGap * 2 + $containerPadding * 2;
        $containerHeight =
            $dirButtonSize * 2 +
            $dirButtonGap +
            20 +
            $containerPadding * 2 +
            10; // Extra space for title + 10px margin below
        $containerX = $dirButtonsX - $containerPadding;
        $containerY = $dirButtonsY - $containerPadding;

        $dirContainer = new Rectangle($modalUid . "_dir_container");
        $dirContainer->setOrigin($containerX, $containerY);
        $dirContainer->setSize($containerWidth, $containerHeight);
        $dirContainer->setColor(0xe0e0e0); // Light gray
        $dirContainer->setBorderRadius(8);
        $dirContainer->setBorderColor(0x000000);
        $dirContainer->setThickness(1);
        $dirContainer->setRenderable(false);
        $dirContainer->addAttributes("z_index", 20042);
        $this->drawItems[] = $dirContainer;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Title above direction buttons (inside container)
        $dirTitle = new Text($modalUid . "_dir_title");
        $dirTitle->setCenterAnchor(false);
        $dirTitle->setOrigin($dirButtonsX, $dirButtonsY + 5);
        $dirTitle->setText("Movimento");
        $dirTitle->setColor(0x000000);
        $dirTitle->setFontSize(14);
        $dirTitle->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $dirTitle->setRenderable(false);
        $dirTitle->addAttributes("z_index", 20043);
        $this->drawItems[] = $dirTitle;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Up button (centered horizontally at top, below title)
        $upButton = new Rectangle($modalUid . "_dir_up");
        $upButton->setOrigin(
            $dirButtonsX + $dirButtonSize + $dirButtonGap,
            $dirButtonsY + 30,
        );
        $upButton->setSize($dirButtonSize, $dirButtonSize);
        $upButton->setColor(0x404040);
        $upButton->setBorderRadius(5);
        $upButton->setRenderable(false);
        $upButton->addAttributes("z_index", 20043);
        $this->drawItems[] = $upButton;
        // Don't add to gridCellUids to prevent auto-renderable=true

        $upText = new Text($modalUid . "_dir_up_text");
        $upText->setCenterAnchor(true);
        $upText->setOrigin(
            $dirButtonsX +
                $dirButtonSize +
                $dirButtonGap +
                (int) floor($dirButtonSize / 2),
            $dirButtonsY + 30 + (int) floor($dirButtonSize / 2),
        );
        $upText->setText("↑");
        $upText->setColor(0xffffff);
        $upText->setFontSize(24);
        $upText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $upText->setRenderable(false);
        $upText->addAttributes("z_index", 20044);
        $this->drawItems[] = $upText;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Left button (left side, bottom row)
        $leftButton = new Rectangle($modalUid . "_dir_left");
        $leftButton->setOrigin(
            $dirButtonsX,
            $dirButtonsY + 30 + $dirButtonSize + $dirButtonGap,
        );
        $leftButton->setSize($dirButtonSize, $dirButtonSize);
        $leftButton->setColor(0x404040);
        $leftButton->setBorderRadius(5);
        $leftButton->setRenderable(false);
        $leftButton->addAttributes("z_index", 20043);
        $this->drawItems[] = $leftButton;
        // Don't add to gridCellUids to prevent auto-renderable=true

        $leftText = new Text($modalUid . "_dir_left_text");
        $leftText->setCenterAnchor(true);
        $leftText->setOrigin(
            $dirButtonsX + (int) floor($dirButtonSize / 2),
            $dirButtonsY +
                30 +
                $dirButtonSize +
                $dirButtonGap +
                (int) floor($dirButtonSize / 2),
        );
        $leftText->setText("←");
        $leftText->setColor(0xffffff);
        $leftText->setFontSize(24);
        $leftText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $leftText->setRenderable(false);
        $leftText->addAttributes("z_index", 20044);
        $this->drawItems[] = $leftText;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Down button (centered horizontally at bottom)
        $downButton = new Rectangle($modalUid . "_dir_down");
        $downButton->setOrigin(
            $dirButtonsX + $dirButtonSize + $dirButtonGap,
            $dirButtonsY + 30 + $dirButtonSize + $dirButtonGap,
        );
        $downButton->setSize($dirButtonSize, $dirButtonSize);
        $downButton->setColor(0x404040);
        $downButton->setBorderRadius(5);
        $downButton->setRenderable(false);
        $downButton->addAttributes("z_index", 20043);
        $this->drawItems[] = $downButton;
        // Don't add to gridCellUids to prevent auto-renderable=true

        $downText = new Text($modalUid . "_dir_down_text");
        $downText->setCenterAnchor(true);
        $downText->setOrigin(
            $dirButtonsX +
                $dirButtonSize +
                $dirButtonGap +
                (int) floor($dirButtonSize / 2),
            $dirButtonsY +
                30 +
                $dirButtonSize +
                $dirButtonGap +
                (int) floor($dirButtonSize / 2),
        );
        $downText->setText("↓");
        $downText->setColor(0xffffff);
        $downText->setFontSize(24);
        $downText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $downText->setRenderable(false);
        $downText->addAttributes("z_index", 20044);
        $this->drawItems[] = $downText;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Right button (right side, bottom row)
        $rightButton = new Rectangle($modalUid . "_dir_right");
        $rightButton->setOrigin(
            $dirButtonsX + ($dirButtonSize + $dirButtonGap) * 2,
            $dirButtonsY + 30 + $dirButtonSize + $dirButtonGap,
        );
        $rightButton->setSize($dirButtonSize, $dirButtonSize);
        $rightButton->setColor(0x404040);
        $rightButton->setBorderRadius(5);
        $rightButton->setRenderable(false);
        $rightButton->addAttributes("z_index", 20043);
        $this->drawItems[] = $rightButton;
        // Don't add to gridCellUids to prevent auto-renderable=true

        $rightText = new Text($modalUid . "_dir_right_text");
        $rightText->setCenterAnchor(true);
        $rightText->setOrigin(
            $dirButtonsX +
                ($dirButtonSize + $dirButtonGap) * 2 +
                (int) floor($dirButtonSize / 2),
            $dirButtonsY +
                30 +
                $dirButtonSize +
                $dirButtonGap +
                (int) floor($dirButtonSize / 2),
        );
        $rightText->setText("→");
        $rightText->setColor(0xffffff);
        $rightText->setFontSize(24);
        $rightText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $rightText->setRenderable(false);
        $rightText->addAttributes("z_index", 20044);
        $this->drawItems[] = $rightText;
        // Don't add to gridCellUids to prevent auto-renderable=true

        // Direction button click handlers
        $jsMoveUp = "window['movePixels_" . $modalUid . "']('up');";
        $jsMoveDown = "window['movePixels_" . $modalUid . "']('down');";
        $jsMoveLeft = "window['movePixels_" . $modalUid . "']('left');";
        $jsMoveRight = "window['movePixels_" . $modalUid . "']('right');";

        $upButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveUp,
        );
        $upText->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsMoveUp);
        $downButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveDown,
        );
        $downText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveDown,
        );
        $leftButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveLeft,
        );
        $leftText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveLeft,
        );
        $rightButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveRight,
        );
        $rightText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsMoveRight,
        );

        // Right side (40%) - TabDraw with 'Body' (primary) and 'Components' tabs
        $rightWidth = $contentWidth - $leftWidth;
        $rightX = $contentX + $leftWidth;

        // Create GridDraw for tab_body
        $gridDrawBody = new GridDraw($modalUid . "_grid_body");
        $gridDrawBody->setOrigin($rightX, $contentY + 40);
        $gridDrawBody->setSize($rightWidth, $contentHeight - 40);
        $gridDrawBody->setRenderable(false);
        $gridDrawBody->setBaseZIndex(20060);
        $gridDrawBody->setElementsPerRow(3);
        $gridDrawBody->setElementSpacing(2);

        $elementDataBody = \App\Models\EntityBody::with(
            "zones.pixels",
            "anchors",
        )
            ->where("state", \App\Models\EntityBody::STATE_COMPLETED)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $pixels = [];

                // Build zone pixel lookup: maps "x,y" => zone_id and zone_id => color/name
                // Zone pixels are saved at 64x64 (editor grid), image is resized to 32x32
                $zonePixelToZoneId = [];
                $zoneIdToColor = [];
                $zoneIdToName = [];
                foreach ($item->zones as $zone) {
                    $zoneIdToColor[$zone->id] = $zone->color ?? "#000000";
                    $zoneIdToName[$zone->id] = $zone->name ?? "Unknown";
                    foreach ($zone->pixels as $pixel) {
                        $gx = (int) floor($pixel->x / 2);
                        $gy = (int) floor($pixel->y / 2);
                        $zonePixelToZoneId[$gx . "," . $gy] = $zone->id;
                    }
                }

                // Read black pixels from the entity body image
                if (!empty($item->image)) {
                    $imagePath = Storage::disk("entity_bodies")->path(
                        $item->image,
                    );
                    if (file_exists($imagePath)) {
                        $img = imagecreatefromstring(
                            file_get_contents($imagePath),
                        );
                        if ($img) {
                            $origW = imagesx($img);
                            $origH = imagesy($img);
                            // Resize to 32x32 with white background
                            $resized = imagecreatetruecolor(32, 32);
                            $white = imagecolorallocate(
                                $resized,
                                255,
                                255,
                                255,
                            );
                            imagefill($resized, 0, 0, $white);
                            imagecopyresampled(
                                $resized,
                                $img,
                                0,
                                0,
                                0,
                                0,
                                32,
                                32,
                                $origW,
                                $origH,
                            );

                            for ($y = 0; $y < 32; $y++) {
                                for ($x = 0; $x < 32; $x++) {
                                    $rgb = imagecolorat($resized, $x, $y);
                                    $r = ($rgb >> 16) & 0xff;
                                    $g = ($rgb >> 8) & 0xff;
                                    $b = $rgb & 0xff;
                                    if ($r < 50 && $g < 50 && $b < 50) {
                                        $key = $x . "," . $y;
                                        $myZoneId =
                                            $zonePixelToZoneId[$key] ?? null;
                                        $hasZone = isset(
                                            $zonePixelToZoneId[$x . "," . $y],
                                        );

                                        $pixels[] = [
                                            "x" => $x,
                                            "y" => $y,
                                            "has_zone" => $hasZone,
                                            "zone_border_top" =>
                                                $hasZone &&
                                                ($zonePixelToZoneId[
                                                    $x . "," . ($y - 1)
                                                ] ??
                                                    null) !==
                                                    $myZoneId,
                                            "zone_border_bottom" =>
                                                $hasZone &&
                                                ($zonePixelToZoneId[
                                                    $x . "," . ($y + 1)
                                                ] ??
                                                    null) !==
                                                    $myZoneId,
                                            "zone_border_left" =>
                                                $hasZone &&
                                                ($zonePixelToZoneId[
                                                    $x - 1 . "," . $y
                                                ] ??
                                                    null) !==
                                                    $myZoneId,
                                            "zone_border_right" =>
                                                $hasZone &&
                                                ($zonePixelToZoneId[
                                                    $x + 1 . "," . $y
                                                ] ??
                                                    null) !==
                                                    $myZoneId,
                                            "zone_color" => $hasZone
                                                ? $zoneIdToColor[$myZoneId] ??
                                                    "#000000"
                                                : null,
                                            "zone_name" => $hasZone
                                                ? $zoneIdToName[$myZoneId] ??
                                                    "Unknown"
                                                : null,
                                        ];
                                    }
                                }
                            }
                            imagedestroy($img);
                            imagedestroy($resized);
                        }
                    }
                }

                $data["pixels_json"] = json_encode($pixels);

                // Add anchor information using morphTo relationship
                $anchors = $item->anchors;
                $data["anchors"] = $anchors
                    ->map(function ($anchor) {
                        return [
                            "id" => $anchor->id,
                            "x" => $anchor->x,
                            "y" => $anchor->y,
                        ];
                    })
                    ->toArray();

                return $data;
            })
            ->toArray();
        $gridDrawBody->setElementData($elementDataBody);
        $gridDrawBody->setOnClickJs("selectEntityBody_" . $modalUid);

        $this->buildGridTemplate($gridDrawBody, $modalUid, false, false);
        $gridDrawBody->build();
        $gridElementUidsBody = $gridDrawBody->getElementUids();
        $gridScrollUidsBody = $gridDrawBody->getScrollUids();
        $this->gridScrollInitJs = $gridDrawBody->getScrollInitJs();

        // Create GridDraw for tab_component
        $gridDrawComponent = new GridDraw($modalUid . "_grid_component");
        $gridDrawComponent->setOrigin($rightX, $contentY + 40);
        $gridDrawComponent->setSize($rightWidth, $contentHeight - 40);
        $gridDrawComponent->setRenderable(false);
        $gridDrawComponent->setBaseZIndex(20080);
        $gridDrawComponent->setElementsPerRow(3);
        $gridDrawComponent->setElementSpacing(2);

        $elementDataComponent = \App\Models\EntityComponent::with([
            "entityTypeComponent",
            "genes.gene",
            "ruleChimicalElements.ruleChimicalElement.details.effects.gene",
            "ruleChimicalElements.ruleChimicalElement.chimicalElement",
            "ruleChimicalElements.ruleChimicalElement.complexChimicalElement",
            "anchors",
        ])
            ->where("state", \App\Models\EntityComponent::STATE_COMPLETED)
            ->get()
            ->map(function ($item) {
                $data = $item->toArray();
                $data["symbol"] = $item->entityTypeComponent
                    ? \App\Helper\FontAwesome::unicode(
                        $item->entityTypeComponent->symbol,
                    )
                    : "";

                // Build split tooltips with genes and chemical elements details
                $genesTooltipParts = [];
                $chimicalTooltipParts = [];

                if ($item->genes->isNotEmpty()) {
                    $genesTooltipParts[] = "GENI:";
                    foreach ($item->genes as $geneRel) {
                        if ($geneRel->gene) {
                            $genesTooltipParts[] = "  - {$geneRel->gene->name} ({$geneRel->value})";
                        }
                    }
                }

                if ($item->ruleChimicalElements->isNotEmpty()) {
                    $chimicalTooltipParts[] = "ELEMENTI CHIMICI:";
                    foreach ($item->ruleChimicalElements as $elemRel) {
                        if ($elemRel->ruleChimicalElement) {
                            $rule = $elemRel->ruleChimicalElement;
                            $chimicalName =
                                $rule->chimicalElement->name ??
                                ($rule->complexChimicalElement->name ??
                                    $rule->name);
                            $chimicalTooltipParts[] = "  - {$chimicalName} ({$rule->title})";
                            if ($rule->details->isNotEmpty()) {
                                foreach ($rule->details as $detail) {
                                    if ($detail->effects->isNotEmpty()) {
                                        $chimicalTooltipParts[] = "    Fascia: [{$detail->min} / {$detail->max}]";
                                        foreach ($detail->effects as $effect) {
                                            $geneName = $effect->gene
                                                ? $effect->gene->name
                                                : "N/A";
                                            $typeLabel =
                                                $effect->type ===
                                                \App\Models\RuleChimicalElementDetailEffect::TYPE_FIXED
                                                    ? "Fisso"
                                                    : "A Tempo";
                                            $durationLabel =
                                                $effect->type ===
                                                \App\Models\RuleChimicalElementDetailEffect::TYPE_TIMED
                                                    ? \App\Models\RuleChimicalElementDetailEffect
                                                            ::DURATION_OPTIONS[
                                                            $effect->duration
                                                        ] ??
                                                        $effect->duration .
                                                            " min"
                                                    : "-";
                                            $chimicalTooltipParts[] = "      Effetto: {$geneName} (valore: {$effect->value}, tipo: {$typeLabel}, durata: {$durationLabel})";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $pixels = [];
                if (!empty($item->image)) {
                    $imagePath = Storage::disk("entity_components")->path(
                        $item->image,
                    );
                    if (file_exists($imagePath)) {
                        $img = imagecreatefromstring(
                            file_get_contents($imagePath),
                        );
                        if ($img) {
                            $origW = imagesx($img);
                            $origH = imagesy($img);
                            $resized = imagecreatetruecolor(32, 32);
                            $white = imagecolorallocate(
                                $resized,
                                255,
                                255,
                                255,
                            );
                            imagefill($resized, 0, 0, $white);
                            imagecopyresampled(
                                $resized,
                                $img,
                                0,
                                0,
                                0,
                                0,
                                32,
                                32,
                                $origW,
                                $origH,
                            );

                            for ($y = 0; $y < 32; $y++) {
                                for ($x = 0; $x < 32; $x++) {
                                    $rgb = imagecolorat($resized, $x, $y);
                                    $r = ($rgb >> 16) & 0xff;
                                    $g = ($rgb >> 8) & 0xff;
                                    $b = $rgb & 0xff;
                                    if (!($r > 245 && $g > 245 && $b > 245)) {
                                        $pixels[] = [
                                            "x" => $x,
                                            "y" => $y,
                                            "tint" =>
                                                ($r << 16) | ($g << 8) | $b,
                                            "has_zone" => false,
                                            "zone_border_top" => false,
                                            "zone_border_bottom" => false,
                                            "zone_border_left" => false,
                                            "zone_border_right" => false,
                                            "zone_color" => null,
                                            "zone_name" => null,
                                        ];
                                    }
                                }
                            }
                            imagedestroy($img);
                            imagedestroy($resized);
                        }
                    }
                }

                $data["pixels_json"] = json_encode($pixels);
                $data["anchors"] = $item->anchors
                    ->map(function ($anchor) {
                        return [
                            "id" => $anchor->id,
                            "x" => $anchor->x,
                            "y" => $anchor->y,
                        ];
                    })
                    ->toArray();

                $data["genes_badges"] = $item->genes
                    ->map(function ($geneRel) {
                        if (!$geneRel->gene) {
                            return null;
                        }

                        return [
                            "name" => $geneRel->gene->name,
                            "image_url" => $geneRel->gene->image_url,
                            "tooltip" => "GENE: {$geneRel->gene->name}\nValore: {$geneRel->value}",
                        ];
                    })
                    ->filter()
                    ->values()
                    ->toArray();

                $data["chimical_badges"] = $item->ruleChimicalElements
                    ->map(function ($elemRel) {
                        if (!$elemRel->ruleChimicalElement) {
                            return null;
                        }

                        $rule = $elemRel->ruleChimicalElement;
                        $imageUrl = null;
                        $badgeName =
                            $rule->chimicalElement->name ??
                            ($rule->complexChimicalElement->name ??
                                $rule->name);
                        if ($rule->complex_chimical_element_id) {
                            $imagePath = public_path(
                                "storage/complex_chimical_elements/" .
                                    $rule->complex_chimical_element_id .
                                    ".png",
                            );
                            if (file_exists($imagePath)) {
                                $imageUrl = asset(
                                    "storage/complex_chimical_elements/" .
                                        $rule->complex_chimical_element_id .
                                        ".png?v=" .
                                        filemtime($imagePath),
                                );
                            }
                        } elseif ($rule->chimical_element_id) {
                            $imagePath = public_path(
                                "storage/chimical_elements/" .
                                    $rule->chimical_element_id .
                                    ".png",
                            );
                            if (file_exists($imagePath)) {
                                $imageUrl = asset(
                                    "storage/chimical_elements/" .
                                        $rule->chimical_element_id .
                                        ".png?v=" .
                                        filemtime($imagePath),
                                );
                            }
                        }

                        $tooltipLines = [
                            "ELEMENTO CHIMICO: {$badgeName}",
                            "Regola: {$rule->title}",
                        ];
                        if ($rule->details->isNotEmpty()) {
                            foreach ($rule->details as $detail) {
                                if ($detail->effects->isNotEmpty()) {
                                    $tooltipLines[] = "Fascia: [{$detail->min} / {$detail->max}]";
                                    foreach ($detail->effects as $effect) {
                                        $geneName = $effect->gene
                                            ? $effect->gene->name
                                            : "N/A";
                                        $typeLabel =
                                            $effect->type ===
                                            \App\Models\RuleChimicalElementDetailEffect::TYPE_FIXED
                                                ? "Fisso"
                                                : "A Tempo";
                                        $durationLabel =
                                            $effect->type ===
                                            \App\Models\RuleChimicalElementDetailEffect::TYPE_TIMED
                                                ? \App\Models\RuleChimicalElementDetailEffect
                                                        ::DURATION_OPTIONS[
                                                        $effect->duration
                                                    ] ??
                                                    $effect->duration . " min"
                                                : "-";
                                        $tooltipLines[] = "Effetto: {$geneName} (valore: {$effect->value}, tipo: {$typeLabel}, durata: {$durationLabel})";
                                    }
                                }
                            }
                        }

                        return [
                            "name" => $badgeName,
                            "image_url" => $imageUrl,
                            "tooltip" => implode("\n", $tooltipLines),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->toArray();

                $data["tooltip_genes"] = implode("\n", $genesTooltipParts);
                $data["tooltip_chimical"] = implode(
                    "\n",
                    $chimicalTooltipParts,
                );
                $data["tooltip"] = trim(
                    implode(
                        "\n\n",
                        array_filter([
                            $data["tooltip_genes"],
                            $data["tooltip_chimical"],
                        ]),
                    ),
                );
                return $data;
            })
            ->toArray();
        $gridDrawComponent->setElementData($elementDataComponent);
        $gridDrawComponent->setImageDisk("entity_components");

        $this->buildGridTemplate($gridDrawComponent, $modalUid, true, true);
        $gridDrawComponent->build();
        $gridElementUidsComponent = $gridDrawComponent->getElementUids();
        $gridScrollUidsComponent = $gridDrawComponent->getScrollUids();

        // Add grid component draw items to drawItems
        foreach ($gridDrawComponent->getDrawItems() as $item) {
            $this->drawItems[] = $item;
        }

        // Add click handler to "Aggiungi" buttons in component grid
        foreach ($gridDrawComponent->getDrawItems() as $item) {
            $attrs = $item->buildJson()["attributes"] ?? [];
            $templateRole = $attrs["template_role"] ?? null;
            if (
                $templateRole === "add_button_rect" ||
                $templateRole === "add_button_text"
            ) {
                $cellIndex = $attrs["cell_index"] ?? null;
                if ($cellIndex === null) {
                    continue;
                }

                $parentUid =
                    $modalUid . "_grid_component_element_" . $cellIndex;
                $jsAddButton =
                    "(function() {
                        window['openAddComponentModal_" .
                    $modalUid .
                    "']('" .
                    $parentUid .
                    "');
                    })();";
                $jsAddButton = Helper::setCommonJsCode(
                    $jsAddButton,
                    Str::random(20),
                );
                $item->setInteractive(
                    BasicDraw::INTERACTIVE_POINTER_DOWN,
                    $jsAddButton,
                );
            }
        }

        // Main content viewport (gray container)
        $contentViewport = new Rectangle($modalUid . "_content_viewport");
        $contentViewport->setOrigin($contentX, $contentY);
        $contentViewport->setSize($contentWidth, $contentHeight);
        $contentViewport->setColor(0xd0d0d0);
        $contentViewport->setRenderable(false);
        $contentViewport->setBorderRadius(0);
        $contentViewport->addAttributes("z_index", 20005);
        $contentViewport->addAttributes("scroll_enabled", true);
        $contentViewport->addAttributes("scroll_direction", "vertical");
        $contentViewport->addAttributes(
            "scroll_child_uids",
            array_merge(
                [
                    $modalUid . "_separator_1",
                    $modalUid . "_border_top",
                    $modalUid . "_border_bottom",
                    $modalUid . "_border_left",
                    $modalUid . "_border_right",
                    $modalUid . "_tabs_tab_tab_body",
                    $modalUid . "_tabs_tab_text_tab_body",
                    $modalUid . "_tabs_tab_tab_component",
                    $modalUid . "_tabs_tab_text_tab_component",
                    $modalUid . "_tabs_container_bg",
                    $modalUid . "_grid_body_viewport",
                    $modalUid . "_grid_body_panel",
                    $modalUid . "_grid_component_viewport",
                    $modalUid . "_grid_component_panel",
                    $modalUid . "_tabs_tab_border_top_tab_body",
                    $modalUid . "_tabs_tab_border_bottom_tab_body",
                    $modalUid . "_tabs_tab_border_left_tab_body",
                    $modalUid . "_tabs_tab_border_right_tab_body",
                    $modalUid . "_tabs_tab_border_top_tab_component",
                    $modalUid . "_tabs_tab_border_bottom_tab_component",
                    $modalUid . "_tabs_tab_border_left_tab_component",
                    $modalUid . "_tabs_tab_border_right_tab_component",
                    $modalUid . "_tabs_tab_strike_tab_component",
                    $modalUid . "_tabs_tab_strike_tab_body",
                    $modalUid . "_back_button_rect",
                    $modalUid . "_back_button_text",
                    $modalUid . "_proceed_button_rect",
                    $modalUid . "_proceed_button_text",
                ],
                $gridCellUids,
                $gridElementUidsBody,
                $gridScrollUidsBody,
                $gridElementUidsComponent,
                $gridScrollUidsComponent,
            ),
        );
        $contentViewport->addAttributes(
            "scroll_initial_renderables",
            array_merge(
                [
                    $modalUid . "_separator_1" => true,
                    $modalUid . "_border_top" => true,
                    $modalUid . "_border_bottom" => true,
                    $modalUid . "_border_left" => true,
                    $modalUid . "_border_right" => true,
                    $modalUid . "_tabs_tab_tab_body" => true,
                    $modalUid . "_tabs_tab_text_tab_body" => true,
                    $modalUid . "_tabs_tab_tab_component" => true,
                    $modalUid . "_tabs_tab_text_tab_component" => true,
                    $modalUid . "_tabs_container_bg" => true,
                    $modalUid . "_grid_body_viewport" => true,
                    $modalUid . "_grid_body_panel" => true,
                    $modalUid . "_grid_component_viewport" => false,
                    $modalUid . "_grid_component_panel" => false,
                    $modalUid . "_tabs_tab_border_top_tab_body" => true,
                    $modalUid . "_tabs_tab_border_bottom_tab_body" => true,
                    $modalUid . "_tabs_tab_border_left_tab_body" => true,
                    $modalUid . "_tabs_tab_border_right_tab_body" => true,
                    $modalUid . "_tabs_tab_border_top_tab_component" => false,
                    $modalUid .
                    "_tabs_tab_border_bottom_tab_component" => false,
                    $modalUid . "_tabs_tab_border_left_tab_component" => false,
                    $modalUid . "_tabs_tab_border_right_tab_component" => false,
                    $modalUid . "_tabs_tab_strike_tab_component" => true,
                    $modalUid . "_tabs_tab_strike_tab_body" => false,
                    $modalUid . "_back_button_rect" => false,
                    $modalUid . "_back_button_text" => false,
                    $modalUid . "_proceed_button_rect" => false,
                    $modalUid . "_proceed_button_text" => false,
                ],
                array_fill_keys($gridCellUids, true),
                array_fill_keys($gridElementUidsBody, true),
                array_fill_keys($gridScrollUidsBody, true),
                array_fill_keys($gridElementUidsComponent, false),
                array_fill_keys($gridScrollUidsComponent, false),
            ),
        );
        $body->addChild($contentViewport);
        $this->drawItems[] = $contentViewport;

        // Vertical separator (thin rectangle) at 60% position
        $separator = new Rectangle($modalUid . "_separator_1");
        $separator->setOrigin($separatorX, $contentY);
        $separator->setSize(2, $contentHeight);
        $separator->setColor(0x000000);
        $separator->setRenderable(false);
        $separator->setBorderRadius(0);
        $separator->addAttributes("z_index", 20050);
        $contentViewport->addChild($separator);
        $this->drawItems[] = $separator;

        // Save grid element positions before TabDraw overrides them
        $gridPositions = [];
        foreach (
            array_merge(
                $gridDrawBody->getDrawItems(),
                $gridDrawComponent->getDrawItems(),
            )
            as $item
        ) {
            $json = $item->buildJson();
            $gridPositions[$item->getUid()] = [
                "x" => $json["x"],
                "y" => $json["y"],
            ];
        }

        // Create TabDraw
        $tabDraw = new TabDraw($modalUid . "_tabs");
        $tabDraw->setOrigin($rightX, $contentY);
        $tabDraw->setSize($rightWidth, $contentHeight);
        $tabDraw->setRenderable(false);
        $tabDraw->setBaseZIndex(20070);
        $tabDraw->addTab("Corpo", "tab_body", $gridDrawBody->getDrawItems());
        $tabDraw->addTab(
            "Componenti",
            "tab_component",
            $gridDrawComponent->getDrawItems(),
        );
        $tabDraw->setPrimaryTab("tab_body");
        $tabDraw->disableTab("tab_component");
        $tabDraw->build();

        // Restore grid element positions overridden by TabDraw
        foreach ($tabDraw->getDrawItems() as $item) {
            if (isset($gridPositions[$item->getUid()])) {
                $item->setOrigin(
                    $gridPositions[$item->getUid()]["x"],
                    $gridPositions[$item->getUid()]["y"],
                );
            }
        }

        // Add tab draw items
        foreach ($tabDraw->getDrawItems() as $item) {
            $this->drawItems[] = $item;
        }

        // Create strikethrough line for tab_body (hidden by default, shown when Prosegui disables tab_body)
        $tabBodyTabWidth = $rightWidth / 2;
        $tabBodyStrike = new Rectangle($modalUid . "_tabs_tab_strike_tab_body");
        $tabBodyStrike->setOrigin($rightX + 10, $contentY + 20);
        $tabBodyStrike->setSize((int) ($tabBodyTabWidth - 20), 2);
        $tabBodyStrike->setColor(0x808080);
        $tabBodyStrike->setRenderable(false);
        $tabBodyStrike->addAttributes("z_index", 20221);
        $this->drawItems[] = $tabBodyStrike;

        // Collect element UIDs for tab content switching JS
        $bodyContentUids = [];
        foreach ($gridDrawBody->getDrawItems() as $item) {
            $bodyContentUids[] = $item->getUid();
        }
        $componentContentUids = [];
        foreach ($gridDrawComponent->getDrawItems() as $item) {
            $componentContentUids[] = $item->getUid();
        }
        $bodyContentUidsJson = json_encode($bodyContentUids);
        $componentContentUidsJson = json_encode($componentContentUids);

        // Red "Indietro" (back) button - shown when tab_component is active
        $backButtonWidth = 120;
        $backButtonHeight = 35;
        $backButtonX = $gridX;
        $backButtonY = $gridY + $gridTotalHeight + 10;

        $backButtonRect = new Rectangle($modalUid . "_back_button_rect");
        $backButtonRect->setOrigin($backButtonX, $backButtonY);
        $backButtonRect->setSize($backButtonWidth, $backButtonHeight);
        $backButtonRect->setColor(0xff0000);
        $backButtonRect->setBorderRadius(5);
        $backButtonRect->setRenderable(false);
        $backButtonRect->addAttributes("z_index", 20095);

        $backButtonText = new Text($modalUid . "_back_button_text");
        $backButtonText->setCenterAnchor(true);
        $backButtonText->setOrigin(
            $backButtonX + (int) floor($backButtonWidth / 2),
            $backButtonY + (int) floor($backButtonHeight / 2),
        );
        $backButtonText->setText("Indietro");
        $backButtonText->setColor(0xffffff);
        $backButtonText->setFontSize(14);
        $backButtonText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $backButtonText->setRenderable(false);
        $backButtonText->addAttributes("z_index", 20096);

        $jsSetBodyActive = "
    var setActiveTabFn = window['setAssemblerActiveTab_{$modalUid}'];
    if (typeof setActiveTabFn === 'function') setActiveTabFn('tab_body');
    var setTabEnabledFn = window['setAssemblerTabEnabled_{$modalUid}'];
    if (typeof setTabEnabledFn === 'function') {
        setTabEnabledFn('tab_body', true);
        setTabEnabledFn('tab_component', false);
    }
    window['__assemblerTabBodyDisabled_{$modalUid}'] = false;
    window['__assemblerTabComponentDisabled_{$modalUid}'] = true;";

        $jsSetComponentActive = "
    var setActiveTabFn = window['setAssemblerActiveTab_{$modalUid}'];
    if (typeof setActiveTabFn === 'function') setActiveTabFn('tab_component');
    var setTabEnabledFn = window['setAssemblerTabEnabled_{$modalUid}'];
    if (typeof setTabEnabledFn === 'function') {
        setTabEnabledFn('tab_body', false);
        setTabEnabledFn('tab_component', true);
    }
    window['__assemblerTabBodyDisabled_{$modalUid}'] = true;
    window['__assemblerTabComponentDisabled_{$modalUid}'] = false;";

        // JS click handler for back button: enable tab_body, go to tab_body, disable tab_component
        $jsBackButton = "(function() {
    {$jsSetBodyActive}

    // Show body content
    var bodyUids = {$bodyContentUidsJson};
    bodyUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    // Hide component content
    var componentUids = {$componentContentUidsJson};
    componentUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    // Update tab borders - show body borders, hide component borders
    ['top','bottom','left','right'].forEach(function(side) {
        var bodyBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_body'];
        if (bodyBorder) bodyBorder.renderable = true;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes.renderable = true;
        var compBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_component'];
        if (compBorder) compBorder.renderable = false;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes.renderable = false;
    });

    // Show strikethrough on tab_component (disable it)
    var strike = shapes['{$modalUid}_tabs_tab_strike_tab_component'];
    if (strike) strike.renderable = true;
    if (objects['{$modalUid}_tabs_tab_strike_tab_component'] && objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes.renderable = true;

    // Gray out tab_component text
    var compText = shapes['{$modalUid}_tabs_tab_text_tab_component'];
    if (compText) compText.style.fill = 0x808080;

    // Re-enable tab_body (hide strikethrough + restore text color)
    var bodyStrike = shapes['{$modalUid}_tabs_tab_strike_tab_body'];
    if (bodyStrike) bodyStrike.renderable = false;
    if (objects['{$modalUid}_tabs_tab_strike_tab_body'] && objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes.renderable = false;

    var bodyText = shapes['{$modalUid}_tabs_tab_text_tab_body'];
    if (bodyText) bodyText.style.fill = 0x000000;

    // Show direction buttons
    ['{$modalUid}_dir_container','{$modalUid}_dir_title','{$modalUid}_dir_up','{$modalUid}_dir_up_text','{$modalUid}_dir_left','{$modalUid}_dir_left_text','{$modalUid}_dir_down','{$modalUid}_dir_down_text','{$modalUid}_dir_right','{$modalUid}_dir_right_text'].forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    // Hide back button
    var backRect = shapes['{$modalUid}_back_button_rect'];
    var backText = shapes['{$modalUid}_back_button_text'];
    if (backRect) backRect.renderable = false;
    if (backText) backText.renderable = false;
    if (objects['{$modalUid}_back_button_rect'] && objects['{$modalUid}_back_button_rect'].attributes) objects['{$modalUid}_back_button_rect'].attributes.renderable = false;
    if (objects['{$modalUid}_back_button_text'] && objects['{$modalUid}_back_button_text'].attributes) objects['{$modalUid}_back_button_text'].attributes.renderable = false;

    // Show proceed button
    var procRect = shapes['{$modalUid}_proceed_button_rect'];
    var procText = shapes['{$modalUid}_proceed_button_text'];
    if (procRect) procRect.renderable = true;
    if (procText) procText.renderable = true;
    if (objects['{$modalUid}_proceed_button_rect'] && objects['{$modalUid}_proceed_button_rect'].attributes) objects['{$modalUid}_proceed_button_rect'].attributes.renderable = true;
    if (objects['{$modalUid}_proceed_button_text'] && objects['{$modalUid}_proceed_button_text'].attributes) objects['{$modalUid}_proceed_button_text'].attributes.renderable = true;
})();";
        $jsBackButton = Helper::setCommonJsCode($jsBackButton, Str::random(20));
        $backButtonRect->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsBackButton,
        );
        $backButtonText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsBackButton,
        );

        $this->drawItems[] = $backButtonRect;
        $this->drawItems[] = $backButtonText;

        // Green "Prosegui" (proceed) button - below grid, next to direction buttons
        $proceedButtonWidth = 120;
        $proceedButtonHeight = 35;
        $proceedButtonX = $gridX;
        $proceedButtonY = $gridY + $gridTotalHeight + 10;

        $proceedButtonRect = new Rectangle($modalUid . "_proceed_button_rect");
        $proceedButtonRect->setOrigin($proceedButtonX, $proceedButtonY);
        $proceedButtonRect->setSize($proceedButtonWidth, $proceedButtonHeight);
        $proceedButtonRect->setColor(0x00aa00);
        $proceedButtonRect->setBorderRadius(5);
        $proceedButtonRect->setRenderable(false);
        $proceedButtonRect->addAttributes("z_index", 20095);

        $proceedButtonText = new Text($modalUid . "_proceed_button_text");
        $proceedButtonText->setCenterAnchor(true);
        $proceedButtonText->setOrigin(
            $proceedButtonX + (int) floor($proceedButtonWidth / 2),
            $proceedButtonY + (int) floor($proceedButtonHeight / 2),
        );
        $proceedButtonText->setText("Prosegui");
        $proceedButtonText->setColor(0xffffff);
        $proceedButtonText->setFontSize(14);
        $proceedButtonText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $proceedButtonText->setRenderable(false);
        $proceedButtonText->addAttributes("z_index", 20096);

        // JS click handler for proceed button: enable tab_component, go to tab_component, disable tab_body
        $jsProceedButton = "(function() {
    {$jsSetComponentActive}

    var hideZonePanelFn = window['hideZonePanel_{$modalUid}'];
    if (typeof hideZonePanelFn === 'function') {
        hideZonePanelFn();
    }

    var bodyUids = {$bodyContentUidsJson};
    bodyUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    var componentUids = {$componentContentUidsJson};
    componentUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    ['top','bottom','left','right'].forEach(function(side) {
        var compBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_component'];
        if (compBorder) compBorder.renderable = true;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes.renderable = true;
        var bodyBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_body'];
        if (bodyBorder) bodyBorder.renderable = false;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes.renderable = false;
    });

    var strike = shapes['{$modalUid}_tabs_tab_strike_tab_component'];
    if (strike) strike.renderable = false;
    if (objects['{$modalUid}_tabs_tab_strike_tab_component'] && objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes.renderable = false;

    var compText = shapes['{$modalUid}_tabs_tab_text_tab_component'];
    if (compText) compText.style.fill = 0x000000;

    // Disable tab_body (strikethrough + gray text)
    var bodyStrike = shapes['{$modalUid}_tabs_tab_strike_tab_body'];
    if (bodyStrike) bodyStrike.renderable = true;
    if (objects['{$modalUid}_tabs_tab_strike_tab_body'] && objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes.renderable = true;

    var bodyText = shapes['{$modalUid}_tabs_tab_text_tab_body'];
    if (bodyText) bodyText.style.fill = 0x808080;

    // Hide direction buttons
    ['{$modalUid}_dir_container','{$modalUid}_dir_title','{$modalUid}_dir_up','{$modalUid}_dir_up_text','{$modalUid}_dir_left','{$modalUid}_dir_left_text','{$modalUid}_dir_down','{$modalUid}_dir_down_text','{$modalUid}_dir_right','{$modalUid}_dir_right_text'].forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    var backRect = shapes['{$modalUid}_back_button_rect'];
    var backText = shapes['{$modalUid}_back_button_text'];
    if (backRect) backRect.renderable = true;
    if (backText) backText.renderable = true;
    if (objects['{$modalUid}_back_button_rect'] && objects['{$modalUid}_back_button_rect'].attributes) objects['{$modalUid}_back_button_rect'].attributes.renderable = true;
    if (objects['{$modalUid}_back_button_text'] && objects['{$modalUid}_back_button_text'].attributes) objects['{$modalUid}_back_button_text'].attributes.renderable = true;

    var procRect = shapes['{$modalUid}_proceed_button_rect'];
    var procText = shapes['{$modalUid}_proceed_button_text'];
    if (procRect) procRect.renderable = false;
    if (procText) procText.renderable = false;
    if (objects['{$modalUid}_proceed_button_rect'] && objects['{$modalUid}_proceed_button_rect'].attributes) objects['{$modalUid}_proceed_button_rect'].attributes.renderable = false;
    if (objects['{$modalUid}_proceed_button_text'] && objects['{$modalUid}_proceed_button_text'].attributes) objects['{$modalUid}_proceed_button_text'].attributes.renderable = false;
})();";
        $jsProceedButton = Helper::setCommonJsCode(
            $jsProceedButton,
            Str::random(20),
        );
        $proceedButtonRect->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsProceedButton,
        );
        $proceedButtonText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsProceedButton,
        );

        $this->drawItems[] = $proceedButtonRect;
        $this->drawItems[] = $proceedButtonText;

        // JS click handler for tab_component tab: switch to component content, show back button
        $jsTabComponentClick = "(function() {
    if (window['__assemblerTabComponentDisabled_{$modalUid}'] === true) {
        return;
    }
    {$jsSetComponentActive}

    // Hide body content
    var bodyUids = {$bodyContentUidsJson};
    bodyUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    // Show component content
    var componentUids = {$componentContentUidsJson};
    componentUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    // Update tab borders - show component borders, hide body borders
    ['top','bottom','left','right'].forEach(function(side) {
        var compBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_component'];
        if (compBorder) compBorder.renderable = true;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes.renderable = true;
        var bodyBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_body'];
        if (bodyBorder) bodyBorder.renderable = false;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes.renderable = false;
    });

    // Hide strikethrough on tab_component (enable it visually)
    var strike = shapes['{$modalUid}_tabs_tab_strike_tab_component'];
    if (strike) strike.renderable = false;
    if (objects['{$modalUid}_tabs_tab_strike_tab_component'] && objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes.renderable = false;

    // Restore tab_component text color
    var compText = shapes['{$modalUid}_tabs_tab_text_tab_component'];
    if (compText) compText.style.fill = 0x000000;

    // Disable tab_body (strikethrough + gray text)
    var bodyStrike = shapes['{$modalUid}_tabs_tab_strike_tab_body'];
    if (bodyStrike) bodyStrike.renderable = true;
    if (objects['{$modalUid}_tabs_tab_strike_tab_body'] && objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes.renderable = true;

    var bodyTabText = shapes['{$modalUid}_tabs_tab_text_tab_body'];
    if (bodyTabText) bodyTabText.style.fill = 0x808080;

    // Hide direction buttons
    ['{$modalUid}_dir_container','{$modalUid}_dir_title','{$modalUid}_dir_up','{$modalUid}_dir_up_text','{$modalUid}_dir_left','{$modalUid}_dir_left_text','{$modalUid}_dir_down','{$modalUid}_dir_down_text','{$modalUid}_dir_right','{$modalUid}_dir_right_text'].forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    // Show back button
    var backRect = shapes['{$modalUid}_back_button_rect'];
    var backText = shapes['{$modalUid}_back_button_text'];
    if (backRect) backRect.renderable = true;
    if (backText) backText.renderable = true;
    if (objects['{$modalUid}_back_button_rect'] && objects['{$modalUid}_back_button_rect'].attributes) objects['{$modalUid}_back_button_rect'].attributes.renderable = true;
    if (objects['{$modalUid}_back_button_text'] && objects['{$modalUid}_back_button_text'].attributes) objects['{$modalUid}_back_button_text'].attributes.renderable = true;

    // Hide proceed button
    var procRect = shapes['{$modalUid}_proceed_button_rect'];
    var procText = shapes['{$modalUid}_proceed_button_text'];
    if (procRect) procRect.renderable = false;
    if (procText) procText.renderable = false;
    if (objects['{$modalUid}_proceed_button_rect'] && objects['{$modalUid}_proceed_button_rect'].attributes) objects['{$modalUid}_proceed_button_rect'].attributes.renderable = false;
    if (objects['{$modalUid}_proceed_button_text'] && objects['{$modalUid}_proceed_button_text'].attributes) objects['{$modalUid}_proceed_button_text'].attributes.renderable = false;
})();";
        $jsTabComponentClick = Helper::setCommonJsCode(
            $jsTabComponentClick,
            Str::random(20),
        );

        $jsTabBodyClick = "(function() {
    if (window['__assemblerTabBodyDisabled_{$modalUid}'] === true) {
        return;
    }
    {$jsSetBodyActive}

    // Show body content
    var bodyUids = {$bodyContentUidsJson};
    bodyUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    // Hide component content
    var componentUids = {$componentContentUidsJson};
    componentUids.forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = false;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = false;
    });

    ['top','bottom','left','right'].forEach(function(side) {
        var bodyBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_body'];
        if (bodyBorder) bodyBorder.renderable = true;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_body'].attributes.renderable = true;
        var compBorder = shapes['{$modalUid}_tabs_tab_border_' + side + '_tab_component'];
        if (compBorder) compBorder.renderable = false;
        if (objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'] && objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes) objects['{$modalUid}_tabs_tab_border_' + side + '_tab_component'].attributes.renderable = false;
    });

    var strike = shapes['{$modalUid}_tabs_tab_strike_tab_component'];
    if (strike) strike.renderable = true;
    if (objects['{$modalUid}_tabs_tab_strike_tab_component'] && objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_component'].attributes.renderable = true;

    var compText = shapes['{$modalUid}_tabs_tab_text_tab_component'];
    if (compText) compText.style.fill = 0x808080;

    var bodyStrike = shapes['{$modalUid}_tabs_tab_strike_tab_body'];
    if (bodyStrike) bodyStrike.renderable = false;
    if (objects['{$modalUid}_tabs_tab_strike_tab_body'] && objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes) objects['{$modalUid}_tabs_tab_strike_tab_body'].attributes.renderable = false;

    var bodyText = shapes['{$modalUid}_tabs_tab_text_tab_body'];
    if (bodyText) bodyText.style.fill = 0x000000;

    ['{$modalUid}_dir_container','{$modalUid}_dir_title','{$modalUid}_dir_up','{$modalUid}_dir_up_text','{$modalUid}_dir_left','{$modalUid}_dir_left_text','{$modalUid}_dir_down','{$modalUid}_dir_down_text','{$modalUid}_dir_right','{$modalUid}_dir_right_text'].forEach(function(uid) {
        if (shapes[uid]) shapes[uid].renderable = true;
        if (objects[uid] && objects[uid].attributes) objects[uid].attributes.renderable = true;
    });

    var backRect = shapes['{$modalUid}_back_button_rect'];
    var backText = shapes['{$modalUid}_back_button_text'];
    if (backRect) backRect.renderable = false;
    if (backText) backText.renderable = false;
    if (objects['{$modalUid}_back_button_rect'] && objects['{$modalUid}_back_button_rect'].attributes) objects['{$modalUid}_back_button_rect'].attributes.renderable = false;
    if (objects['{$modalUid}_back_button_text'] && objects['{$modalUid}_back_button_text'].attributes) objects['{$modalUid}_back_button_text'].attributes.renderable = false;

    var procRect = shapes['{$modalUid}_proceed_button_rect'];
    var procText = shapes['{$modalUid}_proceed_button_text'];
    if (procRect) procRect.renderable = true;
    if (procText) procText.renderable = true;
    if (objects['{$modalUid}_proceed_button_rect'] && objects['{$modalUid}_proceed_button_rect'].attributes) objects['{$modalUid}_proceed_button_rect'].attributes.renderable = true;
    if (objects['{$modalUid}_proceed_button_text'] && objects['{$modalUid}_proceed_button_text'].attributes) objects['{$modalUid}_proceed_button_text'].attributes.renderable = true;
})();";
        $jsTabBodyClick = Helper::setCommonJsCode(
            $jsTabBodyClick,
            Str::random(20),
        );
        $jsTabComponentClick = Helper::setCommonJsCode(
            $jsTabComponentClick,
            Str::random(20),
        );

        // Set guarded click handlers on tabs so disabled tabs are never clickable
        foreach ($tabDraw->getDrawItems() as $item) {
            $itemUid = $item->getUid();
            if (
                $itemUid === $modalUid . "_tabs_tab_tab_component" ||
                $itemUid === $modalUid . "_tabs_tab_text_tab_component"
            ) {
                $item->setInteractive(
                    BasicDraw::INTERACTIVE_POINTER_DOWN,
                    $jsTabComponentClick,
                );
            } elseif (
                $itemUid === $modalUid . "_tabs_tab_tab_body" ||
                $itemUid === $modalUid . "_tabs_tab_text_tab_body"
            ) {
                $item->setInteractive(
                    BasicDraw::INTERACTIVE_POINTER_DOWN,
                    $jsTabBodyClick,
                );
            }
        }

        // Zone info panel (hidden by default) - created LAST to render above everything
        $zonePanelWidth = 280;
        $zonePanelHeight = 210;
        $zonePanelX = $modalX + $modalWidth / 2 - $zonePanelWidth / 2;
        $zonePanelY = $modalY + $headerHeight + 20;

        $zonePanel = new Rectangle($modalUid . "_zone_panel");
        $zonePanel->setOrigin($zonePanelX + 2, $zonePanelY + 2);
        $zonePanel->setSize($zonePanelWidth - 4, $zonePanelHeight - 4);
        $zonePanel->setColor(0xffffff);
        $zonePanel->setRenderable(false);
        $zonePanel->addAttributes("z_index", 50000);
        // NOT a child of body - top level to render above everything
        $this->drawItems[] = $zonePanel;

        // Zone panel border (4 separate rectangles)
        $zoneBorderThickness = 2;
        $zoneBorderTop = new Rectangle($modalUid . "_zone_border_top");
        $zoneBorderTop->setOrigin($zonePanelX, $zonePanelY);
        $zoneBorderTop->setSize($zonePanelWidth, $zoneBorderThickness);
        $zoneBorderTop->setColor(0x000000);
        $zoneBorderTop->setRenderable(false);
        $zoneBorderTop->addAttributes("z_index", 49999);
        $this->drawItems[] = $zoneBorderTop;

        $zoneBorderBottom = new Rectangle($modalUid . "_zone_border_bottom");
        $zoneBorderBottom->setOrigin(
            $zonePanelX,
            $zonePanelY + $zonePanelHeight - $zoneBorderThickness,
        );
        $zoneBorderBottom->setSize($zonePanelWidth, $zoneBorderThickness);
        $zoneBorderBottom->setColor(0x000000);
        $zoneBorderBottom->setRenderable(false);
        $zoneBorderBottom->addAttributes("z_index", 49999);
        $this->drawItems[] = $zoneBorderBottom;

        $zoneBorderLeft = new Rectangle($modalUid . "_zone_border_left");
        $zoneBorderLeft->setOrigin($zonePanelX, $zonePanelY);
        $zoneBorderLeft->setSize($zoneBorderThickness, $zonePanelHeight);
        $zoneBorderLeft->setColor(0x000000);
        $zoneBorderLeft->setRenderable(false);
        $zoneBorderLeft->addAttributes("z_index", 49999);
        $this->drawItems[] = $zoneBorderLeft;

        $zoneBorderRight = new Rectangle($modalUid . "_zone_border_right");
        $zoneBorderRight->setOrigin(
            $zonePanelX + $zonePanelWidth - $zoneBorderThickness,
            $zonePanelY,
        );
        $zoneBorderRight->setSize($zoneBorderThickness, $zonePanelHeight);
        $zoneBorderRight->setColor(0x000000);
        $zoneBorderRight->setRenderable(false);
        $zoneBorderRight->addAttributes("z_index", 49999);
        $this->drawItems[] = $zoneBorderRight;

        // Zone color square
        $zoneColorSize = 24;
        $zoneColorX = $zonePanelX + 15;
        $zoneColorY = $zonePanelY + 13;

        $zoneColorSquare = new Rectangle($modalUid . "_zone_color_square");
        $zoneColorSquare->setOrigin($zoneColorX, $zoneColorY);
        $zoneColorSquare->setSize($zoneColorSize, $zoneColorSize);
        $zoneColorSquare->setColor(0xffffff);
        $zoneColorSquare->setRenderable(false);
        $zoneColorSquare->addAttributes("z_index", 50010);
        $this->drawItems[] = $zoneColorSquare;

        // Zone name text
        $zoneNameText = new Text($modalUid . "_zone_name_text");
        $zoneNameText->setCenterAnchor(false);
        $zoneNameText->setOrigin(
            $zoneColorX + $zoneColorSize + 12,
            $zonePanelY + 16,
        );
        $zoneNameText->setText("Zone Name");
        $zoneNameText->setColor(0x000000);
        $zoneNameText->setFontSize(16);
        $zoneNameText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $zoneNameText->setRenderable(false);
        $zoneNameText->addAttributes("z_index", 50020);
        $this->drawItems[] = $zoneNameText;

        // Zone panel close button (X)
        $zoneCloseSize = 20;
        $zoneCloseX = $zonePanelX + $zonePanelWidth - $zoneCloseSize - 8;
        $zoneCloseY = $zonePanelY + 8;

        $zoneCloseButton = new Rectangle($modalUid . "_zone_close_button");
        $zoneCloseButton->setOrigin($zoneCloseX, $zoneCloseY);
        $zoneCloseButton->setSize($zoneCloseSize, $zoneCloseSize);
        $zoneCloseButton->setColor(0x666666);
        $zoneCloseButton->setBorderRadius(3);
        $zoneCloseButton->setRenderable(false);
        $zoneCloseButton->addAttributes("z_index", 50030);

        $zoneCloseText = new Text($modalUid . "_zone_close_text");
        $zoneCloseText->setCenterAnchor(true);
        $zoneCloseText->setOrigin(
            $zoneCloseX + (int) floor($zoneCloseSize / 2),
            $zoneCloseY + (int) floor($zoneCloseSize / 2),
        );
        $zoneCloseText->setText("X");
        $zoneCloseText->setFontSize(14);
        $zoneCloseText->setColor(0xffffff);
        $zoneCloseText->setRenderable(false);
        $zoneCloseText->addAttributes("z_index", 50040);

        // RGB Sliders in zone panel
        $sliderYStart = $zonePanelY + 45;
        $sliderX = $zonePanelX + 10;
        $sliderWidth = $zonePanelWidth - 20;
        $sliderConfigs = [
            [
                "uid_suffix" => "slider_red",
                "color" => 0xff0000,
                "title" => "Rosso",
            ],
            [
                "uid_suffix" => "slider_green",
                "color" => 0x00ff00,
                "title" => "Verde",
            ],
            [
                "uid_suffix" => "slider_blue",
                "color" => 0x0000ff,
                "title" => "Blu",
            ],
        ];
        $sliderUids = [];
        foreach ($sliderConfigs as $index => $config) {
            $slider = new SliderDraw($modalUid . "_" . $config["uid_suffix"]);
            $slider->setOrigin($sliderX, $sliderYStart + $index * 55);
            $slider->setWidth($sliderWidth);
            $slider->setMin(0);
            $slider->setMax(255);
            $slider->setValue(0);
            $slider->setColor($config["color"]);
            $slider->setTitle($config["title"]);
            $slider->setOnChange(
                "window['updateZoneColor_" . $modalUid . "']();",
            );
            $slider->build();
            foreach ($slider->getDrawItems() as $item) {
                $item->addAttributes("z_index", 50050 + $index);
                $item->setRenderable(false);
                $this->drawItems[] = $item;
                $sliderUids[] = $item->getUid();
            }
        }

        // Zone panel close button click handler
        $sliderUidsJson = json_encode($sliderUids);
        $jsZoneClose =
            "(function() {
            var panel = shapes['{$modalUid}_zone_panel'];
            var colorSquare = shapes['{$modalUid}_zone_color_square'];
            var nameText = shapes['{$modalUid}_zone_name_text'];
            var closeButton = shapes['{$modalUid}_zone_close_button'];
            var closeText = shapes['{$modalUid}_zone_close_text'];
            var borderTop = shapes['{$modalUid}_zone_border_top'];
            var borderBottom = shapes['{$modalUid}_zone_border_bottom'];
            var borderLeft = shapes['{$modalUid}_zone_border_left'];
            var borderRight = shapes['{$modalUid}_zone_border_right'];
            var sliderUids = " .
            $sliderUidsJson .
            ";
            if (panel) panel.renderable = false;
            if (colorSquare) colorSquare.renderable = false;
            if (nameText) nameText.renderable = false;
            if (closeButton) closeButton.renderable = false;
            if (closeText) closeText.renderable = false;
            if (borderTop) borderTop.renderable = false;
            if (borderBottom) borderBottom.renderable = false;
            if (borderLeft) borderLeft.renderable = false;
            if (borderRight) borderRight.renderable = false;
            sliderUids.forEach(function(uid) {
                var s = shapes[uid];
                if (s) s.renderable = false;
            });
        })();";

        $jsZoneClose = Helper::setCommonJsCode($jsZoneClose, Str::random(20));
        $zoneCloseButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsZoneClose,
        );
        $zoneCloseText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsZoneClose,
        );

        $this->drawItems[] = $zoneCloseButton;
        $this->drawItems[] = $zoneCloseText;

        // Generate JS for entity body selection on grid
        $gridJs = file_get_contents(
            resource_path(
                "js/function/entity_body/select_entity_body.blade.php",
            ),
        );
        $gridJs = str_replace("__MODAL_UID__", $modalUid, $gridJs);
        $gridJs = str_replace(
            "__name__",
            "selectEntityBody_" . $modalUid,
            $gridJs,
        );
        $gridJs = str_replace("__SLIDER_UIDS__", $sliderUidsJson, $gridJs);
        $this->gridScrollInitJs .= $gridJs;

        // Generate JS for zone color update
        $updateColorJs = file_get_contents(
            resource_path(
                "js/function/entity_body/update_zone_color.blade.php",
            ),
        );
        $updateColorJs = str_replace(
            "__MODAL_UID__",
            $modalUid,
            $updateColorJs,
        );
        $this->gridScrollInitJs .= $updateColorJs;

        // Generate JS for moving pixels
        $movePixelsJs = file_get_contents(
            resource_path("js/function/entity_body/move_pixels.blade.php"),
        );
        $movePixelsJs = str_replace("__MODAL_UID__", $modalUid, $movePixelsJs);
        $this->gridScrollInitJs .= $movePixelsJs;

        // Build the secondary "add component" modal (shown on Aggiungi click)
        $this->buildAddComponentModal($modalUid);
    }

    private function buildAddComponentModal($parentModalUid): void
    {
        $addModalUid = $parentModalUid . "_add";

        // Wider than the main modal to host badges on the right
        $modalWidth = 1560;
        $modalHeight = 750;
        $modalX = 20;
        $modalY = 20;

        // Modal body
        $body = new Rectangle($addModalUid . "_body");
        $body->setOrigin($modalX, $modalY);
        $body->setSize($modalWidth, $modalHeight);
        $body->setColor(0xd0d0d0);
        $body->setBorderRadius(10);
        $body->setRenderable(false);
        $body->addAttributes("z_index", 100000);
        $this->drawItems[] = $body;

        // Borders
        $borderThickness = 2;
        foreach (["top", "bottom", "left", "right"] as $side) {
            $b = new Rectangle($addModalUid . "_border_" . $side);
            if ($side === "top") {
                $b->setOrigin($modalX, $modalY);
                $b->setSize($modalWidth, $borderThickness);
            } elseif ($side === "bottom") {
                $b->setOrigin(
                    $modalX,
                    $modalY + $modalHeight - $borderThickness,
                );
                $b->setSize($modalWidth, $borderThickness);
            } elseif ($side === "left") {
                $b->setOrigin($modalX, $modalY);
                $b->setSize($borderThickness, $modalHeight);
            } else {
                $b->setOrigin(
                    $modalX + $modalWidth - $borderThickness,
                    $modalY,
                );
                $b->setSize($borderThickness, $modalHeight);
            }
            $b->setColor(0x000000);
            $b->setRenderable(false);
            $b->addAttributes("z_index", 100050);
            $this->drawItems[] = $b;
        }

        // Header
        $headerHeight = 60;
        $header = new Rectangle($addModalUid . "_header");
        $header->setOrigin($modalX, $modalY);
        $header->setSize($modalWidth, $headerHeight);
        $header->setColor(0xe0e0e0);
        $header->setRenderable(false);
        $header->addAttributes("z_index", 100001);
        $body->addChild($header);
        $this->drawItems[] = $header;

        // Title
        $title = new Text($addModalUid . "_title");
        $title->setOrigin($modalX + 16, $modalY + 16);
        $title->setText("aggiunta componente");
        $title->setColor(0x000000);
        $title->setFontSize(24);
        $title->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $title->setRenderable(false);
        $title->addAttributes("z_index", 100002);
        $body->addChild($title);
        $this->drawItems[] = $title;

        $instructionText = new Text($addModalUid . "_instruction_text");
        $instructionText->setOrigin($modalX + 20, $modalY + $headerHeight + 12);
        $instructionText->setText(
            "Seleziona un'ancora a sinistra e una a destra per creare il collegamento",
        );
        $instructionText->setColor(0x333333);
        $instructionText->setFontSize(14);
        $instructionText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $instructionText->setRenderable(false);
        $instructionText->addAttributes("z_index", 100011);
        $this->drawItems[] = $instructionText;

        // Close button
        $closeSize = 28;
        $closeX = $modalX + $modalWidth - $closeSize - 12;
        $closeY = $modalY + 12;
        $closeButton = new Rectangle($addModalUid . "_close_button");
        $closeButton->setOrigin($closeX, $closeY);
        $closeButton->setSize($closeSize, $closeSize);
        $closeButton->setColor(0x666666);
        $closeButton->setBorderRadius(4);
        $closeButton->setRenderable(false);
        $closeButton->addAttributes("z_index", 100003);
        $body->addChild($closeButton);

        $closeText = new Text($addModalUid . "_close_text");
        $closeText->setCenterAnchor(true);
        $closeText->setOrigin(
            $closeX + (int) floor($closeSize / 2),
            $closeY + (int) floor($closeSize / 2),
        );
        $closeText->setText("X");
        $closeText->setFontSize(18);
        $closeText->setColor(0xffffff);
        $closeText->setRenderable(false);
        $closeText->addAttributes("z_index", 100004);
        $body->addChild($closeText);

        $jsCloseAdd =
            "window['closeAddComponentModal_" . $parentModalUid . "']();";
        $jsCloseAdd = Helper::setCommonJsCode($jsCloseAdd, Str::random(20));
        $closeButton->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsCloseAdd,
        );
        $closeText->setInteractive(
            BasicDraw::INTERACTIVE_POINTER_DOWN,
            $jsCloseAdd,
        );

        $this->drawItems[] = $closeButton;
        $this->drawItems[] = $closeText;

        // Two halves background
        $contentPadding = 20;
        $contentX = $modalX + $contentPadding;
        $contentY = $modalY + $headerHeight + 34;
        $contentWidth = $modalWidth - $contentPadding * 2;
        $contentHeight = 580;
        $leftPanelWidth = 620;
        $rightPanelWidth = $contentWidth - $leftPanelWidth - 20;

        // Left half background
        $leftBg = new Rectangle($addModalUid . "_left_bg");
        $leftBg->setOrigin($contentX, $contentY);
        $leftBg->setSize($leftPanelWidth, $contentHeight);
        $leftBg->setColor(0xf5f5f5);
        $leftBg->setBorderRadius(8);
        $leftBg->setBorderColor(0x000000);
        $leftBg->setThickness(1);
        $leftBg->setRenderable(false);
        $leftBg->addAttributes("z_index", 100010);
        $this->drawItems[] = $leftBg;

        $leftTitle = new Text($addModalUid . "_left_title");
        $leftTitle->setOrigin($contentX + 20, $contentY + 14);
        $leftTitle->setText("Assembler corrente");
        $leftTitle->setColor(0x000000);
        $leftTitle->setFontSize(18);
        $leftTitle->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $leftTitle->setRenderable(false);
        $leftTitle->addAttributes("z_index", 100011);
        $this->drawItems[] = $leftTitle;

        // Right half background
        $rightBgX = $contentX + $leftPanelWidth + 20;
        $rightBg = new Rectangle($addModalUid . "_right_bg");
        $rightBg->setOrigin($rightBgX, $contentY);
        $rightBg->setSize($rightPanelWidth, $contentHeight);
        $rightBg->setColor(0xf5f5f5);
        $rightBg->setBorderRadius(8);
        $rightBg->setBorderColor(0x000000);
        $rightBg->setThickness(1);
        $rightBg->setRenderable(false);
        $rightBg->addAttributes("z_index", 100010);
        $this->drawItems[] = $rightBg;

        $rightTitle = new Text($addModalUid . "_right_title");
        $rightTitle->setOrigin($rightBgX + 20, $contentY + 14);
        $rightTitle->setText("Componente selezionato");
        $rightTitle->setColor(0x000000);
        $rightTitle->setFontSize(18);
        $rightTitle->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $rightTitle->setRenderable(false);
        $rightTitle->addAttributes("z_index", 100011);
        $this->drawItems[] = $rightTitle;

        // Vertical separator
        $separator = new Rectangle($addModalUid . "_separator");
        $separator->setOrigin($contentX + $leftPanelWidth + 9, $contentY);
        $separator->setSize(2, $contentHeight);
        $separator->setColor(0x000000);
        $separator->setRenderable(false);
        $separator->addAttributes("z_index", 100030);
        $this->drawItems[] = $separator;

        // Left grid (EntityBody) - 32x32
        $gridSize = 32;
        $cellSize = 15;
        $gridPadding = 20;
        $gridTotalWidth = $cellSize * $gridSize;
        $gridTotalHeight = $cellSize * $gridSize;
        $leftGridX =
            $contentX + (int) floor(($leftPanelWidth - $gridTotalWidth) / 2);
        $leftGridY = $contentY + 58;

        // Left grid background
        $leftGridBg = new Rectangle($addModalUid . "_left_grid_bg");
        $leftGridBg->setOrigin($leftGridX, $leftGridY);
        $leftGridBg->setSize($gridTotalWidth, $gridTotalHeight);
        $leftGridBg->setColor(0x404040);
        $leftGridBg->setRenderable(false);
        $leftGridBg->addAttributes("z_index", 100040);
        $this->drawItems[] = $leftGridBg;

        // Left grid cells
        $cellInnerSize = $cellSize - 1;
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $cellX = $leftGridX + $col * $cellSize + 1;
                $cellY = $leftGridY + $row * $cellSize + 1;

                $cell = new Rectangle(
                    $addModalUid . "_left_cell_" . $row . "_" . $col,
                );
                $cell->setOrigin($cellX, $cellY);
                $cell->setSize($cellInnerSize, $cellInnerSize);
                $cell->setColor(0xffffff);
                $cell->setRenderable(false);
                $cell->addAttributes("z_index", 100041);
                $this->drawItems[] = $cell;
            }
        }

        // Left grid border
        $borderThick = 2;
        $leftGridBorderTop = new Rectangle(
            $addModalUid . "_left_grid_border_top",
        );
        $leftGridBorderTop->setOrigin($leftGridX, $leftGridY);
        $leftGridBorderTop->setSize($gridTotalWidth, $borderThick);
        $leftGridBorderTop->setColor(0x000000);
        $leftGridBorderTop->setRenderable(false);
        $leftGridBorderTop->addAttributes("z_index", 100042);
        $this->drawItems[] = $leftGridBorderTop;

        $leftGridBorderBottom = new Rectangle(
            $addModalUid . "_left_grid_border_bottom",
        );
        $leftGridBorderBottom->setOrigin(
            $leftGridX,
            $leftGridY + $gridTotalHeight - $borderThick,
        );
        $leftGridBorderBottom->setSize($gridTotalWidth, $borderThick);
        $leftGridBorderBottom->setColor(0x000000);
        $leftGridBorderBottom->setRenderable(false);
        $leftGridBorderBottom->addAttributes("z_index", 100042);
        $this->drawItems[] = $leftGridBorderBottom;

        $leftGridBorderLeft = new Rectangle(
            $addModalUid . "_left_grid_border_left",
        );
        $leftGridBorderLeft->setOrigin($leftGridX, $leftGridY);
        $leftGridBorderLeft->setSize($borderThick, $gridTotalHeight);
        $leftGridBorderLeft->setColor(0x000000);
        $leftGridBorderLeft->setRenderable(false);
        $leftGridBorderLeft->addAttributes("z_index", 100042);
        $this->drawItems[] = $leftGridBorderLeft;

        $leftGridBorderRight = new Rectangle(
            $addModalUid . "_left_grid_border_right",
        );
        $leftGridBorderRight->setOrigin(
            $leftGridX + $gridTotalWidth - $borderThick,
            $leftGridY,
        );
        $leftGridBorderRight->setSize($borderThick, $gridTotalHeight);
        $leftGridBorderRight->setColor(0x000000);
        $leftGridBorderRight->setRenderable(false);
        $leftGridBorderRight->addAttributes("z_index", 100042);
        $this->drawItems[] = $leftGridBorderRight;

        // Right grid (EntityComponent) - 32x32
        $rightGridX = $rightBgX + 20;
        $rightGridY = $contentY + 58;

        // Right grid background
        $rightGridBg = new Rectangle($addModalUid . "_right_grid_bg");
        $rightGridBg->setOrigin($rightGridX, $rightGridY);
        $rightGridBg->setSize($gridTotalWidth, $gridTotalHeight);
        $rightGridBg->setColor(0x404040);
        $rightGridBg->setRenderable(false);
        $rightGridBg->addAttributes("z_index", 100040);
        $this->drawItems[] = $rightGridBg;

        // Right grid cells
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $cellX = $rightGridX + $col * $cellSize + 1;
                $cellY = $rightGridY + $row * $cellSize + 1;

                $cell = new Rectangle(
                    $addModalUid . "_right_cell_" . $row . "_" . $col,
                );
                $cell->setOrigin($cellX, $cellY);
                $cell->setSize($cellInnerSize, $cellInnerSize);
                $cell->setColor(0xffffff);
                $cell->setRenderable(false);
                $cell->addAttributes("z_index", 100041);
                $this->drawItems[] = $cell;
            }
        }

        // Right grid border
        $rightGridBorderTop = new Rectangle(
            $addModalUid . "_right_grid_border_top",
        );
        $rightGridBorderTop->setOrigin($rightGridX, $rightGridY);
        $rightGridBorderTop->setSize($gridTotalWidth, $borderThick);
        $rightGridBorderTop->setColor(0x000000);
        $rightGridBorderTop->setRenderable(false);
        $rightGridBorderTop->addAttributes("z_index", 100042);
        $this->drawItems[] = $rightGridBorderTop;

        $rightGridBorderBottom = new Rectangle(
            $addModalUid . "_right_grid_border_bottom",
        );
        $rightGridBorderBottom->setOrigin(
            $rightGridX,
            $rightGridY + $gridTotalHeight - $borderThick,
        );
        $rightGridBorderBottom->setSize($gridTotalWidth, $borderThick);
        $rightGridBorderBottom->setColor(0x000000);
        $rightGridBorderBottom->setRenderable(false);
        $rightGridBorderBottom->addAttributes("z_index", 100042);
        $this->drawItems[] = $rightGridBorderBottom;

        $rightGridBorderLeft = new Rectangle(
            $addModalUid . "_right_grid_border_left",
        );
        $rightGridBorderLeft->setOrigin($rightGridX, $rightGridY);
        $rightGridBorderLeft->setSize($borderThick, $gridTotalHeight);
        $rightGridBorderLeft->setColor(0x000000);
        $rightGridBorderLeft->setRenderable(false);
        $rightGridBorderLeft->addAttributes("z_index", 100042);
        $this->drawItems[] = $rightGridBorderLeft;

        $rightGridBorderRight = new Rectangle(
            $addModalUid . "_right_grid_border_right",
        );
        $rightGridBorderRight->setOrigin(
            $rightGridX + $gridTotalWidth - $borderThick,
            $rightGridY,
        );
        $rightGridBorderRight->setSize($borderThick, $gridTotalHeight);
        $rightGridBorderRight->setColor(0x000000);
        $rightGridBorderRight->setRenderable(false);
        $rightGridBorderRight->addAttributes("z_index", 100042);
        $this->drawItems[] = $rightGridBorderRight;

        $buttonsY = $modalY + $modalHeight - 58;
        $buttonHeight = 36;
        $confirmButtonWidth = 120;
        $previewButtonWidth = 120;
        $cancelButtonWidth = 120;
        $buttonsStartX = $contentX;

        $confirmRect = new Rectangle($addModalUid . "_confirm_button_rect");
        $confirmRect->setOrigin($buttonsStartX, $buttonsY);
        $confirmRect->setSize($confirmButtonWidth, $buttonHeight);
        $confirmRect->setColor(0x0a8f2f);
        $confirmRect->setBorderRadius(6);
        $confirmRect->setRenderable(false);
        $confirmRect->addAttributes("z_index", 100060);
        $this->drawItems[] = $confirmRect;

        $confirmText = new Text($addModalUid . "_confirm_button_text");
        $confirmText->setCenterAnchor(true);
        $confirmText->setOrigin(
            $buttonsStartX + (int) floor($confirmButtonWidth / 2),
            $buttonsY + (int) floor($buttonHeight / 2),
        );
        $confirmText->setText("Conferma");
        $confirmText->setColor(0xffffff);
        $confirmText->setFontSize(14);
        $confirmText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $confirmText->setRenderable(false);
        $confirmText->addAttributes("z_index", 100061);
        $this->drawItems[] = $confirmText;

        $previewX = $buttonsStartX + $confirmButtonWidth + 12;
        $previewRect = new Rectangle($addModalUid . "_preview_button_rect");
        $previewRect->setOrigin($previewX, $buttonsY);
        $previewRect->setSize($previewButtonWidth, $buttonHeight);
        $previewRect->setColor(0x0b5ed7);
        $previewRect->setBorderRadius(6);
        $previewRect->setRenderable(false);
        $previewRect->addAttributes("z_index", 100060);
        $this->drawItems[] = $previewRect;

        $previewText = new Text($addModalUid . "_preview_button_text");
        $previewText->setCenterAnchor(true);
        $previewText->setOrigin(
            $previewX + (int) floor($previewButtonWidth / 2),
            $buttonsY + (int) floor($buttonHeight / 2),
        );
        $previewText->setText("Preview");
        $previewText->setColor(0xffffff);
        $previewText->setFontSize(14);
        $previewText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $previewText->setRenderable(false);
        $previewText->addAttributes("z_index", 100061);
        $this->drawItems[] = $previewText;

        $cancelX = $previewX + $previewButtonWidth + 12;
        $cancelRect = new Rectangle($addModalUid . "_cancel_button_rect");
        $cancelRect->setOrigin($cancelX, $buttonsY);
        $cancelRect->setSize($cancelButtonWidth, $buttonHeight);
        $cancelRect->setColor(0x6c757d);
        $cancelRect->setBorderRadius(6);
        $cancelRect->setRenderable(false);
        $cancelRect->addAttributes("z_index", 100060);
        $this->drawItems[] = $cancelRect;

        $cancelText = new Text($addModalUid . "_cancel_button_text");
        $cancelText->setCenterAnchor(true);
        $cancelText->setOrigin(
            $cancelX + (int) floor($cancelButtonWidth / 2),
            $buttonsY + (int) floor($buttonHeight / 2),
        );
        $cancelText->setText("Annulla");
        $cancelText->setColor(0xffffff);
        $cancelText->setFontSize(14);
        $cancelText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $cancelText->setRenderable(false);
        $cancelText->addAttributes("z_index", 100061);
        $this->drawItems[] = $cancelText;

        // Generate JS for opening/closing the add component modal and populating its grids
        $addModalJs = file_get_contents(
            resource_path(
                "js/function/entity_body/add_component_modal.blade.php",
            ),
        );
        $addModalJs = str_replace(
            "__MODAL_UID__",
            $parentModalUid,
            $addModalJs,
        );
        $addModalJs = str_replace(
            "__ADD_MODAL_UID__",
            $addModalUid,
            $addModalJs,
        );
        $this->gridScrollInitJs .= $addModalJs;
    }

    private function buildGridTemplate(
        $gridDraw,
        $modalUid,
        bool $withSymbol = false,
        bool $withInfoButton = false,
    ): void {
        $templateContainer = new Rectangle("template_container");
        $templateContainer->setColor(0x87ceeb);
        $templateContainer->setBorderColor(0x000000);
        $templateContainer->setBorderRadius(5);
        $templateContainer->addAttributes("use_cell_tooltip", false);

        $templateText = new Text("template_text");
        $templateText->setText("{label}");
        $templateText->setColor(0x000000);
        $templateText->setFontSize(14);
        $templateText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $templateText->addAttributes("use_cell_tooltip", false);

        $templateWhiteSquare = new Rectangle("template_white_square");
        $templateWhiteSquare->setColor(0xffffff);
        $templateWhiteSquare->setBorderColor(0x000000);
        $templateWhiteSquare->setThickness(2);
        $templateWhiteSquare->setBorderRadius(2);
        $templateWhiteSquare->addAttributes("use_cell_tooltip", false);

        $templateImage = new Image("template_image");
        $templateImage->setSrc("{image}");
        $templateImage->addAttributes("use_cell_tooltip", false);

        $templateGrid = new TemplateGridDraw($modalUid . "_template");
        $templateGrid->addTemplate($templateContainer);
        if ($withSymbol) {
            $templateSymbol = new Text("template_symbol");
            $templateSymbol->setText("{symbol}");
            $templateSymbol->setColor(0x000000);
            $templateSymbol->setFontSize(14);
            $templateSymbol->setFontFamily(
                \App\Helper\FontAwesome::fontFamily(),
            );
            $templateSymbol->addAttributes("use_cell_tooltip", false);
            $templateGrid->addTemplate($templateSymbol);
        }
        if ($withInfoButton) {
            $templateInfoButton = new Rectangle("template_info_button");
            $templateInfoButton->setColor(0x000000);
            $templateInfoButton->setBorderRadius(3);
            $templateInfoButton->addAttributes(
                "template_role",
                "info_button_rect",
            );
            $templateInfoButton->addAttributes("use_cell_tooltip", true);

            $templateInfoText = new Text("template_info_text");
            $templateInfoText->setText("Info");
            $templateInfoText->setColor(0xffffff);
            $templateInfoText->setFontSize(10);
            $templateInfoText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $templateInfoText->addAttributes(
                "template_role",
                "info_button_text",
            );
            $templateInfoText->addAttributes("use_cell_tooltip", true);

            $templateAddButton = new Rectangle("template_add_button");
            $templateAddButton->setColor(0xe07b00);
            $templateAddButton->setBorderRadius(3);
            $templateAddButton->addAttributes(
                "template_role",
                "add_button_rect",
            );
            $templateAddButton->addAttributes("use_cell_tooltip", false);

            $templateAddText = new Text("template_add_text");
            $templateAddText->setText("Aggiungi");
            $templateAddText->setColor(0x000000);
            $templateAddText->setFontSize(10);
            $templateAddText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $templateAddText->addAttributes("template_role", "add_button_text");
            $templateAddText->addAttributes("use_cell_tooltip", false);

            $templateWhiteSquare->addAttributes(
                "template_role",
                "component_frame",
            );
            $templateImage->addAttributes("template_role", "component_image");
            $templateGrid->addTemplate($templateInfoButton);
            $templateGrid->addTemplate($templateInfoText);
            $templateGrid->addTemplate($templateAddButton);
            $templateGrid->addTemplate($templateAddText);
        }
        $templateGrid->addTemplate($templateText);
        $templateGrid->addTemplate($templateWhiteSquare);
        $templateGrid->addTemplate($templateImage);
        $templateGrid->addTemplateWithMapping("{label}", "name");
        $templateGrid->addTemplateWithMapping("{image}", "image");
        if ($withSymbol) {
            $templateGrid->addTemplateWithMapping("{symbol}", "symbol");
        }
        $templateGrid->addTemplateWithMapping("{tooltip}", "tooltip");
        $gridDraw->setTemplateGrid($templateGrid);
    }
}
