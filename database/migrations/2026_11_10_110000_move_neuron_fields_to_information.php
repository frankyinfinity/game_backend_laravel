<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rework: i campi dei neuroni (tranne id, *_brain_id, created_at, updated_at)
 * vengono consolidati in un unico campo JSON `information` su entrambe le
 * tabelle: neurons ed element_has_position_neurons.
 */
return new class extends Migration
{
    private const NEURON_FIELDS = [
        'type', 'grid_i', 'grid_j', 'radius', 'stop_before_target',
        'target_type', 'target_element_id', 'gene_life_id', 'gene_attack_id',
        'chemical_element_id', 'complex_chemical_element_id',
        'element_has_rule_chimical_element_id', 'element_infomation_id',
    ];

    private const EHP_NEURON_FIELDS = [
        'active', 'type', 'grid_i', 'grid_j', 'radius', 'stop_before_target',
        'target_type', 'target_element_id', 'gene_life_id', 'gene_attack_id',
        'chemical_element_id', 'complex_chemical_element_id',
        'element_has_position_rule_chimical_element_id',
        'element_has_position_information_id',
    ];

    private const NEURON_FKS = [
        'neurons_chemical_element_id_foreign',
        'neurons_complex_chemical_element_id_foreign',
        'neurons_element_has_rule_chimical_element_id_foreign',
        'neurons_element_infomation_id_foreign',
        'neurons_gene_attack_id_foreign',
        'neurons_gene_life_id_foreign',
        'neurons_target_element_id_foreign',
    ];

    private const EHP_NEURON_FKS = [
        'ehpn_gene_attack_fk',
        'ehpn_gene_life_fk',
        'ehpn_information_fk',
        'ehpn_pos_rule_fk',
        'ehpn_target_element_fk',
        'element_has_position_neurons_chemical_element_id_foreign',
        'element_has_position_neurons_complex_chemical_element_id_foreign',
    ];

    public function up(): void
    {
        // Idempotente: ogni step verifica lo stato prima di agire.
        if (!Schema::hasColumn('neurons', 'information')) {
            Schema::table('neurons', fn(Blueprint $t) => $t->json('information')->nullable()->after('brain_id'));
        }
        if (!Schema::hasColumn('element_has_position_neurons', 'information')) {
            Schema::table('element_has_position_neurons', fn(Blueprint $t) => $t->json('information')->nullable()->after('element_has_position_brain_id'));
        }

        // Backfill: copia i valori delle colonne nel JSON (JSON_OBJECT salta i NULL).
        if (Schema::hasColumn('neurons', 'type')) {
            DB::statement("UPDATE neurons SET information = JSON_OBJECT(
                'type', type, 'grid_i', grid_i, 'grid_j', grid_j,
                'radius', radius, 'stop_before_target', stop_before_target,
                'target_type', target_type, 'target_element_id', target_element_id,
                'gene_life_id', gene_life_id, 'gene_attack_id', gene_attack_id,
                'chemical_element_id', chemical_element_id,
                'complex_chemical_element_id', complex_chemical_element_id,
                'element_has_rule_chimical_element_id', element_has_rule_chimical_element_id,
                'element_infomation_id', element_infomation_id
            ) WHERE information IS NULL");
        }

        if (Schema::hasColumn('element_has_position_neurons', 'type')) {
            DB::statement("UPDATE element_has_position_neurons SET information = JSON_OBJECT(
                'active', active, 'type', type, 'grid_i', grid_i, 'grid_j', grid_j,
                'radius', radius, 'stop_before_target', stop_before_target,
                'target_type', target_type, 'target_element_id', target_element_id,
                'gene_life_id', gene_life_id, 'gene_attack_id', gene_attack_id,
                'chemical_element_id', chemical_element_id,
                'complex_chemical_element_id', complex_chemical_element_id,
                'element_has_position_rule_chimical_element_id', element_has_position_rule_chimical_element_id,
                'element_has_position_information_id', element_has_position_information_id
            ) WHERE information IS NULL");
        }

        // Drop FK sulle colonne consolidate (quelle su *_brain_id restano).
        $this->dropForeignKeysIfExist('neurons', self::NEURON_FKS);
        $this->dropForeignKeysIfExist('element_has_position_neurons', self::EHP_NEURON_FKS);

        // Indice plain su *_brain_id: serve a InnoDB per la FK rimasta,
        // altrimenti non può eliminare l'indice unique composto.
        if (!$this->indexExists('neurons', 'neurons_brain_id_index')) {
            Schema::table('neurons', fn(Blueprint $t) => $t->index('brain_id'));
        }
        if (!$this->indexExists('element_has_position_neurons', 'ehpn_brain_idx')) {
            Schema::table('element_has_position_neurons', fn(Blueprint $t) => $t->index('element_has_position_brain_id', 'ehpn_brain_idx'));
        }

        if ($this->indexExists('neurons', 'neurons_brain_id_grid_i_grid_j_unique')) {
            Schema::table('neurons', fn(Blueprint $t) => $t->dropUnique('neurons_brain_id_grid_i_grid_j_unique'));
        }
        if ($this->indexExists('element_has_position_neurons', 'ehpn_unique_cell')) {
            Schema::table('element_has_position_neurons', fn(Blueprint $t) => $t->dropUnique('ehpn_unique_cell'));
        }

        $dropNeurons = array_values(array_filter(self::NEURON_FIELDS, fn($c) => Schema::hasColumn('neurons', $c)));
        if ($dropNeurons) {
            Schema::table('neurons', fn(Blueprint $t) => $t->dropColumn($dropNeurons));
        }
        $dropEhp = array_values(array_filter(self::EHP_NEURON_FIELDS, fn($c) => Schema::hasColumn('element_has_position_neurons', $c)));
        if ($dropEhp) {
            Schema::table('element_has_position_neurons', fn(Blueprint $t) => $t->dropColumn($dropEhp));
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select(
            'SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index]
        ));
    }

    private function dropForeignKeysIfExist(string $table, array $names): void
    {
        $existing = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table]
        ))->pluck('CONSTRAINT_NAME')->all();

        $toDrop = array_intersect($names, $existing);
        if (empty($toDrop)) return;

        Schema::table($table, function (Blueprint $t) use ($toDrop) {
            foreach ($toDrop as $name) {
                $t->dropForeign($name);
            }
        });
    }

    public function down(): void
    {
        Schema::table('neurons', function (Blueprint $table) {
            $table->string('type')->nullable()->after('brain_id');
            $table->unsignedInteger('grid_i')->default(0);
            $table->unsignedInteger('grid_j')->default(0);
            $table->unsignedInteger('radius')->nullable();
            $table->boolean('stop_before_target')->default(true);
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_element_id')->nullable();
            $table->unsignedBigInteger('gene_life_id')->nullable();
            $table->unsignedBigInteger('gene_attack_id')->nullable();
            $table->unsignedBigInteger('chemical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chemical_element_id')->nullable();
            $table->unsignedBigInteger('element_has_rule_chimical_element_id')->nullable();
            $table->unsignedBigInteger('element_infomation_id')->nullable();
        });
        DB::statement("UPDATE neurons SET
            type = JSON_UNQUOTE(JSON_EXTRACT(information, '$.type')),
            grid_i = COALESCE(JSON_EXTRACT(information, '$.grid_i'), 0),
            grid_j = COALESCE(JSON_EXTRACT(information, '$.grid_j'), 0),
            radius = JSON_EXTRACT(information, '$.radius'),
            stop_before_target = COALESCE(JSON_EXTRACT(information, '$.stop_before_target'), 1),
            target_type = JSON_UNQUOTE(JSON_EXTRACT(information, '$.target_type')),
            target_element_id = JSON_EXTRACT(information, '$.target_element_id'),
            gene_life_id = JSON_EXTRACT(information, '$.gene_life_id'),
            gene_attack_id = JSON_EXTRACT(information, '$.gene_attack_id'),
            chemical_element_id = JSON_EXTRACT(information, '$.chemical_element_id'),
            complex_chemical_element_id = JSON_EXTRACT(information, '$.complex_chemical_element_id'),
            element_has_rule_chimical_element_id = JSON_EXTRACT(information, '$.element_has_rule_chimical_element_id'),
            element_infomation_id = JSON_EXTRACT(information, '$.element_infomation_id')
        ");
        Schema::table('neurons', function (Blueprint $table) {
            $table->unique(['brain_id', 'grid_i', 'grid_j'], 'neurons_brain_id_grid_i_grid_j_unique');
            $table->foreign('chemical_element_id')->references('id')->on('chimical_elements')->nullOnDelete();
            $table->foreign('complex_chemical_element_id')->references('id')->on('complex_chimical_elements')->nullOnDelete();
            $table->foreign('element_has_rule_chimical_element_id')->references('id')->on('rule_chimical_elements')->nullOnDelete();
            $table->foreign('element_infomation_id')->references('id')->on('genes')->nullOnDelete();
            $table->foreign('gene_attack_id')->references('id')->on('genes')->nullOnDelete();
            $table->foreign('gene_life_id')->references('id')->on('genes')->nullOnDelete();
            $table->foreign('target_element_id')->references('id')->on('elements')->nullOnDelete();
            $table->dropColumn('information');
        });

        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->boolean('active')->default(false)->after('element_has_position_brain_id');
            $table->string('type')->nullable();
            $table->unsignedInteger('grid_i')->default(0);
            $table->unsignedInteger('grid_j')->default(0);
            $table->unsignedInteger('radius')->nullable();
            $table->boolean('stop_before_target')->default(true);
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_element_id')->nullable();
            $table->unsignedBigInteger('gene_life_id')->nullable();
            $table->unsignedBigInteger('gene_attack_id')->nullable();
            $table->unsignedBigInteger('chemical_element_id')->nullable();
            $table->unsignedBigInteger('complex_chemical_element_id')->nullable();
            $table->unsignedBigInteger('element_has_position_rule_chimical_element_id')->nullable();
            $table->unsignedBigInteger('element_has_position_information_id')->nullable();
        });
        DB::statement("UPDATE element_has_position_neurons SET
            active = COALESCE(JSON_EXTRACT(information, '$.active'), 0),
            type = JSON_UNQUOTE(JSON_EXTRACT(information, '$.type')),
            grid_i = COALESCE(JSON_EXTRACT(information, '$.grid_i'), 0),
            grid_j = COALESCE(JSON_EXTRACT(information, '$.grid_j'), 0),
            radius = JSON_EXTRACT(information, '$.radius'),
            stop_before_target = COALESCE(JSON_EXTRACT(information, '$.stop_before_target'), 1),
            target_type = JSON_UNQUOTE(JSON_EXTRACT(information, '$.target_type')),
            target_element_id = JSON_EXTRACT(information, '$.target_element_id'),
            gene_life_id = JSON_EXTRACT(information, '$.gene_life_id'),
            gene_attack_id = JSON_EXTRACT(information, '$.gene_attack_id'),
            chemical_element_id = JSON_EXTRACT(information, '$.chemical_element_id'),
            complex_chemical_element_id = JSON_EXTRACT(information, '$.complex_chemical_element_id'),
            element_has_position_rule_chimical_element_id = JSON_EXTRACT(information, '$.element_has_position_rule_chimical_element_id'),
            element_has_position_information_id = JSON_EXTRACT(information, '$.element_has_position_information_id')
        ");
        Schema::table('element_has_position_neurons', function (Blueprint $table) {
            $table->unique(['element_has_position_brain_id', 'grid_i', 'grid_j'], 'ehpn_unique_cell');
            $table->foreign('target_element_id', 'ehpn_target_element_fk')->references('id')->on('elements')->nullOnDelete();
            $table->foreign('gene_life_id', 'ehpn_gene_life_fk')->references('id')->on('genes')->nullOnDelete();
            $table->foreign('gene_attack_id', 'ehpn_gene_attack_fk')->references('id')->on('genes')->nullOnDelete();
            $table->foreign('element_has_position_information_id', 'ehpn_information_fk')->references('id')->on('element_has_position_information')->nullOnDelete();
            $table->foreign('element_has_position_rule_chimical_element_id', 'ehpn_pos_rule_fk')->references('id')->on('element_has_position_rule_chimical_elements')->nullOnDelete();
            $table->foreign('chemical_element_id')->references('id')->on('chimical_elements')->nullOnDelete();
            $table->foreign('complex_chemical_element_id')->references('id')->on('complex_chimical_elements')->nullOnDelete();
            $table->dropColumn('information');
        });
    }
};
