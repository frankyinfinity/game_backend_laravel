<div class="alert alert-info">Definisci quali geni vengono modificati quando questo elemento viene consumato.</div>
<table class="table table-bordered table-striped" id="genes_table">
    <thead>
        <tr>
            <th>Gene</th>
            <th>Effetto (Valore Numerico)</th>
            <th style="width: 50px;"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($element->genes as $index => $gene)
            <tr class="gene-row">
                <td>
                    <select name="consumption_genes[{{$index}}][gene_id]" class="form-control gene-selector" required>
                        @foreach($allGenes as $g)
                            <option value="{{$g->id}}" {{$gene->id == $g->id ? 'selected' : ''}}>{{$g->name}}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="consumption_genes[{{$index}}][effect]" value="{{$gene->pivot->effect}}" class="form-control" required placeholder="Es. 10 o -5">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-gene-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<button type="button" class="btn btn-success btn-sm mt-2" id="add-gene-row"><i class="fa fa-plus"></i> Aggiungi Effetto</button>
