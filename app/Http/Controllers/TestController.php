<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        return view('test');
    }

    public function action(Request $request)
    {
        // Handle the form data
        $data = $request->all();
        \Log::info('Form data received:', $data);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
}