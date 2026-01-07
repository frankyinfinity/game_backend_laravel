<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    /**
     * Recupera la posizione attuale di un'entity tramite uid
     */
    public function position(Request $request)
    {
        $uid = $request->query('uid');

        if (!$uid) {
            return response()->json([
                'success' => false,
                'message' => 'UID non fornito'
            ], 400);
        }

        $entity = Entity::where('uid', $uid)->first();

        if (!$entity) {
            return response()->json([
                'success' => false,
                'message' => 'Entity non trovata'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'uid' => $entity->uid,
            'tile_i' => $entity->tile_i,
            'tile_j' => $entity->tile_j,
        ]);
    }
}
