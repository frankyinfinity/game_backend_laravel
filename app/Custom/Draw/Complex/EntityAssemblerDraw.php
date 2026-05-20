<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Complex\GridDraw;
use App\Custom\Draw\Complex\TabDraw;

use App\Custom\Draw\Primitive\BasicDraw;
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

        // Right side (40%) - TabDraw with 'Corpo' (primary) and 'Componenti' tabs
        $rightWidth = $contentWidth - $leftWidth;
        $rightX = $contentX + $leftWidth;

        // Create containers for tabs
        $componentiContainer = new Rectangle($modalUid . '_tab_container_componenti');
        $componentiContainer->setOrigin($rightX, $contentY + 40);
        $componentiContainer->setSize($rightWidth, $contentHeight - 40);
        $componentiContainer->setColor(0x87CEEB); // Celeste (sky blue)
        $componentiContainer->setRenderable(false);
        $componentiContainer->addAttributes('z_index', 20062);

        $componentiText = new Text($modalUid . '_tab_text_componenti');
        $componentiText->setOrigin($rightX + 10, $contentY + 50);
        $componentiText->setText('Contenuto Componenti');
        $componentiText->setColor(0x000000);
        $componentiText->setFontSize(16);
        $componentiText->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $componentiText->setRenderable(false);
        $componentiText->addAttributes('z_index', 20063);

        // Create GridDraw for tab_corpo with 100 elements
        $gridDraw = new GridDraw($modalUid . '_grid_corpo');
        $gridDraw->setOrigin($rightX, $contentY + 40);
        $gridDraw->setSize($rightWidth, $contentHeight - 40);
        $gridDraw->setRenderable(false);
        $gridDraw->setBaseZIndex(20060);
        $gridDraw->setElementsPerRow(3);
        $gridDraw->setElementSpacing(2);
        $gridDraw->generateElements(100, $modalUid . '_grid');
        $gridDraw->build();
        $gridElementUids = $gridDraw->getElementUids();
        $gridScrollUids = $gridDraw->getScrollUids();
        $this->gridScrollInitJs = $gridDraw->getScrollInitJs();

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
            $modalUid . '_tabs_tab_tab_corpo',
            $modalUid . '_tabs_tab_text_tab_corpo',
            $modalUid . '_tabs_tab_tab_componenti',
            $modalUid . '_tabs_tab_text_tab_componenti',
            $modalUid . '_tabs_container_bg',
            $modalUid . '_grid_corpo_viewport',
            $modalUid . '_grid_corpo_panel',
            $modalUid . '_tab_container_componenti',
            $modalUid . '_tab_text_componenti',
            $modalUid . '_tabs_tab_border_top_tab_corpo',
            $modalUid . '_tabs_tab_border_bottom_tab_corpo',
            $modalUid . '_tabs_tab_border_left_tab_corpo',
            $modalUid . '_tabs_tab_border_right_tab_corpo',
            $modalUid . '_tabs_tab_border_top_tab_componenti',
            $modalUid . '_tabs_tab_border_bottom_tab_componenti',
            $modalUid . '_tabs_tab_border_left_tab_componenti',
            $modalUid . '_tabs_tab_border_right_tab_componenti'
        ], $gridElementUids, $gridScrollUids));
        $contentViewport->addAttributes('scroll_initial_renderables', array_merge([
            $modalUid . '_separator_1' => true,
            $modalUid . '_border_top' => true,
            $modalUid . '_border_bottom' => true,
            $modalUid . '_border_left' => true,
            $modalUid . '_border_right' => true,
            $modalUid . '_tabs_tab_tab_corpo' => true,
            $modalUid . '_tabs_tab_text_tab_corpo' => true,
            $modalUid . '_tabs_tab_tab_componenti' => true,
            $modalUid . '_tabs_tab_text_tab_componenti' => true,
            $modalUid . '_tabs_container_bg' => true,
            $modalUid . '_grid_corpo_viewport' => true,
            $modalUid . '_grid_corpo_panel' => true,
            $modalUid . '_tab_container_componenti' => false,
            $modalUid . '_tab_text_componenti' => false,
            $modalUid . '_tabs_tab_border_top_tab_corpo' => true,
            $modalUid . '_tabs_tab_border_bottom_tab_corpo' => true,
            $modalUid . '_tabs_tab_border_left_tab_corpo' => true,
            $modalUid . '_tabs_tab_border_right_tab_corpo' => true,
            $modalUid . '_tabs_tab_border_top_tab_componenti' => false,
            $modalUid . '_tabs_tab_border_bottom_tab_componenti' => false,
            $modalUid . '_tabs_tab_border_left_tab_componenti' => false,
            $modalUid . '_tabs_tab_border_right_tab_componenti' => false
        ], array_fill_keys($gridElementUids, true), array_fill_keys($gridScrollUids, true)));
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
        foreach ($gridDraw->getDrawItems() as $item) {
            $json = $item->buildJson();
            $gridPositions[$item->getUid()] = ['x' => $json['x'], 'y' => $json['y']];
        }

        // Create TabDraw
        $tabDraw = new TabDraw($modalUid . '_tabs');
        $tabDraw->setOrigin($rightX, $contentY);
        $tabDraw->setSize($rightWidth, $contentHeight);
        $tabDraw->setRenderable(false);
        $tabDraw->setBaseZIndex(20070);
        $tabDraw->addTab('Corpo', 'tab_corpo', $gridDraw->getDrawItems());
        $tabDraw->addTab('Componenti', 'tab_componenti', [$componentiContainer, $componentiText]);
        $tabDraw->setPrimaryTab('tab_corpo');
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
}
