<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use App\Models\ComplexChimicalElementDetail;
use Illuminate\Http\Request;

class ComplexChimicalElementDetailController extends Controller
{
    public function listDataTable(ComplexChimicalElement $complexChimicalElement)
    {
        $details = $complexChimicalElement->details()->with('chimicalElement')->get();
        return datatables($details)
            ->addColumn('chimical_element_name', function ($row) {
                return $row->chimicalElement->name ?? '';
            })
            ->addColumn('chimical_element_symbol', function ($row) {
                return $row->chimicalElement->symbol ?? '';
            })
            ->toJson();
    }

    public function store(Request $request, ComplexChimicalElement $complexChimicalElement)
    {
        $request->validate([
            'chimical_element_id' => 'required|exists:chimical_elements,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $complexChimicalElement->details()->create($request->only('chimical_element_id', 'quantity'));

        return response()->json(['success' => true]);
    }

    public function destroy(ComplexChimicalElement $complexChimicalElement, ComplexChimicalElementDetail $detail)
    {
        $detail->delete();

        return response()->json(['success' => true]);
    }

    public function delete(Request $request, ComplexChimicalElement $complexChimicalElement)
    {
        foreach ($request->ids as $id) {
            $detail = $complexChimicalElement->details()->find($id);
            if ($detail) $detail->delete();
        }
        return response()->json(['success' => true]);
    }
}
