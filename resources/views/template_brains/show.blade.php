@extends('adminlte::page')

@section('title', 'Modifica Template Cervello')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Modifica Template Cervello</h5>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="templateBrainTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tb-data-tab" data-toggle="tab" href="#tb-data" role="tab">Dati</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tb-brain-tab" data-toggle="tab" href="#tb-brain" role="tab"><i class="fas fa-brain"></i> Cervello</a>
            </li>
        </ul>
        <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tb-data" role="tabpanel">
                <form method="POST" action="{{ route('template-brains.update', [$templateBrain->id]) }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-6">
                            <label for="name">Nome*</label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-font"></i></span>
                                </div>
                                <input type="text" id="name" name="name" value="{{$templateBrain->name}}" class="form-control" placeholder="Nome" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <div class="col-3">
                                    <button type="submit" class="btn btn-primary btn-block btn-sm"><i class="fa fa-save"></i> Salva</button>
                                </div>
                                <div class="col-3">
                                    <a href="{{route('template-brains.index')}}">
                                        <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="tb-brain" role="tabpanel">
                @include('shared.brain_editor', [
                    'model'               => $templateBrain,
                    'idPrefix'            => 'tb',
                    'routeBase'           => url('/template-brains/' . $templateBrain->id . '/brain'),
                    'brainLocked'         => false,
                    'existingNeuronItems' => $existingNeuronItems ?? [],
                    'existingNeuronLinks' => $existingNeuronLinks ?? [],
                    'existingCircuits'    => $existingCircuits ?? [],
                ])
            </div>
        </div>
    </div>
</div>
@stop
