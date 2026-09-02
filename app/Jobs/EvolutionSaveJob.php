<?php

namespace App\Jobs;

use App\Models\Entity;
use App\Models\EntityBody;
use App\Models\EntityBodyZonePixel;
use App\Models\EntityDetail;
use App\Models\EvolutionPath;
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
     * @param int   $playerId ID del player proprietario del container Evolution
     * @param array $payload  Tutto il JSON ricevuto dall'API evolution/save
     *                        (es. { entity_id: 1, zones: [{ zone_id, zone_name, r, g, b }] })
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

            // Colora le zone sull'immagine dell'Entity
            $paintedPixels = $this->colorZones($image, $zoneColors, $zonePixels, $offset);

            // Esporta il PNG (con alpha, come l'immagine originale)
            imagealphablending($image, false);
            imagesavealpha($image, true);
            ob_start();
            imagepng($image);
            $pngData = ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        // Salva il file (disco evolution_paths) e il record EvolutionPath.
        // Il nome definitivo del file richiede l'id del record, quindi viene
        // creato prima con un placeholder e aggiornato dopo il salvataggio
        // (stesso approccio della divisione entity in GameController).
        $path = null;
        try {
            /** @var EvolutionPath $path */
            $path = EvolutionPath::query()->create([
                'specie_id' => (int) $entity->specie_id,
                'uid'       => uniqid('', true),
                'imagename' => '__pending__',
                'finish'    => false,
            ]);

            $imagename = $path->id . '.png';
            Storage::disk('evolution_paths')->put($imagename, $pngData);
            $path->update(['imagename' => $imagename]);

            Log::info('[EvolutionSaveJob] EvolutionPath salvata con immagine delle zone colorate', [
                'player_id'         => $this->playerId,
                'entity_id'         => $entity->id,
                'evolution_path_id' => $path->id,
                'imagename'         => $imagename,
                'zones_colored'     => count($zoneColors),
                'pixels_colored'    => $paintedPixels,
            ]);
        } catch (\Throwable $e) {
            // Se il salvataggio del file fallisce, la EvolutionPath non va lasciata
            // sul DB con un imagename placeholder: annulla tutto e rilancia.
            if ($path !== null) {
                $path->delete();
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
     * Colora sull'immagine dell'Entity i pixel delle zone indicate dal JSON.
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
}

