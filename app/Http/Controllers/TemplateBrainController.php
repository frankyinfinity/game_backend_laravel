<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TemplateBrain;
use Illuminate\Http\Request;

class TemplateBrainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("template_brains.index");
    }

    public function listDataTable(Request $request)
    {
        $query = TemplateBrain::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("template_brains.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        TemplateBrain::query()->create([
            "name" => $request->name,
        ]);

        return redirect(route('template-brains.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $templateBrain = TemplateBrain::query()->findOrFail($id);
        return view("template_brains.show", compact("templateBrain"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $templateBrain = TemplateBrain::query()->findOrFail($id);

        $templateBrain->update([
            "name" => $request->name,
        ]);

        return redirect(route('template-brains.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function delete(Request $request){
        foreach ($request->ids as $id) {
            $templateBrain = TemplateBrain::find($id);
            if($templateBrain == null) continue;
            $templateBrain->delete();
        }
        return response()->json(['success' => true]);
    }
}
