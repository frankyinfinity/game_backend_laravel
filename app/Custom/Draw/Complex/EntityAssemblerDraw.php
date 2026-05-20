<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Complex\ButtonDraw;
use App\Custom\Colors;

class EntityAssemblerDraw
{
    private string $uid;
    private array $drawItems = [];

    private float $x;
    private float $y;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
        $this->drawItems = [];
    }

    public function setOrigin(float $x, float $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function build(): void
    {
        $button = new ButtonDraw($this->uid . '_btn');
        $button->setSize(200, 50);
        $button->setOrigin($this->x, $this->y);
        $button->setString('Entity Assembler');
        $button->setColorButton(Colors::NAVY);
        $button->setColorString(Colors::WHITE);
        $button->build();

        foreach ($button->getDrawItems() as $item) {
            $this->drawItems[] = $item;
        }
    }
}
