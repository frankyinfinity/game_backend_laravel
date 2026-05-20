<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Helper\Helper;
use Illuminate\Support\Str;

class TabDraw
{
    private string $uid;
    private array $drawItems = [];
    private array $tabs = [];
    private ?string $primaryTab = null;
    private ?string $activeTab = null;

    private int $x = 0;
    private int $y = 0;
    private int $width = 400;
    private int $height = 300;
    private int $tabHeight = 40;
    private int $tabBackgroundColor = 0xE0E0E0;
    private int $tabActiveColor = 0xFFFFFF;
    private int $tabInactiveColor = 0xC0C0C0;
    private int $tabTextColor = 0x000000;
    private int $containerBackgroundColor = 0xFFFFFF;
    private bool $renderable = true;
    private int $baseZIndex = 100;

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

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function setBaseZIndex(int $zIndex): void
    {
        $this->baseZIndex = $zIndex;
    }

    public function addTab(string $tabName, string $tabUid, array $elements): void
    {
        $this->tabs[$tabUid] = [
            'name' => $tabName,
            'elements' => $elements,
        ];
    }

    public function setPrimaryTab(string $tabUid): void
    {
        $this->primaryTab = $tabUid;
        if ($this->activeTab === null) {
            $this->activeTab = $tabUid;
        }
    }

    public function setActiveTab(string $tabName): void
    {
        $this->activeTab = $tabName;
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function build(): void
    {
        $this->drawItems = [];
        $uid = $this->uid;
        $x = $this->x;
        $y = $this->y;
        $width = $this->width;
        $height = $this->height;
        $tabHeight = $this->tabHeight;

        $tabNames = array_keys($this->tabs);
        $tabCount = count($tabNames);
        $tabWidth = $tabCount > 0 ? $width / $tabCount : 0;

        // Draw tabs
        foreach ($tabNames as $index => $tabUid) {
            $tabData = $this->tabs[$tabUid];
            $tabName = $tabData['name'];
            $tabX = $x + ($index * $tabWidth);
            $tabY = $y;

            $isActive = ($tabUid === $this->activeTab);
            $isPrimary = ($tabUid === $this->primaryTab);

            $tabBg = $this->tabInactiveColor;

            $tab = new Rectangle($uid . '_tab_' . $tabUid);
            $tab->setOrigin($tabX, $tabY);
            $tab->setSize($tabWidth, $tabHeight);
            $tab->setColor($tabBg);
            $tab->setRenderable($this->renderable);
            $tab->addAttributes('z_index', $this->baseZIndex + $index + 100);
            $tab->addAttributes('tab_uid', $tabUid);
            $this->drawItems[] = $tab;

            // Tab border (separate rectangles for reliability) - create for ALL tabs
            $borderThickness = 3;
            // Top border
            $borderTop = new Rectangle($uid . '_tab_border_top_' . $tabUid);
            $borderTop->setOrigin($tabX, $tabY);
            $borderTop->setSize($tabWidth, $borderThickness);
            $borderTop->setColor(0x000000);
            $borderTop->setRenderable(false); // Always start hidden
            $borderTop->addAttributes('z_index', $this->baseZIndex + $index + 101);
            $this->drawItems[] = $borderTop;

            // Bottom border
            $borderBottom = new Rectangle($uid . '_tab_border_bottom_' . $tabUid);
            $borderBottom->setOrigin($tabX, $tabY + $tabHeight - $borderThickness);
            $borderBottom->setSize($tabWidth, $borderThickness);
            $borderBottom->setColor(0x000000);
            $borderBottom->setRenderable(false); // Always start hidden
            $borderBottom->addAttributes('z_index', $this->baseZIndex + $index + 101);
            $this->drawItems[] = $borderBottom;

            // Left border
            $borderLeft = new Rectangle($uid . '_tab_border_left_' . $tabUid);
            $borderLeft->setOrigin($tabX, $tabY);
            $borderLeft->setSize($borderThickness, $tabHeight);
            $borderLeft->setColor(0x000000);
            $borderLeft->setRenderable(false); // Always start hidden
            $borderLeft->addAttributes('z_index', $this->baseZIndex + $index + 101);
            $this->drawItems[] = $borderLeft;

            // Right border
            $borderRight = new Rectangle($uid . '_tab_border_right_' . $tabUid);
            $borderRight->setOrigin($tabX + $tabWidth - $borderThickness, $tabY);
            $borderRight->setSize($borderThickness, $tabHeight);
            $borderRight->setColor(0x000000);
            $borderRight->setRenderable(false); // Always start hidden
            $borderRight->addAttributes('z_index', $this->baseZIndex + $index + 101);
            $this->drawItems[] = $borderRight;

            // Tab text
            $text = new Text($uid . '_tab_text_' . $tabUid);
            $text->setCenterAnchor(true);
            $text->setOrigin($tabX + ($tabWidth / 2), $tabY + ($tabHeight / 2));
            $text->setText($tabName);
            $text->setColor($this->tabTextColor);
            $text->setFontSize(14);
            $text->setFontFamily(Helper::DEFAULT_FONT_FAMILY);
            $text->setRenderable($this->renderable);
            $text->addAttributes('z_index', $this->baseZIndex + $index + 150);
            $this->drawItems[] = $text;

            // Click handler for tab
            $jsClick = $this->generateTabClickScript($tabUid);
            $tab->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClick);
            $text->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsClick);
        }

