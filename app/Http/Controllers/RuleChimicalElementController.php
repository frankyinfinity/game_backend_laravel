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
        $rules = RuleChimicalElement::with(['chimicalElement', 'complexChimicalElement'])->get();
        $chimicalElements = ChimicalElement::query()->get();
        $complexChimicalElements = ComplexChimicalElement::query()->get();
        return view('rule_chimical_elements.index', compact('rules', 'chimicalElements', 'complexChimicalElements'));
    }

    public function list()
    {
        $rules = RuleChimicalElement::where('type', RuleChimicalElement::TYPE_ELEMENT)
            ->orderBy('name')
            ->get();
            
        return response()->json($rules->map(function($rule) {
            return [
                'id' => $rule->id,
                'name' => $rule->name,
                'title' => $rule->title ?? '',
            ];
        }));
    }

    public function create()
    {
        $chimicalElements = ChimicalElement::query()->get();
        $complexChimicalElements = ComplexChimicalElement::query()->get();

        return view('rule_chimical_elements.create', compact('chimicalElements', 'complexChimicalElements'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'min' => 'required|integer',
            'max' => 'required|integer',
            'type' => 'required|string|in:entity,element',
        ]);

        $chimicalElementId = $request->input('chimical_element_id');
        $complexChimicalElementId = $request->input('complex_chimical_element_id');

        $chimicalElementId = $chimicalElementId !== '' && $chimicalElementId !== null ? (int) $chimicalElementId : null;
        $complexChimicalElementId = $complexChimicalElementId !== '' && $complexChimicalElementId !== null ? (int) $complexChimicalElementId : null;

        if (empty($chimicalElementId) && empty($complexChimicalElementId)) {
            return back()->withErrors('Seleziona almeno un elemento chimico o elemento chimico complesso');
        }

        $rule = RuleChimicalElement::create([
            'name' => $request->input('name'),
            'title' => $request->input('title'),
            'type' => $request->input('type'),
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

        $structuralFields = ['chimical_element_id', 'complex_chimical_element_id', 'min', 'max', 'default_value'];
        $hasStructuralChanges = false;

        foreach ($structuralFields as $field) {
            // Se il campo non è presente nella richiesta (perché disabilitato nella UI), lo ignoriamo dal controllo cambiamenti
            if (!$request->has($field)) {
                continue;
            }

            $oldValue = $ruleChimicalElement->{$field};
            $newValue = $request->input($field);

            if ((string) $oldValue !== (string) $newValue) {
                $hasStructuralChanges = true;
                break;
            }
        }

        if ($ruleChimicalElement->details->isNotEmpty() && $hasStructuralChanges) {
            return back()->withInput()->withErrors('Non è possibile modificare i campi strutturali (Elemento, Min, Max, Default) quando sono presenti dei dettagli. Elimina prima i dettagli.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'min' => 'required|integer',
            'max' => 'required|integer',
        ]);

        $elementType = $request->input('element_type');

        // Campi sempre modificabili
        $ruleChimicalElement->name = $request->input('name');
        $ruleChimicalElement->title = $request->input('title');
        $ruleChimicalElement->type = $request->input('type');
        $ruleChimicalElement->degradable = $request->has('degradable');
        $ruleChimicalElement->quantity_tick_degradation = $request->input('quantity_tick_degradation');
        $ruleChimicalElement->percentage_degradation = $request->input('percentage_degradation');

        // Campi strutturali (modificabili solo se non ci sono dettagli)
        if ($ruleChimicalElement->details->isEmpty()) {
            $ruleChimicalElement->min = $request->input('min');
            $ruleChimicalElement->max = $request->input('max');
            $ruleChimicalElement->default_value = $request->input('default_value');
            
            if ($elementType === 'simple') {
                $ruleChimicalElement->chimical_element_id = $request->input('chimical_element_id');
                $ruleChimicalElement->complex_chimical_element_id = null;
            } else {
                $ruleChimicalElement->chimical_element_id = null;
                $ruleChimicalElement->complex_chimical_element_id = $request->input('complex_chimical_element_id');
            }
        }

        $ruleChimicalElement->save();

        return back()->with('success', 'Regola aggiornata con successo nel database!');
    }

    public function destroy(RuleChimicalElement $ruleChimicalElement)
    {
        $ruleChimicalElement->delete();
        return redirect()->route('rule-chimical-elements.index');
    }

    public function replicate(RuleChimicalElement $ruleChimicalElement)
    {
        // Clona la regola
        $newRule = $ruleChimicalElement->replicate();
        $newRule->name = $newRule->name . ' (Copia)';
        $newRule->save();

        // Clona i dettagli e i relativi effetti
        foreach ($ruleChimicalElement->details as $detail) {
            $newDetail = $detail->replicate();
            $newDetail->rule_chimical_element_id = $newRule->id;
            $newDetail->save();

            // Clona gli effetti del dettaglio
            foreach ($detail->effects as $effect) {
                $newEffect = $effect->replicate();
                $newEffect->rule_chimical_element_detail_id = $newDetail->id;
                $newEffect->save();
            }
        }

        return redirect()->route('rule-chimical-elements.index')->with('success', 'Regola clonata con successo!');
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
        $rules = RuleChimicalElement::with(['chimicalElement', 'complexChimicalElement'])->get();

        return response()->json([
            'data' => $rules->map(function ($rule) {
                // Fallback per compatibilità: se type è null, determina dal fatto che abbia chimicalElement o complexChimicalElement
                $type = $rule->type;
                if (is_null($type) || $type === '') {
                    $type = $rule->chimicalElement ? RuleChimicalElement::TYPE_ENTITY : RuleChimicalElement::TYPE_ELEMENT;
                }

                $typeLabel = array_key_exists($type, RuleChimicalElement::getTypes())
                    ? RuleChimicalElement::getTypes()[$type]
                    : $type;

                return [
                    'id' => $rule->id,
                    'element' => $rule->title ?? '-',
                    'type' => $typeLabel,
                    'color' => RuleChimicalElement::getTypeBadgeClass($type),
                    'min' => $rule->min,
                    'max' => $rule->max,
                    'default_value' => $rule->default_value,
                    'degradable' => $rule->degradable,
                ];
            }),
        ]);
    }
}
