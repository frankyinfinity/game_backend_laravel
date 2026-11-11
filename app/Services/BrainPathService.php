<?php

namespace App\Services;

use App\Models\BrainSchedule;
use App\Models\BrainSchedulePath;
use App\Models\BrainSchedulePathStep;
use App\Models\ElementHasPositionNeuron;
use Illuminate\Support\Facades\DB;

/**
 * Enumera tutti i percorsi possibili tra i neuroni di ogni circuito
 * di un BrainSchedule (walk semplici start -> terminale / ciclo).
 */
class BrainPathService
{
    /**
     * Rigenera i percorsi dello schedule (idempotente: reset + insert).
     *
     * @return array{paths:int, steps:int}
     */
    public function generateForSchedule(BrainSchedule $brainSchedule): array
    {
        $stats = ['paths' => 0, 'steps' => 0];
        $seenHashes = [];

        $brainSchedule->loadMissing('details.elementHasPositionNeuronCircuit.details');

        return DB::transaction(function () use ($brainSchedule, &$stats, &$seenHashes) {
            BrainSchedulePath::where('brain_schedule_id', $brainSchedule->id)->delete();

            foreach ($brainSchedule->details as $detail) {
                $circuit = $detail->elementHasPositionNeuronCircuit;
                if ($circuit === null) {
                    continue;
                }

                $this->generateForCircuit($circuit, (int) $brainSchedule->id, $stats, $seenHashes);
            }

            return $stats;
        });
    }

    private function generateForCircuit($circuit, int $scheduleId, array &$stats, array &$seenHashes): void
    {
        $neuronIds = $circuit->details->pluck('element_has_position_neuron_id');
        if ($neuronIds->isEmpty()) {
            return;
        }

        $neurons = ElementHasPositionNeuron::query()
            ->whereIn('id', $neuronIds)
            ->with(['outgoingLinks.conditionOrder', 'incomingLinks'])
            ->get()
            ->keyBy('id');

        // Archi uscenti limitati ai neuroni del circuito, ordinati per condition order.
        $outgoingById = [];
        $incomingCount = [];
        foreach ($neurons as $neuron) {
            $outgoingById[$neuron->id] = $neuron->outgoingLinks
                ->filter(fn($link) => $neuronIds->contains($link->to_element_has_position_neuron_id))
                ->sortBy(fn($link) => $link->conditionOrder ? $link->conditionOrder->sort_order : 0)
                ->values();

            $incomingCount[$neuron->id] = $neuron->incomingLinks
                ->filter(fn($link) => $neuronIds->contains($link->from_element_has_position_neuron_id))
                ->count();
        }

        // Radici: start del circuito se presente, altrimenti tutti gli indegree-0.
        $starts = [];
        $startNeuronId = (int) $circuit->start_element_has_position_neuron_id;
        if ($startNeuronId > 0 && $neurons->has($startNeuronId)) {
            $starts = [$startNeuronId];
        } else {
            foreach ($neurons as $neuron) {
                if (($incomingCount[$neuron->id] ?? 0) === 0) {
                    $starts[] = (int) $neuron->id;
                }
            }
        }

        foreach ($starts as $rootId) {
            $steps = [[
                'neuron_id' => (int) $rootId,
                'via_link_id' => null,
                'condition' => null,
            ]];
            $visited = [(int) $rootId => true];

            $this->expand($rootId, $steps, $visited, $outgoingById, (int) $circuit->id, $scheduleId, $stats, $seenHashes);
        }
    }

    private function expand(
        int $neuronId,
        array $steps,
        array $visited,
        array $outgoingById,
        int $circuitId,
        int $scheduleId,
        array &$stats,
        array &$seenHashes
    ): void {
        $outgoing = $outgoingById[$neuronId] ?? collect();
        if ($outgoing->isEmpty()) {
            $this->savePath($steps, BrainSchedulePath::CLOSED_END, $circuitId, $scheduleId, $stats, $seenHashes);
            return;
        }

        foreach ($outgoing as $link) {
            $targetId = (int) $link->to_element_has_position_neuron_id;
            $step = [
                'neuron_id' => $targetId,
                'via_link_id' => (int) $link->id,
                'condition' => $link->conditionOrder ? $link->conditionOrder->condition : null,
            ];

            if (isset($visited[$targetId])) {
                // Ciclo: chiudo il path includendo lo step di rientro.
                $this->savePath(array_merge($steps, [$step]), BrainSchedulePath::CLOSED_CYCLE, $circuitId, $scheduleId, $stats, $seenHashes);
                continue;
            }

            $visited[$targetId] = true;
            $steps[] = $step;
            $this->expand($targetId, $steps, $visited, $outgoingById, $circuitId, $scheduleId, $stats, $seenHashes);
            array_pop($steps);
            unset($visited[$targetId]);
        }
    }

    private function savePath(
        array $steps,
        string $reason,
        int $circuitId,
        int $scheduleId,
        array &$stats,
        array &$seenHashes
    ): void {
        $hash = sha1(implode('|', array_map(
            fn($s) => $s['neuron_id'] . ':' . ($s['via_link_id'] ?? 0),
            $steps
        )));

        if (isset($seenHashes[$hash])) {
            return;
        }
        $seenHashes[$hash] = true;

        $path = BrainSchedulePath::create([
            'brain_schedule_id' => $scheduleId,
            'element_has_position_neuron_circuit_id' => $circuitId,
            'steps_count' => count($steps),
            'hash' => $hash,
            'closed_reason' => $reason,
        ]);

        $now = now();
        $rows = [];
        foreach ($steps as $i => $step) {
            $rows[] = [
                'brain_schedule_path_id' => $path->id,
                'seq' => $i,
                'element_has_position_neuron_id' => $step['neuron_id'],
                'via_element_has_position_neuron_link_id' => $step['via_link_id'],
                'condition' => $step['condition'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        BrainSchedulePathStep::insert($rows);

        $stats['paths']++;
        $stats['steps'] += count($rows);
    }
}
