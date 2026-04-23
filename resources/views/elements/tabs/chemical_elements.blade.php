<div class="row">
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Regole Elementi Chimici</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal"
                        data-target="#modal-add-rule">
                        <i class="fas fa-plus"></i> Aggiungi Regola
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="alert alert-info border-0 rounded-0 m-0">
                    <i class="fas fa-info-circle mr-1"></i>
                    Ricorda di premere il pulsante <strong>Aggiorna</strong> in fondo alla pagina per salvare le modifiche apportate.
                </div>
                <table class="table table-striped" id="rules_table">
                    <thead>
                        <tr>
                            <th>Regola Chimica</th>
                            <th style="width: 100px">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($element->ruleChimicalElements as $index => $rule)
                            <tr class="rule-row">
                                <td>
                                    <input type="hidden"
                                        name="rule_chimical_elements[{{ $index }}][rule_chimical_element_id]"
                                        value="{{ $rule->id }}" class="rule-id-input">
                                    {{ $rule->title }}
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-rule-row">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal per Aggiunta Regola -->
<div class="modal fade" id="modal-add-rule" tabindex="-1" role="dialog" aria-labelledby="modal-add-rule-label"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-rule-label">Aggiungi Regola Elemento Chimico</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="modal-rule-selector">Seleziona Regola</label>
                    <select id="modal-rule-selector" class="form-control select2" style="width: 100%;">
                        <option value="">-- Seleziona --</option>
                        @foreach($allRuleChimicalElements as $r)
                            <option value="{{ $r->id }}" data-full-name="{{ $r->title }}">
                                {{ $r->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-primary" id="confirm-add-rule">Aggiungi</button>
            </div>
        </div>
    </div>
</div>