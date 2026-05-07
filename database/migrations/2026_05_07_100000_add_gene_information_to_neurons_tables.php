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
            $table->foreignId('element_infomation_id')->nullable()->after('element_has_rule_chimical_element_id')->constrained('genes')->nullOnDelete();
        });

        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->unsignedBigInteger('element_has_position_information_id')->nullable()->after('element_has_rule_chimical_element_id');

            $table->foreign('element_has_position_information_id', 'ehpn_information_gene_fk')
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
            $table->dropForeign('ehpn_information_gene_fk');
            $table->dropColumn('element_has_position_information_id');
        });

        Schema::table('neurons', function (Blueprint $table) {
            $table->dropForeign(['element_infomation_id']);
            $table->dropColumn('element_infomation_id');
        });
    }
};