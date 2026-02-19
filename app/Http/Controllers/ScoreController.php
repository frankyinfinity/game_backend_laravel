<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Score;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function index()
    {
        // Se la richiesta è AJAX, restituisci i dati in JSON
        if (request()->ajax()) {
            $scores = Score::all();
            return response()->json($scores);
        }
        
        return view('scores.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Score::query();

        return datatables($query)
            ->addColumn('image_display', function($row) {
                if ($row->image && \Storage::disk('scores')->exists($row->image)) {
                    $url = \Storage::disk('public')->url('scores/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->rawColumns(['image_display'])
            ->toJson();
    }

    public function create()
    {
        return view('scores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        $data = $request->only('name');

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('scores', $filename, 'public');
            $data['image'] = $filename;
        }

        Score::create($data);

        return redirect()->route('scores.index')
            ->with('success', 'Punteggio creato con successo.');
    }

    public function show(Score $score)
    {
        return view('scores.show', compact('score'));
    }

    public function edit(Score $score)
    {
        return view('scores.edit', compact('score'));
    }

    public function update(Request $request, Score $score)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image_base64' => 'nullable|string',
        ]);

        $data = $request->only('name');

        // Handle base64 image from canvas editor
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $score->id . '.png';
            
            \Storage::disk('scores')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('scores/' . $imageName, base64_decode($imageData));
            
            $data['image'] = $imageName;
        }

        $score->update($data);

        return redirect()->route('scores.index')
            ->with('success', 'Punteggio aggiornato con successo.');
    }

    public function destroy(Score $score)
    {
        // Delete image if exists
        if ($score->image && \Storage::disk('scores')->exists($score->image)) {
            \Storage::disk('scores')->delete($score->image);
            \Storage::disk('public')->delete('scores/' . $score->image);
        }

        $score->delete();

        return redirect()->route('scores.index')
            ->with('success', 'Punteggio eliminato con successo.');
    }

    public function saveGraphics(Request $request, Score $score)
    {
        $request->validate([
            'image' => 'required|string', // base64 image
        ]);

        $imageData = $request->image;
        $imageData = str_replace('data:image/png;base64,', '', $imageData);
        $imageData = str_replace(' ', '+', $imageData);
        $imageName = $score->id . '.png';

        \Storage::disk('scores')->put($imageName, base64_decode($imageData));
        
        // Also copy to public disk for web access
        \Storage::disk('public')->put('scores/' . $imageName, base64_decode($imageData));

        // Update the score record with the image reference
        $score->update(['image' => $imageName]);

        return response()->json(['success' => true]);
    }
}
