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
        Schema::table('neuron_circuits', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('brain_id');
            $table->string('color')->nullable()->after('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('neuron_circuits', function (Blueprint $table) {
            $table->dropColumn(['active', 'color']);
        });
    }
};
