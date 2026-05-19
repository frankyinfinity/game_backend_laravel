<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EntityComponent;
use App\Models\Gene;
use App\Models\RuleChimicalElement;
use App\Models\EntityComponentHasGene;
use App\Models\EntityComponentHasRuleChimicalElement;
use App\Models\EntityTypeComponent;
use Illuminate\Http\Request;

class EntityComponentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('entity_components.index');
    }

    /**
     * Display JSON data for DataTables.
     */
    public function listDataTable(Request $request)
    {
        $query = EntityComponent::with('entityTypeComponent');

        return datatables($query)
            ->addColumn('image_display', function ($row) {
                if ($row->image && \Storage::disk('entity_components')->exists($row->image)) {
                    $url = asset('storage/entity_components/' . $row->image . '?v=' . time());
                    return '<img src="' . $url . '" style="width: 32px; height: 32px; image-rendering: pixelated; border: 1px solid #ccc;">';
                }
                return '<div style="width: 32px; height: 32px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image text-muted"></i></div>';
            })
            ->addColumn('state_display', function ($row) {
                if ($row->isFinished()) {
                    return '<span class="badge badge-success"><i class="fas fa-lock"></i> Completato</span>';
                }
                return '<span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>';
            })
            ->addColumn('type_display', function ($row) {
                if ($row->entityTypeComponent) {
                    return '<span><i class="' . e($row->entityTypeComponent->symbol) . ' fa-fw mr-1 text-dark"></i>' . e($row->entityTypeComponent->name) . '</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->rawColumns(['image_display', 'state_display', 'type_display'])
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = EntityTypeComponent::orderBy('name')->get();
        return view('entity_components.create', compact('types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'entity_type_component_id' => 'nullable|exists:entity_type_components,id',
        ]);

        EntityComponent::create([
            'name' => $request->name,
            'entity_type_component_id' => $request->entity_type_component_id,
            'state' => EntityComponent::STATE_CREATED,
        ]);

        return redirect()->route('entity-components.index')
            ->with('success', 'Componente Entity creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EntityComponent $entityComponent)
    {
        $entityComponent->load(['entityTypeComponent', 'genes.gene', 'ruleChimicalElements.ruleChimicalElement']);
        return view('entity_components.show', compact('entityComponent'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EntityComponent $entityComponent)
    {
        $types = EntityTypeComponent::orderBy('name')->get();
        return view('entity_components.edit', compact('entityComponent', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EntityComponent $entityComponent)
    {
        // Block updates if component is completed/finished
        if ($entityComponent->isFinished()) {
            return redirect()->route('entity-components.index')
                ->with('error', 'Non è possibile modificare un componente completato.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'entity_type_component_id' => 'nullable|exists:entity_type_components,id',
            'image_base64' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'entity_type_component_id' => $request->entity_type_component_id,
        ];

        // Handle base64 image from canvas editor
        if ($request->has('image_base64') && !empty($request->image_base64)) {
            $imageData = $request->image_base64;
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = $entityComponent->id . '.png';

            \Storage::disk('entity_components')->put($imageName, base64_decode($imageData));
            \Storage::disk('public')->put('entity_components/' . $imageName, base64_decode($imageData));

            $data['image'] = $imageName;
        }

        $entityComponent->update($data);

        return redirect()->route('entity-components.index')
            ->with('success', 'Componente Entity aggiornato con successo.');
    }

    /**
     * Toggle component state between Created and Finished.
     */
    public function toggleState(Request $request, EntityComponent $entityComponent)
    {
        if ($entityComponent->isCreated()) {
            // Can only complete if image is generated
            if (!$entityComponent->image || !\Storage::disk('entity_components')->exists($entityComponent->image)) {
                return redirect()->back()->with('error', 'Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
            }
            $entityComponent->state = EntityComponent::STATE_FINISHED;
            $entityComponent->save();
            return redirect()->back()->with('success', 'Componente completato e bloccato.');
        }

        return redirect()->back()->with('error', 'Non è possibile riaprire un componente completato.');
    }

    /**
     * Bulk delete resource components.
     */
    public function delete(Request $request)
    {
        if ($request->has('ids')) {
            foreach ($request->ids as $id) {
                $entityComponent = EntityComponent::find($id);
                if ($entityComponent == null) {
                    continue;
                }

                // Block deletion if completed/finished
                if ($entityComponent->isFinished()) {
                    continue;
                }

                // Delete image if exists
                if ($entityComponent->image && \Storage::disk('entity_components')->exists($entityComponent->image)) {
                    \Storage::disk('entity_components')->delete($entityComponent->image);
                    \Storage::disk('public')->delete('entity_components/' . $entityComponent->image);
                }

                $entityComponent->delete();
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Display JSON data for Genes Datatable.
     */
    public function genesDataTable(Request $request, EntityComponent $entityComponent)
    {
        $query = EntityComponentHasGene::where('entity_component_id', $entityComponent->id)
            ->with('gene');

        return datatables($query)
            ->addColumn('gene_name', function ($row) {
                return $row->gene ? $row->gene->name : '';
            })
            ->addColumn('gene_key', function ($row) {
                return $row->gene ? $row->gene->key : '';
            })
            ->rawColumns([])
            ->toJson();
    }

    /**
     * Get available genes for adding.
     */
    public function getAvailableGenes(Request $request, EntityComponent $entityComponent)
    {
        $alreadyAssociatedIds = EntityComponentHasGene::where('entity_component_id', $entityComponent->id)
            ->pluck('gene_id')
            ->toArray();

        $genes = Gene::whereNotIn('id', $alreadyAssociatedIds)
            ->orderBy('name')
            ->get();

        return response()->json($genes);
    }

    /**
     * Store new gene association.
     */
    public function storeGene(Request $request, EntityComponent $entityComponent)
    {
        if ($entityComponent->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Non è possibile modificare un componente completato.'], 403);
        }

        $request->validate([
            'gene_id' => 'required|exists:genes,id',
        ]);

        $exists = EntityComponentHasGene::where('entity_component_id', $entityComponent->id)
            ->where('gene_id', $request->gene_id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Questo gene è già associato al componente.'], 422);
        }

        EntityComponentHasGene::create([
            'entity_component_id' => $entityComponent->id,
            'gene_id' => $request->gene_id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Destroy gene association.
     */
    public function destroyGene(Request $request, EntityComponentHasGene $entityComponentHasGene)
    {
        $entityComponent = $entityComponentHasGene->entityComponent;
        if ($entityComponent->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Non è possibile modificare un componente completato.'], 403);
        }

        $entityComponentHasGene->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Display JSON data for Rules Datatable.
     */
    public function rulesDataTable(Request $request, EntityComponent $entityComponent)
    {
        $query = EntityComponentHasRuleChimicalElement::where('entity_component_id', $entityComponent->id)
            ->with('ruleChimicalElement');

        return datatables($query)
            ->addColumn('rule_name', function ($row) {
                return $row->ruleChimicalElement ? $row->ruleChimicalElement->name : '';
            })
            ->addColumn('rule_title', function ($row) {
                return $row->ruleChimicalElement ? $row->ruleChimicalElement->title : '';
            })
            ->rawColumns([])
            ->toJson();
    }

    /**
     * Get available rules for adding.
     */
    public function getAvailableRules(Request $request, EntityComponent $entityComponent)
    {
        $alreadyAssociatedIds = EntityComponentHasRuleChimicalElement::where('entity_component_id', $entityComponent->id)
            ->pluck('rule_chimical_element_id')
            ->toArray();

        $rules = RuleChimicalElement::where('type', RuleChimicalElement::TYPE_ENTITY)
            ->whereNotIn('id', $alreadyAssociatedIds)
            ->orderBy('name')
            ->get();

        return response()->json($rules);
    }

    /**
     * Store new rule association.
     */
    public function storeRule(Request $request, EntityComponent $entityComponent)
    {
        if ($entityComponent->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Non è possibile modificare un componente completato.'], 403);
        }

        $request->validate([
            'rule_chimical_element_id' => 'required|exists:rule_chimical_elements,id',
        ]);

        $rule = RuleChimicalElement::findOrFail($request->rule_chimical_element_id);
        if ($rule->type !== RuleChimicalElement::TYPE_ENTITY) {
            return response()->json(['success' => false, 'message' => 'È possibile selezionare solo regole di tipo entity.'], 422);
        }

        $exists = EntityComponentHasRuleChimicalElement::where('entity_component_id', $entityComponent->id)
            ->where('rule_chimical_element_id', $request->rule_chimical_element_id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Questa regola è già associata al componente.'], 422);
        }

        EntityComponentHasRuleChimicalElement::create([
            'entity_component_id' => $entityComponent->id,
            'rule_chimical_element_id' => $request->rule_chimical_element_id,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Destroy rule association.
     */
    public function destroyRule(Request $request, EntityComponentHasRuleChimicalElement $entityComponentHasRule)
    {
        $entityComponent = $entityComponentHasRule->entityComponent;
        if ($entityComponent->isFinished()) {
            return response()->json(['success' => false, 'message' => 'Non è possibile modificare un componente completato.'], 403);
        }

        $entityComponentHasRule->delete();

        return response()->json(['success' => true]);
    }
}
