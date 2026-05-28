<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chimical_elements', function (Blueprint $table) {
            $table->string('image')->nullable()->after('symbol');
        });
    }

    public function down(): void
    {
        Schema::table('chimical_elements', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};