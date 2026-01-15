<?php

namespace App\Custom\Draw\Complex\Form;

use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Manipulation\ObjectDraw;

class Input {

    private string $uid;
    private string $sessionId;
    public function __construct($uid, $sessionId) {

        $this->uid = $uid;
        $this->sessionId = $sessionId;

        $this->name = '';
        $this->placeholder = '';
        $this->x = 0;
        $this->y = 0;
        $this->width = 0;
        $this->height = 0;
        $this->items = [];

    }

    private string $name;
    public function setName($name) {
        $this->name = $name;
    }

    private string $placeholder;
    public function setPlaceholder($placeholder) {
        $this->placeholder = $placeholder;
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

    private $items = [];
    public function getItems() {
        return $this->items;
    }

    public function build() {

        $items = [];

        $x = $this->x;
        $y = $this->y;
        $width = $this->width;
        $height = $this->height;

        //Body
        $body = new Rectangle($this->uid.'_body_input');
        $body->setOrigin($x, $y);
        $body->setSize($width, $height);
        $body->setColor(0xFFFFFF);
        $body->setRenderable(true);
        $items[] = $body->buildJson();

        //Placeholder
        $placeholder = new Text($this->uid.'_placeholder');
        $placeholder->setFontSize(20);
        $placeholder->setColor(0x808080);
        $placeholder->setOrigin($x+15, $y+($height/3.2));
        $placeholder->setText($this->placeholder);
        $placeholder->setRenderable(true);
        $items[] = $placeholder->buildJson();

        $this->items = $items;

    }

}

