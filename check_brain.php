<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$comps = \App\Models\ElementComponent::with('brain.neurons')->get();
foreach ($comps as $comp) {
    echo "ID:{$comp->id} Name:{$comp->name} brain_id:{$comp->brain_id}";
    if ($comp->brain) {
        echo " Brain: {$comp->brain->grid_width}x{$comp->brain->grid_height} neurons:{$comp->brain->neurons->count()}";
    } else {
        echo " NO BRAIN";
    }
    echo PHP_EOL;
}
