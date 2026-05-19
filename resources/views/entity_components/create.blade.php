@extends('adminlte::page')

@section('title', 'Nuovo Componente Entity')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0">
        <h4 class="mb-0 text-dark font-weight-bold">Nuovo Componente Entity</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('entity-components.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 col-12">
                    <label for="name" class="text-dark font-weight-bold">Nome Componente*</label>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-puzzle-piece text-primary"></i></span>
                        </div>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Inserisci il nome del componente..." value="{{ old('name') }}" required>
                    </div>
                </div>
                
                <div class="col-12 mt-4 border-top pt-3">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <button type="submit" class="btn btn-primary btn-block btn-sm shadow-sm">
                                <i class="fa fa-save"></i> Salva
                            </button>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <a href="{{ route('entity-components.index') }}" class="btn btn-danger btn-block btn-sm shadow-sm">
                                <i class="fa fa-backward"></i> Indietro
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@stop
