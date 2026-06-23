<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('element_components', function (Blueprint $table) {
            $table->foreignId('brain_id')
                ->nullable()
                ->after('characteristic')
                ->constrained('brains')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('element_components', function (Blueprint $table) {
            $table->dropForeign(['brain_id']);
            $table->dropColumn('brain_id');
        });
    }
};
