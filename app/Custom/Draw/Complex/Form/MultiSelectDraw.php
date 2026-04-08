<?php

namespace App\Custom\Draw\Complex\Form;

use App\Custom\Draw\Primitive\BasicDraw;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectDraw;
use App\Helper\Helper;
use Illuminate\Support\Str;
use App\Custom\Colors;

class MultiSelectDraw {

    private string $uid;
    public function getUid() {
        return $this->uid;
    }

    private string $sessionId;
    public function __construct($uid, $sessionId) {

        $this->uid = $uid;
        $this->sessionId = $sessionId;

        $this->name = '';
        $this->required = false;
        $this->x = 0;
        $this->y = 0;
        $this->width = 0;
        $this->height = 0;
        $this->titleColor = 0x000000;
        $this->backgroundColor = 0x000000;
        $this->borderColor = 0x000000;
        $this->borderThickness = 0;
        $this->boxIconColor = 0x000000;
        $this->boxIconTextColor = 0x000000;

        $this->options = [];
        $this->optionId = '';
        $this->optionText = '';
        $this->optionShowDisplay = 5;
        $this->onChangePath = '';
        $this->onChangeReplacements = [];
        $this->uidValueElement = $this->uid.'_value_ids';

        $this->drawItems = [];

    }

    private string $name;
    public function setName($name) {
        $this->name = $name;
    }
    public function getName() {
        return $this->name;
    }

    private bool $required;
    public function setRequired(bool $required) {
        $this->required = $required;
    }

    private string $title;
    public function setTitle($title) {
        $this->title = $title;
    }

    private $x;
    private $y;
    public function setOrigin($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }

    private $width;
    private $height;
    public function setSize($width, $height) {
        $this->width = $width;
        $this->height = $height;
    }

    private $titleColor;
    public function setTitleColor($titleColor) {
        $this->titleColor = $titleColor;
    }

    private $backgroundColor;
    public function setBackgroundColor($backgroundColor) {
        $this->backgroundColor = $backgroundColor;
    }

    private $borderColor;
    public function setBorderColor($borderColor) {
        $this->borderColor = $borderColor;
    }

    private $borderThickness;
    public function setBorderThickness($borderThickness) {
        $this->borderThickness = $borderThickness;
    }

    private $boxIconColor;
    public function setBoxIconColor($boxIconColor) {
        $this->boxIconColor = $boxIconColor;
    }

    private $boxIconTextColor;
    public function setBoxIconTextColor($boxIconTextColor) {
        $this->boxIconTextColor = $boxIconTextColor;
    }

    private $valueColor;
    public function setValueColor($valueColor) {
        $this->valueColor = $valueColor;
    }

    private string $uidValueElement;
    public function getUidValueElement() {
        return $this->uidValueElement;
    }

    private array $options;
    public function setOptions(array $options) {
        $this->options = $options;
    }

    private string $optionId;
    public function setOptionId(string $optionId) {
        $this->optionId = $optionId;
    }

    private string $optionText;
    public function setOptionText(string $optionText) {
        $this->optionText = $optionText;
    }

    private int $optionShowDisplay;
    public function setOptionShowDisplay(int $optionShowDisplay) {
        $this->optionShowDisplay = $optionShowDisplay;
    }

    private string $onChangePath;
    private array $onChangeReplacements = [];
    public function setOnChange(string $path, array $replacements = []) {
        $this->onChangePath = $path;
        $this->onChangeReplacements = $replacements;
    }

    private $drawItems = [];
    public function getDrawItems() {
        return $this->drawItems;
    }

    public function build() {

        $drawItems = [];

        $x = $this->x;
        $y = $this->y;
        $width = $this->width;
        $height = $this->height;

        $options = $this->options;
        $optionId = $this->optionId;
        $optionText = $this->optionText;

        $optionShowDisplay = $this->optionShowDisplay;
        $heightPanel = $height * $optionShowDisplay;
        $heightOption = $heightPanel / $optionShowDisplay;

        $title = new Text($this->uid.'_title');
        $title->setFontSize(20);
        $title->setColor($this->titleColor);
        $title->setOrigin($x, $y);
        $title->setText($this->title.($this->required?'*':''));
        $title->setRenderable(true);
        $this->drawItems[] = $title;

        $y += 25;

        $body = new Rectangle($this->uid.'_body_multiselect');
        $body->setOrigin($x, $y);
        $body->setSize($width, $height);
        $body->setColor($this->backgroundColor);
        $body->setRenderable(true);

        $body->addAttributes('border_not_active_color', $this->borderColor);
        $body->addAttributes('border_active_color', 0x0000FF);
        $body->addAttributes('active', false);
        $body->addAttributes('currentStart', 0);
        $body->addAttributes('optionShowDisplay', $this->optionShowDisplay);
        $body->addAttributes('totalOptions', count($this->options));
        $body->addAttributes('heightOption', $heightOption);
        $body->addAttributes('optionIds', array_map(function($option, $index) use ($optionId) { return $option[$optionId] ?? $index; }, $this->options, array_keys($this->options)));
        $body->addAttributes('selectedOptionIds', []);
        
        $onChangeJs = '';
        if ($this->onChangePath && file_exists($this->onChangePath)) {
            $onChangeJs = file_get_contents($this->onChangePath);
            foreach ($this->onChangeReplacements as $key => $value) {
                $onChangeJs = str_replace($key, $value, $onChangeJs);
            }
            $onChangeJs = Helper::setCommonJsCode($onChangeJs, Str::random(20));
        }
        $body->addAttributes('onChangeJs', $onChangeJs);

        $jsPathClickInput = resource_path('js/function/entity/click_multiselect.blade.php');
        $jsPathClickInput = file_get_contents($jsPathClickInput);
        $jsPathClickInput = Helper::setCommonJsCode($jsPathClickInput, Str::random(20));
        $body->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsPathClickInput);

