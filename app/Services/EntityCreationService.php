<?php

namespace App\Services;

use App\Jobs\CreateEntityContainerJob;
use App\Models\Entity;
use App\Models\EntityBody;
use App\Models\EntityBodyZone;
use App\Models\EntityChimicalElement;
use App\Models\EntityComponent;
use App\Models\EntityDetail;
use App\Models\EntityDetailData;
use App\Models\EntityInformation;
use App\Models\Gene;
use App\Models\Genome;
use App\Models\Player;
use App\Models\PlayerRuleChimicalElement;
use App\Models\PlayerValue;
use App\Models\Specie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service che crea TUTTE le tabelle (righe DB) necessarie per una nuova entity.
 *
 * In modalità register ($division = false) accetta in ingresso SOLO le coordinate
 * del tile ($i e $j): tutto il resto (specie LUCA del player, birth region,
 * str_assembler_json, regole chimiche del player) viene risolto internamente dal
 * contesto del player passato al costruttore.
 *
 * In modalità division ($division = true) crea il clone dell'entity con uid
 * $entityUid nella posizione ($i, $j): immagine, genomes + entity_information
 * (con lifepoint configurato), entity_chimical_elements, entity_details +
 * entity_detail_data e container.
 *
 * Tabelle popolate:
 *  - entities
 *  - entity_details + entity_detail_data (EntityBody, EntityBodyZone, EntityComponent)
 *  - genomes + entity_information (geni con valori sommati per componente)
 *  - entity_chimical_elements (dalle PlayerRuleChimicalElement del player)
 *
 * Viene inoltre generata e salvata l'immagine 32x32 dell'entity sul disk entity_images.
 */
class EntityCreationService
{
    public function __construct(private Player $player)
    {
    }

    /**
     * Crea tutte le tabelle per una nuova entity del player.
     *
     * @param int $i coordinata tile I
     * @param int $j coordinata tile J
     * @param bool $division true per clonare l'entity $entityUid (division)
     * @param string|null $entityUid uid dell'entity sorgente (richiesto se $division)
     * @return Entity l'entity creata
     */
    public function createAllTablesForEntity(int $i, int $j, bool $division = false, ?string $entityUid = null): Entity
    {
        if ($i < 0 || $j < 0) {
            throw new InvalidArgumentException("Coordinate tile non valide: i={$i}, j={$j}");
        }

        // Se $division è true: clone dell'entity con uid $entityUid (obbligatorio)
        if ($division) {
            if (empty($entityUid)) {
                throw new InvalidArgumentException('entity_uid obbligatorio quando $division è true');
            }

            return $this->createEntityByDivision($i, $j, $entityUid);
        }

        $player = $this->player;

        // La specie LUCA del player deve esistere (creata durante il register)
        $specie = Specie::query()
            ->where('player_id', $player->id)
            ->where('luca', true)
            ->first();

        if (!$specie) {
            throw new RuntimeException("Nessuna specie LUCA trovata per il player {$player->id}");
        }

        // La birth region del player deve esistere (impostata durante il register)
        if (empty($player->birth_region_id)) {
            throw new RuntimeException("Nessuna birth region trovata per il player {$player->id}");
        }

        Log::info('EntityCreationService: creazione entity con tutte le tabelle', [
            'player_id' => $player->id,
            'specie_id' => $specie->id,
            'tile_i'    => $i,
            'tile_j'    => $j,
        ]);

        $entity = DB::transaction(function () use ($player, $specie, $i, $j) {
            // entities
            $entity = Entity::query()->create([
                'specie_id'       => $specie->id,
                'birth_region_id' => $player->birth_region_id,
                'uid'             => uniqid('', true),
                'tile_i'          => $i,
                'tile_j'          => $j,
                'state'           => Entity::STATE_LIFE,
            ]);

            // entity_details + entity_detail_data
            $this->createEntityDetailTables($entity);

            // genomes + entity_information
            $this->createGenomeTables($entity);

            // entity_chimical_elements
            $this->createEntityChimicalElementTable($entity);

            return $entity;
        });

        // Immagine dell'entity: storage fuori dalla transazione DB
        $this->createEntityImage($entity);

        Log::info('EntityCreationService: entity creata con tutte le tabelle', [
            'player_id' => $player->id,
            'entity_id' => $entity->id,
            'uid'       => $entity->uid,
        ]);

        return $entity;
    }

