<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('neurons', function (Blueprint $table) {
            $table->foreignId('gene_life_id')->nullable()->after('target_element_id')->constrained('genes')->nullOnDelete();
            $table->foreignId('gene_attack_id')->nullable()->after('gene_life_id')->constrained('genes')->nullOnDelete();
        });

        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->unsignedBigInteger('gene_life_id')->nullable()->after('target_element_id');
            $table->unsignedBigInteger('gene_attack_id')->nullable()->after('gene_life_id');

            $table->foreign('gene_life_id', 'ehpn_gene_life_fk')
                ->references('id')
                ->on('genes')
                ->nullOnDelete();

            $table->foreign('gene_attack_id', 'ehpn_gene_attack_fk')
                ->references('id')
                ->on('genes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->dropForeign('ehpn_gene_life_fk');
            $table->dropForeign('ehpn_gene_attack_fk');
            $table->dropColumn(['gene_life_id', 'gene_attack_id']);
        });

        Schema::table('neurons', function (Blueprint $table) {
            $table->dropForeign(['gene_life_id']);
            $table->dropForeign(['gene_attack_id']);
            $table->dropColumn(['gene_life_id', 'gene_attack_id']);
        });
    }
};

