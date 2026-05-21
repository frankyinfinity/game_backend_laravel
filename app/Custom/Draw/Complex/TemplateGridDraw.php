<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;

class TemplateGridDraw
{
    private string $uid;
    private array $templates = [];

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function addTemplate(BasicDraw $element): void
    {
        $this->templates[] = $element;
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getUid(): string
    {
        return $this->uid;
    }
}
