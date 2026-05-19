<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityBody;
use Illuminate\Http\Request;

class EntityBodyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('entity_bodies.index');
    }

    /**
     * Display JSON data for DataTables.
     */
    public function listDataTable(Request $request)
    {
        $query = EntityBody::query();

        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('entity_bodies')->exists($row->image)) {
                    $url = asset('storage/entity_bodies/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isFinishDraw()) {
                    return '<span class="badge badge-success"><i class="fas fa-lock"></i> Disegno Terminato</span>';
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
        return view('entity_bodies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        EntityBody::create([
            'name' => $request->name,
        ]);

        return redirect()->route('entity-bodies.index')
            ->with('success', 'Corpo Entity creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityBody $entityBody)
    {
        return view('entity_bodies.show', compact('entityBody'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntityBody $entityBody)
    {
        return view('entity_bodies.edit', compact('entityBody'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntityBody $entityBody)
    {
        if ($entityBody->isFinishDraw()) {
            return redirect()->route('entity-bodies.index')
                ->with('error', 'Il corpo con disegno terminato non può essere modificato.');
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
            $imageName = $entityBody->id . '.png';

            \Storage::disk('entity_bodies')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('entity_bodies/' . $imageName, base64_decode($imageData));

            $data['image'] = $imageName;
        }

        $entityBody->update($data);

        return redirect()->route('entity-bodies.index')
            ->with('success', 'Corpo Entity aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EntityBody $entityBody)
    {
        if ($entityBody->isFinishDraw()) {
            return response()->json(['success' => false, 'message' => 'Impossibile eliminare un corpo con disegno terminato.']);
        }
        if ($entityBody->image) {
            \Storage::disk('entity_bodies')->delete($entityBody->image);
            \Storage::disk('public')->delete('entity_bodies/' . $entityBody->image);
        }

        $entityBody->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Bulk delete items.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');

        if (!empty($ids)) {
            $bodies = EntityBody::whereIn('id', $ids)->where('state', EntityBody::STATE_CREATED)->get();
            foreach ($bodies as $body) {
                if ($body->image) {
                    \Storage::disk('entity_bodies')->delete($body->image);
                    \Storage::disk('public')->delete('entity_bodies/' . $body->image);
                }
            }
            EntityBody::whereIn('id', $bodies->pluck('id'))->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Nessun ID fornito']);
    }

    /**
     * Toggle the state between Created and Finished.
     */
    public function toggleState(Request $request)
    {
        $id = $request->input('id');
        if (!$id) {
            return redirect()->back()->with('error', 'ID non valido.');
        }

        $entityBody = EntityBody::findOrFail($id);

        if ($entityBody->isCreated()) {
            if (!$entityBody->image) {
                return redirect()->back()->with('error', 'Disegna la grafica prima di completare.');
            }
            $entityBody->update(['state' => EntityBody::STATE_FINISH_DRAW]);
            return redirect()->route('entity-bodies.index')->with('success', 'Corpo completato e bloccato.');
        } else {
            $entityBody->update(['state' => EntityBody::STATE_CREATED]);
            return redirect()->route('entity-bodies.index')->with('success', 'Stato del corpo ripristinato a Creato.');
        }
    }
}
