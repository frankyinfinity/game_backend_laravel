@extends('adminlte::page')

@section('title', 'Dettagli Tipologia Componente')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-secondary shadow-sm">
    <div class="card-header pb-0">
        <h4 class="mb-0 text-dark font-weight-bold">Dettagli Tipologia Componente</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 col-12 mb-3">
                <h5 class="text-muted font-weight-bold">ID</h5>
                <p class="text-dark">{{ $elementTypeComponent->id }}</p>
            </div>

            <div class="col-md-6 col-12 mb-3">
                <h5 class="text-muted font-weight-bold">Nome</h5>
                <p class="text-dark">{{ $elementTypeComponent->name }}</p>
            </div>

            <div class="col-md-6 col-12 mb-3">
                <h5 class="text-muted font-weight-bold">Simbolo</h5>
                <p class="text-dark">
                    <i class="{{ $elementTypeComponent->symbol }} fa-fw fa-2x text-dark"></i>
                    <code class="ml-2">{{ $elementTypeComponent->symbol }}</code>
                </p>
            </div>

            <div class="col-12 mt-4 border-top pt-3">
                <div class="row">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('element-type-components.edit', $elementTypeComponent) }}" class="btn btn-primary btn-block btn-sm shadow-sm">
                            <i class="fa fa-edit"></i> Modifica
                        </a>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('element-type-components.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
                            <i class="fa fa-backward"></i> Indietro
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
