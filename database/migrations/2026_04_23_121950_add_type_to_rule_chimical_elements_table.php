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
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->string('type')->default('entity')->after('degradable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};