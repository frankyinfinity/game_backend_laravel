<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genomes', function (Blueprint $table) {
            $table->integer('modifier')->default(0)->after('max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genomes', function (Blueprint $table) {
            $table->dropColumn('modifier');
        });
    }
};
