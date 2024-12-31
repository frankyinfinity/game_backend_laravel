<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("user.index");
    }

    public function listDataTable(Request $request)
    {
        $query = User::query()->get();
        return datatables($query)->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view("user.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        User::query()->create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => bcrypt($request->password),
        ]);

        return redirect(route('users.index'));

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::query()->findOrFail($id);
        return view("user.show", compact("user"));
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
        
        $user = User::query()->findOrFail($id);

        $fields = [
            "name" => $request->name,
            "email" => $request->email,
        ];
        if($request->password !== null) {
            $fields["password"] = bcrypt($request->password);
        }

        $user->update($fields);
        return redirect(route('users.index'));

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
            $user = User::find($id);
            if($user == null) continue;
            $user->delete();
        }
        return response()->json(['success' => true]);
    }

}
