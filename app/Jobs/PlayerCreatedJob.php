<?php

namespace App\Jobs;

use App\Models\Age;
use App\Models\AgePlayer;
use App\Models\BirthClimate;
use App\Models\BirthPlanet;
use App\Models\BirthRegion;
use App\Models\BirthRegionDetail;
use App\Models\BirthRegionDetailData;
use App\Models\BirthRegionDiffusion;
use App\Models\BirthRegionDiffusionDetail;
use App\Models\BirthRegionLimit;
use App\Models\BirthRegionLimitDetail;
use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use App\Models\Entity;
use App\Models\EntityBody;
use App\Models\EntityBodyZone;
use App\Models\EntityChimicalElement;
use App\Models\EntityComponent;
use App\Models\EntityDetail;
use App\Models\EntityDetailData;
use App\Models\EntityInformation;
use App\Models\FamilyTile;
use App\Models\FamilyTileDiffusion;
use App\Models\FamilyTileLimit;
use App\Models\Gene;
use App\Models\Genome;
use App\Models\Phase;
use App\Models\PhaseColumn;
use App\Models\PhaseColumnPlayer;
use App\Models\PhasePlayer;
use App\Models\Planet;
use App\Models\Player;
use App\Models\PlayerHasScore;
use App\Models\PlayerRuleChimicalElement;
use App\Models\PlayerRuleChimicalElementDetail;
use App\Models\PlayerRuleChimicalElementDetailEffect;
use App\Models\PlayerValue;
use App\Models\Region;
use App\Models\RuleChimicalElement;
use App\Models\Score;
use App\Models\Specie;
use App\Models\Target;
use App\Models\TargetHasScore;
use App\Models\TargetHasScorePlayer;
use App\Models\TargetLink;
use App\Models\TargetLinkPlayer;
use App\Models\TargetPlayer;
use App\Services\DockerContainerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PlayerCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    protected $player;

    protected $registrationData;

    /**
     * Create a new job instance.
     */
    public function __construct(Player $player, ?array $registrationData = null)
    {
        $this->player = $player;
        $this->registrationData = $registrationData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        $player = $this->player;

        PlayerValue::ensureDefaultsForPlayer($player->id);
        PlayerValue::setValue($player->id, PlayerValue::KEY_DIVISION_COST, 50, PlayerValue::TYPE_INTEGER);
        PlayerValue::setValue($player->id, PlayerValue::KEY_LIFEPOINT_GENERATE_NEW_ENTITY, 40, PlayerValue::TYPE_INTEGER);

        /** @var DockerContainerService $containerService */
        $containerService = app(DockerContainerService::class);
        $containerService->ensurePlayerVolume($player);

        // Initialize player
        $birthRegionIds = $this->initializePlayerWithRegistrationData($player);
        $this->populateBirthRegionLimits($player, $birthRegionIds);
        $this->populateBirthRegionDiffusions($player, $birthRegionIds);
        $this->populateBirthRegionDetailData($player, $birthRegionIds);

        // Clone RuleChimicalElements for the player
        $this->cloneRuleChimicalElements($player);

        // Populate EntityChimicalElement for the player's entities
        $this->populateEntityChimicalElements($player);

        // Clone the objective structure for the player
        $this->cloneObjectiveStructure($player);

        // Create Docker containers - must be last
        try {
            $containerService->createContainersForPlayer($player);
        } catch (\Throwable $e) {
            \Log::error("Errore nella creazione dei container per il player {$player->id}: " . $e->getMessage());
        }
    }

    /**
     * Initialize player with registration data.
     */
    protected function initializePlayerWithRegistrationData(Player $player): array
    {

        $data = $this->registrationData;
        if (!$data) {
            return [];
        }

        // Clone Planet
        $planet = Planet::find($data['birth_planet_id']);
        $region = Region::find($data['birth_region_id']);

        $birthPlanet = BirthPlanet::query()->create([
            'planet_id' => $planet->id,
            'name' => $planet->name,
            'description' => $planet->description,
        ]);

        $birthRegionIds = [];
        $regions = Region::query()
            ->where('planet_id', $planet->id)
            ->where('state', Region::STATE_COMPLETED)
            ->orderBy('name')
            ->whereNotNull('filename')
            ->whereNotNull('modified_image')
            ->get();

        foreach ($regions as $itemRegion) {
            $birthClimate = BirthClimate::query()->create([
                'climate_id' => $itemRegion->climate->id,
                'name' => $itemRegion->climate->name,
                'started' => $itemRegion->climate->started,
                'min_temperature' => $itemRegion->climate->min_temperature,
                'max_temperature' => $itemRegion->climate->max_temperature,
                'default_tile' => $itemRegion->climate->defaultTile,
            ]);

            $filename = $itemRegion->filename;
            $imagename = $itemRegion->modified_image;
            $birthRegion = BirthRegion::query()->create([
                'region_id' => $itemRegion->id,
                'birth_planet_id' => $birthPlanet->id,
                'birth_climate_id' => $birthClimate->id,
                'name' => $itemRegion->name,
                'width' => $itemRegion->width,
                'height' => $itemRegion->height,
                'description' => $itemRegion->description,
                'filename' => $filename,
                'imagename' => $imagename,
            ]);

            $birthRegionIds[] = $birthRegion->id;

            $jsonEntries = [];
            if ($filename !== null) {
                $jsonContent = Storage::disk('regions')->get($itemRegion->id . '/' . $filename);
                $json = json_decode($jsonContent, true);
                $jsonData = json_encode($json, JSON_PRETTY_PRINT);
                Storage::disk('birth_regions')->put($birthRegion->id . '/' . $filename, $jsonData);

                if (is_array($json)) {
                    foreach ($json as $entry) {
                        $tileI = $entry['i'] ?? 0;
                        $tileJ = $entry['j'] ?? 0;
                        $jsonEntries[$tileI . ':' . $tileJ] = $entry;
                    }
                }
            }

            if ($imagename !== null && Storage::disk('map_tile')->exists($itemRegion->id . '/' . $imagename)) {
                $imageContent = Storage::disk('map_tile')->get($itemRegion->id . '/' . $imagename);
                Storage::disk('birth_regions')->put($birthRegion->id . '/' . $imagename, $imageContent);
            }

            $defaultTile = $birthClimate->default_tile;
            for ($ti = 0; $ti < $itemRegion->height; $ti++) {
                for ($tj = 0; $tj < $itemRegion->width; $tj++) {
                    $entry = $jsonEntries[$ti . ':' . $tj] ?? null;
                    $tileData = $entry['tile'] ?? $defaultTile;
                    $generatorData = $entry['generator'] ?? null;

                    $birthRegionDetail = BirthRegionDetail::query()->create([
                        'birth_region_id' => $birthRegion->id,
                        'tile_i' => $ti,
                        'tile_j' => $tj,
                        'json_tile' => json_encode($tileData),
                        'json_generator' => $generatorData ? json_encode($generatorData) : null,
                    ]);

                }
            }
        }

        $searchBirthRegion = BirthRegion::query()
            ->whereIn('id', $birthRegionIds)
            ->where('name', $region->name)
            ->first();

        // Update Player with birth planet and region
        $player->update([
            'birth_planet_id' => $birthPlanet->id,
            'birth_region_id' => $searchBirthRegion ? $searchBirthRegion->id : $birthRegionIds[0],
        ]);

        // Create Player Scores
        $scores = Score::all();
        foreach ($scores as $score) {
            PlayerHasScore::query()->create([
                'player_id' => $player->id,
                'score_id' => $score->id,
            ]);
        }

        // Create Specie
        $specie = Specie::query()->create([
            'player_id' => $player->id,
            'name' => $data['name_specie'],
            'luca' => true,
        ]);

        // Create Entity
        $uid = uniqid('', true);
        $entity = Entity::query()->create([
            'specie_id' => $specie->id,
            'birth_region_id' => $searchBirthRegion ? $searchBirthRegion->id : $birthRegionIds[0],
            'uid' => $uid,
            'tile_i' => $data['tile_i'],
            'tile_j' => $data['tile_j'],
        ]);

        // Populate EntityDetail and EntityDetailData from str_assembler_json
        $this->populateEntityDetails($player, $entity);

        // Populate Genome and EntityInformation from str_assembler_json genes (summing values per gene)
        $this->populateEntityInformation($player, $entity);

        // Generate and save entity image from assembler pixels
        $this->populateEntityImage($player, $entity);

        return $birthRegionIds;

    }

    /**
     * Clone the entire objective structure for a new player.
     */
    protected function cloneObjectiveStructure(Player $player): void
    {
        // Maps to track original IDs to player IDs
        $ageMap = [];
        $phaseMap = [];
        $phaseColumnMap = [];
        $targetMap = [];

        // Clone Ages
        $ages = Age::orderBy('order')->get();
        $isFirstAge = true;
        foreach ($ages as $age) {
            $agePlayer = AgePlayer::create([
                'player_id' => $player->id,
                'age_id' => $age->id,
                'name' => $age->name,
                'order' => $age->order,
                'state' => $isFirstAge ? AgePlayer::STATE_UNLOCKED : AgePlayer::STATE_LOCKED,
            ]);
            $ageMap[$age->id] = $agePlayer->id;
            $isFirstAge = false;
        }

        // Clone Phases - unlock only the very first phase of the first age
        $firstAgeId = Age::orderBy('order')->value('id');
        $firstPhaseId = Phase::where('age_id', $firstAgeId)->orderBy('order')->value('id');
        $phases = Phase::orderBy('order')->get();
        foreach ($phases as $phase) {
            $isFirstAgePhase = ($phase->id === $firstPhaseId);
            $phasePlayer = PhasePlayer::create([
                'player_id' => $player->id,
                'age_player_id' => $ageMap[$phase->age_id],
                'phase_id' => $phase->id,
                'name' => $phase->name,
                'height' => $phase->height,
                'order' => $phase->order,
                'state' => $isFirstAgePhase ? PhasePlayer::STATE_UNLOCKED : PhasePlayer::STATE_LOCKED,
            ]);
            $phaseMap[$phase->id] = $phasePlayer->id;
        }

        // Clone PhaseColumns
        $phaseColumns = PhaseColumn::all();
        foreach ($phaseColumns as $phaseColumn) {
            $phaseColumnPlayer = PhaseColumnPlayer::create([
                'player_id' => $player->id,
                'phase_player_id' => $phaseMap[$phaseColumn->phase_id],
                'phase_column_id' => $phaseColumn->id,
                'uid' => $phaseColumn->uid,
            ]);
            $phaseColumnMap[$phaseColumn->id] = $phaseColumnPlayer->id;
        }

        // Clone Targets - unlock only the very first target of first phase in first age
        $firstPhaseColumnId = PhaseColumn::where('phase_id', $firstPhaseId)
            ->orderBy('id')
            ->value('id');
        $firstTargetId = Target::where('phase_column_id', $firstPhaseColumnId)
            ->orderBy('slot')
            ->orderBy('id')
            ->value('id');

        $targets = Target::all();
        foreach ($targets as $target) {
            // Only the first target of first phase in first age is unlocked
            $isFirstPhaseTarget = ($target->id === $firstTargetId);
            $targetPlayer = TargetPlayer::create([
                'player_id' => $player->id,
                'phase_column_player_id' => $phaseColumnMap[$target->phase_column_id],
                'target_id' => $target->id,
                'slot' => $target->slot,
                'title' => $target->title,
                'description' => $target->description,
                'state' => $isFirstPhaseTarget ? TargetPlayer::STATE_UNLOCKED : TargetPlayer::STATE_LOCKED,
            ]);
            $targetMap[$target->id] = $targetPlayer->id;

            // Clone reward script from template target disk to player target disk
            $sourceFilename = $target->id . '.php';
            $destinationFilename = $targetPlayer->id . '.php';
            try {
                if (Storage::disk('rewards')->exists($sourceFilename)) {
                    $rewardContent = Storage::disk('rewards')->get($sourceFilename);
                    Storage::disk('rewards_player')->put($destinationFilename, $rewardContent);
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to clone target reward file for player target', [
                    'player_id' => $player->id,
                    'target_id' => $target->id,
                    'target_player_id' => $targetPlayer->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clone TargetHasScores
        $targetHasScores = TargetHasScore::all();
        foreach ($targetHasScores as $targetHasScore) {
            TargetHasScorePlayer::create([
                'player_id' => $player->id,
                'target_player_id' => $targetMap[$targetHasScore->target_id],
                'score_id' => $targetHasScore->score_id,
                'value' => $targetHasScore->value,
            ]);
        }

        // Clone TargetLinks
        $targetLinks = TargetLink::all();
        foreach ($targetLinks as $targetLink) {
            TargetLinkPlayer::create([
                'player_id' => $player->id,
                'from_target_player_id' => $targetMap[$targetLink->from_target_id],
                'to_target_player_id' => $targetMap[$targetLink->to_target_id],
            ]);
        }
    }

    /**
     * Clone RuleChimicalElements for the player.
     */
    protected function cloneRuleChimicalElements(Player $player): void
    {
        $ruleChimicalElementIds = $player->str_rule_chimical_element_ids;
        if (empty($ruleChimicalElementIds)) {
            return;
        }

        $ruleIds = array_filter(array_map('trim', explode(',', $ruleChimicalElementIds)));
        foreach ($ruleIds as $ruleId) {
            $rule = RuleChimicalElement::with(['details', 'details.effects'])->find($ruleId);
            if (!$rule) {
                continue;
            }

            $playerRule = PlayerRuleChimicalElement::create([
                'player_id' => $player->id,
                'chimical_element_id' => $rule->chimical_element_id,
                'complex_chimical_element_id' => $rule->complex_chimical_element_id,
                'min' => $rule->min,
                'max' => $rule->max,
                'title' => $rule->title,
                'default_value' => $rule->default_value,
                'quantity_tick_degradation' => $rule->quantity_tick_degradation,
                'percentage_degradation' => $rule->percentage_degradation,
                'degradable' => $rule->degradable,
            ]);

            foreach ($rule->details as $detail) {
                $playerDetail = PlayerRuleChimicalElementDetail::create([
                    'player_rule_chimical_element_id' => $playerRule->id,
                    'min' => $detail->min,
                    'max' => $detail->max,
                    'color' => $detail->color,
                ]);

                foreach ($detail->effects as $effect) {
                    PlayerRuleChimicalElementDetailEffect::create([
                        'player_rule_chimical_element_detail_id' => $playerDetail->id,
                        'type' => $effect->type,
                        'gene_id' => $effect->gene_id,
                        'value' => $effect->value,
                        'duration' => $effect->duration,
                    ]);
                }
            }
        }
    }

    /**
     * Populate BirthRegionLimit and BirthRegionLimitDetail for the player's birth regions.
     */
    protected function populateBirthRegionLimits(Player $player, ?array $birthRegionIds = null): void
    {
        Log::info('populateBirthRegionLimits called', ['player_id' => $player->id]);

        if ($birthRegionIds !== null) {
            $birthRegions = BirthRegion::whereIn('id', $birthRegionIds)->limit(30)->get();
        } else {
            Log::info('No birth_planet_id in registrationData', ['registrationData' => $player->registrationData]);

            return;
        }

        foreach ($birthRegions as $birthRegion) {
            Log::info('Processing birth region', ['birth_region_id' => $birthRegion->id]);

            $familyTiles = FamilyTile::query()->get();
            Log::info('Found family tiles', ['count' => $familyTiles->count()]);

            foreach ($familyTiles as $familyTile) {
                Log::info('Creating BirthRegionLimit for family tile', ['family_tile_id' => $familyTile->id]);

                $birthRegionLimit = BirthRegionLimit::firstOrCreate([
                    'birth_region_id' => $birthRegion->id,
                    'family_tile_id' => $familyTile->id,
                ], [
                    'json_family_tile' => $familyTile->toArray(),
                ]);

                Log::info('BirthRegionLimit created or found', ['id' => $birthRegionLimit->id]);

                // Only create details if not exists
                if ($birthRegionLimit->birthRegionLimitDetails->isEmpty()) {
                    // Chemical elements
                    $chemicalElements = ChimicalElement::all();
                    foreach ($chemicalElements as $element) {
                        $limitValue = FamilyTileLimit::where('family_tile_id', $familyTile->id)
                            ->where('chimical_element_id', $element->id)
                            ->value('limit_value');
                        if ($limitValue === null) {
                            $limitValue = FamilyTile::DEFAULT_LIMIT_VALUE;
                        }

                        BirthRegionLimitDetail::create([
                            'birth_region_limit_id' => $birthRegionLimit->id,
                            'json_chimical_element' => $element->toArray(),
                            'limit_value' => $limitValue,
                        ]);
                    }

                    // Complex chemical elements
                    $complexElements = ComplexChimicalElement::all();
                    foreach ($complexElements as $element) {
                        $limitValue = FamilyTileLimit::where('family_tile_id', $familyTile->id)
                            ->where('complex_chimical_element_id', $element->id)
                            ->value('limit_value');
                        if ($limitValue === null) {
                            $limitValue = FamilyTile::DEFAULT_LIMIT_VALUE;
                        }

                        BirthRegionLimitDetail::create([
                            'birth_region_limit_id' => $birthRegionLimit->id,
                            'json_complex_chimical_element' => $element->toArray(),
                            'limit_value' => $limitValue,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Populate BirthRegionDiffusion and BirthRegionDiffusionDetail for the player's birth regions.
     */
    protected function populateBirthRegionDiffusions(Player $player, ?array $birthRegionIds = null): void
    {
        Log::info('populateBirthRegionDiffusions called', ['player_id' => $player->id]);

        if ($birthRegionIds !== null) {
            $birthRegions = BirthRegion::whereIn('id', $birthRegionIds)->limit(30)->get();
        } else {
            Log::info('No birth_planet_id in registrationData', ['registrationData' => $player->registrationData]);

            return;
        }

        foreach ($birthRegions as $birthRegion) {
            Log::info('Processing birth region for diffusions', ['birth_region_id' => $birthRegion->id]);

            $familyTiles = FamilyTile::query()->get();
            Log::info('Found family tiles for diffusions', ['count' => $familyTiles->count()]);

            foreach ($familyTiles as $familyTile) {
                Log::info('Creating BirthRegionDiffusion for family tile', ['family_tile_id' => $familyTile->id]);

                $birthRegionDiffusion = BirthRegionDiffusion::firstOrCreate([
                    'birth_region_id' => $birthRegion->id,
                    'family_tile_id' => $familyTile->id,
                ], [
                    'json_family_tile' => $familyTile->toArray(),
                ]);

                Log::info('BirthRegionDiffusion created or found', ['id' => $birthRegionDiffusion->id]);

                // Only create details if not exists
                if ($birthRegionDiffusion->birthRegionDiffusionDetails->isEmpty()) {
                    $diffusions = FamilyTileDiffusion::where('family_tile_id', $familyTile->id)->get();
                    foreach ($diffusions as $diffusion) {
                        $jsonElement = null;
                        if ($diffusion->chimical_element_id) {
                            $element = $diffusion->chimicalElement;
                            $jsonElement = $element ? json_encode([
                                'id' => $element->id,
                                'name' => $element->name,
                                'symbol' => $element->symbol,
                            ]) : null;
                        } elseif ($diffusion->complex_chimical_element_id) {
                            $element = $diffusion->complexChimicalElement;
                            $jsonElement = $element ? json_encode([
                                'id' => $element->id,
                                'name' => $element->name,
                                'symbol' => $element->symbol,
                            ]) : null;
                        }

                        if ($jsonElement) {
                            BirthRegionDiffusionDetail::create([
                                'birth_region_diffusion_id' => $birthRegionDiffusion->id,
                                'json_chimical_element' => $diffusion->chimical_element_id ? $jsonElement : null,
                                'json_complex_chimical_element' => $diffusion->complex_chimical_element_id ? $jsonElement : null,
                                'from' => $diffusion->from,
                                'to' => $diffusion->to,
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Populate BirthRegionDetailData for the player's birth regions.
     */
    protected function populateBirthRegionDetailData(Player $player, ?array $birthRegionIds = null): void
    {
        Log::info('populateBirthRegionDetailData called', ['player_id' => $player->id]);

        if ($birthRegionIds === null) {
            Log::info('No birth_region_ids provided', ['player_id' => $player->id]);
            return;
        }

        $birthRegionDetails = BirthRegionDetail::whereIn('birth_region_id', $birthRegionIds)->get();

        foreach ($birthRegionDetails as $birthRegionDetail) {
            $tileData = is_string($birthRegionDetail->json_tile)
                ? json_decode($birthRegionDetail->json_tile, true)
                : $birthRegionDetail->json_tile;

            $familyTileId = is_array($tileData) ? ($tileData['family_tile_id'] ?? null) : $tileData;

            if (!$familyTileId) {
                continue;
            }

            $birthRegionDiffusion = BirthRegionDiffusion::query()
                ->where('birth_region_id', $birthRegionDetail->birth_region_id)
                ->where('family_tile_id', $familyTileId)
                ->first();

            if (!$birthRegionDiffusion) {
                continue;
            }

            foreach ($birthRegionDiffusion->birthRegionDiffusionDetails as $diffusionDetail) {

                $quantity = rand($diffusionDetail->from, $diffusionDetail->to);
                if ($quantity === 0) {
                    continue;
                }

                BirthRegionDetailData::query()->create([
                    'birth_region_detail_id' => $birthRegionDetail->id,
                    'json_chimical_element' => $diffusionDetail->json_chimical_element,
                    'json_complex_chimical_element' => $diffusionDetail->json_complex_chimical_element,
                    'quantity' => $quantity,
                ]);

            }
        }
    }

    /**
     * Populate Genome and EntityInformation from str_assembler_json.
     * Genes appearing in multiple components have their values summed.
     */
    protected function populateEntityInformation(Player $player, Entity $entity): void
    {
        Log::info('populateEntityInformation called', ['player_id' => $player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $player->str_assembler_json;
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
            Log::info('No genes found in components', ['player_id' => $player->id]);
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

        Log::info('populateEntityInformation completed', [
            'player_id'  => $player->id,
            'entity_id'  => $entity->id,
            'genes_count' => count($geneValueMap),
        ]);
    }

    /**
     * Populate EntityDetail and EntityDetailData from str_assembler_json components.
     */
    protected function populateEntityDetails(Player $player, Entity $entity): void
    {
        Log::info('populateEntityDetails called', ['player_id' => $player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $player->str_assembler_json;
        if (empty($assemblerJson)) {
            Log::info('No str_assembler_json found for player', ['player_id' => $player->id]);
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

        Log::info('populateEntityDetails completed', [
            'player_id'        => $player->id,
            'entity_id'        => $entity->id,
            'components_count' => count($components),
        ]);
    }

    /**
     * Generate a 32x32 PNG from assembler pixels and save it to entity_images disk.
     * Pixels with rgb "0,0,0" are treated as transparent.
     */
    protected function populateEntityImage(Player $player, Entity $entity): void
    {
        Log::info('populateEntityImage called', ['player_id' => $player->id, 'entity_id' => $entity->id]);

        $assemblerJson = $player->str_assembler_json;
        if (empty($assemblerJson)) {
            Log::info('No str_assembler_json for entity image', ['player_id' => $player->id]);
            return;
        }

        $assemblerData = json_decode($assemblerJson, true);
        $pixels = $assemblerData['pixels'] ?? [];

        if (empty($pixels)) {
            Log::info('No pixels in assembler json', ['player_id' => $player->id]);
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
        \Illuminate\Support\Facades\Storage::disk('entity_images')->put($filename, $pngData);

        $entity->update(['image' => $filename]);

        Log::info('populateEntityImage completed', [
            'player_id' => $player->id,
            'entity_id' => $entity->id,
            'filename'  => $filename,
        ]);
    }

    /**
     * Populate EntityChimicalElement for all entities of the player.
     */
    protected function populateEntityChimicalElements(Player $player): void
    {
        Log::info('populateEntityChimicalElements called', ['player_id' => $player->id]);

        $playerRules = PlayerRuleChimicalElement::where('player_id', $player->id)->get();
        if ($playerRules->isEmpty()) {
            Log::info('No PlayerRuleChimicalElement found for player', ['player_id' => $player->id]);
            return;
        }

        $entities = Entity::whereHas('specie', function ($q) use ($player) {
            $q->where('player_id', $player->id);
        })->get();

        Log::info('Found entities for player', ['player_id' => $player->id, 'count' => $entities->count()]);

        foreach ($entities as $entity) {
            foreach ($playerRules as $playerRule) {
                $existing = EntityChimicalElement::where('entity_id', $entity->id)
                    ->where('player_rule_chimical_element_id', $playerRule->id)
                    ->first();

                if (!$existing) {
                    $value = $playerRule->default_value ?? $playerRule->max;
                    EntityChimicalElement::create([
                        'entity_id' => $entity->id,
                        'player_rule_chimical_element_id' => $playerRule->id,
                        'value' => $value,
                    ]);
                }
            }
        }

        Log::info('EntityChimicalElement population completed', ['player_id' => $player->id]);
    }
}