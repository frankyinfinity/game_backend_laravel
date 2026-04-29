<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Brain;
use App\Models\Neuron;
use App\Models\NeuronCircuit;
use App\Models\NeuronCircuitDetail;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('neuron_circuit_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('neuron_circuit_id')->index();
            $table->unsignedBigInteger('neuron_id')->index();
            $table->timestamps();

            $table->foreign('neuron_circuit_id')->references('id')->on('neuron_circuits')->onDelete('cascade');
            $table->foreign('neuron_id')->references('id')->on('neurons')->onDelete('cascade');
        });

        // Migrate existing neurons into closed circuits
        $brains = Brain::with('neurons')->get();
        foreach ($brains as $brain) {
            if ($brain->neurons->isEmpty()) {
                continue;
            }

            $circuit = NeuronCircuit::create([
                'brain_id' => $brain->id,
                'uid' => Str::uuid()->toString(),
                'state' => NeuronCircuit::STATE_CLOSED,
            ]);

            foreach ($brain->neurons as $neuron) {
                NeuronCircuitDetail::create([
                    'neuron_circuit_id' => $circuit->id,
                    'neuron_id' => $neuron->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neuron_circuit_details');
    }
};
