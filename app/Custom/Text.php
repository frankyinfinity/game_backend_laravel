<?php

namespace App\Custom;

class Text extends BasicDraw
{

    private $uid;
    private $x;
    private $y;
    private $text;
    private $fontFamily;
    private $fontSize;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        $this->fontFamily = 'Arial';
        $this->fontSize = 16;
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

    public function setFontSize($value) {
        $this->fontSize = $value;
    }

    public function setFontFamily($value) {
        $this->fontFamily = $value;
    }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'fontFamily' => $this->fontFamily,
            'fontSize' => $this->fontSize,
            'text' => $this->text,
        ]);
    }

}
