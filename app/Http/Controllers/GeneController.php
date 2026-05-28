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
        $query = Gene::query()->select('id', 'name', 'key')->get();
        return datatables($query)->toJson();
    }

    public function show(Gene $gene)
    {
        return view('genes.show', compact('gene'));
    }
}