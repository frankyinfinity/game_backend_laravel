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

                <script>
                    (function() {
                        let ruleIndex = {{ $element->ruleChimicalElements->count() }};

                        document.addEventListener('click', function (event) {
                            var target = event.target;
                            var removeRuleButton = target.closest ? target.closest('.remove-rule-row') : null;
                            if (removeRuleButton) {
                                event.preventDefault();
                                event.stopPropagation();
                                var row = removeRuleButton.closest('tr');
                                if (row) row.remove();
                                return;
                            }

                            var confirmAddButton = target.closest ? target.closest('#confirm-add-rule') : null;
                            if (confirmAddButton) {
                                event.preventDefault();
                                event.stopPropagation();
                                var selector = document.getElementById('modal-rule-selector');
                                if (!selector) return;
                                var ruleId = selector.value;
                                if (!ruleId) {
                                    alert('Seleziona una regola');
                                    return;
                                }
                                var selectedOption = selector.options[selector.selectedIndex];
                                var ruleName = selectedOption ? selectedOption.text : '';

                                var tbody = document.querySelector('#rules_table tbody');
                                if (!tbody) return;

                                var alreadyAdded = false;
                                var ruleIdInputs = document.querySelectorAll('.rule-id-input');
                                for (var i = 0; i < ruleIdInputs.length; i++) {
                                    if (ruleIdInputs[i].value == ruleId) {
                                        alreadyAdded = true;
                                        break;
                                    }
                                }

                                if (alreadyAdded) {
                                    alert('Questa regola è già stata aggiunta');
                                    return;
                                }

                                tbody.insertAdjacentHTML('beforeend',
                                    '<tr class="rule-row">' +
                                    '<td>' +
                                    '<input type="hidden" name="rule_chimical_elements[' + ruleIndex + '][rule_chimical_element_id]" value="' + ruleId + '" class="rule-id-input">' +
                                    ruleName +
                                    '</td>' +
                                    '<td>' +
                                    '<button type="button" class="btn btn-danger btn-sm remove-rule-row"><i class="fa fa-trash"></i></button>' +
                                    '</td>' +
                                    '</tr>'
                                );
                                ruleIndex++;

                                selector.value = '';
                                var modal = document.getElementById('modal-add-rule');
                                if (modal) {
                                    modal.classList.remove('show');
                                    modal.style.display = 'none';
                                    var backdrop = document.querySelector('.modal-backdrop');
                                    if (backdrop) backdrop.remove();
                                    document.body.classList.remove('modal-open');
                                    document.body.style.overflow = '';
                                }
                            }
                        });
                    })();
                </script>

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