        $this->drawItems[] = $body;

        $border = new MultiLine($this->uid.'_border_multiselect');
        $border->setPoint($x, $y);
        $border->setPoint($x+$width, $y);
        $border->setPoint($x+$width, $y+$height);
        $border->setPoint($x, $y+$height);
        $border->setPoint($x, $y);
        $border->setThickness($this->borderThickness);
        $border->setColor($this->borderColor);
        $border->setRenderable(true);
        $this->drawItems[] = $border;

        $boxIcon = new Square($this->uid.'_box_icon');
        $boxIcon->setOrigin($x + $width - $height, $y);
        $boxIcon->setSize($height, $height);
        $boxIcon->setColor($this->boxIconColor);
        $boxIcon->setRenderable(true);
        $this->drawItems[] = $boxIcon;

        $centerSquare = $boxIcon->getCenter();

        $boxIconText = new Text($this->uid.'_box_icon_text');
        $boxIconText->setCenterAnchor(true);
        $boxIconText->setFontSize(24);
        $boxIconText->setOrigin($centerSquare['x'], $centerSquare['y']);
        $boxIconText->setColor($this->boxIconTextColor);
        $boxIconText->setText('V');
        $boxIconText->setRenderable(true);
        $this->drawItems[] = $boxIconText;

        $valueText = new Text($this->uid.'_value_text');
        $valueText->setOrigin($x + 5, $y + ($height - 20)/2);
        $valueText->setFontSize(18);
        $valueText->setColor($this->valueColor);
        $valueText->setText('');
        $valueText->setRenderable(true);
        $this->drawItems[] = $valueText;

        $valueIdText = new Text($this->uidValueElement);
        $valueIdText->setOrigin(10, 10);
        $valueIdText->setFontSize(20);
        $valueIdText->setColor($this->valueColor);
        $valueIdText->setText('');
        $valueIdText->setRenderable(false);
        $this->drawItems[] = $valueIdText;

        $y += ($height + 5);
        $colorPanel = Colors::LIGHT_GRAY;

        $panel = new Rectangle($this->uid.'_panel_multiselect');
        $panel->setOrigin($x, $y);
        $panel->setSize($width, $heightPanel);
        $panel->setColor($colorPanel);
        $panel->setRenderable(false);

        $panelY = $y;

        foreach ($options as $index => $option) {
            $id = $option[$optionId] ?? $index;
            $text = $option[$optionText];

            $optionRect = new Rectangle($this->uid.'_option_rect_'.$id);
            $optionRect->setOrigin($x, $y);
            $optionRect->setSize($width - 30, $heightOption);
            $optionRect->setColor($colorPanel);
            $optionRect->setRenderable(false);
            $optionRect->addAttributes('optionId', $id);
            $optionRect->addAttributes('optionText', $text);
            $optionRect->addAttributes('selected', false);
            
            $jsPathClickOption = resource_path('js/function/entity/click_multiselect_option.blade.php');
            $jsPathClickOption = file_get_contents($jsPathClickOption);
            $jsPathClickOption = Helper::setCommonJsCode($jsPathClickOption, Str::random(20));
            $optionRect->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsPathClickOption);
            
            $panel->addChild($optionRect);
            $this->drawItems[] = $optionRect;

            $checkboxSize = min(20, $heightOption - 10);
            $checkboxX = $x + 5;
            $checkboxY = $y + ($heightOption - $checkboxSize) / 2;

            $checkbox = new Square($this->uid.'_checkbox_'.$id);
            $checkbox->setOrigin($checkboxX, $checkboxY);
            $checkbox->setSize($checkboxSize, $checkboxSize);
            $checkbox->setColor(0xFFFFFF);
            $checkbox->setRenderable(false);
            $checkbox->addAttributes('zIndex', 11001);
            $this->drawItems[] = $checkbox;

