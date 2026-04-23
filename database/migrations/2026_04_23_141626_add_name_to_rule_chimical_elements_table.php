<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->string('name')->nullable()->after('max');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
