<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Star;

class StarController extends Controller
{

    public function index()
    {
        return view("star.index");
    }

    public function listDataTable(Request $request)
    {
        $query = Star::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("star.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Star::query()->create([
            "name" => $request->name,
            "rgb" => $request->rgb,
            "description" => $request->description,
        ]);

        return redirect(route('stars.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $star = Star::query()->findOrFail($id);
        return view("star.show", compact("star"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $star = Star::query()->findOrFail($id);
        return view("star.edit", compact("star"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $star = Star::query()->findOrFail($id);

        $star->update([
            "name" => $request->name,
            "rgb" => $request->rgb,
            "description" => $request->description,
        ]);

        return redirect(route('stars.show', [$id]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function delete(Request $request)
    {
        foreach ($request->ids as $id) {
            $star = Star::find($id);
            if ($star == null) continue;
            $star->delete();
        }
        return response()->json(['success' => true]);
    }

}
