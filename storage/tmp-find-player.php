<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$entity = App\Models\Entity::where('uid', '6aa2a5822c5b24.50034885')->first();
if (!$entity) {
    echo "ENTITY_NOT_FOUND";
    exit(1);
}
echo "player_id=".$entity->specie->player_id."\n";
$c = App\Models\Container::where('parent_type', App\Models\Container::PARENT_TYPE_ENTITY)->where('parent_id', $entity->id)->first();
echo "ws_port=".($c?->ws_port ?? 'none')."\n";
