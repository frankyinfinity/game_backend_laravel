<?php

namespace App\Jobs;

use App\Models\Entity;
use App\Models\EntityBody;
use App\Models\EntityBodyZone;
use App\Models\EntityBodyZonePixel;
use App\Models\EntityDetail;
use App\Models\EvolutionPath;
use App\Models\EvolutionStep;
use App\Models\EvolutionStepDetail;
use App\Models\PlayerValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvolutionSaveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout del job in secondi. -1 = nessun limite: il worker esegue
     * pcntl_alarm(max(timeout, 0)) => pcntl_alarm(0) => nessun alarm attivo.
     */
    public $timeout = -1;

    /**
     * I pixel delle zone sono salvati su una griglia 64x64 relativa al body,
     * mentre l'immagine dell'Entity (e quella del body ridimensionata) e' 32x32.
     */
    private const ZONE_GRID_SIZE = 64;
    private const RENDER_SIZE = 32;

    /**
     * Raggio di ricerca dell'auto-allineamento tra la sagoma del body e
     * quella dell'Entity (stessa logica di EntityEvolutionDraw).
     */
    private const ALIGNMENT_SEARCH = 8;

    /**
     * Di quanto si sposta il colore ad ogni passo di evoluzione. I canali e
     * le zone avanzano IN SEQUENZA (prima il canale R di una zona, poi il G,
     * poi il B, poi si passa alla zona successiva): ad ogni passo si muove
     * un solo canale di una sola zona. Es. con 1, una zona da R 120 verso
     * R 150 genera 30 EvolutionStep (R 121, R 122, ... fino a R 150).
     */
    private const COLOR_STEP = 1;

    /**
     * @param int   $playerId ID del player proprietario del container Evolution
     * @param array $payload  Tutto il JSON ricevuto dall'API evolution/save
     *                        (es. { entity_id: 1, zones: [{ zone_id, zone_name, r, g, b }] }).
     *                        Oltre alla EvolutionPath (colore d'arrivo) vengono creati tutti
     *                        gli EvolutionStep intermedi dal colore attuale al colore richiesto.
     */
    public function __construct(
        public int $playerId,
        public array $payload
    ) {}

    public function handle(): void
    {
        // Nessun limite di memoria per la lavorazione delle immagini
        // (stesso approccio di GameController::evolutionSave)
        ini_set('memory_limit', '-1');

        Log::info('[EvolutionSaveJob] JSON ricevuto dal container Evolution (evolution/save)', [
            'player_id' => $this->playerId,
            'payload'   => $this->payload,
        ]);

        $entityId = (int) ($this->payload['entity_id'] ?? $this->payload['entityId'] ?? 0);
        if ($entityId <= 0) {
            Log::warning('[EvolutionSaveJob] entity_id mancante nel payload, operazione annullata', [
                'player_id' => $this->playerId,
            ]);
            return;
        }

        /** @var Entity|null $entity */
        $entity = Entity::query()->find($entityId);
        if ($entity === null) {
            Log::warning('[EvolutionSaveJob] Entity non trovata, operazione annullata', [
                'player_id' => $this->playerId,
                'entity_id' => $entityId,
            ]);
            return;
        }

        if (empty($entity->specie_id)) {
            Log::warning('[EvolutionSaveJob] Entity senza specie associata, operazione annullata', [
                'player_id' => $this->playerId,
                'entity_id' => $entity->id,
            ]);
            return;
        }

        // Colori delle zone ricevute dal JSON: zone_id => ['r','g','b']
        $zoneColors = $this->parseZoneColors();
        if (empty($zoneColors)) {
            Log::info('[EvolutionSaveJob] Nessuna zona da colorare nel payload, operazione annullata', [
                'player_id' => $this->playerId,
                'entity_id' => $entity->id,
            ]);
            return;
        }

        // Immagine attuale dell'Entity (disco entity_images)
        $image = $this->loadEntityImage($entity);
        if ($image === null) {
            Log::warning("[EvolutionSaveJob] Immagine dell'Entity non trovata sul disco entity_images, operazione annullata", [
                'player_id' => $this->playerId,
                'entity_id' => $entity->id,
                'image'     => $entity->image,
            ]);
            return;
        }

        try {
            // Pixel delle zone richieste dal JSON (griglia 64x64 del body)
            $zonePixels = $this->loadZonePixels(array_keys($zoneColors));
            if (empty($zonePixels)) {
                Log::warning('[EvolutionSaveJob] Nessun pixel trovato per le zone richieste, operazione annullata', [
                    'player_id' => $this->playerId,
                    'entity_id' => $entity->id,
                    'zone_ids'  => array_keys($zoneColors),
                ]);
                return;
            }

            // Auto-allineamento tra la sagoma del body e quella dell'Entity
            $offset = $this->computeAlignmentOffset($entity);

            // Colore attuale di ogni zona sull'immagine dell'Entity:
            // e' il punto di partenza dei passaggi di evoluzione
            $currentColors = $this->currentZoneColors($image, $zonePixels, $offset);

            // Numero di passaggi necessari per arrivare ai colori richiesti
            $stepsCount = $this->computeStepsCount($currentColors, $zoneColors);

            // Colora le zone sull'immagine dell'Entity con il colore d'arrivo
            $paintedPixels = $this->colorZones($image, $zoneColors, $zonePixels, $offset);

            // Esporta il PNG finale (con alpha, come l'immagine originale)
            $pngData = $this->imageToPng($image);
        } finally {
            imagedestroy($image);
        }

        // Salva il file (disco evolution_paths) e il record EvolutionPath, poi
        // tutti i passaggi (EvolutionStep) dal colore attuale fino al colore
        // d'arrivo richiesto dal JSON, ognuno con la sua immagine (disco
        // evolution_steps) e gli EvolutionStepDetail con i colori del passo.
        // Il nome definitivo dei file richiede gli id dei record, quindi i
        // record vengono creati prima con un placeholder e aggiornati dopo il
        // salvataggio (stesso approccio della divisione entity in GameController).
        $path = null;
        $savedFiles = [];
        try {
            /** @var EvolutionPath $path */
            // Nasce in STATE_CREATED; passa a STATE_READY quando l'ultimo
            // EvolutionStep e' stato creato (vedi createEvolutionSteps).
            $path = EvolutionPath::query()->create([
                'specie_id' => (int) $entity->specie_id,
                'uid'       => uniqid('', true),
                'imagename' => '__pending__',
                'finish'    => false,
                'state'     => EvolutionPath::STATE_CREATED,
            ]);

            $imagename = $path->id . '.png';
            Storage::disk('evolution_paths')->put($imagename, $pngData);
            $path->update(['imagename' => $imagename]);
            $savedFiles[] = ['evolution_paths', $imagename];

            // Tutti i passaggi dal colore attuale fino all'arrivo. Anche quando
            // sono zero (zone gia' al colore d'arrivo) si passa da
            // createEvolutionSteps: alla fine il path va in STATE_READY.
            $this->createEvolutionSteps(
                $path,
                $entity,
                $zonePixels,
                $offset,
                $currentColors,
                $zoneColors,
                $stepsCount,
                $savedFiles
            );

            Log::info('[EvolutionSaveJob] EvolutionPath salvata con immagine delle zone colorate', [
                'player_id'         => $this->playerId,
                'entity_id'         => $entity->id,
                'evolution_path_id' => $path->id,
                'imagename'         => $imagename,
                'zones_colored'     => count($zoneColors),
                'pixels_colored'    => $paintedPixels,
                'steps_created'     => $stepsCount,
                'path_state'        => $path->state,
            ]);
        } catch (\Throwable $e) {
            // Se un salvataggio fallisce, la EvolutionPath non va lasciata sul DB
            // con imagename placeholder: le FK sono in cascade, quindi cancellando
            // la EvolutionPath vengono rimossi anche gli EvolutionStep e i relativi
            // EvolutionStepDetail. I file gia' creati vengono rimossi a mano.
            if ($path !== null) {
                $path->delete();
            }
            foreach ($savedFiles as [$disk, $file]) {
                Storage::disk($disk)->delete($file);
            }
            // La cartella degli step (intitolata all'id della EvolutionPath)
            // dopo la cancellazione dei file resta vuota: viene rimossa anch'essa.
            if ($path !== null) {
                Storage::disk('evolution_steps')->deleteDirectory((string) $path->id);
            }
            throw $e;
        }
    }

    /**
     * Estrae i colori delle zone dal JSON ricevuto.
     * Ogni zona ha la forma { zone_id, zone_name, r, g, b } con valori 0-255.
     */
    private function parseZoneColors(): array
    {
        $zonesInput = $this->payload['zones'] ?? null;
        if (!is_array($zonesInput)) {
            return [];
        }

        $colors = [];
        foreach ($zonesInput as $zone) {
            if (!is_array($zone)) {
                continue;
            }

            $zoneId = (int) ($zone['zone_id'] ?? 0);
            if ($zoneId <= 0) {
                continue;
            }

            $colors[$zoneId] = [
                'r' => min(255, max(0, (int) ($zone['r'] ?? 0))),
                'g' => min(255, max(0, (int) ($zone['g'] ?? 0))),
                'b' => min(255, max(0, (int) ($zone['b'] ?? 0))),
            ];
        }

        return $colors;
    }

    /**
     * Carica l'immagine dell'Entity dal disco entity_images.
     *
     * @return \GdImage|resource|null
     */
    private function loadEntityImage(Entity $entity)
    {
        if (!$entity->image || !Storage::disk('entity_images')->exists($entity->image)) {
            return null;
        }

        $image = @imagecreatefromstring(Storage::disk('entity_images')->get($entity->image));

        return $image === false ? null : $image;
    }

    /**
     * Raggruppa i pixel per zona: zone_id => [[x, y], ...].
     */
    private function loadZonePixels(array $zoneIds): array
    {
        $pixels = EntityBodyZonePixel::query()
            ->whereIn('entity_body_zone_id', $zoneIds)
            ->get(['entity_body_zone_id', 'x', 'y']);

        $map = [];
        foreach ($pixels as $pixel) {
            $map[(int) $pixel->entity_body_zone_id][] = [(int) $pixel->x, (int) $pixel->y];
        }

        return $map;
    }

    /**
     * Restituisce l'EntityBody associato all'Entity (via EntityDetail).
     */
    private function getEntityBody(Entity $entity): ?EntityBody
    {
        $entityDetail = EntityDetail::query()
            ->where('entity_id', $entity->id)
            ->where('detailable_type', EntityBody::class)
            ->first();

        if ($entityDetail === null) {
            return null;
        }

        return EntityBody::query()->find($entityDetail->detailable_id);
    }

    /**
     * Calcola l'offset (dx, dy) di allineamento tra la sagoma del body e quella
     * dell'Entity: l'immagine dell'Entity e' la creatura assemblata e il body
     * puo' essere posizionato con uno shift rispetto all'immagine del body.
     * Stesso algoritmo usato da EntityEvolutionDraw per la modalita' evolution.
     *
     * @return array{x: int, y: int}
     */
    private function computeAlignmentOffset(Entity $entity): array
    {
        $offset = ['x' => 0, 'y' => 0];

        $entityImage = $this->loadEntityImage($entity);
        if ($entityImage === null) {
            return $offset;
        }

        $body = $this->getEntityBody($entity);
        if ($body === null || !$body->image || !Storage::disk('entity_bodies')->exists($body->image)) {
            imagedestroy($entityImage);
            return $offset;
        }

        $bodyImage = @imagecreatefromstring(Storage::disk('entity_bodies')->get($body->image));
        if ($bodyImage === false) {
            imagedestroy($entityImage);
            return $offset;
        }

        $size = self::RENDER_SIZE;

        // Sagoma dell'Entity (32x32): pixel visibili, escluso lo sfondo trasparente/quasi bianco
        $eow = imagesx($entityImage);
        $eoh = imagesy($entityImage);
        $ers = imagecreatetruecolor($size, $size);
        imagefill($ers, 0, 0, imagecolorallocate($ers, 255, 255, 255));
        imagecopyresampled($ers, $entityImage, 0, 0, 0, 0, $size, $size, $eow, $eoh);

        $entityMask = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($ers, $x, $y);
                $a = ($rgb >> 24) & 0x7F;
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($a < 100 && !($r > 250 && $g > 250 && $b > 250)) {
                    $entityMask[$y][$x] = true;
                }
            }
        }
        imagedestroy($ers);
        imagedestroy($entityImage);

        // Sagoma del body (32x32): pixel neri
        $ow = imagesx($bodyImage);
        $oh = imagesy($bodyImage);
        $rs = imagecreatetruecolor($size, $size);
        imagefill($rs, 0, 0, imagecolorallocate($rs, 255, 255, 255));
        imagecopyresampled($rs, $bodyImage, 0, 0, 0, 0, $size, $size, $ow, $oh);

        $bodyMask = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($rs, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < 50 && $g < 50 && $b < 50) {
                    $bodyMask[$y][$x] = true;
                }
            }
        }
        imagedestroy($rs);
        imagedestroy($bodyImage);

        if (empty($entityMask) || empty($bodyMask)) {
            return $offset;
        }

        // Auto-allineamento: cerco lo shift (dx, dy) che massimizza la
        // sovrapposizione delle due sagome (a parita' vince lo shift minore)
        $bestScore = -1;
        $bestDist = PHP_INT_MAX;
        for ($dy = -self::ALIGNMENT_SEARCH; $dy <= self::ALIGNMENT_SEARCH; $dy++) {
            for ($dx = -self::ALIGNMENT_SEARCH; $dx <= self::ALIGNMENT_SEARCH; $dx++) {
                $inter = 0;
                $total = 0;
                foreach ($bodyMask as $by => $brow) {
                    foreach ($brow as $bx => $v) {
                        $total++;
                        if (isset($entityMask[$by + $dy][$bx + $dx])) {
                            $inter++;
                        }
                    }
                }
                if ($total === 0 || ($inter / $total) < 0.5) {
                    continue;
                }
                $dist = abs($dx) + abs($dy);
                if ($inter > $bestScore || ($inter === $bestScore && $dist < $bestDist)) {
                    $bestScore = $inter;
                    $bestDist = $dist;
                    $offset = ['x' => $dx, 'y' => $dy];
                }
            }
        }

        return $offset;
    }

    /**
     * Colora sull'immagine i pixel delle zone con i colori indicati
     * (zone_id => ['r','g','b']). Usato sia per il colore d'arrivo del JSON
     * sia per il colore di ciascun passo intermedio di evoluzione.
     *
     * Ogni pixel di zona (griglia 64x64 del body) corrisponde alla cella
     * (px/2, py/2) dell'immagine 32x32, traslata dell'offset di allineamento.
     * Vengono colorati solo i pixel effettivamente visibili (parte della creatura).
     *
     * @param \GdImage|resource $image
     * @return int Numero di pixel colorati
     */
    private function colorZones($image, array $zoneColors, array $zonePixels, array $offset): int
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $factor = (int) (self::ZONE_GRID_SIZE / self::RENDER_SIZE);

        $painted = 0;
        foreach ($zonePixels as $zoneId => $pixels) {
            if (!isset($zoneColors[$zoneId])) {
                continue;
            }

            $colorDef = $zoneColors[$zoneId];
            $color = imagecolorallocatealpha($image, $colorDef['r'], $colorDef['g'], $colorDef['b'], 0);

            foreach ($pixels as [$px, $py]) {
                $x = intdiv($px, $factor) + $offset['x'];
                $y = intdiv($py, $factor) + $offset['y'];
                if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                    continue;
                }

                // Salta lo sfondo trasparente (alpha GD: 0=opaco, 127=trasparente)
                $current = imagecolorat($image, $x, $y);
                if ((($current >> 24) & 0x7F) >= 100) {
                    continue;
                }

                imagesetpixel($image, $x, $y, $color);
                $painted++;
            }
        }

        return $painted;
    }

    /**
     * Esporta l'immagine GD come PNG preservando il canale alpha.
     *
     * @param \GdImage|resource $image
     */
    private function imageToPng($image): string
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * Crea una copia indipendente dell'immagine (preservando l'alpha):
     * usata come base per generare l'immagine di ciascun passo.
     *
     * @param \GdImage|resource $image
     * @return \GdImage|resource
     */
    private function cloneImage($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $copy = imagecreatetruecolor($width, $height);
        imagealphablending($copy, false);
        imagesavealpha($copy, true);
        imagecopy($copy, $image, 0, 0, 0, 0, $width, $height);

        return $copy;
    }

    /**
     * Legge il colore attuale di ogni zona direttamente dall'immagine
     * dell'Entity (media dei pixel visibili della zona, deduplicando le
     * coordinate mappate perche' piu' pixel di zona (griglia 64x64) cadono
     * sulla stessa cella 32x32). Se una zona non ha pixel visibili viene
     * usato il colore di default della zona su DB (entity_body_zones.color).
     *
     * @param \GdImage|resource $image
     * @return array<int, array{r: int, g: int, b: int}> zone_id => colore attuale
     */
    private function currentZoneColors($image, array $zonePixels, array $offset): array
    {
        $zoneIds = array_keys($zonePixels);
        $defaultColors = EntityBodyZone::query()->whereIn('id', $zoneIds)->pluck('color', 'id');

        $width = imagesx($image);
        $height = imagesy($image);
        $factor = (int) (self::ZONE_GRID_SIZE / self::RENDER_SIZE);

        $sums = [];
        $counts = [];
        foreach ($zonePixels as $zoneId => $pixels) {
            $seen = [];
            foreach ($pixels as [$px, $py]) {
                $x = intdiv($px, $factor) + $offset['x'];
                $y = intdiv($py, $factor) + $offset['y'];
                if ($x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                    continue;
                }

                // Stessa cella gia' conteggiata per questa zona
                $key = $x . ',' . $y;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                // Solo pixel visibili (alpha GD: 0=opaco, 127=trasparente)
                $rgb = imagecolorat($image, $x, $y);
                if ((($rgb >> 24) & 0x7F) >= 100) {
                    continue;
                }

                $sums[$zoneId][0] = ($sums[$zoneId][0] ?? 0) + (($rgb >> 16) & 0xFF);
                $sums[$zoneId][1] = ($sums[$zoneId][1] ?? 0) + (($rgb >> 8) & 0xFF);
                $sums[$zoneId][2] = ($sums[$zoneId][2] ?? 0) + ($rgb & 0xFF);
                $counts[$zoneId] = ($counts[$zoneId] ?? 0) + 1;
            }
        }

        $colors = [];
        foreach ($zoneIds as $zoneId) {
            if (($counts[$zoneId] ?? 0) > 0) {
                $colors[$zoneId] = [
                    'r' => (int) round($sums[$zoneId][0] / $counts[$zoneId]),
                    'g' => (int) round($sums[$zoneId][1] / $counts[$zoneId]),
                    'b' => (int) round($sums[$zoneId][2] / $counts[$zoneId]),
                ];
            } else {
                $default = (int) ($defaultColors[$zoneId] ?? 0);
                $colors[$zoneId] = [
                    'r' => ($default >> 16) & 0xFF,
                    'g' => ($default >> 8) & 0xFF,
                    'b' => $default & 0xFF,
                ];
            }
        }

        return $colors;
    }

    /**
     * Numero di passaggi necessari per portare tutte le zone richieste dal
     * JSON al colore d'arrivo. I passaggi sono IN SEQUENZA (ogni passo muove
     * un solo canale di una sola zona: prima i passi del canale R di una
     * zona, poi quelli del canale G, poi del B, poi la zona successiva),
     * quindi il totale e' la SOMMA dei passi di ogni canale di ogni zona
     * (arrotondati per eccesso rispetto a COLOR_STEP).
     * Es. con COLOR_STEP = 1 e R da 120 a 150 => 30 passaggi.
     *
     * @param array<int, array{r: int, g: int, b: int}> $currentColors
     * @param array<int, array{r: int, g: int, b: int}> $zoneColors
     */
    private function computeStepsCount(array $currentColors, array $zoneColors): int
    {
        $stepsCount = 0;
        foreach ($zoneColors as $zoneId => $target) {
            $current = $currentColors[$zoneId] ?? ['r' => 0, 'g' => 0, 'b' => 0];

            foreach (['r', 'g', 'b'] as $channel) {
                $delta = abs($target[$channel] - $current[$channel]);
                $stepsCount += (int) ceil($delta / self::COLOR_STEP);
            }
        }

        return $stepsCount;
    }

    /**
     * Valore di un singolo canale R/G/B al passo k RELATIVO al canale: il
     * canale si sposta verso il target di COLOR_STEP per passo e l'ultimo
     * passo arriva esattamente al colore richiesto dal JSON.
     */
    private function channelValueAtStep(int $current, int $target, int $step): int
    {
        $delta = $target - $current;
        $moved = min(abs($delta), $step * self::COLOR_STEP);

        return $current + (($delta <=> 0) * $moved);
    }

    /**
     * Colore di ogni zona al passo complessivo indicato (1-based).
     *
     * I passaggi sono IN SEQUENZA (stesso ordine di computeStepsCount):
     * prima tutti i passi del canale R di una zona, poi quelli del canale G,
     * poi del B, poi si passa alla zona successiva. Ad ogni passo si muove
     * un solo canale di una sola zona, quindi:
     * - i canali gia' completati sono al colore d'arrivo;
     * - il canale "in movimento" e' al suo valore intermedio;
     * - i canali non ancora iniziati restano al colore attuale.
     *
     * @param array<int, array{r: int, g: int, b: int}> $currentColors
     * @param array<int, array{r: int, g: int, b: int}> $zoneColors
     * @return array<int, array{r: int, g: int, b: int}> zone_id => colore al passo
     */
    private function stepColorsAt(int $step, array $currentColors, array $zoneColors): array
    {
        $colors = [];
        $offset = 0; // passi gia' consumati dai canali precedenti

        foreach ($zoneColors as $zoneId => $target) {
            $current = $currentColors[$zoneId] ?? ['r' => 0, 'g' => 0, 'b' => 0];

            foreach (['r', 'g', 'b'] as $channel) {
                $delta = $target[$channel] - $current[$channel];
                $channelSteps = (int) ceil(abs($delta) / self::COLOR_STEP);

                if ($channelSteps === 0) {
                    // canale fermo: attuale == d'arrivo
                    $colors[$zoneId][$channel] = $current[$channel];
                    continue;
                }

                if ($step <= $offset) {
                    // non ancora iniziato: resta al colore attuale
                    $colors[$zoneId][$channel] = $current[$channel];
                } elseif ($step >= $offset + $channelSteps) {
                    // gia' completato: al colore d'arrivo
                    $colors[$zoneId][$channel] = $target[$channel];
                } else {
                    // canale "in movimento" a questo passo
                    $colors[$zoneId][$channel] = $this->channelValueAtStep(
                        $current[$channel],
                        $target[$channel],
                        $step - $offset
                    );
                }

                $offset += $channelSteps;
            }
        }

        return $colors;
    }

    /**
     * Crea tutti gli EvolutionStep dall'attuale colore delle zone fino al
     * colore d'arrivo richiesto dal JSON: un passo per ogni scatto del
     * colore (COLOR_STEP), ognuno con la propria immagine nella cartella
     * dell'EvolutionPath sul disco evolution_steps
     * (evolution_steps/{id_evolution_path}/{id_step}.png) e gli
     * EvolutionStepDetail con, per ogni zona, le
     * chiavi ZONE_ID / R / G / B (id della zona e canali al colore a quel
     * passo).
     * I passaggi sono IN SEQUENZA (prima il canale R di una zona, poi il G,
     * poi il B, poi la zona successiva): ad ogni passo si muove un solo
     * canale di una sola zona. L'ultimo passo e' l'arrivo.
     * Il campo `finish` non viene MAI impostato a true dal job: resta
     * sempre false (sia per EvolutionPath sia per EvolutionStep).
     * Al termine, quando l'ultimo EvolutionStep e' stato creato, il path
     * passa da STATE_CREATED a STATE_READY.
     *
     * @param array<int, array<int, array{0: int, 1: int}>> $zonePixels zone_id => [[x (0-63), y (0-63)], ...]
     * @param array<int, array{r: int, g: int, b: int}> $currentColors zone_id => colore attuale
     * @param array<int, array{r: int, g: int, b: int}> $zoneColors zone_id => colore d'arrivo dal JSON
     * @param array<int, array{0: string, 1: string}> $savedFiles [disk, file] di tutti i file creati (per eventuale cleanup)
     */
    private function createEvolutionSteps(
        EvolutionPath $path,
        Entity $entity,
        array $zonePixels,
        array $offset,
        array $currentColors,
        array $zoneColors,
        int $stepsCount,
        array &$savedFiles
    ): void {
        // Immagine dell'Entity allo stato attuale: base di ogni passo
        $baseImage = $this->loadEntityImage($entity);
        if ($baseImage === null) {
            Log::warning("[EvolutionSaveJob] Immagine dell'Entity non trovata, EvolutionStep non creati", [
                'player_id' => $this->playerId,
                'entity_id' => $entity->id,
                'image'     => $entity->image,
            ]);
            return;
        }

        try {
            $now = now();
            $detailRows = [];

            // Ogni EvolutionPath ha la propria cartella sul disco evolution_steps
            // (intitolata all'id dell'EvolutionPath): i file dei suoi passaggi
            // vengono salvati dentro quella cartella.
            $stepFolder = (string) $path->id;
            Storage::disk('evolution_steps')->makeDirectory($stepFolder);

            for ($stepNumber = 1; $stepNumber <= $stepsCount; $stepNumber++) {
                // Colore di ogni zona a questo passo (passi in sequenza:
                // ad ogni passo si muove un solo canale di una sola zona)
                $stepColors = $this->stepColorsAt($stepNumber, $currentColors, $zoneColors);

                // Immagine del passo: copia dell'immagine attuale dell'Entity
                // con le zone richieste colorate al colore di questo passo
                $stepImage = $this->cloneImage($baseImage);
                try {
                    $this->colorZones($stepImage, $stepColors, $zonePixels, $offset);
                    $stepPngData = $this->imageToPng($stepImage);
                } finally {
                    imagedestroy($stepImage);
                }

                /** @var EvolutionStep $step */
                // NB: `finish` non viene MAI impostato a true dal job
                // (vale sia per EvolutionStep sia per EvolutionPath).
                $step = EvolutionStep::query()->create([
                    'evolution_path_id' => $path->id,
                    'uid'               => uniqid('', true),
                    'imagename'         => '__pending__',
                    'finish'            => false,
                ]);

                $stepImagename = $stepFolder . '/' . $step->id . '.png';
                Storage::disk('evolution_steps')->put($stepImagename, $stepPngData);
                $step->update(['imagename' => $stepImagename]);
                $savedFiles[] = ['evolution_steps', $stepImagename];

                // Dettagli di ogni zona a questo passo: chiavi ZONE_ID / R / G / B
                foreach ($stepColors as $zoneId => $color) {
                    $detailRows[] = [
                        'evolution_step_id' => $step->id,
                        'key'               => 'ZONE_ID',
                        'value'             => (string) $zoneId,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                    $detailRows[] = [
                        'evolution_step_id' => $step->id,
                        'key'               => 'R',
                        'value'             => (string) $color['r'],
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                    $detailRows[] = [
                        'evolution_step_id' => $step->id,
                        'key'               => 'G',
                        'value'             => (string) $color['g'],
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                    $detailRows[] = [
                        'evolution_step_id' => $step->id,
                        'key'               => 'B',
                        'value'             => (string) $color['b'],
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];
                }
            }

            // Dettagli di tutti i passaggi, inseriti in blocco
            foreach (array_chunk($detailRows, 1000) as $chunk) {
                EvolutionStepDetail::insert($chunk);
            }

            // Tutti gli EvolutionStep sono stati creati (anche zero, se le zone
            // erano gia' al colore d'arrivo): il path passa a READY
            $path->update(['state' => EvolutionPath::STATE_READY]);

            // Blocca il pulsante Evoluzione perché lo stato è cambiato
            PlayerValue::setFlag($this->playerId, PlayerValue::KEY_BLOCK_EVOLUTION, true);
        } finally {
            imagedestroy($baseImage);
        }
    }
}

