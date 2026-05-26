<?php

namespace App\Http\Controllers;

use App\Models\EntityAnchor;
use App\Models\EntityBody;
use App\Models\EntityComponent;
use Illuminate\Http\Request;

class EntityAnchorController extends Controller
{
    /**
     * Get the model instance based on type and id.
     */
    private function getModel(string $type, int $id)
    {
        return match($type) {
            'entity_body', 'App\Models\EntityBody', 'AppModelsEntityBody' => EntityBody::findOrFail($id),
            'entity_component', 'App\Models\EntityComponent', 'AppModelsEntityComponent' => EntityComponent::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid type: {$type}"),
        };
    }

    /**
     * Display a listing of the resource for the specified polymorphic entity.
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->type, $request->id);
        $anchors = $model->anchors;

        return response()->json($anchors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
            'x' => 'required|integer|min:0|max:31',
            'y' => 'required|integer|min:0|max:31',
        ]);

        $model = $this->getModel($request->type, $request->id);

        // Prevent duplicates at the same coordinate
        $exists = $model->anchors()
            ->where('x', $request->x)
            ->where('y', $request->y)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Un\'ancora esiste già in questa posizione.'], 422);
        }

        $anchor = $model->anchors()->create([
            'x' => $request->x,
            'y' => $request->y,
        ]);

        return response()->json(['success' => true, 'anchor' => $anchor]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityAnchor $entityAnchor)
    {
        $entityAnchor->delete();

        return response()->json(['success' => true]);
    }
}
