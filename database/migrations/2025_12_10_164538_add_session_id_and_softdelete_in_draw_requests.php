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
        Schema::table('draw_requests', function (Blueprint $table) {
            $table->string('session_id')->nullable()->after('id');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draw_requests', function (Blueprint $table) {
            $table->dropColumn('session_id');
            $table->dropSoftDeletes();
        });
    }
};
