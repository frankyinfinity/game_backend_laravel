@php
    $rewardGroups = collect($groupScores ?? [])->map(function ($group) use ($allScores, $element) {
        return [
            'key' => (string) $group->id,
            'name' => $group->name,
            'options' => $allScores->filter(fn ($s) => (int) $s->group_score_id === (int) $group->id)->values(),
            'rewards' => $element->scores->filter(fn ($s) => (int) $s->group_score_id === (int) $group->id)->values(),
        ];
    });
    $rewardGroups = $rewardGroups->values();
@endphp

@if($element->isCompleted())
<div class="card-body">
    <div class="alert alert-success mb-3"><i class="fas fa-lock mr-1"></i> Elemento completato. Ricompense bloccate.</div>
    @foreach($rewardGroups as $rg)
        <div class="reward-group-box border rounded p-3 mb-3">
            <h5 class="mt-0 mb-2"><i class="fas fa-layer-group mr-1"></i> {{ $rg['name'] }}</h5>
            <table class="table table-bordered table-sm mb-0">
                <thead><tr><th style="width:60px;">Icona</th><th>Punteggio</th><th>Quantità</th></tr></thead>
                <tbody>
                    @forelse($rg['rewards'] as $score)
                    <tr>
                        <td>
                            @if($score->image)
                                <img src="{{ asset('storage/scores/'.$score->image) }}" style="width:32px;height:32px;image-rendering:pixelated;border:1px solid #ccc;">
                            @else
                                <i class="fas fa-image text-muted" style="font-size:24px;"></i>
                            @endif
                        </td>
                        <td>{{ $score->name }}</td>
                        <td>{{ $score->pivot->amount }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-muted text-center">Nessuna ricompensa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@else
<div class="card-body">
    <div class="alert alert-info alert-dismissible">
        <h5><i class="icon fas fa-info"></i> Ricompensa Uscita!</h5>
        <p>Definisci i punteggi che il giocatore riceve quando elimina questo elemento.</p>
    </div>

    <script>
        (function() {
            const rewardOptionsByGroup = @json($rewardGroups->mapWithKeys(fn ($g) => [$g['key'] => $g['options']->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'image' => $s->image ? asset('storage/scores/'.$s->image) : null])])->all());
            let fallbackRewardIndex = {{ $element->scores->count() }};
            const rewardAddUrl = @json(route('elements.reward-scores.store', $element));
            const rewardRemoveUrl = @json(route('elements.reward-scores.remove', $element));
            const csrfToken = @json(csrf_token());

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

            function iconHtml(option) {
                if (option && option.image) {
                    return '<img src="' + option.image + '" style="width:32px;height:32px;image-rendering:pixelated;border:1px solid #ccc;">';
                }
                return '<i class="fas fa-image text-muted" style="font-size:24px;"></i>';
            }

            document.addEventListener('click', function (event) {
                var target = event.target;
                var addRewardButton = target.closest ? target.closest('.add-reward-row') : null;
                if (addRewardButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    var groupKey = addRewardButton.getAttribute('data-group');
                    var select = document.getElementById('rewardModalScoreSelect');
                    if (!select) return;
                    var groupOptions = rewardOptionsByGroup[groupKey] || [];
                    select.innerHTML = optionsHtml(groupOptions, 'Seleziona Punteggio');
                    select.setAttribute('data-group', groupKey);
                    var amountInput = document.getElementById('rewardModalAmount');
                    if (amountInput) amountInput.value = '1';
                    if (window.jQuery) jQuery('#addRewardModal').modal('show');
                    return;
                }

                var saveRewardButton = target.closest ? target.closest('#rewardModalSave') : null;
                if (saveRewardButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    var modalSelect = document.getElementById('rewardModalScoreSelect');
                    var modalAmount = document.getElementById('rewardModalAmount');
                    if (!modalSelect || !modalSelect.value) { alert('Seleziona un punteggio.'); return; }
                    var scoreId = modalSelect.value;
                    var amount = modalAmount ? modalAmount.value : '1';
                    var gKey = modalSelect.getAttribute('data-group');
                    var opt = (rewardOptionsByGroup[gKey] || []).find(function (o) { return String(o.id) === String(scoreId); });
                    if (!window.jQuery) return;
                    jQuery.post(rewardAddUrl, { score_id: scoreId, amount: amount, _token: csrfToken })
                        .done(function (res) {
                            if (!res || res.success !== true) { alert('Errore durante il salvataggio.'); return; }
                            var tbody = document.querySelector('.reward-group-table[data-group="' + gKey + '"] tbody');
                            if (tbody) {
                                tbody.insertAdjacentHTML('beforeend',
                                    '<tr class="reward-row" data-score-id="' + scoreId + '">' +
                                    '<td>' + iconHtml(opt) + '</td>' +
                                    '<td>' + escapeHtml(opt ? opt.name : ('Punteggio #' + scoreId)) +
                                        '<input type="hidden" name="reward_scores[' + fallbackRewardIndex + '][score_id]" value="' + scoreId + '"></td>' +
                                    '<td>' + escapeHtml(String(amount)) +
                                        '<input type="hidden" name="reward_scores[' + fallbackRewardIndex + '][amount]" value="' + escapeHtml(String(amount)) + '"></td>' +
                                    '<td><button type="button" class="btn btn-danger btn-sm remove-reward-row"><i class="fa fa-trash"></i></button></td>' +
                                    '</tr>');
                                fallbackRewardIndex++;
                            }
                            jQuery('#addRewardModal').modal('hide');
                        })
                        .fail(function () { alert('Errore durante il salvataggio.'); });
                    return;
                }

                var removeRewardButton = target.closest ? target.closest('.remove-reward-row') : null;
                if (removeRewardButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    var row = removeRewardButton.closest('tr');
                    if (!row) return;
                    var scoreId = row.getAttribute('data-score-id');
                    if (scoreId && window.jQuery) {
                        jQuery.post(rewardRemoveUrl, { score_id: scoreId, _token: csrfToken })
                            .done(function () { row.remove(); })
                            .fail(function () { alert('Errore durante la rimozione.'); });
                    } else {
                        row.remove();
                    }
                }
            });
        })();
    </script>

    @php $rowIndex = 0; @endphp
    @foreach($rewardGroups as $rg)
        <div class="reward-group-box border rounded p-3 mb-3">
            <h5 class="mt-0 mb-2"><i class="fas fa-layer-group mr-1"></i> {{ $rg['name'] }}</h5>
            <table class="table table-bordered reward-group-table mb-0" data-group="{{ $rg['key'] }}">
            <thead>
                <tr>
                    <th style="width: 60px;">Icona</th>
                    <th style="width: 35%;">Tipo Punteggio</th>
                    <th style="width: 35%;">Quantità</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rg['rewards'] as $score)
                    <tr class="reward-row" data-score-id="{{ $score->id }}">
                        <td>
                            @if($score->image)
                                <img src="{{ asset('storage/scores/'.$score->image) }}" style="width:32px;height:32px;image-rendering:pixelated;border:1px solid #ccc;">
                            @else
                                <i class="fas fa-image text-muted" style="font-size:24px;"></i>
                            @endif
                        </td>
                        <td>
                            <select name="reward_scores[{{ $rowIndex }}][score_id]" class="form-control reward-selector" required>
                                <option value="">Seleziona Punteggio</option>
                                @foreach($rg['options'] as $s)
                                    <option value="{{ $s->id }}" {{ $score->id == $s->id ? 'selected' : '' }}>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="reward_scores[{{ $rowIndex }}][amount]" class="form-control" required placeholder="Quantità" value="{{ $score->pivot->amount }}">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-reward-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                    @php $rowIndex++; @endphp
                @endforeach
            </tbody>
            </table>
            <button type="button" class="btn btn-success btn-sm mt-2 add-reward-row" data-group="{{ $rg['key'] }}">
                <i class="fa fa-plus"></i> Aggiungi Ricompensa
            </button>
        </div>
    @endforeach

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
    var rows = document.querySelectorAll('.reward-group-table .reward-row');
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

<!-- Modal aggiunta ricompensa -->
<div class="modal fade" id="addRewardModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> Aggiungi Ricompensa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Chiudi">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="rewardModalScoreSelect">Punteggio</label>
                    <select id="rewardModalScoreSelect" class="form-control"></select>
                </div>
                <div class="form-group">
                    <label for="rewardModalAmount">Quantità</label>
                    <input type="number" id="rewardModalAmount" class="form-control" value="1" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success" id="rewardModalSave"><i class="fa fa-save"></i> Salva</button>
            </div>
        </div>
    </div>
</div>
@endif
