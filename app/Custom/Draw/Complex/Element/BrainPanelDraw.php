<?php

namespace App\Custom\Draw\Complex\Element;

use App\Custom\Colors;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Square;
use App\Models\ElementHasPositionBrain;
use App\Models\ElementHasPositionNeuron;
use App\Models\ElementHasPositionNeuronLink;

class BrainPanelDraw
{
    private string $uid;
    private array $drawItems = [];
    private ?ElementHasPositionBrain $brain = null;

    private int $x = 0;
    private int $y = 0;
    private int $width = 220;
    private int $height = 120;
    private bool $renderable = true;
    private int $cellSize = 18;
    private int $padding = 10;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function setBrain(?ElementHasPositionBrain $brain): void
    {
        $this->brain = $brain;
    }

    public function setOrigin(int $x, int $y): void
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function setSize(int $width, int $height): void
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function setRenderable(bool $renderable): void
    {
        $this->renderable = $renderable;
    }

    public function getDrawItems(): array
    {
        return $this->drawItems;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function build(): void
    {
        $this->drawItems = [];

        if ($this->brain) {
            $gridWidth = max(1, (int) ($this->brain->grid_width ?? 5));
            $gridHeight = max(1, (int) ($this->brain->grid_height ?? 5));
            $this->width = ($gridWidth * $this->cellSize) + ($this->padding * 2);
            $this->height = ($gridHeight * $this->cellSize) + ($this->padding * 2);
        }

        $panel = new Rectangle($this->uid);
        $panel->setOrigin($this->x, $this->y);
        $panel->setSize($this->width, $this->height);
        $panel->setColor(Colors::WHITE);
        $panel->setBorderColor(Colors::BLACK);
        $panel->setThickness(2);
        $panel->setBorderRadius(0);
        $panel->setRenderable($this->renderable);
        $panel->addAttributes('z_index', 20006);
        $this->drawItems[] = $panel;

        $this->addGridLines();

        if ($this->brain) {
            $this->addConnections();
            $this->addNodes();
        }
    }

    private function addGridLines(): void
    {
        $left = $this->x + $this->padding;
        $top = $this->y + $this->padding;
        $gridWidth = $this->brain ? max(1, (int) $this->brain->grid_width) : max(1, (int) floor(($this->width - ($this->padding * 2)) / $this->cellSize));
        $gridHeight = $this->brain ? max(1, (int) $this->brain->grid_height) : max(1, (int) floor(($this->height - ($this->padding * 2)) / $this->cellSize));
        $right = $left + ($gridWidth * $this->cellSize);
        $bottom = $top + ($gridHeight * $this->cellSize);

        for ($x = $left + $this->cellSize; $x < $right; $x += $this->cellSize) {
            $line = new MultiLine($this->uid . '_grid_v_' . $x);
            $line->setPoint($x, $top);
            $line->setPoint($x, $bottom);
            $line->setColor(0xD1D5DB);
            $line->setThickness(1);
            $line->setRenderable($this->renderable);
            $line->addAttributes('z_index', 20007);
            $this->drawItems[] = $line;
        }

        for ($y = $top + $this->cellSize; $y < $bottom; $y += $this->cellSize) {
            $line = new MultiLine($this->uid . '_grid_h_' . $y);
            $line->setPoint($left, $y);
            $line->setPoint($right, $y);
            $line->setColor(0xD1D5DB);
            $line->setThickness(1);
            $line->setRenderable($this->renderable);
            $line->addAttributes('z_index', 20007);
            $this->drawItems[] = $line;
        }
    }

    private function addConnections(): void
    {
        $neurons = $this->brain->neurons()->with(['outgoingLinks.toNeuron'])->orderBy('grid_i')->orderBy('grid_j')->get();

        foreach ($neurons as $neuron) {
            /** @var ElementHasPositionNeuron $neuron */
            foreach ($neuron->outgoingLinks as $linkIndex => $link) {
                /** @var ElementHasPositionNeuronLink $link */
                $toNeuron = $link->toNeuron;
                if (!$toNeuron) {
                    continue;
                }

                $start = $this->cellCenter((int) $neuron->grid_j, (int) $neuron->grid_i);
                $end = $this->cellCenter((int) $toNeuron->grid_j, (int) $toNeuron->grid_i);
                if ($start === null || $end === null) {
                    continue;
                }

                $line = new MultiLine($this->uid . '_link_' . $neuron->id . '_' . $link->id . '_' . $linkIndex);
                $line->setPoint($start['x'], $start['y']);
                $line->setPoint($end['x'], $end['y']);
                $line->setColor(0x6B7280);
                $line->setThickness(2);
                $line->setRenderable($this->renderable);
                $line->addAttributes('z_index', 20008);
                $this->drawItems[] = $line;
            }
        }
    }

    private function addNodes(): void
    {
        if (!$this->brain) {
            return;
        }

        $neurons = $this->brain->neurons()
            ->with(['outgoingLinks', 'incomingLinks'])
            ->orderBy('grid_i')
            ->orderBy('grid_j')
            ->get();

        foreach ($neurons as $index => $neuron) {
            $position = $this->cellOrigin((int) $neuron->grid_j, (int) $neuron->grid_i);
            if ($position === null) {
                continue;
            }

            $node = new Square($this->uid . '_node_' . $index);
            $node->setOrigin($position['x'], $position['y']);
            $node->setSize($this->cellSize);
            $node->setColor($this->getNeuronColor((string) $neuron->type));
            $node->setRenderable($this->renderable);
            $node->addAttributes('z_index', 20009);
            $node->addAttributes('neuron_type', $neuron->type);
            $node->addAttributes('grid_i', (int) $neuron->grid_i);
            $node->addAttributes('grid_j', (int) $neuron->grid_j);
            $this->drawItems[] = $node;
        }
    }

    private function cellOrigin(int $gridJ, int $gridI): ?array
    {
        if (!$this->brain) {
            return null;
        }

        $gridWidth = max(1, (int) $this->brain->grid_width);
        $gridHeight = max(1, (int) $this->brain->grid_height);
        if ($gridJ < 0 || $gridJ >= $gridWidth || $gridI < 0 || $gridI >= $gridHeight) {
            return null;
        }

        return [
            'x' => $this->x + $this->padding + ($gridJ * $this->cellSize),
            'y' => $this->y + $this->padding + ($gridI * $this->cellSize),
        ];
    }

    private function cellCenter(int $gridJ, int $gridI): ?array
    {
        $origin = $this->cellOrigin($gridJ, $gridI);
        if ($origin === null) {
            return null;
        }

        return [
            'x' => $origin['x'] + (int) floor($this->cellSize / 2),
            'y' => $origin['y'] + (int) floor($this->cellSize / 2),
        ];
    }

    private function getNeuronColor(string $type): int
    {
        return match ($type) {
            'detection' => 0x2563EB,
            'path' => 0x16A34A,
            'attack' => 0xDC2626,
            'movement' => 0xD97706,
            default => 0x111111,
        };
    }
}
