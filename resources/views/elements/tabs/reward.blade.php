@if($element->isCompleted())
<div class="card-body">
    <div class="alert alert-success mb-3"><i class="fas fa-lock mr-1"></i> Elemento completato. Ricompense bloccate.</div>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Punteggio</th><th>Quantità</th></tr></thead>
        <tbody>
            @forelse($element->scores as $score)
            <tr><td>{{ $score->name }}</td><td>{{ $score->pivot->amount }}</td></tr>
            @empty
            <tr><td colspan="2" class="text-muted text-center">Nessuna ricompensa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@else
<div class="card-body">
    <div class="alert alert-info alert-dismissible">
        <h5><i class="icon fas fa-info"></i> Ricompensa Uscita!</h5>
        <p>Definisci i punteggi che il giocatore riceve quando elimina questo elemento.</p>
    </div>

    <script>
        (function() {
            const fallbackRewardOptions = @json($allScores->map(fn ($score) => ['id' => $score->id, 'name' => $score->name])->values());
            let fallbackRewardIndex = {{ $element->scores->count() }};

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

            document.addEventListener('click', function (event) {
                var target = event.target;
                var addRewardButton = target.closest ? target.closest('#add-reward-row') : null;
                if (addRewardButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    var tbody = document.querySelector('#reward_table tbody');
                    if (!tbody) return;

                    tbody.insertAdjacentHTML('beforeend',
                        '<tr class="reward-row">' +
                        '<td>' +
                        '<select name="reward_scores[' + fallbackRewardIndex + '][score_id]" class="form-control reward-selector" required>' +
                        optionsHtml(fallbackRewardOptions, 'Seleziona Punteggio') +
                        '</select>' +
                        '</td>' +
                        '<td>' +
                        '<input type="number" name="reward_scores[' + fallbackRewardIndex + '][amount]" class="form-control" required placeholder="Quantità" value="1">' +
                        '</td>' +
                        '<td>' +
                        '<button type="button" class="btn btn-danger btn-sm remove-reward-row"><i class="fa fa-trash"></i></button>' +
                        '</td>' +
                        '</tr>'
                    );
                    fallbackRewardIndex++;
                    return;
                }

                var removeRewardButton = target.closest ? target.closest('.remove-reward-row') : null;
                if (removeRewardButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    var row = removeRewardButton.closest('tr');
                    if (row) row.remove();
                }
            });
        })();
    </script>

    <table class="table table-bordered" id="reward_table">
        <thead>
            <tr>
                <th style="width: 40%;">Tipo Punteggio</th>
                <th style="width: 40%;">Quantità</th>
                <th style="width: 20%;">Azioni</th>
            </tr>
        </thead>
        <tbody>
            @foreach($element->scores as $index => $score)
                <tr class="reward-row">
                    <td>
                        <select name="reward_scores[{{ $index }}][score_id]" class="form-control reward-selector" required>
                            <option value="">Seleziona Punteggio</option>
                            @foreach($allScores as $s)
                                <option value="{{ $s->id }}" {{ $score->id == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" name="reward_scores[{{ $index }}][amount]" class="form-control" required placeholder="Quantità" value="{{ $score->pivot->amount }}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-reward-row"><i class="fa fa-trash"></i></button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <button type="button" class="btn btn-success btn-sm mt-2" id="add-reward-row">
        <i class="fa fa-plus"></i> Aggiungi Ricompensa
    </button>

    <div class="mt-3 border-top pt-3">
        <button type="button" class="btn btn-success shadow-sm" onclick="submitRewardComplete();">
            <i class="fas fa-check-double mr-1"></i> Completa Elemento
        </button>
    </div>
</div>

<script>
window.submitRewardComplete = function() {
    if (!confirm('Completare l\'elemento? Irreversibile.')) return;
    var container = document.getElementById('el-reward-complete-inputs');
    if (!container) return;
    container.innerHTML = '';
    var rows = document.querySelectorAll('#reward_table .reward-row');
    rows.forEach(function(row, i) {
        var scoreSelect = row.querySelector('[name*="[score_id]"]');
        var amountInput = row.querySelector('[name*="[amount]"]');
        if (scoreSelect && scoreSelect.value) {
            container.innerHTML += '<input type="hidden" name="reward_scores[' + i + '][score_id]" value="' + scoreSelect.value + '">';
            container.innerHTML += '<input type="hidden" name="reward_scores[' + i + '][amount]" value="' + (amountInput ? amountInput.value : '1') + '">';
        }
    });
    document.getElementById('el-reward-complete-form').submit();
};
</script>
@endif
