<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('element_components', function (Blueprint $table) {
            $table->integer('characteristic')->default(0)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('element_components', function (Blueprint $table) {
            $table->dropColumn('characteristic');
        });
    }
};
