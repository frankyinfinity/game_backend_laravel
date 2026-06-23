<?php

namespace App\Http\Controllers;

use App\Models\ElementAnchor;
use App\Models\ElementComponent;
use Illuminate\Http\Request;

class ElementAnchorController extends Controller
{
    private function getModel(string $type, int $id)
    {
        return match($type) {
            'element_component',
            'App\Models\ElementComponent',
            'AppModelsElementComponent' => ElementComponent::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid type: {$type}"),
        };
    }

    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id'   => 'required|integer',
        ]);

        $model   = $this->getModel($request->type, $request->id);
        $anchors = $model->anchors;

        return response()->json($anchors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id'   => 'required|integer',
            'x'    => 'required|integer|min:0|max:31',
            'y'    => 'required|integer|min:0|max:31',
        ]);

        $model = $this->getModel($request->type, $request->id);

        $exists = $model->anchors()
            ->where('x', $request->x)
            ->where('y', $request->y)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => "Un'ancora esiste già in questa posizione."], 422);
        }

        $anchor = $model->anchors()->create([
            'x' => $request->x,
            'y' => $request->y,
        ]);

        return response()->json(['success' => true, 'anchor' => $anchor]);
    }

    public function destroy(ElementAnchor $elementAnchor)
    {
        $elementAnchor->delete();

        return response()->json(['success' => true]);
    }
}
