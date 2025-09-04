<?php

namespace App\Custom;

class BasicDraw
{
    private $uid;
    private $type;
    private $color;
    private $thickness;
    private bool $renderable;
    private array $interactives;
    private int $countInteractives;

    const INTERACTIVE_POINTER_DOWN = 'pointerdown';
    const INTERACTIVE_POINTER_UP = 'pointerupoutside';

    public function __construct($type, $uid)
    {
        $this->type = $type;
        $this->uid = $uid;
        $this->renderable = true;
        $this->interactives = [];
        $this->countInteractives = 0;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setThickness($thickness) {
        $this->thickness = $thickness;
    }

    public function setRenderable(bool $renderable) {
        $this->renderable = $renderable;
    }

    public function setInteractive($event, $function) {
        $this->interactives[$event] = $function;
        $this->countInteractives++;
    }

    public function commonJson($extra)
    {
        return [
            'uid' => $this->uid,
            'type' => $this->type,
            'color' => $this->color,
            'thickness' => $this->thickness,
            'extra' => [
                'renderable' => $this->renderable,
                'interactives' => [
                    'items' => $this->interactives,
                    'count' => $this->countInteractives,
                ],
            ]
        ]+$extra;
    }

}
