<?php

namespace App\Custom;

use App\Helper\Helper;

class Text extends BasicDraw
{

    private $uid;
    private $x;
    private $y;
    private $text;
    private string $fontFamily;
    private int $fontSize;
    private bool $centerAnchor;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        $this->fontFamily = Helper::getDefaultFontFamily();
        $this->fontSize = Helper::getDefaultFontSize();
        $this->centerAnchor = false;
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

    public function setFontSize(int $value): void
    {
        $this->fontSize = $value;
    }

    public function setFontFamily(string $value): void
    {
        $this->fontFamily = $value;
    }

    public function setCenterAnchor(bool $value): void
    {
        $this->centerAnchor = $value;
    }

    public function buildJson() {
        return $this->commonJson([
            'x' => $this->x,
            'y' => $this->y,
            'fontFamily' => $this->fontFamily,
            'fontSize' => $this->fontSize,
            'centerAnchor' => $this->centerAnchor,
            'text' => $this->text,
        ]);
    }

}
