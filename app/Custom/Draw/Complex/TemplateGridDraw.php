<?php

namespace App\Custom\Draw\Complex;

use App\Custom\Draw\Primitive\BasicDraw;

class TemplateGridDraw
{
    private string $uid;
    private array $templates = [];
    private array $placeholderMappings = [];

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function addTemplate(BasicDraw $element): void
    {
        $this->templates[] = $element;
    }

    public function addTemplateWithMapping(string $placeholder, string $dataKey): void
    {
        $this->placeholderMappings[] = ['placeholder' => $placeholder, 'dataKey' => $dataKey];
    }

    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    public function getPlaceholderMappings(): array
    {
        return $this->placeholderMappings;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public static function replacePlaceholders(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $text = str_replace($placeholder, (string)$value, $text);
        }
        return $text;
    }

    public static function replacePlaceholdersWithMapping(string $text, array $data, string $placeholder, string $dataKey): string
    {
        if (isset($data[$dataKey])) {
            $text = str_replace($placeholder, (string)$data[$dataKey], $text);
        }
        return $text;
    }
}
