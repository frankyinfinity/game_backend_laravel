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
        $boxIcon->setOrigin($x+($width-$x), $y);
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

        //Panel
        $y+= ($height+5);
        $optionShowDisplay = $this->optionShowDisplay;
        $heightPanel = $height * $optionShowDisplay;

        $panel = new Rectangle($this->uid.'_panel_select');
        $panel->setOrigin($x, $y);
        $panel->setSize($width, $heightPanel);
        $panel->setColor(Colors::LIGHT_GRAY);
        $panel->setRenderable(false);

        //Options
        $heightOption = $heightPanel / $optionShowDisplay;
        $options = $this->options;
        $optionId = $this->optionId;
        $optionText = $this->optionText;

        foreach ($options as $option) {

            $id = $option[$optionId];
            $text = $option[$optionText];

            $optionText = new Text($this->uid.'_option_'.$id);
            $optionText->setOrigin($x, $y);
            $optionText->setFontSize(20);
            $optionText->setColor($this->valueColor);
            $optionText->setText($text);
            $optionText->setRenderable(false);
            $panel->addChild($optionText);
            $drawItems[] = $optionText->buildJson();

            $y+= $heightOption;

        }

        $drawItems[] = $panel->buildJson();

        $this->drawItems = $drawItems;

    }

}