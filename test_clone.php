<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Player;
use App\Models\AgePlayer;
use App\Models\PhasePlayer;
use App\Models\TargetPlayer;
use App\Models\TargetHasScorePlayer;
use App\Models\TargetLinkPlayer;

$player = new Player();
$player->name = 'Test Player ' . time();
$player->email = 'test' . time() . '@example.com';
$player->password = bcrypt('password');
$player->save();

echo 'Player created with ID: ' . $player->id . PHP_EOL;
echo 'Age Players: ' . AgePlayer::where('player_id', $player->id)->count() . PHP_EOL;
echo 'Phase Players: ' . PhasePlayer::where('player_id', $player->id)->count() . PHP_EOL;
echo 'Target Players: ' . TargetPlayer::where('player_id', $player->id)->count() . PHP_EOL;
echo 'Target Has Score Players: ' . TargetHasScorePlayer::where('player_id', $player->id)->count() . PHP_EOL;
echo 'Target Link Players: ' . TargetLinkPlayer::where('player_id', $player->id)->count() . PHP_EOL;

// Check states
echo PHP_EOL . '--- States ---' . PHP_EOL;
$firstAge = AgePlayer::where('player_id', $player->id)->orderBy('order')->first();
echo 'First Age State: ' . $firstAge->state . PHP_EOL;

$firstPhase = PhasePlayer::where('player_id', $player->id)->where('age_player_id', $firstAge->id)->first();
if ($firstPhase) {
    echo 'First Phase State: ' . $firstPhase->state . PHP_EOL;
}

$firstTarget = TargetPlayer::where('player_id', $player->id)->first();
if ($firstTarget) {
    echo 'First Target State: ' . $firstTarget->state . PHP_EOL;
}
