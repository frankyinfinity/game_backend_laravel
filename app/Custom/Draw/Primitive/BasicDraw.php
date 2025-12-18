<?php

namespace App\Custom\Draw\Primitive;

class BasicDraw
{
    private $uid;
    public function getUid() {
        return $this->uid;
    }
    private $type;
    protected $color;
    private $thickness;
    private bool $renderable;
    private array $interactives;
    private int $countInteractives;
    private array $extraAttributes;
    private array $children;

    const INTERACTIVE_POINTER_DOWN = 'pointerdown';
    const INTERACTIVE_POINTER_UP = 'pointerupoutside';
    const INTERACTIVE_POINTER_OVER = 'pointerover';
    const INTERACTIVE_POINTER_OUT = 'pointerout';

    public function __construct($type, $uid)
    {
        $this->type = $type;
        $this->uid = $uid;
        $this->renderable = true;
        $this->interactives = [];
        $this->countInteractives = 0;
        $this->extraAttributes = [];
        $this->children = [];
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

    public function addChild($uid): void
    {
        $this->children[] = $uid;
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
            'attributes' => $attributes,
            'children' => $this->children
        ]+$extra;
    }

}
