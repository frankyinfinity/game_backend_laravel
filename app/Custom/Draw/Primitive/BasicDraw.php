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
    protected $x;
    protected $y;
    protected $relativeX;
    protected $relativeY;
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

    public function setColor($color): void
    {
        $this->color = $color;
    }

    public function setOrigin($x, $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setThickness($thickness): void
    {
        $this->thickness = $thickness;
    }

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function setInteractive($event, $function): void
    {
        $this->interactives[$event] = $function;
        $this->countInteractives++;
    }

    public function addAttributes($key, $value): void
    {
        $this->extraAttributes[$key] = $value;
    }

    private function setRelativePosition($draw): void
    {

        $json = $draw->buildJson();
        $parentX = $this->x;
        $parentY = $this->y;
        $x = $json['x'];
        $y = $json['y'];
        $relativeX = $x - $parentX;
        $relativeY = $y - $parentY;

        $this->relativeX = $relativeX;
        $this->relativeY = $relativeY;

    }

    public function addChild($draw): void
    {

        $uid = $draw->getUid();

        //Actions
        $this->setRelativePosition($draw);

        $this->children[] = $uid;

    }

    public function commonJson($extra): array
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
            'x' => $this->x,
            'y' => $this->y,
            'relative_x' => $this->relativeX,
            'relative_y' => $this->relativeY,
            'color' => $this->color,
            'thickness' => $this->thickness,
            'attributes' => $attributes,
            'children' => $this->children
        ]+$extra;
    }

}
