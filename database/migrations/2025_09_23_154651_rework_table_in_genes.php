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
        Schema::table('genes', function (Blueprint $table) {

            $table->dropColumn('name');
            $table->dropColumn('started');
            $table->dropColumn('min');
            $table->dropColumn('max');
            $table->dropColumn('max_from');
            $table->dropColumn('max_to');

            $table->string('type')->nullable()->after('key');
            $table->string('name')->after('type');
            $table->boolean('show_on_registration')->after('name');
            $table->integer('min')->nullable()->after('show_on_registration');
            $table->integer('max')->nullable()->after('min');
            $table->integer('max_from')->nullable()->after('max');
            $table->integer('max_to')->nullable()->after('max_from');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genes', function (Blueprint $table) {

            $table->dropColumn('type');
            $table->dropColumn('name');
            $table->dropColumn('show_on_registration');
            $table->dropColumn('min');
            $table->dropColumn('max');
            $table->dropColumn('max_from');
            $table->dropColumn('max_to');

            $table->string('name')->after('key');
            $table->boolean('started')->after('name');
            $table->integer('min')->nullable()->after('started');
            $table->integer('max')->nullable()->after('min');
            $table->integer('max_from')->nullable()->after('max');
            $table->integer('max_to')->nullable()->after('max_from');

        });
    }
};
