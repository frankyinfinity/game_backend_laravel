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
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->string('title')->nullable()->after('max');
        });

        $rules = \App\Models\RuleChimicalElement::all();
        foreach ($rules as $rule) {
            $title = null;
            if ($rule->chimicalElement) {
                $title = $rule->chimicalElement->name;
            } elseif ($rule->complexChimicalElement) {
                $title = $rule->complexChimicalElement->name;
            }
            if ($title) {
                $rule->title = $title;
                $rule->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rule_chimical_elements', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
