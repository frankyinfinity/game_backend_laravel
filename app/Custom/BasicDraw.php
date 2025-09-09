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
    private array $extraAttributes;

    const INTERACTIVE_POINTER_DOWN = 'pointerdown';
    const INTERACTIVE_POINTER_UP = 'pointerupoutside';

    public function __construct($type, $uid)
    {
        $this->type = $type;
        $this->uid = $uid;
        $this->renderable = true;
        $this->interactives = [];
        $this->countInteractives = 0;
        $this->extraAttributes = [];
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

    public function addAttributes($key, $value) {
        $this->extraAttributes[$key] = $value;
    }

    public function commonJson($extra)
    {

        $attributes = [
            'renderable' => $this->renderable,
            'interactives' => [
                'items' => $this->interactives,
                'count' => $this->countInteractives,
            ]
        ];
        foreach ($this->extraAttributes as $key => $value) {
            $attributes[$key] = $value;
        }

        return [
            'uid' => $this->uid,
            'type' => $this->type,
            'color' => $this->color,
            'thickness' => $this->thickness,
            'attributes' => $attributes
        ]+$extra;
    }

}
