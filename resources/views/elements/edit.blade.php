@extends('adminlte::page')

@section('title', 'Modifica Elemento')

@section('content_header')
    <h1>Modifica Elemento</h1>
@stop

@section('content')
    <form action="{{ route('elements.update', $element) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="main-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-general-link" data-toggle="pill" href="#tab-general" role="tab" aria-controls="tab-general" aria-selected="true">Dati Generali</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-diffusion-link" data-toggle="pill" href="#tab-diffusion" role="tab" aria-controls="tab-diffusion" aria-selected="false">Diffusione</a>
                    </li>
                    @if($element->consumable)
                    <li class="nav-item">
                        <a class="nav-link" id="tab-consumption-link" data-toggle="pill" href="#tab-consumption" role="tab" aria-controls="tab-consumption" aria-selected="false">Effetti Consumo</a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" id="tab-graphics-link" data-toggle="pill" href="#tab-graphics" role="tab" aria-controls="tab-graphics" aria-selected="false">Grafica</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="main-tabs-content">
                    
                    <!-- TAB DATI GENERALI -->
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general-link">
                        @include('elements.tabs.general')
                    </div>

                    <!-- TAB DIFFUSIONE -->
                    <div class="tab-pane fade" id="tab-diffusion" role="tabpanel" aria-labelledby="tab-diffusion-link">
                        @include('elements.tabs.diffusion')
                    </div>

                    <!-- TAB CONSUMPTION -->
                    @if($element->consumable)
                    <div class="tab-pane fade" id="tab-consumption" role="tabpanel" aria-labelledby="tab-consumption-link">
                        @include('elements.tabs.consumption')
                    </div>
                    @endif

                    <!-- TAB GRAPHICS -->
                    <div class="tab-pane fade" id="tab-graphics" role="tabpanel" aria-labelledby="tab-graphics-link">
                        @include('elements.tabs.graphics')
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

@section('js')
    <script>
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();

            // Gene Rows Management
            let geneIndex = {{ $element->genes->count() }};
            
            function updateGeneOptions() {
                // Raccogli tutti i valori selezionati
                let selectedValues = [];
                $('.gene-selector').each(function() {
                    let val = $(this).val();
                    if (val) {
                        selectedValues.push(val);
                    }
                });

                // Aggiorna ogni select
                $('.gene-selector').each(function() {
                    let currentSelect = $(this);
                    let currentValue = currentSelect.val();
                    
                    currentSelect.find('option').each(function() {
                        let option = $(this);
                        let optionValue = option.val();
                        
                        // Disabilita se è selezionato altrove (non in questa select)
                        if (selectedValues.includes(optionValue) && optionValue != currentValue) {
                            option.prop('disabled', true);
                        } else {
                            option.prop('disabled', false);
                        }
                    });
                });
            }

            // Bind change event
            $(document).on('change', '.gene-selector', function() {
                updateGeneOptions();
            });

            $('#add-gene-row').click(function() {
                let html = `
                    <tr class="gene-row">
                        <td>
                            <select name="consumption_genes[${geneIndex}][gene_id]" class="form-control gene-selector" required>
                                <option value="">Seleziona Gene</option>
                                @foreach($allGenes as $g)
                                    <option value="{{$g->id}}">{{$g->name}}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="consumption_genes[${geneIndex}][effect]" class="form-control" required placeholder="Es. 10 o -5">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-gene-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#genes_table tbody').append(html);
                geneIndex++;
                updateGeneOptions();
            });
            
            $(document).on('click', '.remove-gene-row', function() {
                $(this).closest('tr').remove();
                updateGeneOptions();
            });

            // Initial call
            updateGeneOptions();
        })
    </script>
@stop
