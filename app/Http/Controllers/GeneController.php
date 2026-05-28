<?php

namespace App\Http\Controllers;

use App\Models\Gene;
use Illuminate\Http\Request;

class GeneController extends Controller
{
    public function index()
    {
        return view('genes.index');
    }

    public function listDataTable(Request $request)
    {
        $query = Gene::query()->select('id', 'name', 'key', 'image')->get();
        return datatables($query)->toJson();
    }

    public function show(Gene $gene)
    {
        return view('genes.show', compact('gene'));
    }

    public function edit(Gene $gene)
    {
        return view('genes.edit', compact('gene'));
    }

    public function update(Request $request, Gene $gene)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'image_base64' => 'nullable|string',
        ]);

        $gene->update($request->only(['name', 'key']));

        // Save Image if provided from canvas editor
        if ($request->has('image_base64') && ! empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $gene->id.'.png';
            \Storage::disk('genes')->put($imageName, base64_decode($imageData));
        }

        return redirect()->route('genes.index')
            ->with('success', 'Gene aggiornato con successo.');
    }
}
