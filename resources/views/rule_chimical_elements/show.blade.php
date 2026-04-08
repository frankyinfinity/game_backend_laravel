@extends('adminlte::page')

@section('title', 'Dettaglio Regola Elemento Chimico')

@section('content_header')
<h1>Dettaglio Regola Elemento Chimico</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            @if($ruleChimicalElement->chimicalElement)
                {{ $ruleChimicalElement->chimicalElement->name }} ({{ $ruleChimicalElement->chimicalElement->symbol }})
            @elseif($ruleChimicalElement->complexChimicalElement)
                {{ $ruleChimicalElement->complexChimicalElement->name }}
            @endif
        </h3>
        <div class="card-tools">
            <a href="{{ route('rule-chimical-elements.edit', $ruleChimicalElement->id) }}"
                class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Modifica
            </a>
            <a href="{{ route('rule-chimical-elements.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Indietro
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Grafico Lineare</h5>
                        <small class="text-muted">Clicca sul grafico per aggiungere un dettaglio</small>
                    </div>
                    <div class="card-body p-2">
                        <div id="linearChart" class="linear-chart-container"
                            style="position: relative; height: 80px; background: #fff; border-radius: 6px; cursor: crosshair; border: 2px solid #dee2e6;"
                            data-rule-id="{{ $ruleChimicalElement->id }}"
                            data-rule-min="{{ $ruleChimicalElement->min }}"
                            data-rule-max="{{ $ruleChimicalElement->max }}">
                            @php
                                $range = $ruleChimicalElement->max - $ruleChimicalElement->min;
                                if ($range <= 0)
                                    $range = 1;
                            @endphp
                            @forelse($ruleChimicalElement->details as $detail)
                                @php
                                    $leftPercent = (($detail->min - $ruleChimicalElement->min) / $range) * 100;
                                    $widthPercent = (($detail->max - $detail->min) / $range) * 100;
                                    if ($leftPercent < 0)
                                        $leftPercent = 0;
                                    if ($widthPercent < 0)
                                        $widthPercent = 0;
                                    if ($leftPercent + $widthPercent > 100)
                                        $widthPercent = 100 - $leftPercent;
                                @endphp
                                <div class="chart-segment" data-detail-id="{{ $detail->id }}"
                                    data-detail-min="{{ $detail->min }}" data-detail-max="{{ $detail->max }}"
                                    style="position: absolute; left: {{ $leftPercent }}%; width: {{ $widthPercent }}%; top: 5px; bottom: 5px; background: {{ $detail->color }}; opacity: 0.85; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                    <span class="segment-label"
                                        style="color: white; font-size: 11px; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.5); white-space: nowrap;">{{ $detail->min }}-{{ $detail->max }}</span>
                                    <div class="resize-handle resize-left" title="Trascina per ridimensionare"></div>
                                    <div class="resize-handle resize-right" title="Trascina per ridimensionare"></div>
                                </div>
                            @empty
                                <div
                                    class="text-center text-muted w-100 h-100 d-flex align-items-center justify-content-center">
                                    <span>Clicca per aggiungere il primo dettaglio</span>
                                </div>
                            @endforelse
                        </div>
                        <div class="mt-2 d-flex justify-content-between text-muted small">
                            <span><strong>Min:</strong> {{ $ruleChimicalElement->min }}</span>
                            <span class="chart-axis"></span>
                            <span><strong>Max:</strong> {{ $ruleChimicalElement->max }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Dettagli</h4>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-success" data-toggle="modal"
                                data-target="#addDetailModal" id="openAddModal">
                                <i class="fas fa-plus"></i> Aggiungi
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Min</th>
                                    <th>Max</th>
                                    <th>Colore</th>
                                    <th style="width: 130px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ruleChimicalElement->details as $detail)
                                    <tr>
                                        <td>{{ $detail->min }}</td>
                                        <td>{{ $detail->max }}</td>
                                        <td>
                                            <span
                                                style="display: inline-block; width: 24px; height: 24px; background: {{ $detail->color }}; border-radius: 4px; border: 1px solid #ddd; vertical-align: middle;"></span>
                                            <code>{{ $detail->color }}</code>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button"
                                                    class="btn btn-info btn-effects d-flex align-items-center"
                                                    data-detail-id="{{ $detail->id }}" data-detail-range="{{ $detail->min }}-{{ $detail->max }}" title="Gestisci Effetti">
                                                    <i class="fas fa-flask mr-1"></i> Effetti
                                                </button>
                                                <form
                                                    action="{{ route('rule-chimical-elements.detail.destroy', [$ruleChimicalElement->id, $detail->id]) }}"
                                                    method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger d-flex align-items-center"
                                                        onclick="return confirm('Elimina?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Nessun dettaglio inserito</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addDetailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('rule-chimical-elements.detail.store', $ruleChimicalElement->id) }}" method="POST"
            id="detailForm" class="ajax-form">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Aggiungi Dettaglio</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Range consentito:
                        <strong>{{ $ruleChimicalElement->min }}</strong> -
                        <strong>{{ $ruleChimicalElement->max }}</strong>
                    </div>
                    <div class="form-group">
                        <label for="detail_min">Min <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="detail_min" name="min"
                            value="{{ old('min', $ruleChimicalElement->min) }}" min="{{ $ruleChimicalElement->min }}"
                            max="{{ $ruleChimicalElement->max }}" required>
                    </div>
                    <div class="form-group">
                        <label for="detail_max">Max <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="detail_max" name="max"
                            value="{{ old('max', $ruleChimicalElement->max) }}" min="{{ $ruleChimicalElement->min }}"
                            max="{{ $ruleChimicalElement->max }}" required>
                    </div>
                    <div class="form-group">
                        <label for="detail_color">Colore <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="color" class="form-control" id="detail_color" name="color"
                                value="{{ old('color', '#3498db') }}" style="width: 60px; height: 38px; padding: 2px;">
                            <input type="text" class="form-control ml-2" id="detail_color_hex"
                                value="{{ old('color', '#3498db') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Salva</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="effectsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Effetti - Dettaglio <span id="effectsDetailRange"></span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-success btn-sm" id="addEffectBtn">
                        <i class="fas fa-plus"></i> Aggiungi Effetto
                    </button>
                </div>
                <table class="table table-bordered table-sm" id="effectsTable">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Gene</th>
                            <th>Valore</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody id="effectsTableBody">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addEffectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form id="effectForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aggiungi Effetto</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="effect_type">Tipo <span class="text-danger">*</span></label>
                        <select class="form-control" id="effect_type" name="type" required>
                            <option value="">Seleziona Tipo</option>
                            <option value="{{ App\Models\RuleChimicalElementDetailEffect::TYPE_FIXED }}">Fisso</option>
                            <option value="{{ App\Models\RuleChimicalElementDetailEffect::TYPE_TIMED }}">A tempo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="effect_gene_id">Gene <span class="text-danger">*</span></label>
                        <select class="form-control" id="effect_gene_id" name="gene_id" required>
                            <option value="">Seleziona Gene</option>
                            @foreach(\App\Models\Gene::all() as $gene)
                                <option value="{{ $gene->id }}">{{ $gene->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="effect_value">Valore <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="effect_value" name="value" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Salva</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                </div>
            </div>
        </form>
    </div>
</div>
@stop

@section('css')
<style>
    .resize-handle {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 12px;
        cursor: ew-resize;
        background: rgba(255, 255, 255, 0.3);
        z-index: 10;
    }

    .resize-handle:hover {
        background: rgba(255, 255, 255, 0.6);
    }

    .resize-left {
        left: 0;
        border-radius: 4px 0 0 4px;
    }

    .resize-right {
        right: 0;
        border-radius: 0 4px 4px 0;
    }
</style>
@stop

@section('js')
<script>
    document.getElementById('detail_color').addEventListener('input', function () {
        document.getElementById('detail_color_hex').value = this.value;
    });
    document.getElementById('detail_color_hex').addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            document.getElementById('detail_color').value = this.value;
        }
    });

    document.getElementById('detailForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var url = this.action;

        if (editDetailId) {
            formData.append('_method', 'POST');
            url = '/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/details/' + editDetailId;
        }

        fetch(url, {
            method: 'POST',
            body: formData
        }).then(function () {
            editDetailId = null;
            fetch('/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/reload')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    renderChart(data);
                });
        });
        jQuery('#addDetailModal').modal('hide');
    });

    var chartContainer = document.getElementById('linearChart');
    var ruleMin = parseInt(chartContainer.dataset.ruleMin);
    var ruleMax = parseInt(chartContainer.dataset.ruleMax);
    var range = ruleMax - ruleMin;

    var isDragging = false;
    var dragEnded = false;
    var editDetailId = null;

    function openEditModal(detailId, detailMin, detailMax, color) {
        editDetailId = detailId;
        document.getElementById('detail_min').value = detailMin;
        document.getElementById('detail_max').value = detailMax;

        if (color) {
            if (!color.startsWith('#')) {
                var match = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
                if (match) {
                    var r = parseInt(match[1]).toString(16).padStart(2, '0');
                    var g = parseInt(match[2]).toString(16).padStart(2, '0');
                    var b = parseInt(match[3]).toString(16).padStart(2, '0');
                    color = '#' + r + g + b;
                } else {
                    color = '#3498db';
                }
            }
            document.getElementById('detail_color').value = color;
            document.getElementById('detail_color_hex').value = color;
        }

        var form = document.getElementById('detailForm');
        if (detailId) {
            form.action = '/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/details/' + detailId;
        } else {
            form.action = '/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/details';
        }

        jQuery('#addDetailModal').modal('show');
    }

    chartContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('resize-handle') || isDragging || dragEnded) return;
        dragEnded = false;

        var clickedSegment = e.target.closest('.chart-segment');
        if (clickedSegment) {
            var detailId = clickedSegment.dataset.detailId;
            var detailMin = clickedSegment.dataset.detailMin;
            var detailMax = clickedSegment.dataset.detailMax;
            var currentColor = clickedSegment.style.background;
            openEditModal(detailId, detailMin, detailMax, currentColor);
            return;
        }

        var rect = chartContainer.getBoundingClientRect();
        var clickX = e.clientX - rect.left;
        var percent = clickX / rect.width;
        var clickValue = Math.round(ruleMin + (percent * range));

        var segmentWidth = Math.round(range * 0.2);
        if (segmentWidth < 1) segmentWidth = 1;

        var newMin = clickValue;
        var newMax = Math.min(clickValue + segmentWidth, ruleMax);

        openEditModal(null, newMin, newMax, '#3498db');
    });

    var activeResize = null;
    var activeSegment = null;
    var originalMin = 0;
    var originalMax = 0;

    function setupChartHandlers() {
        document.querySelectorAll('.resize-handle').forEach(function (handle) {
            handle.addEventListener('mousedown', function (e) {
                e.stopPropagation();
                isDragging = true;
                activeResize = handle.classList.contains('resize-left') ? 'left' : 'right';
                activeSegment = handle.closest('.chart-segment');
                originalMin = parseInt(activeSegment.dataset.detailMin);
                originalMax = parseInt(activeSegment.dataset.detailMax);
                document.body.style.cursor = 'ew-resize';
            });
        });

        document.querySelectorAll('.chart-segment').forEach(function (seg) {
            seg.addEventListener('click', function (e) {
                if (e.target.classList.contains('resize-handle')) return;
                var detailId = this.dataset.detailId;
                var detailMin = this.dataset.detailMin;
                var detailMax = this.dataset.detailMax;
                var currentColor = this.style.background;
                openEditModal(detailId, detailMin, detailMax, currentColor);
            });
        });
    }

    setupChartHandlers();

    document.addEventListener('mousemove', function (e) {
        if (!activeSegment) return;

        var rect = chartContainer.getBoundingClientRect();
        var clickX = e.clientX - rect.left;
        var percent = Math.max(0, Math.min(1, clickX / rect.width));
        var newValue = Math.round(ruleMin + (percent * range));
        newValue = Math.max(ruleMin, Math.min(ruleMax, newValue));

        if (activeResize === 'left') {
            if (newValue < originalMax - 1 && newValue >= ruleMin) {
                activeSegment.dataset.detailMin = newValue;
                var leftPercent = ((newValue - ruleMin) / range) * 100;
                activeSegment.style.left = leftPercent + '%';
                var widthPercent = ((originalMax - newValue) / range) * 100;
                activeSegment.style.width = widthPercent + '%';
                activeSegment.querySelector('.segment-label').textContent = newValue + '-' + originalMax;

                var allSegs = Array.from(document.querySelectorAll('.chart-segment'));
                var sortedSegs = allSegs.sort(function (a, b) { return parseInt(a.dataset.detailMin) - parseInt(b.dataset.detailMin); });
                var idx = sortedSegs.findIndex(function (s) { return s === activeSegment; });
                if (idx > 0) {
                    var leftSeg = sortedSegs[idx - 1];
                    var lsMin = parseInt(leftSeg.dataset.detailMin);
                    var lsMax = parseInt(leftSeg.dataset.detailMax);
                    if (newValue <= lsMax) {
                        leftSeg.dataset.detailMax = newValue;
                        leftSeg.style.width = ((newValue - lsMin) / range) * 100 + '%';
                        leftSeg.querySelector('.segment-label').textContent = lsMin + '-' + newValue;
                    }
                }
            }
        } else {
            if (newValue > originalMin + 1 && newValue <= ruleMax) {
                activeSegment.dataset.detailMax = newValue;
                var widthPercent = ((newValue - originalMin) / range) * 100;
                activeSegment.style.width = widthPercent + '%';
                activeSegment.querySelector('.segment-label').textContent = originalMin + '-' + newValue;

                var allSegs = Array.from(document.querySelectorAll('.chart-segment'));
                var sortedSegs = allSegs.sort(function (a, b) { return parseInt(a.dataset.detailMin) - parseInt(b.dataset.detailMin); });
                var idx = sortedSegs.findIndex(function (s) { return s === activeSegment; });
                if (idx < sortedSegs.length - 1) {
                    var rightSeg = sortedSegs[idx + 1];
                    var rsMin = parseInt(rightSeg.dataset.detailMin);
                    var rsMax = parseInt(rightSeg.dataset.detailMax);
                    if (newValue >= rsMin) {
                        rightSeg.dataset.detailMin = newValue;
                        rightSeg.style.left = ((newValue - ruleMin) / range) * 100 + '%';
                        rightSeg.style.width = ((rsMax - newValue) / range) * 100 + '%';
                        rightSeg.querySelector('.segment-label').textContent = newValue + '-' + rsMax;
                    }
                }
            }
        }
    });

    document.addEventListener('mouseup', function (e) {
        if (!activeSegment) return;

        e.stopPropagation();

        var detailId = activeSegment.dataset.detailId;
        var newMin = parseInt(activeSegment.dataset.detailMin);
        var newMax = parseInt(activeSegment.dataset.detailMax);
        var color = activeSegment.style.background;

        var segments = Array.from(document.querySelectorAll('.chart-segment')).sort(function (a, b) {
            return parseInt(a.dataset.detailMin) - parseInt(b.dataset.detailMin);
        });

        var updates = [];
        segments.forEach(function (seg) {
            updates.push({
                id: seg.dataset.detailId,
                min: parseInt(seg.dataset.detailMin),
                max: parseInt(seg.dataset.detailMax),
                color: rgbToHex(seg.style.background) || '#3498db'
            });
        });

        console.log('Saving all details:', updates);

        var fd = new FormData();
        fd.append('details', JSON.stringify(updates));
        fd.append('_token', '{{ csrf_token() }}');

        var saveUrl = '/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/save-all';
        console.log('Save URL:', saveUrl, 'Updates:', updates.length);

        fetch(saveUrl, {
            method: 'POST',
            body: fd
        }).then(function (r) { return r.json(); }).then(function (res) {
            console.log('Save result:', res);
            fetch('/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/reload')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    renderChart(data);
                })
                .catch(function () {
                    location.reload();
                });
        }).catch(function () {
            location.reload();
        });

        activeResize = null;
        activeSegment = null;
        isDragging = false;
        dragEnded = true;
        document.body.style.cursor = '';
        setTimeout(function () { dragEnded = false; }, 100);
    });

    function renderChart(data) {
        var html = '';
        var range = data.ruleMax - data.ruleMin;
        if (range <= 0) range = 1;

        data.details.forEach(function (detail) {
            var leftPercent = ((detail.min - data.ruleMin) / range) * 100;
            var widthPercent = ((detail.max - detail.min) / range) * 100;
            html += '<div class="chart-segment" data-detail-id="' + detail.id + '" data-detail-min="' + detail.min + '" data-detail-max="' + detail.max + '" style="position: absolute; left: ' + leftPercent + '%; width: ' + widthPercent + '%; top: 5px; bottom: 5px; background: ' + detail.color + '; opacity: 0.85; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden;">';
            html += '<span class="segment-label" style="color: white; font-size: 11px; font-weight: 600; text-shadow: 0 1px 2px rgba(0,0,0,0.5); white-space: nowrap;">' + detail.min + '-' + detail.max + '</span>';
            html += '<div class="resize-handle resize-left" title="Trascina per ridimensionare"></div>';
            html += '<div class="resize-handle resize-right" title="Trascina per ridimensionare"></div>';
            html += '</div>';
        });

        if (data.details.length === 0) {
            html = '<div class="text-center text-muted w-100 h-100 d-flex align-items-center justify-content-center"><span>Clicca per aggiungere il primo dettaglio</span></div>';
        }

        chartContainer.innerHTML = html;
        ruleMin = data.ruleMin;
        ruleMax = data.ruleMax;
        range = ruleMax - ruleMin;

        setupChartHandlers();
        updateDetailsTable(data.details);
    }

    jQuery('#openAddModal').on('click', function () {
        editDetailId = null;
        var segmentWidth = Math.round(range * 0.2);
        if (segmentWidth < 1) segmentWidth = 1;
        document.getElementById('detail_min').value = ruleMin;
        document.getElementById('detail_max').value = ruleMin + segmentWidth;
        document.getElementById('detail_color').value = '#3498db';
        document.getElementById('detail_color_hex').value = '#3498db';
        var form = document.getElementById('detailForm');
        form.action = '/rule-chimical-elements/' + chartContainer.dataset.ruleId + '/details';
    });

    function updateDetailsTable(details) {
        var tbody = document.querySelector('.card-body table tbody');
        var html = '';
        details.forEach(function (detail) {
            var csrf = '{{ csrf_token() }}';
            var deleteUrl = '{{ route("rule-chimical-elements.detail.destroy", [$ruleChimicalElement->id, "__id__"]) }}'.replace('__id__', detail.id);
            html += '<tr>';
            html += '<td>' + detail.min + '</td>';
            html += '<td>' + detail.max + '</td>';
            html += '<td><span style="display: inline-block; width: 24px; height: 24px; background: ' + detail.color + '; border-radius: 4px; border: 1px solid #ddd; vertical-align: middle;"></span> <code>' + detail.color + '</code></td>';
            html += '<td><div class="btn-group btn-group-sm" role="group">';
            html += '<button type="button" class="btn btn-info btn-effects d-flex align-items-center" data-detail-id="' + detail.id + '" data-detail-range="' + detail.min + '-' + detail.max + '"><i class="fas fa-flask mr-1"></i> Effetti</button>';
            html += '<form action="' + deleteUrl + '" method="POST" class="d-inline">';
            html += '<input type="hidden" name="_token" value="' + csrf + '">';
            html += '<input type="hidden" name="_method" value="DELETE">';
            html += '<button type="submit" class="btn btn-danger d-flex align-items-center" onclick="return confirm(\'Elimina?\')">';
            html += '<i class="fas fa-trash"></i></button></form></div></td></tr>';
        });
        if (details.length === 0) {
            html = '<tr><td colspan="4" class="text-center text-muted">Nessun dettaglio inserito</td></tr>';
        }
        tbody.innerHTML = html;
    }

    function rgbToHex(color) {
        if (!color || color.indexOf('rgb') === -1) return null;
        var match = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!match) return null;
        var r = parseInt(match[1]).toString(16).padStart(2, '0');
        var g = parseInt(match[2]).toString(16).padStart(2, '0');
        var b = parseInt(match[3]).toString(16).padStart(2, '0');
        return '#' + r + g + b;
    }

    chartContainer.addEventListener('mousemove', function (e) {
        if (activeSegment) return;
        var rect = chartContainer.getBoundingClientRect();
        var clickX = e.clientX - rect.left;
        var value = Math.round(ruleMin + ((clickX / rect.width) * range));

        var tooltip = chartContainer.querySelector('.chart-axis');
        if (!tooltip) {
            tooltip = document.createElement('span');
            tooltip.className = 'chart-axis';
            chartContainer.parentElement.querySelector('.d-flex.justify-content-between').appendChild(tooltip);
        }
        tooltip.textContent = '| ' + value;
    });

    jQuery('#addDetailModal').on('hidden.bs.modal', function () {
        var tooltip = document.querySelector('.chart-axis');
        if (tooltip) tooltip.textContent = '';
        editDetailId = null;
        chartContainer.dataset.ruleId = chartContainer.dataset.ruleId;
    });

    var currentDetailId = null;
    var currentDetailRange = null;

    $(document).on('click', '.btn-effects', function () {
        currentDetailId = $(this).data('detail-id');
        currentDetailRange = $(this).data('detail-range');
        $('#effectsDetailRange').text(currentDetailRange);
        loadEffects(currentDetailId);
        $('#effectsModal').modal('show');
    });

    function loadEffects(detailId) {
        $.get('/rule-chimical-elements/details/' + detailId + '/effects', function (response) {
            var tbody = $('#effectsTableBody');
            tbody.empty();

            response.effects.forEach(function (effect) {
                var typeName = effect.type === {{ App\Models\RuleChimicalElementDetailEffect::TYPE_FIXED }} ? 'Fisso' : 'A tempo';
                var row = '<tr>';
                row += '<td>' + typeName + '</td>';
                row += '<td>' + effect.gene_name + '</td>';
                row += '<td>' + effect.value + '</td>';
                row += '<td>';
                row += '<button type="button" class="btn btn-sm btn-primary btn-edit-effect mr-1" data-effect-id="' + effect.id + '" data-effect-type="' + effect.type + '" data-effect-gene-id="' + effect.gene_id + '" data-effect-value="' + effect.value + '" title="Modifica">';
                row += '<i class="fas fa-edit"></i></button>';
                row += '<button type="button" class="btn btn-sm btn-danger btn-delete-effect" data-effect-id="' + effect.id + '" title="Elimina">';
                row += '<i class="fas fa-trash"></i></button>';
                row += '</td></tr>';
                tbody.append(row);
            });

            if (response.effects.length === 0) {
                tbody.append('<tr><td colspan="4" class="text-center text-muted">Nessun effetto inserito</td></tr>');
            }
        });
    }

    $('#addEffectBtn').on('click', function () {
        $('#effectForm')[0].reset();
        $('#addEffectModal').modal('show');
    });

    var editingEffectId = null;

    $(document).on('click', '.btn-edit-effect', function () {
        editingEffectId = $(this).data('effect-id');
        var type = $(this).data('effect-type');
        var geneId = $(this).data('effect-gene-id');
        var value = $(this).data('effect-value');
        
        $('#effect_type').val(type);
        $('#effect_gene_id').val(geneId);
        $('#effect_value').val(value);
        $('#addEffectModal').modal('show');
    });

    $('#addEffectModal').on('shown.bs.modal', function () {
        if (editingEffectId) {
            $(this).find('.modal-title').text('Modifica Effetto');
        } else {
            $(this).find('.modal-title').text('Aggiungi Effetto');
        }
    });

    $('#effectForm').on('submit', function (e) {
        e.preventDefault();
        var url, type;
        
        if (editingEffectId) {
            url = '/rule-chimical-elements/effects/' + editingEffectId;
            type = 'PUT';
        } else {
            url = '/rule-chimical-elements/details/' + currentDetailId + '/effects';
            type = 'POST';
        }
        
        $.ajax({
            url: url,
            type: type,
            data: $(this).serialize(),
            success: function (response) {
                $('#addEffectModal').modal('hide');
                editingEffectId = null;
                loadEffects(currentDetailId);
                toastr.success('Effetto salvato');
            },
            error: function (xhr) {
                toastr.error('Errore nel salvataggio');
            }
        });
    });

    $('#addEffectModal').on('hidden.bs.modal', function () {
        editingEffectId = null;
        $('#effectForm')[0].reset();
    });

    $(document).on('click', '.btn-delete-effect', function () {
        if (!confirm('Elimina effetto?')) return;
        var effectId = $(this).data('effect-id');
        $.ajax({
            url: '/rule-chimical-elements/effects/' + effectId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (response) {
                loadEffects(currentDetailId);
                toastr.success('Effetto eliminato');
            }
        });
    });

    @if($errors->any())
        jQuery('#addDetailModal').modal('show');
    @endif
</script>
@stop