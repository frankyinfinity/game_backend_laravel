<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Colors;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;
use Illuminate\Support\Str;

class ModalDraw
{
    private string $uid;
    private array $drawItems = [];
    private array $contentItems = [];

    private int $x = 0;
    private int $y = 0;
    private int $width = 700;
    private int $height = 520;
    private int $screenWidth = 1280;
    private int $screenHeight = 720;
    private bool $autoCenter = true;
    private bool $renderable = true;

    private string $title = 'Modal';
    private int $titleFontSize = 24;

    private int $headerHeight = 60;
    private int $contentPadding = 16;

    private int $backgroundColor = Colors::WHITE;
    private int $headerColor = Colors::LIGHT_GRAY;
    private int $borderColor = Colors::BLACK;
    private int $titleColor = Colors::BLACK;
    private int $closeButtonColor = Colors::DARK_GRAY;
    private int $closeTextColor = Colors::WHITE;
    private int $contentColor = 0xF4F4F4;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function setOrigin(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
        $this->autoCenter = false;
    }

    public function setScreenSize(int $width, int $height): void
    {
        $this->screenWidth = $width;
        $this->screenHeight = $height;
    }

    public function setSize(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function setBackgroundColor(int $color): void
    {
        $this->backgroundColor = $color;
    }

    public function setHeaderColor(int $color): void
    {
        $this->headerColor = $color;
    }

    public function setBorderColor(int $color): void
    {
        $this->borderColor = $color;
    }

    public function setContentColor(int $color): void
    {
        $this->contentColor = $color;
    }

    public function addContentItem(BasicDraw $draw, int $offsetX = 0, int $offsetY = 0): void
    {
        $this->contentItems[] = [
            'draw' => $draw,
            'offset_x' => $offsetX,
            'offset_y' => $offsetY,
        ];
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function build(): void
    {
        if ($this->autoCenter) {
            $this->x = (int) floor(($this->screenWidth - $this->width) / 2);
            $this->y = (int) floor(($this->screenHeight - $this->height) / 2);
        }

        $this->drawItems = [];
        $uid = $this->uid;

        $body = new Rectangle($uid . '_body');
        $body->setOrigin($this->x, $this->y);
        $body->setSize($this->width, $this->height);
        $body->setColor($this->backgroundColor);
        $body->setBorderRadius(10);
        $body->setBorderColor($this->borderColor);
        $body->setRenderable($this->renderable);
        $body->addAttributes('z_index', 20000);
        $this->drawItems[] = $body;

        $header = new Rectangle($uid . '_header');
        $header->setOrigin($this->x, $this->y);
        $header->setSize($this->width, $this->headerHeight);
        $header->setColor($this->headerColor);
        $header->setRenderable($this->renderable);
        $header->addAttributes('z_index', 20001);
        $body->addChild($header);
        $this->drawItems[] = $header;

        $title = new Text($uid . '_title');
        $title->setOrigin($this->x + 16, $this->y + 16);
        $title->setText($this->title);
        $title->setColor($this->titleColor);
        $title->setFontSize($this->titleFontSize);
        $title->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $title->setRenderable($this->renderable);
        $title->addAttributes('z_index', 20002);
        $body->addChild($title);
        $this->drawItems[] = $title;

        $closeSize = 28;
        $closeX = $this->x + $this->width - $closeSize - 12;
        $closeY = $this->y + 12;

        $closeButton = new Rectangle($uid . '_close_button');
        $closeButton->setOrigin($closeX, $closeY);
        $closeButton->setSize($closeSize, $closeSize);
        $closeButton->setColor($this->closeButtonColor);
        $closeButton->setBorderRadius(4);
        $closeButton->setRenderable($this->renderable);
        $closeButton->addAttributes('z_index', 20003);
        $body->addChild($closeButton);

        $closeText = new Text($uid . '_close_text');
        $closeText->setCenterAnchor(true);
        $closeText->setOrigin($closeX + (int) floor($closeSize / 2), $closeY + (int) floor($closeSize / 2));
        $closeText->setText('X');
        $closeText->setFontSize(18);
        $closeText->setColor($this->closeTextColor);
        $closeText->setRenderable($this->renderable);
        $closeText->addAttributes('z_index', 20004);
        $body->addChild($closeText);

        $jsClose = file_get_contents(resource_path('js/function/modal/click_close_modal.blade.php'));
        $jsClose = str_replace('__MODAL_UID__', $uid, $jsClose);
        $jsClose = Helper::setCommonJsCode($jsClose, Str::random(20));
        $closeButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose);
        $closeText->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose);

        $this->drawItems[] = $closeButton;
        $this->drawItems[] = $closeText;

        $contentX = $this->x + $this->contentPadding;
        $contentY = $this->y + $this->headerHeight + $this->contentPadding;
        $contentWidth = $this->width - ($this->contentPadding * 2);
        $contentHeight = $this->height - $this->headerHeight - ($this->contentPadding * 2);

        $contentViewport = new Rectangle($uid . '_content_viewport');
        $contentViewport->setOrigin($contentX, $contentY);
        $contentViewport->setSize($contentWidth, $contentHeight);
        $contentViewport->setColor($this->contentColor);
        $contentViewport->setRenderable($this->renderable);
        $contentViewport->setBorderRadius(6);
        $contentViewport->addAttributes('z_index', 20005);
        $body->addChild($contentViewport);
        $this->drawItems[] = $contentViewport;

        $childUids = [];
        $basePositionsX = [];
        $basePositionsY = [];
        $itemWidths = [];
        $itemHeights = [];
        $basePoints = [];
        $initialRenderables = [];
        $contentLeft = null;
        $contentRight = null;
        $contentTop = null;
        $contentBottom = null;

        foreach ($this->contentItems as $index => $item) {
            /** @var BasicDraw $draw */
            $draw = $item['draw'];
            $before = $draw->buildJson();
            $originalRenderable = (bool) (($before['attributes']['renderable'] ?? true));
            $absoluteX = $contentX + $item['offset_x'];
            $absoluteY = $contentY + $item['offset_y'];
            $draw->setOrigin($absoluteX, $absoluteY);

            if ($draw instanceof MultiLine) {
                $beforeX = (isset($before['x']) && is_numeric($before['x'])) ? (int) $before['x'] : 0;
                $beforeY = (isset($before['y']) && is_numeric($before['y'])) ? (int) $before['y'] : 0;
                $draw->translatePoints($absoluteX - $beforeX, $absoluteY - $beforeY);
            }

            $draw->setRenderable($this->renderable);
            $draw->addAttributes('z_index', 20020 + $index);

            $preview = $draw->buildJson();
            $width = $this->estimateItemWidth($preview);
            $height = $this->estimateItemHeight($preview);

            $itemLeft = $absoluteX;
            $itemRight = $absoluteX + $width;
            $itemTop = $absoluteY;
            $itemBottom = $absoluteY + $height;

            if (isset($preview['points']) && is_array($preview['points']) && count($preview['points']) > 0) {
                $xs = array_map(static fn($p) => (int) ($p['x'] ?? 0), $preview['points']);
                $ys = array_map(static fn($p) => (int) ($p['y'] ?? 0), $preview['points']);
                $itemLeft = min($xs);
                $itemRight = max($xs);
                $itemTop = min($ys);
                $itemBottom = max($ys);
                $basePoints[$draw->getUid()] = $preview['points'];
            }

            $contentLeft = $contentLeft === null ? $itemLeft : min($contentLeft, $itemLeft);
            $contentRight = $contentRight === null ? $itemRight : max($contentRight, $itemRight);
            $contentTop = $contentTop === null ? $itemTop : min($contentTop, $itemTop);
            $contentBottom = $contentBottom === null ? $itemBottom : max($contentBottom, $itemBottom);

            $childUids[] = $draw->getUid();
            $basePositionsX[$draw->getUid()] = $absoluteX;
            $basePositionsY[$draw->getUid()] = $absoluteY;
            $itemWidths[$draw->getUid()] = $width;
            $itemHeights[$draw->getUid()] = $height;
            // Preserve the draw's intended visibility before modal-level hiding.
            $initialRenderables[$draw->getUid()] = $originalRenderable;
            $contentViewport->addChild($draw);
            $this->drawItems[] = $draw;
        }

        $contentViewport->addAttributes('scroll_child_uids', $childUids);
        $contentViewport->addAttributes('scroll_base_positions_x', $basePositionsX);
        $contentViewport->addAttributes('scroll_base_positions_y', $basePositionsY);
        $contentViewport->addAttributes('scroll_item_widths', $itemWidths);
        $contentViewport->addAttributes('scroll_item_heights', $itemHeights);
        $contentViewport->addAttributes('scroll_base_points', $basePoints);
        $contentViewport->addAttributes('scroll_initial_renderables', $initialRenderables);
        $contentViewport->addAttributes('scroll_viewport_left', $contentX);
        $contentViewport->addAttributes('scroll_viewport_right', $contentX + $contentWidth);
        $contentViewport->addAttributes('scroll_viewport_top', $contentY);
        $contentViewport->addAttributes('scroll_viewport_bottom', $contentY + $contentHeight);
        $contentViewport->addAttributes('scroll_content_left', $contentLeft ?? $contentX);
        $contentViewport->addAttributes('scroll_content_right', $contentRight ?? $contentX);
        $contentViewport->addAttributes('scroll_content_top', $contentTop ?? $contentY);
        $contentViewport->addAttributes('scroll_content_bottom', $contentBottom ?? $contentY);
        $contentViewport->addAttributes('modal_uid', $uid);

        $jsDrag = file_get_contents(resource_path('js/function/modal/drag_scroll_modal.blade.php'));
        $jsDrag = str_replace('__MODAL_UID__', $uid, $jsDrag);
        $jsDrag = Helper::setCommonJsCode($jsDrag, Str::random(20));
        $contentViewport->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsDrag);

    }

    private function estimateItemHeight(array $draw): int
    {
        if (isset($draw['points']) && is_array($draw['points']) && count($draw['points']) > 0) {
            $ys = array_map(static fn($p) => (int) ($p['y'] ?? 0), $draw['points']);
            return max(1, max($ys) - min($ys));
        }
        if (isset($draw['height']) && is_numeric($draw['height'])) {
            return (int) $draw['height'];
        }
        if (isset($draw['size']) && is_numeric($draw['size'])) {
            return (int) $draw['size'];
        }
        if (isset($draw['radius']) && is_numeric($draw['radius'])) {
            return (int) ($draw['radius'] * 2);
        }
        if (isset($draw['fontSize']) && is_numeric($draw['fontSize'])) {
            return (int) $draw['fontSize'] + 10;
        }

        return 40;
    }

    private function estimateItemWidth(array $draw): int
    {
        if (isset($draw['points']) && is_array($draw['points']) && count($draw['points']) > 0) {
            $xs = array_map(static fn($p) => (int) ($p['x'] ?? 0), $draw['points']);
            return max(1, max($xs) - min($xs));
        }
        if (isset($draw['width']) && is_numeric($draw['width'])) {
            return (int) $draw['width'];
        }
        if (isset($draw['size']) && is_numeric($draw['size'])) {
            return (int) $draw['size'];
        }
        if (isset($draw['radius']) && is_numeric($draw['radius'])) {
            return (int) ($draw['radius'] * 2);
        }
        if (isset($draw['fontSize']) && is_numeric($draw['fontSize'])) {
            return max(80, ((int) $draw['fontSize'] * 8));
        }

        return 120;
    }
}
