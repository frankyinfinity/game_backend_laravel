@if($element->climates->isEmpty())
<div class="alert alert-warning">
    <i class="icon fas fa-exclamation-triangle"></i> Nessun clima associato.
    Seleziona e salva dei climi nel tab "Dati Generali" per configurare la diffusione.
</div>
@else
<div class="row">
    <div class="col-3">
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
            @foreach($element->climates as $index => $climate)
                <a class="nav-link {{ $index === 0 ? 'active' : '' }}" 
                    id="v-pills-{{ $climate->id }}-tab" 
                    data-toggle="pill" 
                    href="#v-pills-{{ $climate->id }}" 
                    role="tab" 
                    aria-controls="v-pills-{{ $climate->id }}" 
                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                    {{ $climate->name }}
                </a>
            @endforeach
        </div>
    </div>
    <div class="col-9">
        <div class="tab-content" id="v-pills-tabContent">
            @foreach($element->climates as $index => $climate)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" 
                        id="v-pills-{{ $climate->id }}" 
                        role="tabpanel" 
                        aria-labelledby="v-pills-{{ $climate->id }}-tab">
                        
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-hover head-fixed">
                            <thead>
                                <tr>
                                    <th>Tile</th>
                                    <th style="width: 150px;">
                                        Diffusione (0-100%)
                                        <i class="fas fa-info-circle text-muted ml-1" title="Questo indica la percentuale in cui un elemento può apparire in quel tile in quel clima" data-toggle="tooltip" style="cursor: help;"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allTiles as $tile)
                                    @php
                                        $val = $diffusionMap[$climate->id][$tile->id] ?? 0;
                                    @endphp
                                    <tr>
                                        <td class="align-middle">
                                            <div style="display:flex; align-items:center;">
                                                <span style="display:inline-block; width: 24px; height: 24px; background-color: {{ $tile->color }}; margin-right: 10px; border:1px solid #ddd; border-radius: 3px;"></span>
                                                {{ $tile->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                    name="diffusion[{{ $climate->id }}][{{ $tile->id }}]" 
                                                    value="{{ $val }}" 
                                                    min="0" 
                                                    max="100" 
                                                    class="form-control">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
