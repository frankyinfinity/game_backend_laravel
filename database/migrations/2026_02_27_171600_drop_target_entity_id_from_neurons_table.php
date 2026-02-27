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
        if (Schema::hasColumn('neurons', 'target_entity_id')) {
            Schema::table('neurons', function (Blueprint $table) {
                $table->dropForeign(['target_entity_id']);
                $table->dropColumn('target_entity_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('neurons', 'target_entity_id')) {
            Schema::table('neurons', function (Blueprint $table) {
                $table->foreignId('target_entity_id')->nullable()->after('target_element_id')->constrained('entities')->nullOnDelete();
            });
        }
    }
};

