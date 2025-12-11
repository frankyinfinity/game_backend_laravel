<?php

namespace App\Custom\Manipulation;

use App\Helper\Helper;
use Spatie\Valuestore\Valuestore;
use Illuminate\Support\Facades\File;

class ObjectDraw
{

    private $object;
    private string $sessionId;
    public function __construct($object, $sessionId)
    {
        $this->object = $object;
        $this->sessionId = $sessionId;
    }

    private function writeFile(): void
    {

        $object = $this->object;

        $folder = 'json';
        $subFolder = 'object';
        $path = $folder.'/'.$subFolder.'/'.$this->sessionId.'.json';

        $fullPath = storage_path($path);
        $directory = dirname($fullPath);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $store = Valuestore::make($fullPath);
        $store->put($object['uid'], [
            'uid' => $object['uid'],
            'children' => $object['children']
        ]);

    }


    public function get(): array
    {
        $this->writeFile();
        return [
            'type' => Helper::DRAW_REQUEST_TYPE_DRAW,
            'object' => $this->object,
        ];
    }

}
