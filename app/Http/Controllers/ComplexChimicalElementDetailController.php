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
        $details = $complexChimicalElement->details()->with(['chimicalElement', 'complexChimicalElement'])->get();
        return datatables($details)
            ->addColumn('chimical_element_name', function ($row) {
                if ($row->chimical_element_id) {
                    return $row->chimicalElement->name ?? '';
                }
                if ($row->complex_chimical_element_id) {
                    return $row->complexChimicalElement->name ?? '';
                }
                return '';
            })
            ->addColumn('chimical_element_symbol', function ($row) {
                if ($row->chimical_element_id) {
                    return $row->chimicalElement->symbol ?? '';
                }
                if ($row->complex_chimical_element_id) {
                    return $row->complexChimicalElement->symbol ?? '';
                }
                return '';
            })
            ->toJson();
    }

    public function store(Request $request, ComplexChimicalElement $complexChimicalElement)
    {
        $request->validate([
            'chimical_element_id' => 'required_without:complex_chimical_element_id|nullable|exists:chimical_elements,id',
            'complex_chimical_element_id' => 'required_without:chimical_element_id|nullable|exists:complex_chimical_elements,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($request->complex_chimical_element_id == $complexChimicalElement->id) {
            return response()->json(['success' => false, 'message' => 'Non puoi inserire se stesso'], 422);
        }

        $complexChimicalElement->details()->create($request->only('chimical_element_id', 'complex_chimical_element_id', 'quantity'));

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
