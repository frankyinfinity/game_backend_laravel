@extends('adminlte::page')

@section('title', 'Stella')

@section('content_header')@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header pb-0">
        <h4 class="mb-0">Stella</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <label for="name">Nome</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-font"></i></span>
                    </div>
                    <input type="text" id="name" name="name" class="form-control" value="{{$star->name}}" placeholder="Nome" disabled>
                </div>
            </div>
            <div class="col-12">
                <label for="rgb">RGB</label>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-palette"></i></span>
                    </div>
                    <input type="text" id="rgb" name="rgb" class="form-control" value="{{$star->rgb}}" disabled>
                    <div class="input-group-append">
                        <span class="input-group-text" style="width:42px;background-color:rgb({{$star->rgb}})"></span>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label for="description">Descrizione</label>
                <div class="form-group">
                    <textarea class="form-control" rows="4" id="description" name="description" disabled>{{$star->description}}</textarea>
                </div>
            </div>
            <div class="col-12">
                <div class="row">
                    <div class="col-4">
                        <a href="{{route('stars.edit', [$star->id])}}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-edit"></i> {{ $star->isZones() ? 'Fascie' : 'Modifica' }}</button>
                        </a>
                    </div>
                    @if(!$star->isZones())
                    <div class="col-4">
                        <form method="POST" action="{{ route('stars.state', [$star->id]) }}">
                            @csrf
                            <input type="hidden" name="state" value="{{ \App\Models\Star::STATE_ZONES }}">
                            <button type="submit" class="btn btn-teal btn-block btn-sm"><i class="fa fa-bullseye"></i> Gestisci Fascie</button>
                        </form>
                    </div>
                    @endif
                    <div class="col-4">
                        <a href="{{route('stars.index')}}">
                            <button type="button" class="btn btn-danger btn-block btn-sm"><i class="fa fa-backward"></i> Indietro</button>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    @parent
@stop
