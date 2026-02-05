<div class="alert alert-info">Definisci le informazioni associate a questo elemento interattivo.</div>

<script>
    // Gene data for quick lookup (prepared in controller)
    const geneData = @json($geneData);
    
    // Convert to object for easy access by id
    const geneMap = {};
    geneData.forEach(gene => {
        geneMap[gene.id] = gene;
    });
</script>

<table class="table table-bordered table-striped" id="information_table">
    <thead>
        <tr>
            <th>Gene</th>
            <th>Valore Minimo</th>
            <th>Valore Massimo Da</th>
            <th>Valore Massimo A</th>
            <th>Valore Attuale</th>
            <th style="width: 50px;"></th>
        </tr>
    </thead>
    <tbody>
        @foreach($element->informations as $index => $info)
            <tr class="information-row">
                <td>
                    <select name="information_genes[{{$index}}][gene_id]" class="form-control information-selector" required>
                        @foreach($allGenes as $g)
                            <option value="{{$g->id}}" {{$info->gene_id == $g->id ? 'selected' : ''}}>{{$g->name}}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" name="information_genes[{{$index}}][min_value]" value="{{$info->min_value}}" class="form-control" required placeholder="Valore Minimo" readonly style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;">
                </td>
                <td>
                    <input type="number" name="information_genes[{{$index}}][max_from]" value="{{$info->max_from ?? 0}}" class="form-control" required placeholder="Valore Massimo Da" readonly style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;">
                </td>
                <td>
                    <input type="number" name="information_genes[{{$index}}][max_to]" value="{{$info->max_to ?? 0}}" class="form-control" required placeholder="Valore Massimo A" readonly style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;">
                </td>
                <td>
                    <input type="number" name="information_genes[{{$index}}][value]" value="{{$info->value}}" class="form-control" required placeholder="Valore Attuale">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-information-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<button type="button" class="btn btn-success btn-sm mt-2" id="add-information-row"><i class="fa fa-plus"></i> Aggiungi Informazione</button>
