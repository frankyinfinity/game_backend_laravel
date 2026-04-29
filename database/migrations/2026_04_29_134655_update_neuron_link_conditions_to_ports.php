<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update template links (neuron_links)
        
        // Detection - Success
        DB::table('neuron_links')
            ->join('neurons', 'neuron_links.from_neuron_id', '=', 'neurons.id')
            ->where('neurons.type', 'detection')
            ->where(function ($q) {
                $q->whereIn('neuron_links.condition', ['main', 'found', ''])
                  ->orWhereNull('neuron_links.condition');
            })
            ->update(['neuron_links.condition' => 'success_detection']);

        // Detection - Failure
        DB::table('neuron_links')
            ->join('neurons', 'neuron_links.from_neuron_id', '=', 'neurons.id')
            ->where('neurons.type', 'detection')
            ->whereIn('neuron_links.condition', ['else', 'not_found'])
            ->update(['neuron_links.condition' => 'failure_detection']);

        // Others - Trigger
        DB::table('neuron_links')
            ->join('neurons', 'neuron_links.from_neuron_id', '=', 'neurons.id')
            ->where('neurons.type', '!=', 'detection')
            ->update(['neuron_links.condition' => 'trigger']);

        // Update instance links (element_has_position_neuron_links)
        
        // Detection - Success
        DB::table('element_has_position_neuron_links')
            ->join('element_has_position_neurons', 'element_has_position_neuron_links.from_element_has_position_neuron_id', '=', 'element_has_position_neurons.id')
            ->where('element_has_position_neurons.type', 'detection')
            ->where(function ($q) {
                $q->whereIn('element_has_position_neuron_links.condition', ['main', 'found', ''])
                  ->orWhereNull('element_has_position_neuron_links.condition');
            })
            ->update(['element_has_position_neuron_links.condition' => 'success_detection']);

        // Detection - Failure
        DB::table('element_has_position_neuron_links')
            ->join('element_has_position_neurons', 'element_has_position_neuron_links.from_element_has_position_neuron_id', '=', 'element_has_position_neurons.id')
            ->where('element_has_position_neurons.type', 'detection')
            ->whereIn('element_has_position_neuron_links.condition', ['else', 'not_found'])
            ->update(['element_has_position_neuron_links.condition' => 'failure_detection']);

        // Others - Trigger
        DB::table('element_has_position_neuron_links')
            ->join('element_has_position_neurons', 'element_has_position_neuron_links.from_element_has_position_neuron_id', '=', 'element_has_position_neurons.id')
            ->where('element_has_position_neurons.type', '!=', 'detection')
            ->update(['element_has_position_neuron_links.condition' => 'trigger']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting would be ambiguous since we mapped multiple values to one.
        // We'll map them back to 'main' and 'else' for detection, and '' for others.
        
        DB::table('neuron_links')
            ->where('condition', 'success_detection')
            ->update(['condition' => 'main']);
        DB::table('neuron_links')
            ->where('condition', 'failure_detection')
            ->update(['condition' => 'else']);
        DB::table('neuron_links')
            ->where('condition', 'trigger')
            ->update(['condition' => 'main']);

        DB::table('element_has_position_neuron_links')
            ->where('condition', 'success_detection')
            ->update(['condition' => 'main']);
        DB::table('element_has_position_neuron_links')
            ->where('condition', 'failure_detection')
            ->update(['condition' => 'else']);
        DB::table('element_has_position_neuron_links')
            ->where('condition', 'trigger')
            ->update(['condition' => 'main']);
    }
};
