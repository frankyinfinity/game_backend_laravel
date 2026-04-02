<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Colors;
use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Support\ScrollGroup;
use App\Helper\Helper;
use Illuminate\Support\Str;

class TilePanelDraw
{
    private const PANEL_WIDTH = 320;
    private const PANEL_HEIGHT = 400;
    private const HEADER_HEIGHT = 50;
    private const MAX_CONTENT_LINES = 10;
    private const CONTENT_PADDING = 16;

    private string $uid;
    private array $drawItems = [];

    public function __construct()
    {
        $this->uid = 'tile_panel';
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function build(): void
    {
        $uid = $this->uid;
        $x = 0;
        $y = 0;
        $width = self::PANEL_WIDTH;
        $height = self::PANEL_HEIGHT;

        // Body
        $body = new Rectangle($uid . '_body');
        $body->setOrigin($x, $y);
        $body->setSize($width, $height);
        $body->setColor(0x1E293B);
        $body->setBorderRadius(12);
        $body->setBorderColor(0x334155);
        $body->setThickness(1);
        $body->setRenderable(false);
        $body->addAttributes('z_index', 19000);
        $this->drawItems[] = $body;

        // Header
        $header = new Rectangle($uid . '_header');
        $header->setOrigin($x, $y);
        $header->setSize($width, self::HEADER_HEIGHT);
        $header->setColor(0x0F172A);
        $header->setBorderRadius(12);
        $header->setRenderable(false);
        $header->addAttributes('z_index', 19001);
        $this->drawItems[] = $header;

        // Title
        $title = new Text($uid . '_title');
        $title->setOrigin($x + self::CONTENT_PADDING, $y + 14);
        $title->setText('Dettagli Tile');
        $title->setFontSize(18);
        $title->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
        $title->setColor(0x38BDF8);
        $title->setRenderable(false);
        $title->addAttributes('z_index', 19002);
        $this->drawItems[] = $title;

        // Close button
        $closeSize = 28;
        $closeX = $x + $width - $closeSize - 10;
        $closeY = $y + 10;

        $closeButton = new Rectangle($uid . '_close_button');
        $closeButton->setOrigin($closeX, $closeY);
        $closeButton->setSize($closeSize, $closeSize);
        $closeButton->setColor(0x334155);
        $closeButton->setBorderRadius(6);
        $closeButton->setRenderable(false);
        $closeButton->addAttributes('z_index', 19003);

        $jsClose = file_get_contents(resource_path('js/function/modal/click_close_tile_panel.blade.php'));
        $jsClose = Helper::setCommonJsCode($jsClose, Str::random(20));
        $closeButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose);
        $this->drawItems[] = $closeButton;

        // Close text
        $closeText = new Text($uid . '_close_text');
        $closeText->setCenterAnchor(true);
        $closeText->setOrigin($closeX + (int) floor($closeSize / 2), $closeY + (int) floor($closeSize / 2));
        $closeText->setText('X');
        $closeText->setFontSize(16);
        $closeText->setColor(0xFFFFFF);
        $closeText->setRenderable(false);
        $closeText->addAttributes('z_index', 19004);

        $jsClose2 = file_get_contents(resource_path('js/function/modal/click_close_tile_panel.blade.php'));
        $jsClose2 = Helper::setCommonJsCode($jsClose2, Str::random(20));
        $closeText->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClose2);
        $this->drawItems[] = $closeText;

        // Content lines
        $contentStartY = $y + self::HEADER_HEIGHT + self::CONTENT_PADDING;
        for ($i = 0; $i < self::MAX_CONTENT_LINES; $i++) {
            $lineY = $contentStartY + ($i * 28);
            $line = new Text($uid . '_content_' . $i);
            $line->setOrigin($x + self::CONTENT_PADDING, $lineY);
            $line->setText('');
            $line->setFontSize(14);
            $line->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $line->setColor(0xE2E8F0);
            $line->setRenderable(false);
            $line->addAttributes('z_index', 19010 + $i);
            $this->drawItems[] = $line;
        }

    }
}