    /**
     * Division ($division = true): crea il clone dell'entity con uid $entityUid
     * nella posizione ($i, $j). Copia l'immagine, clona genomes +
     * entity_information (con il gene lifepoint configurato su
     * KEY_LIFEPOINT_GENERATE_NEW_ENTITY), entity_chimical_elements,
     * entity_details + entity_detail_data e crea il container della nuova entity.
     *
     * Nota: il service NON crea DrawRequest né richieste di disegno UI.
     * Eventuali draw (es. spawn della nuova entity) restano a carico del chiamante.
     * Il container viene ora creato in Job separato (CreateEntityContainerJob).
     */
    protected function createEntityByDivision(int $i, int $j, string $entityUid): Entity
    {
        $player = $this->player;

        $sourceEntity = Entity::query()
            ->where('uid', $entityUid)
            ->where('state', Entity::STATE_LIFE)
            ->first();

        if (!$sourceEntity) {
            throw new RuntimeException("Entity sorgente non trovata (o non in vita) per uid {$entityUid}");
        }

        $generatedEntityLifepoint = PlayerValue::getIntegerValue(
            $player->id,
            PlayerValue::KEY_LIFEPOINT_GENERATE_NEW_ENTITY
        );

        Log::info('EntityCreationService: division entity', [
            'player_id'         => $player->id,
            'source_entity_uid' => $entityUid,
            'tile_i'            => $i,
            'tile_j'            => $j,
        ]);

        $entity = DB::transaction(function () use ($player, $sourceEntity, $i, $j, $generatedEntityLifepoint) {
            // Copia immagine della entity sorgente sul nuovo id
            $imageContent = null;
            if ($sourceEntity->image && Storage::disk('entity_images')->exists($sourceEntity->image)) {
                $imageContent = Storage::disk('entity_images')->get($sourceEntity->image);
            }

            // entities
            $entity = Entity::query()->create([
                'specie_id'       => $sourceEntity->specie_id,
                'birth_region_id' => $sourceEntity->birth_region_id,
                'uid'             => uniqid('', true),
                'tile_i'          => $i,
                'tile_j'          => $j,
                'state'           => Entity::STATE_LIFE,
            ]);

            // Salva l'immagine con il nuovo entity id e aggiorna il record
            if ($imageContent !== null) {
                $newImageFilename = $entity->id . '.png';
                Storage::disk('entity_images')->put($newImageFilename, $imageContent);
                $entity->update(['image' => $newImageFilename]);
            }

            // genomes + entity_information (con lifepoint configurato)
            $this->cloneGenomeTablesFromEntity($sourceEntity, $entity, $generatedEntityLifepoint);

            // entity_chimical_elements (clonati con i valori correnti)
            $this->cloneEntityChimicalElementTableFromEntity($sourceEntity, $entity);

            // entity_details + entity_detail_data (clonati)
            $this->cloneEntityDetailTablesFromEntity($sourceEntity, $entity);

            return $entity;
        });

        // Dopo la creazione di tutte le tabelle dell'entity: applica il costo
        // di divisione (PlayerValue::KEY_DIVISION_COST) ai lifepoint della sorgente
        $this->applyDivisionCostToSourceEntity($sourceEntity);

        // Crea e avvia il container per la nuova entity tramite job asincrono
        CreateEntityContainerJob::dispatch($entity, $player);

        return $entity;
    }

