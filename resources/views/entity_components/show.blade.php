@extends('adminlte::page')

@section('title', 'Dettaglio Componente Entity')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-dark font-weight-bold">Dettaglio Componente Entity</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 col-12 mb-4">
                <table class="table table-bordered bg-light">
                    <tr>
                        <th style="width: 30%" class="text-dark font-weight-bold">ID</th>
                        <td>{{ $entityComponent->id }}</td>
                    </tr>
                    <tr>
                        <th class="text-dark font-weight-bold">Nome</th>
                        <td>{{ $entityComponent->name }}</td>
                    </tr>
                    <tr>
                        <th class="text-dark font-weight-bold">Stato</th>
                        <td>
                            @if($entityComponent->isFinished())
                                <span class="badge badge-success"><i class="fas fa-lock"></i> Completato</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-edit"></i> Creato</span>
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="mt-4">
                    <a href="{{ route('entity-components.index') }}" class="btn btn-secondary shadow-sm mr-2">
                        <i class="fas fa-backward"></i> Indietro
                    </a>
                    
                    @if($entityComponent->isCreated())
                        <a href="{{ route('entity-components.edit', $entityComponent) }}" class="btn btn-primary shadow-sm mr-2">
                            <i class="fas fa-edit"></i> Modifica
                        </a>
                        
                        @if(!$entityComponent->image || !\Storage::disk('entity_components')->exists($entityComponent->image))
                            <button type="button" class="btn btn-success shadow-sm disabled-state-btn" data-toggle="tooltip" title="Disegna la grafica per poter completare il componente">
                                <i class="fas fa-check-circle"></i> Completa e Blocca
                            </button>
                        @else
                            <form action="{{ route('entity-components.toggle-state', $entityComponent) }}" method="POST" style="display:inline-block;" class="m-0 js-confirm-complete">
                                @csrf
                                <button type="submit" class="btn btn-success shadow-sm">
                                    <i class="fas fa-check-circle"></i> Completa e Blocca
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div class="col-md-6 col-12 text-center border-left">
                <h5 class="text-muted font-weight-bold mb-3">Grafica Componente</h5>
                @if($entityComponent->image && \Storage::disk('entity_components')->exists($entityComponent->image))
                    <img src="{{ asset('storage/entity_components/' . $entityComponent->image) }}?v={{ time() }}" style="width: 200px; height: 200px; image-rendering: pixelated; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px;">
                @else
                    <div class="d-inline-flex align-items-center justify-content-center border rounded bg-light" style="width: 200px; height: 200px; border-style: dashed !important;">
                        <i class="fas fa-image fa-4x text-muted"></i>
                    </div>
                    <p class="text-muted mt-3">Nessuna grafica generata per questo componente.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $(document).on('click', '.disabled-state-btn', function(e) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
                } else {
                    alert('Non è possibile impostare lo stato su "Completato" senza prima aver generato la grafica del componente.');
                }
            });

            $(document).on('submit', '.js-confirm-complete', function(e) {
                if (!confirm("Sei sicuro di voler completare e bloccare questo componente? Questa azione è irreversibile.")) {
                    e.preventDefault();
                }
            });
        });
    </script>
@stop