            $checkboxBorder = new MultiLine($this->uid.'_checkbox_border_'.$id);
            $checkboxBorder->setPoint($checkboxX, $checkboxY);
            $checkboxBorder->setPoint($checkboxX + $checkboxSize, $checkboxY);
            $checkboxBorder->setPoint($checkboxX + $checkboxSize, $checkboxY + $checkboxSize);
            $checkboxBorder->setPoint($checkboxX, $checkboxY + $checkboxSize);
            $checkboxBorder->setPoint($checkboxX, $checkboxY);
            $checkboxBorder->setThickness(2);
            $checkboxBorder->setColor(0x000000);
            $checkboxBorder->setRenderable(false);
            $checkboxBorder->addAttributes('zIndex', 11001);
            $this->drawItems[] = $checkboxBorder;

            $optionBorder = new MultiLine($this->uid.'_option_border_'.$id);
            $optionBorder->setPoint($x, $y);
            $optionBorder->setPoint($x+$width-30, $y);
            $optionBorder->setPoint($x+$width-30, $y+$heightOption);
            $optionBorder->setPoint($x, $y+$heightOption);
            $optionBorder->setPoint($x, $y);
            $optionBorder->setThickness($this->borderThickness);
            $optionBorder->setColor(0xFFFFFF);
            $optionBorder->setRenderable(false);
            $optionBorder->addAttributes('zIndex', 11001);
            $panel->addChild($optionBorder);
            $this->drawItems[] = $optionBorder;

            $optionTextObj = new Text($this->uid.'_option_text_'.$id);
            $optionTextObj->setOrigin($checkboxX + $checkboxSize + 10, $y + ($heightOption - 20)/2);
            $optionTextObj->setFontSize(20);
            $optionTextObj->setColor(0xFFFFFF);
            $optionTextObj->setText($text);
            $optionTextObj->setRenderable(false);
            $optionTextObj->addAttributes('zIndex', 11001);
            $panel->addChild($optionTextObj);
            $this->drawItems[] = $optionTextObj;

            $y += $heightOption;
        }

        $this->drawItems[] = $panel;

        $scrollbarStrip = new Rectangle($this->uid.'_scrollbar_strip');
        $scrollbarStrip->setOrigin($x + $width - 30, $panelY);
        $scrollbarStrip->setSize(30, $heightPanel);
        $scrollbarStrip->setColor($colorPanel);
        $scrollbarStrip->setRenderable(false);
        $scrollbarStrip->addAttributes('zIndex', 11005);
        $this->drawItems[] = $scrollbarStrip;

        $scrollbarBorder = new MultiLine($this->uid.'_scrollbar_border');
        $scrollbarBorder->setPoint($x + $width - 30, $panelY);
        $scrollbarBorder->setPoint($x + $width, $panelY);
        $scrollbarBorder->setPoint($x + $width, $panelY + $heightPanel);
        $scrollbarBorder->setPoint($x + $width - 30, $panelY + $heightPanel);
        $scrollbarBorder->setPoint($x + $width - 30, $panelY);
        $scrollbarBorder->setThickness(1);
        $scrollbarBorder->setColor(0x000000);
        $scrollbarBorder->setRenderable(false);
        $scrollbarBorder->addAttributes('zIndex', 11006);
        $this->drawItems[] = $scrollbarBorder;

        $upButton = new Rectangle($this->uid.'_scroll_up');
        $upButton->setOrigin($x + $width - 30, $panelY);
        $upButton->setSize(30, 30);
        $upButton->setColor(0xBBBBBB);
        $upButton->setRenderable(false);
        $upButton->addAttributes('zIndex', 12000);
        $upButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_up_" . $this->uid . "']();");
        $this->drawItems[] = $upButton;

        $upText = new Text($this->uid.'_scroll_up_text');
        $upText->setCenterAnchor(true);
        $upText->setFontSize(22);
        $upText->setOrigin($x + $width - 15, $panelY + 15);
        $upText->setColor(0x333333);
        $upText->setText('^');
        $upText->setRenderable(false);
        $upText->addAttributes('zIndex', 12001);
        $this->drawItems[] = $upText;

        $downButton = new Rectangle($this->uid.'_scroll_down');
        $downButton->setOrigin($x + $width - 30, $panelY + $heightPanel - 30);
        $downButton->setSize(30, 30);
        $downButton->setColor(0xBBBBBB);
        $downButton->setRenderable(false);
        $downButton->addAttributes('zIndex', 12000);
        $downButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_down_" . $this->uid . "']();");
        $this->drawItems[] = $downButton;

        $downText = new Text($this->uid.'_scroll_down_text');
        $downText->setCenterAnchor(true);
        $downText->setFontSize(22);
        $downText->setOrigin($x + $width - 15, $panelY + $heightPanel - 15);
        $downText->setColor(0x333333);
        $downText->setText('V');
        $downText->setRenderable(false);
        $downText->addAttributes('zIndex', 12001);
        $this->drawItems[] = $downText;

    }

}
