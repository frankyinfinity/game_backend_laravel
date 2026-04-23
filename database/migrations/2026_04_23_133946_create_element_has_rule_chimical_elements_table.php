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
        Schema::create('element_has_rule_chimical_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('element_id')->constrained()->onDelete('cascade');
            $table->foreignId('rule_chimical_element_id')
                ->constrained('rule_chimical_elements', 'id', 'ehrce_rule_id_foreign')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('element_has_rule_chimical_elements');
    }
};
