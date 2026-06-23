<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ElementComponent;
use App\Models\Gene;
use App\Models\RuleChimicalElement;
use App\Models\ElementComponentHasGene;
use App\Models\ElementComponentHasRuleChimicalElement;
use App\Models\ElementTypeComponent;
use Illuminate\Http\Request;

class ElementComponentController extends Controller
{
    public function index() { return view('element_components.index'); }

    public function listDataTable(Request $request) {
        $query = ElementComponent::with('elementTypeComponent');
        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('element_components')->exists($row->image)) {
                    $url = asset('storage/element_components/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isCompleted()) return '<span class="badge badge-success"><i class="fas fa-check-double"></i> Completato</span>';
                if ($row->isFinishDraw()) return '<span class="badge badge-info"><i class="fas fa-lock"></i> Disegno Terminato</span>';
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>';
            })
            ->addColumn('type_display', function ($row) {
                if ($row->elementTypeComponent) {
                    return '<span>' . \App\Helper\FontAwesome::html($row->elementTypeComponent->symbol, 'fa-fw mr-1 text-dark') . e($row->elementTypeComponent->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('characteristic_display', function ($row) {
                return $row->getCharacteristicLabel();
            })
            ->rawColumns(['image_display', 'state_display', 'type_display'])
            ->toJson();
    }

    public function create() {
        $types = ElementTypeComponent::orderBy('name')->get();
        return view('element_components.create', compact('types'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
            'element_type_component_id' => 'nullable|exists:element_type_components,id',
        ]);
        ElementComponent::create([
            'name' => $request->name,
            'characteristic' => $request->characteristic,
            'element_type_component_id' => $request->element_type_component_id,
            'state' => ElementComponent::STATE_CREATED,
        ]);
        return redirect()->route('element-components.index')->with('success', 'Componente Element creato con successo.');
    }

    public function show(ElementComponent $elementComponent) {
        $elementComponent->load(['elementTypeComponent', 'genes.gene', 'ruleChimicalElements.ruleChimicalElement']);
        return view('element_components.show', compact('elementComponent'));
    }

    public function edit(ElementComponent $elementComponent) {
        $types = ElementTypeComponent::orderBy('name')->get();
        return view('element_components.edit', compact('elementComponent', 'types'));
    }

    public function update(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) {
            return redirect()->route('element-components.index')->with('error', 'Non è possibile modificare un componente con disegno terminato.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'characteristic' => 'required|integer',
            'element_type_component_id' => 'nullable|exists:element_type_components,id',
            'image_base64' => 'nullable|string',
        ]);
        $data = ['name' => $request->name, 'characteristic' => $request->characteristic, 'element_type_component_id' => $request->element_type_component_id];
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = str_replace(['data:image/png;base64,', ' '], ['', '+'], $request->image_base64);
            $imageName = $elementComponent->id . '.png';
            \Storage::disk('element_components')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('element_components/' . $imageName, base64_decode($imageData));
            $data['image'] = $imageName;
        }
        $elementComponent->update($data);
        return redirect()->route('element-components.index')->with('success', 'Componente Element aggiornato con successo.');
    }

    public function toggleState(Request $request, ElementComponent $elementComponent) {
        $targetState = $request->input('state');
        if ($targetState == ElementComponent::STATE_FINISH_DRAW && $elementComponent->isCreated()) {
            if (!$elementComponent->image || !\Storage::disk('element_components')->exists($elementComponent->image)) {
                return redirect()->back()->with('error', 'Non è possibile impostare lo stato su "Disegno Terminato" senza prima aver generato la grafica del componente.');
            }
            $elementComponent->state = ElementComponent::STATE_FINISH_DRAW;
            $elementComponent->save();
            return redirect()->back()->with('success', 'Grafica del componente bloccata.');
        }
        if ($targetState == ElementComponent::STATE_COMPLETED && $elementComponent->isFinishDraw()) {
            $elementComponent->state = ElementComponent::STATE_COMPLETED;
            $elementComponent->save();
            return redirect()->back()->with('success', 'Componente completato e bloccato definitivamente.');
        }
        return redirect()->back()->with('error', 'Operazione di stato non valida.');
    }

    public function delete(Request $request) {
        if ($request->has('ids')) {
            foreach ($request->ids as $id) {
                $elementComponent = ElementComponent::find($id);
                if ($elementComponent == null) continue;
                if ($elementComponent->isFinishDraw()) continue;
                if ($elementComponent->image && \Storage::disk('element_components')->exists($elementComponent->image)) {
                    \Storage::disk('element_components')->delete($elementComponent->image);
                    \Storage::disk('public')->delete('element_components/' . $elementComponent->image);
                }
                $elementComponent->delete();
            }
        }
        return response()->json(['success' => true]);
    }

    public function genesDataTable(Request $request, ElementComponent $elementComponent) {
        $query = ElementComponentHasGene::where('element_component_id', $elementComponent->id)->with('gene');
        return datatables($query)
            ->addColumn('gene_name', fn($row) => $row->gene ? $row->gene->name : '')
            ->addColumn('gene_key', fn($row) => $row->gene ? $row->gene->key : '')
            ->addColumn('value', fn($row) => $row->value)
            ->toJson();
    }

    public function getAvailableGenes(Request $request, ElementComponent $elementComponent) {
        $alreadyIds = ElementComponentHasGene::where('element_component_id', $elementComponent->id)->pluck('gene_id')->toArray();
        return response()->json(Gene::whereNotIn('id', $alreadyIds)->orderBy('name')->get());
    }

    public function storeGene(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $request->validate(['gene_id' => 'required|exists:genes,id', 'value' => 'nullable|integer']);
        if (ElementComponentHasGene::where('element_component_id', $elementComponent->id)->where('gene_id', $request->gene_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Gene già associato.'], 422);
        }
        ElementComponentHasGene::create(['element_component_id' => $elementComponent->id, 'gene_id' => $request->gene_id, 'value' => $request->value]);
        return response()->json(['success' => true]);
    }

    public function destroyGene(Request $request, ElementComponentHasGene $elementComponentHasGene) {
        if ($elementComponentHasGene->elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $elementComponentHasGene->delete();
        return response()->json(['success' => true]);
    }

    public function rulesDataTable(Request $request, ElementComponent $elementComponent) {
        $query = ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->with('ruleChimicalElement');
        return datatables($query)
            ->addColumn('rule_name', fn($row) => $row->ruleChimicalElement ? $row->ruleChimicalElement->name : '')
            ->addColumn('rule_title', fn($row) => $row->ruleChimicalElement ? $row->ruleChimicalElement->title : '')
            ->toJson();
    }

    public function getAvailableRules(Request $request, ElementComponent $elementComponent) {
        $alreadyIds = ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->pluck('rule_chimical_element_id')->toArray();
        return response()->json(RuleChimicalElement::whereNotIn('id', $alreadyIds)->where('type', RuleChimicalElement::TYPE_ELEMENT)->orderBy('name')->get());
    }

    public function storeRule(Request $request, ElementComponent $elementComponent) {
        if ($elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $request->validate(['rule_chimical_element_id' => 'required|exists:rule_chimical_elements,id']);
        if (ElementComponentHasRuleChimicalElement::where('element_component_id', $elementComponent->id)->where('rule_chimical_element_id', $request->rule_chimical_element_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Regola già associata.'], 422);
        }
        ElementComponentHasRuleChimicalElement::create(['element_component_id' => $elementComponent->id, 'rule_chimical_element_id' => $request->rule_chimical_element_id]);
        return response()->json(['success' => true]);
    }

    public function destroyRule(Request $request, ElementComponentHasRuleChimicalElement $elementComponentHasRule) {
        if ($elementComponentHasRule->elementComponent->isFinishDraw()) return response()->json(['success' => false, 'message' => 'Componente bloccato.'], 403);
        $elementComponentHasRule->delete();
        return response()->json(['success' => true]);
    }
}
