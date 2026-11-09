@extends('adminlte::page')

@section('title', 'Modifica Cervello — Element #' . $elementHasPosition->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-brain text-danger"></i>
            Cervello — Element #{{ $elementHasPosition->id }}
        </h1>
        <a href="{{ route('containers.show', $elementHasPosition->player_id) }}" class="btn btn-danger btn-sm">
            <i class="fa fa-backward"></i> Indietro
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                Element: <strong>{{ optional($elementHasPosition->element)->name ?? '-' }}</strong>
                <small class="text-muted ml-2">
                    Tile ({{ $elementHasPosition->tile_i }}, {{ $elementHasPosition->tile_j }}) ·
                    UID {{ $elementHasPosition->uid }}
                </small>
            </h3>
        </div>
        <div class="card-body">
            @include('shared.brain_editor', [
                'model'                       => $elementHasPosition,
                'idPrefix'                    => 'ehp',
                'routeBase'                   => url('/element-has-positions/' . $elementHasPosition->id . '/brain'),
                'brainLocked'                 => false,
                'circuitToggleEnabled'        => false,
                'existingNeuronItems'         => $existingNeuronItems,
                'existingNeuronLinks'         => $existingNeuronLinks,
                'existingCircuits'            => $existingCircuits,
                'brainTargetElements'         => $brainTargetElements,
                'brainGenes'                  => $brainGenes,
                'brainChimicalElements'       => $brainChimicalElements,
                'brainComplexChimicalElements'=> $brainComplexChimicalElements,
                'allRuleChimicalElements'     => $allRuleChimicalElements,
                'informationGenes'            => $informationGenes,
            ])
        </div>
    </div>
@stop
