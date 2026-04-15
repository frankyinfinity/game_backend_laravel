<?php

namespace App\Http\Controllers;

use App\Models\RuleChimicalElement;
use App\Models\ChimicalElement;
use App\Models\ComplexChimicalElement;
use Illuminate\Http\Request;

class RuleChimicalElementController extends Controller
{
    public function index()
    {
        $existingChimicalIds = RuleChimicalElement::whereNotNull('chimical_element_id')->pluck('chimical_element_id')->toArray();
        $existingComplexIds = RuleChimicalElement::whereNotNull('complex_chimical_element_id')->pluck('complex_chimical_element_id')->toArray();
        
        $rules = RuleChimicalElement::with(['chimicalElement', 'complexChimicalElement'])->get();
        $chimicalElements = ChimicalElement::whereNotIn('id', $existingChimicalIds)->get();
        $complexChimicalElements = ComplexChimicalElement::whereNotIn('id', $existingComplexIds)->get();
        return view('rule_chimical_elements.index', compact('rules', 'chimicalElements', 'complexChimicalElements'));
    }

    public function create()
    {
        $existingChimicalIds = RuleChimicalElement::whereNotNull('chimical_element_id')->pluck('chimical_element_id')->toArray();
        $existingComplexIds = RuleChimicalElement::whereNotNull('complex_chimical_element_id')->pluck('complex_chimical_element_id')->toArray();
        
        $chimicalElements = ChimicalElement::whereNotIn('id', $existingChimicalIds)->get();
        $complexChimicalElements = ComplexChimicalElement::whereNotIn('id', $existingComplexIds)->get();
        
        return view('rule_chimical_elements.create', compact('chimicalElements', 'complexChimicalElements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'min' => 'required|integer',
            'max' => 'required|integer',
        ]);

        $chimicalElementId = $request->input('chimical_element_id');
        $complexChimicalElementId = $request->input('complex_chimical_element_id');

        $chimicalElementId = $chimicalElementId !== '' && $chimicalElementId !== null ? (int) $chimicalElementId : null;
        $complexChimicalElementId = $complexChimicalElementId !== '' && $complexChimicalElementId !== null ? (int) $complexChimicalElementId : null;

        if (empty($chimicalElementId) && empty($complexChimicalElementId)) {
            return back()->withErrors('Seleziona almeno un elemento chimico o elemento chimico complesso');
        }

$rule = RuleChimicalElement::create([
            'chimical_element_id' => $chimicalElementId,
            'complex_chimical_element_id' => $complexChimicalElementId,
            'min' => $request->input('min'),
            'max' => $request->input('max'),
            'default_value' => $request->input('default_value'),
            'quantity_tick_degradation' => $request->input('quantity_tick_degradation'),
            'percentage_degradation' => $request->input('percentage_degradation'),
            'degradable' => $request->boolean('degradable'),
        ]);

        return redirect()->route('rule-chimical-elements.index');
    }

    public function show(RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->load('details');
        return view('rule_chimical_elements.show', compact('ruleChimicalElement'));
    }

    public function edit(RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->load('details');
        $chimicalElements = ChimicalElement::all();
        $complexChimicalElements = ComplexChimicalElement::all();
        return view('rule_chimical_elements.edit', compact('ruleChimicalElement', 'chimicalElements', 'complexChimicalElements'));
    }

    public function update(Request $request, RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->load('details');
        
        $basicFields = ['chimical_element_id', 'complex_chimical_element_id', 'min', 'max', 'default_value'];
        $hasBasicChanges = false;
        
        foreach ($basicFields as $field) {
            $oldValue = $ruleChimicalElement->{$field};
            $newValue = $request->input($field);
            
            if ((string) $oldValue !== (string) $newValue) {
                $hasBasicChanges = true;
                break;
            }
        }
        
        if ($ruleChimicalElement->details->isNotEmpty() && $hasBasicChanges) {
            return back()->withErrors('Non è possibile modificare la regola quando sono presenti dei dettagli. Elimina prima i dettagli.');
        }

        $request->validate([
            'min' => 'required|integer',
            'max' => 'required|integer',
        ]);

        $elementType = $request->input('element_type');
        
        if ($elementType === 'simple') {
            $chimicalElementId = $request->input('chimical_element_id');
            $chimicalElementId = $chimicalElementId ? (int) $chimicalElementId : null;
            
            if (empty($chimicalElementId)) {
                return back()->withErrors('Seleziona un elemento chimico');
            }
            
            $ruleChimicalElement->update([
                'chimical_element_id' => $chimicalElementId,
                'complex_chimical_element_id' => null,
                'min' => $request->input('min'),
                'max' => $request->input('max'),
                'default_value' => $request->input('default_value'),
                'quantity_tick_degradation' => $request->input('quantity_tick_degradation'),
                'percentage_degradation' => $request->input('percentage_degradation'),
                'degradable' => $request->boolean('degradable'),
            ]);
        } else {
            $complexChimicalElementId = $request->input('complex_chimical_element_id');
            $complexChimicalElementId = $complexChimicalElementId ? (int) $complexChimicalElementId : null;
            
            if (empty($complexChimicalElementId)) {
                return back()->withErrors('Seleziona un elemento chimico complesso');
            }
            
            $ruleChimicalElement->update([
                'chimical_element_id' => null,
                'complex_chimical_element_id' => $complexChimicalElementId,
                'min' => $request->input('min'),
                'max' => $request->input('max'),
                'default_value' => $request->input('default_value'),
                'quantity_tick_degradation' => $request->input('quantity_tick_degradation'),
                'percentage_degradation' => $request->input('percentage_degradation'),
                'degradable' => $request->boolean('degradable'),
            ]);
        }

        return redirect()->route('rule-chimical-elements.index');
    }

    public function destroy(RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->delete();
        return redirect()->route('rule-chimical-elements.index');
    }

    public function delete(Request $request)
    {
        $ids = $request->input('ids', []);
        foreach ($ids as $id) {
            $rule = RuleChimicalElement::find($id);
            if ($rule) {
                $rule->delete();
            }
        }
        return response()->json(['success' => true]);
    }

    public function listDataTable()
    {
        $rules = RuleChimicalElement::all();
        
        return response()->json([
            'data' => $rules->map(function ($rule) {
                $type = $rule->chimicalElement ? 'Semplice' : 'Complesso';
                
                return [
                    'id' => $rule->id,
                    'element' => $rule->title ?? '-',
                    'type' => $type,
                    'min' => $rule->min,
                    'max' => $rule->max,
                    'default_value' => $rule->default_value,
                    'degradable' => $rule->degradable,
                ];
            }),
        ]);
    }
}
