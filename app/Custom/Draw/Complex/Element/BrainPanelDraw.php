<?php

namespace App\Custom\Draw\Complex\Element;

use App\Custom\Colors;
use App\Custom\Draw\Primitive\Line;
use App\Custom\Draw\Primitive\MultiLine;
use App\Custom\Draw\Primitive\Rectangle;
use App\Custom\Draw\Primitive\Text;
use App\Custom\Draw\Primitive\Circle;
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
    private int $cellSize = 20;
    private int $padding = 12;

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
            $circuitNeuronIds = $this->getCircuitNeuronIds();
            $this->addConnections($circuitNeuronIds);
            $this->addNodes($circuitNeuronIds);
        }
    }

    private function getCircuitNeuronIds(): array
    {
        if (!$this->brain) {
            return [];
        }

        $elementHasPosition = $this->brain->elementHasPosition;
        if (!$elementHasPosition) {
            return [];
        }

        return \App\Models\ElementHasPositionNeuronCircuitDetail::query()
            ->whereHas('circuit', fn($q) => $q->where('element_has_position_id', $elementHasPosition->id))
            ->pluck('element_has_position_neuron_id')
            ->unique()
            ->values()
            ->toArray();
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
            $line = new Line($this->uid . '_grid_v_' . $x);
            $line->setPoint($x, $top);
            $line->setPoint($x, $bottom);
            $line->setColor(0xD1D5DB);
            $line->setThickness(1);
            $line->setRenderable($this->renderable);
            $line->addAttributes('z_index', 20007);
            $this->drawItems[] = $line;
        }

        for ($y = $top + $this->cellSize; $y < $bottom; $y += $this->cellSize) {
            $line = new Line($this->uid . '_grid_h_' . $y);
            $line->setPoint($left, $y);
            $line->setPoint($right, $y);
            $line->setColor(0xD1D5DB);
            $line->setThickness(1);
            $line->setRenderable($this->renderable);
            $line->addAttributes('z_index', 20007);
            $this->drawItems[] = $line;
        }
    }

    private function addConnections(array $circuitNeuronIds = []): void
    {
        $neurons = $this->brain->neurons()->with(['outgoingLinks.toNeuron', 'conditionOrders', 'chemicalRule.details'])->orderBy('grid_i')->orderBy('grid_j')->get();

        if (!empty($circuitNeuronIds)) {
            $neurons = $neurons->filter(fn($n) => in_array((int) $n->id, $circuitNeuronIds));
        }

        foreach ($neurons as $neuron) {
            /** @var ElementHasPositionNeuron $neuron */
            $sortedLinks = $neuron->outgoingLinks->sortBy('sort_order');
            foreach ($sortedLinks as $linkIndex => $link) {
                /** @var ElementHasPositionNeuronLink $link */
                $toNeuron = $link->toNeuron;
                if (!$toNeuron) {
                    continue;
                }

                if (!empty($circuitNeuronIds) && !in_array((int) $toNeuron->id, $circuitNeuronIds)) {
                    continue;
                }

                $start = $this->getRightAnchorPoint($neuron, $this->cellSize, (string)$link->condition);
                $end = $this->getLeftAnchorPoint($toNeuron, $this->cellSize);
                if ($start === null || $end === null) {
                    continue;
                }

                // Get color from conditionOrders first, then fall back to link color
                $condOrder = $neuron->conditionOrders->firstWhere('condition', (string)$link->condition);
                $linkColor = $condOrder && $condOrder->color
                    ? (int) hexdec(ltrim($condOrder->color, '#'))
                    : $this->getLinkColorForNeuron($neuron, $link);

                $line = new MultiLine($this->uid . '_link_' . $neuron->id . '_' . $link->id . '_' . $linkIndex);
                $line->setPoint($start['x'], $start['y']);
                $line->setPoint($end['x'], $end['y']);
                $line->setColor($linkColor);
                $line->setThickness(2);
                $line->setRenderable($this->renderable);
                $line->addAttributes('z_index', 20008);
                $this->drawItems[] = $line;
            }
        }
    }

    private function getRightAnchorPoint(ElementHasPositionNeuron $neuron, int $cellSize, string $condition): array
    {
        $origin = $this->cellOrigin((int) $neuron->grid_j, (int) $neuron->grid_i);
        if (!$origin) return ['x' => 0, 'y' => 0];
        
        $baseX = $origin['x'] + $cellSize;
        $topLeftY = $origin['y'];

        // Use conditionOrders sorted by sort_order as the single source of truth
        $sortedOrders = $neuron->conditionOrders->sortBy('sort_order')->values();
        
        if ($sortedOrders->isEmpty()) {
            // Fallback if no condition orders exist yet
            return ['x' => $baseX, 'y' => (int) ($topLeftY + ($cellSize / 2))];
        }

        $index = $sortedOrders->search(fn($o) => (string)$o->condition === $condition);
        $count = $sortedOrders->count();
        
        if ($count > 0 && $index !== false) {
            $step = $cellSize / ($count + 1);
            return ['x' => $baseX, 'y' => (int) ($topLeftY + ($step * ($index + 1)))];
        }

        return ['x' => $baseX, 'y' => (int) ($topLeftY + ($cellSize / 2))];
    }

    private function getLeftAnchorPoint(ElementHasPositionNeuron $neuron, int $cellSize): array
    {
        $origin = $this->cellOrigin((int) $neuron->grid_j, (int) $neuron->grid_i);
        if (!$origin) return ['x' => 0, 'y' => 0];
        
        return [
            'x' => $origin['x'],
            'y' => (int) ($origin['y'] + ($cellSize / 2))
        ];
    }

    private function addNodes(array $circuitNeuronIds = []): void
    {
        if (!$this->brain) {
            return;
        }

        $neurons = $this->brain->neurons()
            ->with(['outgoingLinks', 'incomingLinks', 'conditionOrders', 'chemicalRule.details', 'chemicalRule.chimicalElement', 'chemicalRule.complexChimicalElement', 'targetElement', 'chemicalElement', 'complexChemicalElement'])
            ->orderBy('grid_i')
            ->orderBy('grid_j')
            ->get();

        if (!empty($circuitNeuronIds)) {
            $neurons = $neurons->filter(fn($n) => in_array((int) $n->id, $circuitNeuronIds));
        }

        foreach ($neurons as $index => $neuron) {
            $position = $this->cellOrigin((int) $neuron->grid_j, (int) $neuron->grid_i);
            if ($position === null) {
                continue;
            }

            $node = new Rectangle($this->uid . '_node_' . $index);
            $node->setOrigin($position['x'], $position['y']);
            $node->setSize($this->cellSize, $this->cellSize);
            $node->setColor(Colors::WHITE);
            $node->setBorderColor(Colors::BLACK);
            $node->setThickness(2);
            $node->setBorderRadius(0);
            $node->setRenderable($this->renderable);
            $node->addAttributes('z_index', 20010);
            $node->addAttributes('neuron_type', $neuron->type);
            $node->addAttributes('grid_i', (int) $neuron->grid_i);
            $node->addAttributes('grid_j', (int) $neuron->grid_j);
            $node->addAttributes('tooltip_text', $this->buildNeuronTooltip($neuron));
            $this->drawItems[] = $node;



            $icon = new Text($this->uid . '_node_icon_' . $index);
            $icon->setOrigin($position['x'] + (int) floor($this->cellSize / 2), $position['y'] + (int) floor($this->cellSize / 2) - 1);
            $icon->setCenterAnchor(true);
            $icon->setText($this->getNeuronIcon((string) $neuron->type));
            $icon->setFontSize(12);
            $icon->setColor(Colors::BLACK);
            $icon->setRenderable($this->renderable);
            $icon->addAttributes('z_index', 20011);
            $this->drawItems[] = $icon;
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

    private function getNeuronIcon(string $type): string
    {
        return \App\Models\Neuron::TYPE_SYMBOLS[$type] ?? '•';
    }

    private function getLinkColor(string $condition): int
    {
        return \App\Models\NeuronLink::getColorByCondition($condition);
    }

    private function getLinkColorForNeuron(ElementHasPositionNeuron $neuron, ElementHasPositionNeuronLink $link): int
    {
        $condition = (string) $link->condition;
        if ((string) $neuron->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $neuron->chemicalRule;
            if ($rule && $rule->details) {
                foreach ($rule->details as $detail) {
                    if ("[{$detail->min}/{$detail->max}]" === $condition) {
                        return (int) hexdec(ltrim($detail->color, '#'));
                    }
                }
            }
        }

        return $this->getLinkColor($condition);
    }

    private function buildNeuronTooltip(ElementHasPositionNeuron $neuron): string
    {
        $label = \App\Models\Neuron::TYPE_LABELS[(string) $neuron->type] ?? ucfirst((string) $neuron->type);

        $lines = [];
        $lines[] = $label;
        $lines[] = 'Cella: (' . (int) $neuron->grid_i . ', ' . (int) $neuron->grid_j . ')';

        if ((string) $neuron->type === \App\Models\Neuron::TYPE_DETECTION) {
            $targetLabel = \App\Models\Neuron::TARGET_TYPE_LABELS[$neuron->target_type] ?? '-';
            $lines[] = 'Raggio: ' . ($neuron->radius !== null ? (int) $neuron->radius : '-');
            
            $targetInfo = $targetLabel;
            if ($neuron->target_type === \App\Models\Neuron::TARGET_TYPE_ELEMENT && $neuron->targetElement) {
                $targetInfo .= " ({$neuron->targetElement->name})";
            } elseif ($neuron->target_type === \App\Models\Neuron::TARGET_TYPE_CHEMICAL_ELEMENT && $neuron->chemicalElement) {
                $targetInfo .= " ({$neuron->chemicalElement->name})";
            } elseif ($neuron->target_type === \App\Models\Neuron::TARGET_TYPE_COMPLEX_CHEMICAL_ELEMENT && $neuron->complexChemicalElement) {
                $targetInfo .= " ({$neuron->complexChemicalElement->name})";
            }
            $lines[] = 'Target: ' . $targetInfo;
        } elseif ((string) $neuron->type === \App\Models\Neuron::TYPE_ATTACK) {
            $lines[] = 'Gene Vita: ' . ($neuron->gene_life_id !== null ? (int) $neuron->gene_life_id : '-');
            $lines[] = 'Gene Attacco: ' . ($neuron->gene_attack_id !== null ? (int) $neuron->gene_attack_id : '-');
        } elseif ((string) $neuron->type === \App\Models\Neuron::TYPE_MOVEMENT) {
            $lines[] = 'Raggio: ' . ($neuron->radius !== null ? (int) $neuron->radius : '-');
        } elseif ((string) $neuron->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
            $rule = $neuron->chemicalRule;
            $lines[] = 'Elemento: ' . ($rule ? ($rule->title ?: $rule->name) : '-');
        }

        return implode("\n", $lines);
    }
}
