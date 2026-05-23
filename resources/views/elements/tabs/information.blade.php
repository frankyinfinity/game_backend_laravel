<div class="alert alert-info">Definisci le informazioni associate a questo elemento interattivo.</div>

<script>
    (function() {
        const fallbackGeneOptions = @json($allGenes->map(fn ($gene) => ['id' => $gene->id, 'name' => $gene->name])->values());
        let fallbackInformationIndex = {{ $element->informations->count() }};

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }

        function optionsHtml(options, placeholder) {
            return '<option value="">' + placeholder + '</option>' + options.map(function (option) {
                return '<option value="' + option.id + '">' + escapeHtml(option.name) + '</option>';
            }).join('');
        }

        function syncInformationRow(row) {
            if (!row) return;
            const valueInput = row.querySelector('input[name$="[value]"]');
            const value = valueInput ? valueInput.value : '';
            const minInput = row.querySelector('input[name$="[min_value]"]');
            const maxFromInput = row.querySelector('input[name$="[max_from]"]');
            const maxToInput = row.querySelector('input[name$="[max_to]"]');
            const maxValueInput = row.querySelector('input[name$="[max_value]"]');

            if (minInput) minInput.value = 1;
            if (maxFromInput) maxFromInput.value = value;
            if (maxToInput) maxToInput.value = value;
            if (maxValueInput) maxValueInput.value = value;
        }

        document.addEventListener('input', function (event) {
            if (event.target.classList.contains('information-value')) {
                syncInformationRow(event.target.closest('tr'));
            }
        });

        document.addEventListener('click', function (event) {
            var target = event.target;
            var addInformationButton = target.closest ? target.closest('#add-information-row') : null;
            if (addInformationButton) {
                event.preventDefault();
                event.stopPropagation();
                var tbody = document.querySelector('#information_table tbody');
                if (!tbody) return;

                tbody.insertAdjacentHTML('beforeend', 
                    '<tr class="information-row">' +
                    '<td>' +
                    '<select name="information_genes[' + fallbackInformationIndex + '][gene_id]" class="form-control information-selector" required>' +
                    optionsHtml(fallbackGeneOptions, 'Seleziona Gene') +
                    '</select>' +
                    '</td>' +
                    '<td>' +
                    '<input type="number" name="information_genes[' + fallbackInformationIndex + '][min_value]" value="1" class="form-control" required readonly style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;">' +
                    '</td>' +
                    '<td>' +
                    '<input type="number" name="information_genes[' + fallbackInformationIndex + '][value]" class="form-control information-value" required min="1" placeholder="Valore">' +
                    '<input type="hidden" name="information_genes[' + fallbackInformationIndex + '][max_from]" class="information-max-from">' +
                    '<input type="hidden" name="information_genes[' + fallbackInformationIndex + '][max_to]" class="information-max-to">' +
                    '<input type="hidden" name="information_genes[' + fallbackInformationIndex + '][max_value]" class="information-max-value">' +
                    '</td>' +
                    '<td>' +
                    '<button type="button" class="btn btn-danger btn-sm remove-information-row"><i class="fa fa-trash"></i></button>' +
                    '</td>' +
                    '</tr>'
                );
                fallbackInformationIndex++;
                return;
            }

            var removeInformationButton = target.closest ? target.closest('.remove-information-row') : null;
            if (removeInformationButton) {
                event.preventDefault();
                event.stopPropagation();
                var row = removeInformationButton.closest('tr');
                if (row) row.remove();
            }
        });

        var informationRows = document.querySelectorAll('.information-row');
        for (var i = 0; i < informationRows.length; i++) {
            syncInformationRow(informationRows[i]);
        }
    })();
</script>

<table class="table table-bordered table-striped" id="information_table">
    <thead>
        <tr>
            <th>Gene</th>
            <th>Valore Minimo</th>
            <th>Valore</th>
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
                    <input type="number" name="information_genes[{{$index}}][min_value]" value="1" class="form-control" required readonly style="background-color: #f4f6f9; color: #6c757d; border-style: dashed;">
                </td>
                <td>
                    <input type="number" name="information_genes[{{$index}}][value]" value="{{$info->value}}" class="form-control information-value" required min="1" placeholder="Valore">
                    <input type="hidden" name="information_genes[{{$index}}][max_from]" value="{{$info->value}}" class="information-max-from">
                    <input type="hidden" name="information_genes[{{$index}}][max_to]" value="{{$info->value}}" class="information-max-to">
                    <input type="hidden" name="information_genes[{{$index}}][max_value]" value="{{$info->value}}" class="information-max-value">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-information-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<button type="button" class="btn btn-success btn-sm mt-2" id="add-information-row"><i class="fa fa-plus"></i> Aggiungi Informazione</button>
