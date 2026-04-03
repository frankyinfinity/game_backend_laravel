<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generator_chimical_elements', function (Blueprint $table) {
            $table->integer('depth')->default(0)->after('tick_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('generator_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('depth');
        });
    }
};
