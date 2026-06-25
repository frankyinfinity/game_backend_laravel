<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$el = \App\Models\Element::find(5);
echo "Element 5: brain_id=" . $el->brain_id . " state=" . $el->state . PHP_EOL;

// Brain neurons
if ($el->brain_id) {
    $neurons = DB::table('neurons')->where('brain_id', $el->brain_id)->get();
    echo "Neurons in brain " . $el->brain_id . ": " . $neurons->count() . PHP_EOL;
}

// ElementDetail with brain_placed
$details = \App\Models\ElementDetail::where('element_id', 5)->with('elementDetailData')->get();
echo PHP_EOL . "ElementDetails:" . PHP_EOL;
foreach ($details as $d) {
    echo "  id={$d->id} type={$d->detailable_type} detailable_id={$d->detailable_id}" . PHP_EOL;
    foreach ($d->elementDetailData as $dd) {
        echo "    {$dd->key} = {$dd->value}" . PHP_EOL;
    }
}