    /**
     * Applica il costo di divisione (PlayerValue::KEY_DIVISION_COST) ai lifepoint
     * dell'entity sorgente, sottraendolo dal valore corrente (minimo 0).
     *
     * Viene invocato dopo la creazione di tutte le tabelle della nuova entity.
     * Nessun DrawRequest / aggiornamento UI: il disegno resta a carico del chiamante.
     */
    protected function applyDivisionCostToSourceEntity(Entity $sourceEntity): void
    {
        $player = $this->player;

        $divisionCost = PlayerValue::getIntegerValue(
            $player->id,
            PlayerValue::KEY_DIVISION_COST
        );

        $lifepointGenome = Genome::query()
            ->where('entity_id', $sourceEntity->id)
            ->whereHas('gene', function ($q) {
                $q->where('key', Gene::KEY_LIFEPOINT);
            })
            ->first();

        if (!$lifepointGenome) {
            Log::warning('EntityCreationService: gene lifepoint non trovato, costo di divisione non applicato', [
                'player_id'         => $player->id,
                'source_entity_uid' => $sourceEntity->uid,
            ]);
            return;
        }

        $lifepointInfo = EntityInformation::query()->where('genome_id', $lifepointGenome->id)->first();
        if (!$lifepointInfo) {
            Log::warning('EntityCreationService: valore lifepoint non trovato, costo di divisione non applicato', [
                'player_id'         => $player->id,
                'source_entity_uid' => $sourceEntity->uid,
                'genome_id'         => $lifepointGenome->id,
            ]);
            return;
        }

        $currentLife = (int) $lifepointInfo->value;
        $updatedLife = max(0, $currentLife - $divisionCost);
        $lifepointInfo->update(['value' => $updatedLife]);

        Log::info('EntityCreationService: costo di divisione applicato ai lifepoint della sorgente', [
            'player_id'         => $player->id,
            'source_entity_uid' => $sourceEntity->uid,
            'division_cost'     => $divisionCost,
            'lifepoint_before'  => $currentLife,
            'lifepoint_after'   => $updatedLife,
        ]);
    }

    /**
     * Clona genomes ed entity_information dall'entity sorgente.
     * Il gene lifepoint assume il valore configurato (KEY_LIFEPOINT_GENERATE_NEW_ENTITY),
     * gli altri valori sono clampati tra min e max + modifier.
     */
    protected function cloneGenomeTablesFromEntity(Entity $sourceEntity, Entity $entity, int $generatedEntityLifepoint): void
    {
        $sourceGenomes = Genome::query()
            ->where('entity_id', $sourceEntity->id)
            ->with(['gene'])
            ->get();

        foreach ($sourceGenomes as $sourceGenome) {
            $newGenome = Genome::query()->create([
                'entity_id' => $entity->id,
                'gene_id'   => $sourceGenome->gene_id,
                'min'       => $sourceGenome->min,
                'max'       => $sourceGenome->max,
            ]);

            $sourceInfo = EntityInformation::query()->where('genome_id', $sourceGenome->id)->first();
            $newValue = $sourceInfo ? (int) $sourceInfo->value : (int) $sourceGenome->min;

            if ($sourceGenome->gene && $sourceGenome->gene->key === Gene::KEY_LIFEPOINT) {
                $newValue = $generatedEntityLifepoint;
            }
            $newValue = max((int) $sourceGenome->min, min((int) ($sourceGenome->max + ($sourceGenome->modifier ?? 0)), $newValue));

            EntityInformation::query()->create([
                'genome_id' => $newGenome->id,
                'value'     => $newValue,
            ]);
        }
    }

    /**
     * Clona entity_chimical_elements dall'entity sorgente,
     * mantenendo i valori correnti.
     */
    protected function cloneEntityChimicalElementTableFromEntity(Entity $sourceEntity, Entity $entity): void
    {
        $sourceChimicalElements = EntityChimicalElement::query()
            ->where('entity_id', $sourceEntity->id)
            ->get();

        foreach ($sourceChimicalElements as $sourceChimical) {
            EntityChimicalElement::query()->create([
                'entity_id'                       => $entity->id,
                'player_rule_chimical_element_id' => $sourceChimical->player_rule_chimical_element_id,
                'value'                           => $sourceChimical->value,
            ]);
        }
    }

    /**
     * Clona entity_details ed entity_detail_data dall'entity sorgente.
     */
    protected function cloneEntityDetailTablesFromEntity(Entity $sourceEntity, Entity $entity): void
    {
        $sourceDetails = EntityDetail::query()
            ->where('entity_id', $sourceEntity->id)
            ->with('entityDetailData')
            ->get();

        foreach ($sourceDetails as $sourceDetail) {
            $newDetail = EntityDetail::query()->create([
                'entity_id'       => $entity->id,
                'detailable_type' => $sourceDetail->detailable_type,
                'detailable_id'   => $sourceDetail->detailable_id,
            ]);

            foreach ($sourceDetail->entityDetailData as $sourceData) {
                EntityDetailData::query()->create([
                    'entity_detail_id' => $newDetail->id,
                    'key'              => $sourceData->key,
                    'value'            => $sourceData->value,
                ]);
            }
        }
    }

