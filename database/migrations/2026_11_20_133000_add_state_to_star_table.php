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
        Schema::table('star', function (Blueprint $table) {
            // Per ora l'unico stato possibile e' Star::STATE_CREATED (0)
            $table->integer('state')->default(0)->after('rgb');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('star', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
