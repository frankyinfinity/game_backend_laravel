@extends('adminlte::page')

@section('title', 'Modifica Elemento')

@section('content_header')
<h1>Modifica Elemento</h1>
@stop

@section('content')
<form action="{{ route('elements.update', $element) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card card-primary card-outline shadow-sm">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab"
                        aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="main-tabs-content">

                <!-- TAB DATI GENERALI -->
                <div class="tab-pane fade show active" id="tab-general" role="tabpanel"
                    aria-labelledby="tab-general-link">
                    @include('elements.tabs.general')
                </div>

            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Aggiorna
            </button>
            <a href="{{ route('elements.index') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Annulla
            </a>
        </div>
    </div>
</form>
@stop
