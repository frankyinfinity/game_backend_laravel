<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityComponent;
use Illuminate\Http\Request;

class EntityComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('entity_components.index');
    }

    /**
     * Display JSON data for DataTables.
     */
    public function listDataTable(Request $request)
    {
        $query = EntityComponent::query();

        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('entity_components')->exists($row->image)) {
                    $url = asset('storage/entity_components/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isFinished()) {
                    return '<span class="badge badge-success"><i class="fas fa-lock"></i> Completato</span>';
                }
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>';
            })
            ->rawColumns(['image_display', 'state_display'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('entity_components.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        EntityComponent::create([
            'name' => $request->name,
            'state' => EntityComponent::STATE_CREATED,
        ]);

        return redirect()->route('entity-components.index')
            ->with('success', 'Componente Entity creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityComponent $entityComponent)
    {
        return view('entity_components.show', compact('entityComponent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntityComponent $entityComponent)
    {
        return view('entity_components.edit', compact('entityComponent'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntityComponent $entityComponent)
    {
        // Block updates if component is completed/finished
        if ($entityComponent->isFinished()) {
            return redirect()->route('entity-components.index')
                ->with('error', 'Non è possibile modificare un componente completato.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'image_base64' => 'nullable|string',
        ]);

        $data = ['name' => $request->name];

        // Handle base64 image from canvas editor
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $entityComponent->id . '.png';

            \Storage::disk('entity_components')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('entity_components/' . $imageName, base64_decode($imageData));

            $data['image'] = $imageName;
        }

        $entityComponent->update($data);

        return redirect()->route('entity-components.index')
            ->with('success', 'Componente Entity aggiornato con successo.');
    }

    /**
     * Toggle component state between Created and Finished.
     */
    public function toggleState(Request $request, EntityComponent $entityComponent)
    {
        if ($entityComponent->isCreated()) {
            // Can only complete if image is generated
            if (!$entityComponent->image || !\Storage::disk('entity_components')->exists($entityComponent->image)) {
                return redirect()->back()->with('error', 'Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
            }
            $entityComponent->state = EntityComponent::STATE_FINISHED;
            $entityComponent->save();
            return redirect()->back()->with('success', 'Componente completato e bloccato.');
        }

        return redirect()->back()->with('error', 'Non è possibile riaprire un componente completato.');
    }

    /**
     * Bulk delete resource components.
     */
    public function delete(Request $request)
    {
        if ($request->has('ids')) {
            foreach ($request->ids as $id) {
                $entityComponent = EntityComponent::find($id);
                if ($entityComponent == null) {
                    continue;
                }

                // Block deletion if completed/finished
                if ($entityComponent->isFinished()) {
                    continue;
                }

                // Delete image if exists
                if ($entityComponent->image && \Storage::disk('entity_components')->exists($entityComponent->image)) {
                    \Storage::disk('entity_components')->delete($entityComponent->image);
                    \Storage::disk('public')->delete('entity_components/' . $entityComponent->image);
                }

                $entityComponent->delete();
            }
        }

        return response()->json(['success' => true]);
    }
}
