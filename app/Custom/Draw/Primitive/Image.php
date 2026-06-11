<?php

namespace App\Custom\Draw\Primitive;

class Image extends BasicDraw
{

    private $src;
    private $width;
    private $height;
    private bool $centerAnchor = false;

    public function __construct($uid = null)
    {
        if($uid == null) {
            $uid = uniqid();
        }
        parent::__construct('image', $uid);
    }

    public function setSrc($src): void
    {
        $this->src = $src;
    }

    public function getSrc()
    {
        return $this->src;
    }

    public function setSize($width, $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setCenterAnchor(bool $value): void
    {
        $this->centerAnchor = $value;
    }

    public function buildJson(): array
    {
        return $this->commonJson([
            'src' => $this->src,
            'width' => $this->width,
            'height' => $this->height,
            'centerAnchor' => $this->centerAnchor,
        ]);
    }

    private function getCenterX(): float
    {
        return $this->x + ($this->width / 2);
    }

    private function getCenterY(): float
    {
        return $this->y + ($this->height / 2);
    }

    public function getCenter(): array
    {
        return [
            'x' => $this->getCenterX(),
            'y' => $this->getCenterY(),
        ];
    }

}
