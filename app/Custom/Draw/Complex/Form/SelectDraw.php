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

class SelectDraw {

    private string $uid;
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

        //Options
        $options = $this->options;
        $optionId = $this->optionId;
        $optionText = $this->optionText;

        $optionShowDisplay = $this->optionShowDisplay;
        $heightPanel = $height * $optionShowDisplay;
        $heightOption = $heightPanel / $optionShowDisplay;

        //Title
        $title = new Text($this->uid.'_title');
        $title->setFontSize(20);
        $title->setColor($this->titleColor);
        $title->setOrigin($x, $y);
        $title->setText($this->title.($this->required?'*':''));
        $title->setRenderable(true);
        $drawItems[] = $title->buildJson();

        $y += 25;

        //Body
        $body = new Rectangle($this->uid.'_body_select');
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
        $body->addAttributes('selectedOptionId', null);
        $body->addAttributes('selectedOptionText', '');

        $jsPathClickInput = resource_path('js/function/entity/click_select.blade.php');
        $jsPathClickInput = file_get_contents($jsPathClickInput);
        $jsPathClickInput = Helper::setCommonJsCode($jsPathClickInput, Str::random(20));
        $body->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsPathClickInput);

        $drawItems[] = $body->buildJson();

        //Border
        $border = new MultiLine($this->uid.'_border_select');
        $border->setPoint($x, $y);
        $border->setPoint($x+$width, $y);
        $border->setPoint($x+$width, $y+$height);
        $border->setPoint($x, $y+$height);
        $border->setPoint($x, $y);
        $border->setThickness($this->borderThickness);
        $border->setColor($this->borderColor);
        $border->setRenderable(true);
        $drawItems[] = $border->buildJson();

        //Box Icon
        $boxIcon = new Square($this->uid.'_box_icon');
        $boxIcon->setOrigin($x + $width - $height, $y);
        $boxIcon->setSize($height, $height);
        $boxIcon->setColor($this->boxIconColor);
        $boxIcon->setRenderable(true);
        $drawItems[] = $boxIcon->buildJson();

        //Box Icon Text
        $centerSquare = $boxIcon->getCenter();

        $boxIconText = new Text($this->uid.'_box_icon_text');
        $boxIconText->setCenterAnchor(true);
        $boxIconText->setFontSize(24);
        $boxIconText->setOrigin($centerSquare['x'], $centerSquare['y']);
        $boxIconText->setColor($this->boxIconTextColor);
        $boxIconText->setText('V');
        $boxIconText->setRenderable(true);
        $drawItems[] = $boxIconText->buildJson();

        //Value Text (shows selected option)
        $valueText = new Text($this->uid.'_value_text');
        $valueText->setOrigin($x + 5, $y + ($height - 20)/2);
        $valueText->setFontSize(20);
        $valueText->setColor($this->valueColor);
        $valueText->setText('');
        $valueText->setRenderable(true);
        $drawItems[] = $valueText->buildJson();

        //Value ID (shows selected option ID)
        $this->uidValueElement = $this->uid.'_value_id';
        $valueIdText = new Text($this->uidValueElement);
        $valueIdText->setOrigin(10, 10);
        $valueIdText->setFontSize(20);
        $valueIdText->setColor($this->valueColor);
        $valueIdText->setText('');
        $valueIdText->setRenderable(false);
        $drawItems[] = $valueIdText->buildJson();

        //Panel
        $y += ($height + 5);
        $colorPanel = Colors::LIGHT_GRAY;

        $panel = new Rectangle($this->uid.'_panel_select');
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
            $optionRect->setSize($width - 30, $heightOption); // Lascia spazio per la scrollbar
            $optionRect->setColor($colorPanel);
            $optionRect->setRenderable(false);
            $optionRect->addAttributes('optionId', $id);
            $optionRect->addAttributes('optionText', $text);
            
            $jsPathClickOption = resource_path('js/function/entity/click_select_option.blade.php');
            $jsPathClickOption = file_get_contents($jsPathClickOption);
            $jsPathClickOption = Helper::setCommonJsCode($jsPathClickOption, Str::random(20));
            $optionRect->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, $jsPathClickOption);
            
            $panel->addChild($optionRect);
            // Non lo aggiungiamo a $drawItems qui se vogliamo che venga gestito dal panel, 
            // ma il sistema sembra richiedere che tutti gli oggetti siano in $drawItems per essere inviati al client inizialmente
            $drawItems[] = $optionRect->buildJson();

            $optionBorder = new MultiLine($this->uid.'_option_border_'.$id);
            $optionBorder->setPoint($x, $y);
            $optionBorder->setPoint($x+$width-30, $y); // Ridotto larghezza
            $optionBorder->setPoint($x+$width-30, $y+$heightOption); // Ridotto larghezza
            $optionBorder->setPoint($x, $y+$heightOption);
            $optionBorder->setPoint($x, $y);
            $optionBorder->setThickness($this->borderThickness);
            $optionBorder->setColor(0xFFFFFF);
            $optionBorder->setRenderable(false);
            $optionBorder->addAttributes('zIndex', 1);
            $panel->addChild($optionBorder);
            $drawItems[] = $optionBorder->buildJson();

            $optionTextObj = new Text($this->uid.'_option_text_'.$id);
            $optionTextObj->setOrigin($x + 5, $y + ($heightOption - 20)/2);
            $optionTextObj->setFontSize(20);
            $optionTextObj->setColor(0xFFFFFF);
            $optionTextObj->setText($text);
            $optionTextObj->setRenderable(false);
            $optionTextObj->addAttributes('zIndex', 1);
            $panel->addChild($optionTextObj);
            $drawItems[] = $optionTextObj->buildJson();

            $y += $heightOption;
        }

        $drawItems[] = $panel->buildJson();

        //Up button
        $upButton = new Rectangle($this->uid.'_scroll_up');
        $upButton->setOrigin($x + $width - 30, $panelY);
        $upButton->setSize(30, 30);
        $upButton->setColor(0xBBBBBB);
        $upButton->setRenderable(false);
        $upButton->addAttributes('zIndex', 10000);
        $upButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_up_" . $this->uid . "']();");
        $drawItems[] = $upButton->buildJson();

        //Up text
        $upText = new Text($this->uid.'_scroll_up_text');
        $upText->setCenterAnchor(true);
        $upText->setFontSize(22);
        // Center in 30x30: x = +15, y = +15
        $upText->setOrigin($x + $width - 15, $panelY + 15);
        $upText->setColor(0x333333);
        $upText->setText('^');
        $upText->setRenderable(false);
        $upText->addAttributes('zIndex', 10001);
        $drawItems[] = $upText->buildJson();

        //Down button
        $downButton = new Rectangle($this->uid.'_scroll_down');
        $downButton->setOrigin($x + $width - 30, $panelY + $heightPanel - 30);
        $downButton->setSize(30, 30);
        $downButton->setColor(0xBBBBBB);
        $downButton->setRenderable(false);
        $downButton->addAttributes('zIndex', 10000);
        $downButton->setInteractive(BasicDraw::INTERACTIVE_POINTER_DOWN, "window['scroll_down_" . $this->uid . "']();");
        $drawItems[] = $downButton->buildJson();

        //Down text
        $downText = new Text($this->uid.'_scroll_down_text');
        $downText->setCenterAnchor(true);
        $downText->setFontSize(22);
        // Center in 30x30: x = +15, y = +15
        $downText->setOrigin($x + $width - 15, $panelY + $heightPanel - 15);
        $downText->setColor(0x333333);
        $downText->setText('V');
        $downText->setRenderable(false);
        $downText->addAttributes('zIndex', 10001);
        $drawItems[] = $downText->buildJson();

        $this->drawItems = $drawItems;

    }

}