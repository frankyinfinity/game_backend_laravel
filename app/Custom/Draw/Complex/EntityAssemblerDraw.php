<?php

namespace App\Custom\Draw\Complex;

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

        // Add click handler to open modal
        $modalUid = 'objective_modal_assembler_' . $this->uid;
        $jsOpen = file_get_contents(resource_path('js/function/modal/click_open_modal.blade.php'));
        $jsOpen = str_replace('__MODAL_UID__', $modalUid, $jsOpen);
        $jsOpen = str_replace('__name__', 'open_assembler_modal_' . $this->uid, $jsOpen);
        $jsOpen = Helper::setCommonJsCode($jsOpen, Str::random(20));
        $rect->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsOpen);
        $square->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsOpen);

        $this->drawItems[] = $rect;
        $this->drawItems[] = $text;
        $this->drawItems[] = $square;

        // Build modal (without scroll)
        $this->buildModal($modalUid);
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
        $body->setBorderColor(0x000000);
        $body->setRenderable(false);
        $body->addAttributes('z_index', 20000);
        $this->drawItems[] = $body;

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

        // Content area (empty for now, no scroll)
        $contentPadding = 16;
        $contentX = $modalX + $contentPadding;
        $contentY = $modalY + $headerHeight + $contentPadding;
        $contentWidth = $modalWidth - ($contentPadding * 2);
        $contentHeight = $modalHeight - $headerHeight - ($contentPadding * 2);

        $contentViewport = new Rectangle($modalUid . '_content_viewport');
        $contentViewport->setOrigin($contentX, $contentY);
        $contentViewport->setSize($contentWidth, $contentHeight);
        $contentViewport->setColor(0xF4F4F4);
        $contentViewport->setRenderable(false);
        $contentViewport->setBorderRadius(6);
        $contentViewport->addAttributes('z_index', 20005);
        $body->addChild($contentViewport);
        $this->drawItems[] = $contentViewport;
    }
}