    /**
     * Popola entity_details ed entity_detail_data da str_assembler_json
     * (EntityBody con le sue EntityBodyZone ed EntityComponent).
     */
    protected function createEntityDetailTables(Entity $entity): void
    {
        Log::info('createEntityDetailTables called', ['player_id' => $this->player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $this->player->str_assembler_json;
        if (empty($assemblerJson)) {
            Log::info('No str_assembler_json found for player', ['player_id' => $this->player->id]);
            return;
        }

        $assemblerData = json_decode($assemblerJson, true);

        // --- EntityBody ---
        $bodyId = $assemblerData['body_selected']['id'] ?? null;
        if ($bodyId) {
            $entityBody = EntityBody::with('zones')->find($bodyId);
            if ($entityBody) {
                EntityDetail::create([
                    'entity_id'       => $entity->id,
                    'detailable_type' => EntityBody::class,
                    'detailable_id'   => $entityBody->id,
                ]);

                // --- EntityBodyZone (one EntityDetail + EntityDetailData per zone) ---
                foreach ($entityBody->zones as $zone) {
                    $zoneDetail = EntityDetail::create([
                        'entity_id'       => $entity->id,
                        'detailable_type' => EntityBodyZone::class,
                        'detailable_id'   => $zone->id,
                    ]);

                    $zoneKeyValues = [
                        'name'  => $zone->name,
                        'color' => $zone->color,
                    ];

                    foreach ($zoneKeyValues as $key => $value) {
                        if ($value === null) {
                            continue;
                        }

                        EntityDetailData::create([
                            'entity_detail_id' => $zoneDetail->id,
                            'key'              => $key,
                            'value'            => (string) $value,
                        ]);
                    }
                }
            } else {
                Log::warning('EntityBody not found', ['id' => $bodyId]);
            }
        }

        // --- EntityComponent ---
        $components = $assemblerData['components'] ?? [];
        foreach ($components as $componentData) {
            $componentId = $componentData['id'] ?? null;
            if (!$componentId) {
                continue;
            }

            $entityComponent = EntityComponent::find($componentId);
            if (!$entityComponent) {
                Log::warning('EntityComponent not found', ['id' => $componentId]);
                continue;
            }

            $entityDetail = EntityDetail::create([
                'entity_id'       => $entity->id,
                'detailable_type' => EntityComponent::class,
                'detailable_id'   => $entityComponent->id,
            ]);

            $keyValues = [
                'name'             => $componentData['name'] ?? $entityComponent->name,
                'body_anchor'      => $componentData['link_to_body']['body_anchor'] ?? null,
                'component_anchor' => $componentData['link_to_body']['component_anchor'] ?? null,
            ];

            foreach ($keyValues as $key => $value) {
                if ($value === null) {
                    continue;
                }

                EntityDetailData::create([
                    'entity_detail_id' => $entityDetail->id,
                    'key'              => $key,
                    'value'            => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }
        }

        Log::info('createEntityDetailTables completed', [
            'player_id'        => $this->player->id,
            'entity_id'        => $entity->id,
            'components_count' => count($components),
        ]);
    }

    /**
     * Popola genomes ed entity_information da str_assembler_json.
     * I geni presenti in più componenti hanno i valori sommati.
     */
    protected function createGenomeTables(Entity $entity): void
    {
        Log::info('createGenomeTables called', ['player_id' => $this->player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $this->player->str_assembler_json;
        if (empty($assemblerJson)) {
            return;
        }

        $assemblerData = json_decode($assemblerJson, true);
        $components    = $assemblerData['components'] ?? [];
        if (empty($components)) {
            return;
        }

        // Collect all component IDs and eager-load their genes
        $componentIds = collect($components)->pluck('id')->filter()->unique()->values()->all();

        $entityComponents = EntityComponent::whereIn('id', $componentIds)
            ->with('genes.gene')
            ->get();

        // Sum values per gene_id across all components
        $geneValueMap = []; // [gene_id => total_value]
        foreach ($entityComponents as $ec) {
            foreach ($ec->genes as $geneRel) {
                if (!$geneRel->gene) {
                    continue;
                }
                $geneId = $geneRel->gene_id;
                $geneValueMap[$geneId] = ($geneValueMap[$geneId] ?? 0) + (int) $geneRel->value;
            }
        }

        if (empty($geneValueMap)) {
            Log::info('No genes found in components', ['player_id' => $this->player->id]);
            return;
        }

        // Load all needed genes in one query
        $genes = Gene::whereIn('id', array_keys($geneValueMap))->get()->keyBy('id');

        foreach ($geneValueMap as $geneId => $totalValue) {
            $gene = $genes->get($geneId);
            if (!$gene) {
                Log::warning('Gene not found', ['gene_id' => $geneId]);
                continue;
            }

            $genome = Genome::create([
                'entity_id' => $entity->id,
                'gene_id'   => $geneId,
                'min'       => $gene->min ?? 0,
                'max'       => $gene->max ?? $totalValue,
            ]);

            EntityInformation::create([
                'genome_id' => $genome->id,
                'value'     => $totalValue,
            ]);
        }

        Log::info('createGenomeTables completed', [
            'player_id'   => $this->player->id,
            'entity_id'   => $entity->id,
            'genes_count' => count($geneValueMap),
        ]);
    }

    /**
     * Popola entity_chimical_elements dalle PlayerRuleChimicalElement del player.
     */
    protected function createEntityChimicalElementTable(Entity $entity): void
    {
        Log::info('createEntityChimicalElementTable called', ['player_id' => $this->player->id, 'entity_id' => $entity->id]);

        $playerRules = PlayerRuleChimicalElement::where('player_id', $this->player->id)->get();
        if ($playerRules->isEmpty()) {
            Log::info('No PlayerRuleChimicalElement found for player', ['player_id' => $this->player->id]);
            return;
        }

        foreach ($playerRules as $playerRule) {
            $value = $playerRule->default_value ?? $playerRule->max;
            EntityChimicalElement::create([
                'entity_id'                       => $entity->id,
                'player_rule_chimical_element_id' => $playerRule->id,
                'value'                           => $value,
            ]);
        }

        Log::info('createEntityChimicalElementTable completed', [
            'player_id'            => $this->player->id,
            'entity_id'            => $entity->id,
            'chimical_elements'    => $playerRules->count(),
        ]);
    }

    /**
     * Generate a 32x32 PNG from assembler pixels and save it to entity_images disk.
     * Pixels with rgb "0,0,0" are treated as transparent.
     */
    protected function createEntityImage(Entity $entity): void
    {
        Log::info('createEntityImage called', ['player_id' => $this->player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $this->player->str_assembler_json;
        if (empty($assemblerJson)) {
            Log::info('No str_assembler_json for entity image', ['player_id' => $this->player->id]);
            return;
        }

        $assemblerData = json_decode($assemblerJson, true);
        $pixels = $assemblerData['pixels'] ?? [];

        if (empty($pixels)) {
            Log::info('No pixels in assembler json', ['player_id' => $this->player->id]);
            return;
        }

        // Create a 32x32 true-color image with alpha support
        $img = imagecreatetruecolor(32, 32);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        // Fill with fully transparent background
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);

        foreach ($pixels as $pixel) {
            $x   = (int) ($pixel['x'] ?? -1);
            $y   = (int) ($pixel['y'] ?? -1);
            $rgb = $pixel['rgb'] ?? '0,0,0';

            if ($x < 0 || $x > 31 || $y < 0 || $y > 31) {
                continue;
            }

            $parts = explode(',', $rgb);
            $r = (int) ($parts[0] ?? 0);
            $g = (int) ($parts[1] ?? 0);
            $b = (int) ($parts[2] ?? 0);

            $color = imagecolorallocatealpha($img, $r, $g, $b, 0);
            imagesetpixel($img, $x, $y, $color);
        }

        // Capture PNG to buffer
        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        $filename = $entity->id . '.png';
        Storage::disk('entity_images')->put($filename, $pngData);

        $entity->update(['image' => $filename]);

        Log::info('createEntityImage completed', [
            'player_id' => $this->player->id,
            'entity_id' => $entity->id,
            'filename'  => $filename,
        ]);
    }
}
