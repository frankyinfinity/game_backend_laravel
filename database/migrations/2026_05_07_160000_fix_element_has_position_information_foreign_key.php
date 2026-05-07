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
        // Rimuovi il vecchio FK che punta a genes.id (nome: ehpn_information_gene_fk)
        try {
            DB::statement('ALTER TABLE element_has_position_neurons DROP FOREIGN KEY ehpn_information_gene_fk');
        } catch (\Throwable $e) {
            // Ignora se non esiste
        }

        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            // Aggiungi il nuovo FK corretto che punta a element_has_position_information.id
            $table->foreign('element_has_position_information_id', 'ehpn_information_fk')
                ->references('id')
                ->on('element_has_position_information')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi il nuovo FK
        try {
            DB::statement('ALTER TABLE element_has_position_neurons DROP FOREIGN KEY ehpn_information_fk');
        } catch (\Throwable $e) {
            // Ignora se non esiste
        }

        // Ripristina il vecchio FK (sbagliato) se necessario
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->foreign('element_has_position_information_id', 'ehpn_information_gene_fk')
                ->references('id')
                ->on('genes')
                ->onDelete('set null');
        });
    }
};
