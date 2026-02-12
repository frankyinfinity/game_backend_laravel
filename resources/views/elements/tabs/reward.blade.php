<div class="card-body">
    <div class="alert alert-info alert-dismissible">
        <h5><i class="icon fas fa-info"></i> Ricompensa Uscita!</h5>
        <p>Definisci i punteggi che il giocatore riceve quando elimina questo elemento.</p>
    </div>

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
</div>
