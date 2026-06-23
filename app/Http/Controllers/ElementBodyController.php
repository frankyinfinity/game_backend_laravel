<?php

namespace App\Http\Controllers;

use App\Models\ElementBody;
use Illuminate\Http\Request;

class ElementBodyController extends Controller
{
    public function index()
    {
        return view('element_bodies.index');
    }

    public function listDataTable(Request $request)
    {
        $query = ElementBody::query()->withCount('zones');

        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('element_bodies')->exists($row->image)) {
                    $url = asset('storage/element_bodies/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isCompleted()) return '<span class="badge badge-success"><i class="fas fa-check-double"></i> Completato</span>';
                if ($row->state == 2) return '<span class="badge badge-info"><i class="fas fa-lock"></i> Zone Terminate</span>';
                if ($row->state == 1) return '<span class="badge badge-info"><i class="fas fa-pencil-ruler"></i> Disegno Terminato</span>';
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>';
            })
            ->addColumn('characteristic_display', function ($row) {
                return $row->getCharacteristicLabel();
            })
            ->rawColumns(['image_display', 'state_display'])
            ->toJson();
    }

    public function create()
    {
        return view('element_bodies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
        ]);

        ElementBody::create([
            'name' => $request->name,
            'characteristic' => $request->characteristic,
        ]);

        return redirect()->route('element-bodies.index')->with('success', 'Corpo Element creato con successo.');
    }

    public function show(ElementBody $elementBody)
    {
        return view('element_bodies.show', compact('elementBody'));
    }

    public function edit(ElementBody $elementBody)
    {
        return view('element_bodies.edit', compact('elementBody'));
    }

    public function update(Request $request, ElementBody $elementBody)
    {
        if ($elementBody->isFinishDraw()) {
            return redirect()->route('element-bodies.index')->with('error', 'Il corpo con disegno terminato non può essere modificato.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
            'image_base64' => 'nullable|string',
        ]);

        $data = ['name' => $request->name, 'characteristic' => $request->characteristic];

        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = str_replace(['data:image/png;base64,', ' '], ['', '+'], $request->image_base64);
            $imageName = $elementBody->id . '.png';
            \Storage::disk('element_bodies')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('element_bodies/' . $imageName, base64_decode($imageData));
            $data['image'] = $imageName;
        }

        $elementBody->update($data);

        return redirect()->route('element-bodies.index')->with('success', 'Corpo Element aggiornato con successo.');
    }

    public function destroy(ElementBody $elementBody)
    {
        if ($elementBody->isFinishDraw()) {
            return response()->json(['success' => false, 'message' => 'Impossibile eliminare un corpo con disegno terminato.']);
        }
        if ($elementBody->image) {
            \Storage::disk('element_bodies')->delete($elementBody->image);
            \Storage::disk('public')->delete('element_bodies/' . $elementBody->image);
        }
        $elementBody->delete();
        return response()->json(['success' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!empty($ids)) {
            $bodies = ElementBody::whereIn('id', $ids)->where('state', ElementBody::STATE_CREATED)->get();
            foreach ($bodies as $body) {
                if ($body->image) {
                    \Storage::disk('element_bodies')->delete($body->image);
                    \Storage::disk('public')->delete('element_bodies/' . $body->image);
                }
            }
            ElementBody::whereIn('id', $bodies->pluck('id'))->delete();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'Nessun ID fornito']);
    }

    public function toggleState(Request $request)
    {
        $id = $request->input('id');
        $state = (int) $request->input('state');

        if (!$id) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'ID non valido.'], 422)
                : redirect()->back()->with('error', 'ID non valido.');
        }

        $elementBody = ElementBody::findOrFail($id);

        if (in_array($state, [ElementBody::STATE_FINISH_DRAW, ElementBody::STATE_FINISH_ZONE])) {
            if (!$elementBody->image || !\Storage::disk('element_bodies')->exists($elementBody->image)) {
                $msg = 'Disegna la grafica prima di poter completare.';
                return $request->ajax() ? response()->json(['success' => false, 'message' => $msg], 422) : redirect()->back()->with('error', $msg);
            }
        }

        if ($state === ElementBody::STATE_FINISH_ZONE) {
            if ($elementBody->zones()->count() === 0) {
                $msg = 'Crea almeno una zona prima.';
                return $request->ajax() ? response()->json(['success' => false, 'message' => $msg], 422) : redirect()->back()->with('error', $msg);
            }
        }

        if ($state === ElementBody::STATE_COMPLETED && $elementBody->state !== ElementBody::STATE_FINISH_ZONE) {
            $msg = 'Stato precedente non valido.';
            return $request->ajax() ? response()->json(['success' => false, 'message' => $msg], 422) : redirect()->back()->with('error', $msg);
        }

        $elementBody->update(['state' => $state]);

        $msg = 'Stato aggiornato con successo.';
        return $request->ajax() ? response()->json(['success' => true, 'message' => $msg]) : redirect()->route('element-bodies.index')->with('success', $msg);
    }
}
