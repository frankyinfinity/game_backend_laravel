{{--
    Tab Cervello per ElementComponent.
    Delega tutto al componente generico shared.brain_editor.
    Le collezioni per le dropdown (brainGenes, brainTargetElements, ecc.)
    arrivano dal controller; se assenti il componente le carica da solo.
--}}

@include('shared.brain_editor', [
    'model'       => $elementComponent,
    'idPrefix'    => 'ec',
    'routeBase'   => url('/element-components/' . $elementComponent->id . '/brain'),
    'brainLocked' => $elementComponent->isCompleted(),
])
