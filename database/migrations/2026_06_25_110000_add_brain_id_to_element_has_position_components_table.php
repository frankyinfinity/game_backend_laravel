<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('element_has_position_components', function (Blueprint $table) {
            $table->unsignedBigInteger('brain_id')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('element_has_position_components', function (Blueprint $table) {
            $table->dropColumn('brain_id');
        });
    }
};
