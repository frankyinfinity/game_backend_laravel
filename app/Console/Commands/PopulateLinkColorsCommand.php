<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PopulateLinkColorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-link-colors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popola il campo color per tutti i collegamenti esistenti e aggiorna il formato condition';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Inizio popolamento colori per NeuronLink...');
        $this->populateLinks(\App\Models\NeuronLink::with(['fromNeuron.chemicalRule.details'])->get());

        $this->info('Inizio popolamento colori per ElementHasPositionNeuronLink...');
        $this->populateLinks(\App\Models\ElementHasPositionNeuronLink::with(['fromNeuron.chemicalRule.details'])->get());

        $this->info('Popolamento completato!');
    }

    private function populateLinks($links)
    {
        foreach ($links as $link) {
            $fromN = $link->fromNeuron;
            if (!$fromN) continue;

            $color = null;
            if ((string) $fromN->type === \App\Models\Neuron::TYPE_DETECTION) {
                if ($link->condition === \App\Models\NeuronLink::PORT_DETECTION_FAILURE) {
                    $color = '#F97316'; // Orange
                } else {
                    $color = '#16A34A'; // Green
                }
            } elseif ((string) $fromN->type === \App\Models\Neuron::TYPE_READ_CHIMICAL_ELEMENT) {
                $rule = $fromN->chemicalRule;
                if ($rule && $rule->details) {
                    foreach ($rule->details as $detail) {
                        $targetCondition = "[{$detail->min}/{$detail->max}]";
                        // Accettiamo sia il vecchio formato che il nuovo per trovare il match, poi salviamo il nuovo
                        if ($detail->min . '_' . $detail->max === $link->condition || $targetCondition === $link->condition) {
                            $color = $detail->color;
                            $link->condition = $targetCondition; // Aggiorna al nuovo formato
                            break;
                        }
                    }
                }
            }

            if (!$color) {
                $color = '#16A34A'; // Default Green
            }

            $link->update([
                'color' => $color,
                'condition' => $link->condition
            ]);
        }
    }
}
