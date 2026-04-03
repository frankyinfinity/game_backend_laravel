<?php

namespace App\Jobs;

use App\Models\BirthRegion;
use App\Models\BirthRegionDetail;
use App\Models\BirthRegionDetailData;
use App\Models\GeneratorChimicalElement;
use App\Models\PlayerValue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalculateChimicalElementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $birthRegionId;

    public function __construct(int $birthRegionId)
    {
        $this->birthRegionId = $birthRegionId;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $birthRegionId = $this->birthRegionId;

        $birthRegion = BirthRegion::find($birthRegionId);
        if ($birthRegion === null) {
            Log::warning('[CalculateChimicalElementJob] Birth region non trovata', [
                'birth_region_id' => $birthRegionId,
            ]);
            return;
        }

        $player = \App\Models\Player::query()->where('birth_region_id', $birthRegion->id)->first();
        if ($player === null) {
            Log::warning('[CalculateChimicalElementJob] Player non trovato', [
                'birth_region_id' => $birthRegionId,
            ]);
            return;
        }

        $playerId = $player->id;
        PlayerValue::setFlag($playerId, PlayerValue::KEY_CHIMICAL_ELEMENT, true);

        try {
            $this->processCalculateChimicalElement($birthRegion, $playerId);
        } finally {
            PlayerValue::setFlag($playerId, PlayerValue::KEY_CHIMICAL_ELEMENT, false);
        }
    }

    private function processCalculateChimicalElement(BirthRegion $birthRegion, int $playerId): void
    {
        $birthRegionId = $birthRegion->id;

        Log::info('[CalculateChimicalElementJob] Chiamata ricevuta', [
            'birth_region_id' => $birthRegionId,
        ]);

        $detailsWithGenerators = BirthRegionDetail::query()
            ->where('birth_region_id', $birthRegionId)
            ->whereNotNull('json_generator')
            ->get();

        $created = 0;

        foreach ($detailsWithGenerators as $birthRegionDetail) {
            $result = $this->processGeneratorDetail($birthRegionDetail);

            if ($result === null) {
                continue;
            }

            $created++;

            $maxRadius = $result['depth'] ?? 0;
            $tickQuantity = $result['tick_quantity'];

            if ($maxRadius > 0) {
                $this->distributeByRadius(
                    $birthRegion,
                    $birthRegionDetail->tile_i,
                    $birthRegionDetail->tile_j,
                    $result['json_chimical_element'],
                    $maxRadius,
                    $tickQuantity
                );
            }
        }

        Log::info('[CalculateChimicalElementJob] Completato', [
            'birth_region_id' => $birthRegionId,
            'details_with_generators' => $detailsWithGenerators->count(),
            'created' => $created,
        ]);
    }

    private function processGeneratorDetail(BirthRegionDetail $birthRegionDetail): ?array
    {
        $generatorData = is_string($birthRegionDetail->json_generator)
            ? json_decode($birthRegionDetail->json_generator, true)
            : $birthRegionDetail->json_generator;

        if (!is_array($generatorData) || !isset($generatorData['id'])) {
            return null;
        }

        $generator = GeneratorChimicalElement::with('chimicalElement')->find($generatorData['id']);
        if ($generator === null) {
            return null;
        }

        $chimicalElement = $generator->chimicalElement;

        $jsonChimicalElement = $chimicalElement ? json_encode([
            'id' => $chimicalElement->id,
            'name' => $chimicalElement->name,
            'symbol' => $chimicalElement->symbol,
        ]) : null;

        return ['json_chimical_element' => $jsonChimicalElement, 'depth' => $generator->depth ?? 0, 'tick_quantity' => $generator->tick_quantity];
    }

    private function distributeByRadius(
        BirthRegion $birthRegion,
        int $centerI,
        int $centerJ,
        ?string $jsonChimicalElement,
        int $radius,
        int $totalQuantity
    ): void {
        $allTiles = [];

        $centerDetail = BirthRegionDetail::query()
            ->where('birth_region_id', $birthRegion->id)
            ->where('tile_i', $centerI)
            ->where('tile_j', $centerJ)
            ->first();

        if ($centerDetail) {
            $allTiles[] = $centerDetail;
        }

        for ($r = 1; $r <= $radius; $r++) {
            for ($di = -$r; $di <= $r; $di++) {
                for ($dj = -$r; $dj <= $r; $dj++) {
                    if (abs($di) < $r && abs($dj) < $r) {
                        continue;
                    }

                    $ni = $centerI + $di;
                    $nj = $centerJ + $dj;

                    if ($ni < 0 || $nj < 0 || $ni >= $birthRegion->height || $nj >= $birthRegion->width) {
                        continue;
                    }

                    $detail = BirthRegionDetail::query()
                        ->where('birth_region_id', $birthRegion->id)
                        ->where('tile_i', $ni)
                        ->where('tile_j', $nj)
                        ->first();

                    if ($detail) {
                        $allTiles[] = $detail;
                    }
                }
            }
        }

        if (empty($allTiles)) {
            return;
        }

        $remaining = $totalQuantity;

        while ($remaining > 0) {
            $availableTiles = [];

            foreach ($allTiles as $tile) {
                $existingData = BirthRegionDetailData::query()
                    ->where('birth_region_detail_id', $tile->id)
                    ->where('json_chimical_element', $jsonChimicalElement)
                    ->first();

                if (!$existingData || $existingData->quantity < 100) {
                    $availableTiles[] = $tile;
                }
            }

            if (empty($availableTiles)) {
                break;
            }

            $target = $availableTiles[array_rand($availableTiles)];

            $existingData = BirthRegionDetailData::query()
                ->where('birth_region_detail_id', $target->id)
                ->where('json_chimical_element', $jsonChimicalElement)
                ->first();

            $currentQty = $existingData ? $existingData->quantity : 0;
            $spaceAvailable = 100 - $currentQty;

            if ($spaceAvailable <= 0) {
                continue;
            }

            $maxPortion = min(ceil($remaining / 3), $spaceAvailable);
            $portion = rand(1, max(1, $maxPortion));

            if ($existingData) {
                $existingData->update([
                    'quantity' => $currentQty + $portion,
                ]);
            } else {
                BirthRegionDetailData::query()->create([
                    'birth_region_detail_id' => $target->id,
                    'json_chimical_element' => $jsonChimicalElement,
                    'json_complex_chimical_element' => null,
                    'quantity' => $portion,
                ]);
            }

            $remaining -= $portion;
        }
    }
}
