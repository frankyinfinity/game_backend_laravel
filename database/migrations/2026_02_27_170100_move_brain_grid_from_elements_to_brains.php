<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('elements', 'brain_id')) {
            Schema::table('elements', function (Blueprint $table) {
                $table->foreignId('brain_id')->nullable()->after('characteristic')->constrained('brains')->nullOnDelete();
            });
        }

        $hasOldWidth = Schema::hasColumn('elements', 'brain_grid_width');
        $hasOldHeight = Schema::hasColumn('elements', 'brain_grid_height');

        if ($hasOldWidth || $hasOldHeight) {
            $elements = DB::table('elements')
                ->select(['id', 'brain_id', 'brain_grid_width', 'brain_grid_height', 'characteristic'])
                ->get();
            foreach ($elements as $element) {
                // Create/assign brain only for interactive elements.
                if ((int) ($element->characteristic ?? 0) !== 1) {
                    DB::table('elements')
                        ->where('id', $element->id)
                        ->update(['brain_id' => null]);
                    continue;
                }

                $currentBrainId = $element->brain_id ?? null;
                if (!empty($currentBrainId)) {
                    continue;
                }

                $gridWidth = (int) ($element->brain_grid_width ?? 5);
                $gridHeight = (int) ($element->brain_grid_height ?? 5);
                $gridWidth = max(1, $gridWidth);
                $gridHeight = max(1, $gridHeight);

                $brainId = DB::table('brains')->insertGetId([
                    'uid' => (string) Str::uuid(),
                    'grid_width' => $gridWidth,
                    'grid_height' => $gridHeight,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('elements')
                    ->where('id', $element->id)
                    ->update(['brain_id' => $brainId]);
            }

            Schema::table('elements', function (Blueprint $table) use ($hasOldWidth, $hasOldHeight) {
                $drop = [];
                if ($hasOldWidth) {
                    $drop[] = 'brain_grid_width';
                }
                if ($hasOldHeight) {
                    $drop[] = 'brain_grid_height';
                }
                if (!empty($drop)) {
                    $table->dropColumn($drop);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('elements', 'brain_grid_width')) {
            Schema::table('elements', function (Blueprint $table) {
                $table->unsignedInteger('brain_grid_width')->default(5)->after('characteristic');
            });
        }

        if (!Schema::hasColumn('elements', 'brain_grid_height')) {
            Schema::table('elements', function (Blueprint $table) {
                $table->unsignedInteger('brain_grid_height')->default(5)->after('brain_grid_width');
            });
        }

        if (Schema::hasColumn('elements', 'brain_id')) {
            $elements = DB::table('elements')
                ->leftJoin('brains', 'elements.brain_id', '=', 'brains.id')
                ->select(['elements.id as element_id', 'brains.grid_width', 'brains.grid_height'])
                ->get();

            foreach ($elements as $row) {
                DB::table('elements')
                    ->where('id', $row->element_id)
                    ->update([
                        'brain_grid_width' => max(1, (int) ($row->grid_width ?? 5)),
                        'brain_grid_height' => max(1, (int) ($row->grid_height ?? 5)),
                    ]);
            }

            Schema::table('elements', function (Blueprint $table) {
                $table->dropForeign(['brain_id']);
                $table->dropColumn('brain_id');
            });
        }
    }
};