        // Container area
        $containerY = $y + $tabHeight;
        $containerHeight = $height - $tabHeight;

        $containerBg = new Rectangle($uid . '_container_bg');
        $containerBg->setOrigin($x, $containerY);
        $containerBg->setSize($width, $containerHeight);
        $containerBg->setColor($this->containerBackgroundColor);
        $containerBg->setRenderable($this->renderable);
        $containerBg->addAttributes('z_index', $this->baseZIndex - 50);
        $this->drawItems[] = $containerBg;

        // Draw all containers (they will be shown/hidden via JavaScript)
        foreach ($this->tabs as $tabUid => $tabData) {
            $elements = $tabData['elements'];
            foreach ($elements as $element) {
                if ($element instanceof BasicDraw) {
                    $element->setOrigin($x, $containerY);
                    // Don't override renderable - let it stay as set by the element itself
                    $element->addAttributes('z_index', $this->baseZIndex - 40);
                    $element->addAttributes('tab_uid', $tabUid);
                    $this->drawItems[] = $element;
                }
            }
        }

    }

    private function generateTabClickScript(string $tabUid): string
    {
        $elementUids = $this->getElementUidsArray($tabUid);
        $allElementUids = $this->getAllElementUidsArray();
        $script = "(function() {
    console.log('Tab clicked: {$tabUid}');
    // Hide all elements
    const allElementUids = {$allElementUids};
    allElementUids.forEach(function(uid) {
        if (shapes[uid]) {
            shapes[uid].renderable = false;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = false;
        }
    });

    // Show active elements
    const elementUids = {$elementUids};
    elementUids.forEach(function(uid) {
        console.log('Showing element:', uid);
        if (shapes[uid]) {
            shapes[uid].renderable = true;
        }
        if (objects[uid] && objects[uid].attributes) {
            objects[uid].attributes.renderable = true;
        }
    });

    // Update tab borders
    const tabUids = {$this->getTabUidsArray()};
    console.log('Tab UIDs:', tabUids);
    tabUids.forEach(function(uid) {
        const isActive = (uid === '{$tabUid}');
        const borderTop = '{$this->uid}_tab_border_top_' + uid;
        const borderBottom = '{$this->uid}_tab_border_bottom_' + uid;
        const borderLeft = '{$this->uid}_tab_border_left_' + uid;
        const borderRight = '{$this->uid}_tab_border_right_' + uid;

        console.log('Tab:', uid, 'Active:', isActive, 'Borders:', [borderTop, borderBottom, borderLeft, borderRight]);

        [borderTop, borderBottom, borderLeft, borderRight].forEach(function(borderUid) {
            console.log('Setting border:', borderUid, 'renderable:', isActive);
            if (shapes[borderUid]) {
                shapes[borderUid].renderable = isActive;
            }
            if (objects[borderUid] && objects[borderUid].attributes) {
                objects[borderUid].attributes.renderable = isActive;
            }
        });
    });
})();";
        return Helper::setCommonJsCode($script, Str::random(20));
    }

    private function getElementUidsArray(string $tabUid): string
    {
        $uids = [];
        if (isset($this->tabs[$tabUid]['elements'])) {
            foreach ($this->tabs[$tabUid]['elements'] as $element) {
                if ($element instanceof BasicDraw) {
                    $uids[] = "'" . $element->getUid() . "'";
                }
            }
        }
        return '[' . implode(', ', $uids) . ']';
    }

    private function getAllElementUidsArray(): string
    {
        $uids = [];
        foreach ($this->tabs as $tabUid => $tabData) {
            if (isset($tabData['elements'])) {
                foreach ($tabData['elements'] as $element) {
                    if ($element instanceof BasicDraw) {
                        $uids[] = "'" . $element->getUid() . "'";
                    }
                }
            }
        }
        return '[' . implode(', ', $uids) . ']';
    }

    private function getTabUidsArray(): string
    {
        return json_encode(array_keys($this->tabs));
    }
}
