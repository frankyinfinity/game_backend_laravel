<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prova a rimuovere il vecchio vincolo FK se esiste (nome: ehpn_rule_chimical_element_fk)
        try {
            DB::statement('ALTER TABLE element_has_position_neurons DROP FOREIGN KEY ehpn_rule_chimical_element_fk');
        } catch (\Throwable $e) {
            // Ignora se il vincolo non esiste
        }

        // Se la colonna vecchia esiste, rinominala; altrimenti la colonna è già rinominata
        if (Schema::hasColumn('element_has_position_neurons', 'element_has_rule_chimical_element_id')) {
            Schema::table('element_has_position_neurons', function (Blueprint $table) {
                $table->renameColumn('element_has_rule_chimical_element_id', 'element_has_position_rule_chimical_element_id');
            });
        }

        // Aggiungi il nuovo vincolo FK con nome breve, se non esiste già
        try {
            Schema::table('element_has_position_neurons', function (Blueprint $table) {
                $table->foreign('element_has_position_rule_chimical_element_id', 'ehpn_pos_rule_fk')
                    ->references('id')
                    ->on('element_has_position_rule_chimical_elements')
                    ->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // Ignora se il vincolo esiste già o se la colonna non esiste
            Log::info('FK ehpn_pos_rule_fk might already exist or column missing', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi il nuovo vincolo FK se esiste
        try {
            DB::statement('ALTER TABLE element_has_position_neurons DROP FOREIGN KEY ehpn_pos_rule_fk');
        } catch (\Throwable $e) {
            // Ignora se non esiste
        }

        // Se la colonna con nuovo nome esiste, rinominala indietro; altrimenti è già nel vecchio nome
        if (Schema::hasColumn('element_has_position_neurons', 'element_has_position_rule_chimical_element_id')) {
            Schema::table('element_has_position_neurons', function (Blueprint $table) {
                $table->renameColumn('element_has_position_rule_chimical_element_id', 'element_has_rule_chimical_element_id');
            });
        }

        // Ripristina il vecchio vincolo FK
        try {
            Schema::table('element_has_position_neurons', function (Blueprint $table) {
                $table->foreign('element_has_rule_chimical_element_id', 'ehpn_rule_chimical_element_fk')
                    ->references('id')
                    ->on('rule_chimical_elements')
                    ->onDelete('set null');
            });
        } catch (\Throwable $e) {
            Log::info('FK ehpn_rule_chimical_element_fk might already exist or column missing', ['error' => $e->getMessage()]);
        }
    }
};
