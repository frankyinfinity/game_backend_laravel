<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->foreignId('group_score_id')
                ->nullable()
                ->after('id')
                ->constrained('group_scores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropForeign(['group_score_id']);
            $table->dropColumn('group_score_id');
        });
    }
};
