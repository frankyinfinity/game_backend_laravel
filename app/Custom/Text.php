<?php

namespace App\Custom;

class Text extends BasicDraw
{

    private $uid;
    private $x;
    private $y;
    private $text;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('text', $uid);
    }

    public function setOrigin($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setText($text) {
        $this->text = $text;
    }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'text' => $this->text,
        ]);
    }

}
