@extends('adminlte::page')

@section('title', 'Corpo Element')

@section('content_header')@stop

@section('content')
<div class="card card-outline card-primary shadow-sm">
    <div class="card-header pb-0 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-dark font-weight-bold">Corpo Element</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row justify-content-end">
                    <div class="col-md-3 col-sm-6 mb-2">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete shadow-sm" data-list="table_list" data-url="{{ route('element-bodies.delete') }}">
                            <i class="fa fa-trash"></i> Elimina Selezionati
                        </button>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-2">
                        <a href="{{ route('element-bodies.create') }}">
                            <button type="button" class="btn btn-primary btn-block btn-sm shadow-sm"><i class="fa fa-plus"></i> Nuovo Corpo</button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0" width="100%" style="width:100%">
                        <thead class="bg-light text-dark">
                            <tr>
                                <th class="column_with_checkbox"><div class="form-check"><input class="form-check-input" type="checkbox" onClick="toggle(this, 'selected[]')"></div></th>
                                <th>ID</th>
                                <th>Immagine</th>
                                <th>Nome</th>
                                <th>Caratteristica</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
$(document).ready(function () {
    $(document).on('click', '.btn_edit', function () {
        window.location.href = "{{ route('element-bodies.edit', ['_id_']) }}".replace('_id_', $(this).data('id'));
    });

    var STATE_CREATED = {{ \App\Models\ElementBody::STATE_CREATED }};
    var STATE_FINISH_DRAW = {{ \App\Models\ElementBody::STATE_FINISH_DRAW }};
    var STATE_FINISH_ZONE = {{ \App\Models\ElementBody::STATE_FINISH_ZONE }};
    var STATE_COMPLETED = {{ \App\Models\ElementBody::STATE_COMPLETED }};

    $("#table_list").DataTable({
        order: [1, 'asc'], pageLength: 10,
        ajax: { type: 'POST', url: '{{ route('element-bodies.datatable') }}', headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} },
        columns: [
            {searchable:false, orderable:false, data:null, name:"checkbox", defaultContent:"", class:"disableEdit"},
            {data:"id", name:"id"},
            {data:"image_display", name:"image_display", searchable:false, orderable:false},
            {data:"name", name:"name"},
            {data:"characteristic_display", name:"characteristic_display", searchable:false, orderable:false},
            {data:"state_display", name:"state_display"},
            {data:"id", name:"id"},
        ],
        sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
        initComplete: function(){ jsgrid(); },
        drawCallback: function(){ jsgrid(); $('#selAll').prop('checked', false); },
        columnDefs: [
            { render: function(d, t, row){ var dis = row.state >= 1 ? 'disabled' : ''; return '<div class="form-check"><input class="form-check-input" type="checkbox" name="selected[]" value="'+row.id+'" '+dis+'></div>'; }, targets: 0 },
            { render: function(data, t, row){
                var editBtn = '<button type="button" class="btn btn-primary btn-sm btn_edit mr-1" data-id="'+data+'"><i class="fa fa-edit"></i></button>';
                var toggleBtn = '';
                if (row.state == STATE_CREATED) {
                    if (!row.image) toggleBtn = '<button type="button" class="btn btn-success btn-sm disabled-state-btn mr-1"><i class="fas fa-check-circle" style="opacity:0.5;"></i></button>';
                    else toggleBtn = '<form action="{{ route('element-bodies.toggle-state') }}" method="POST" style="display:inline-block;" class="js-confirm-complete mr-1">@csrf<input type="hidden" name="id" value="'+data+'"><input type="hidden" name="state" value="'+STATE_FINISH_DRAW+'"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i></button></form>';
                } else if (row.state == STATE_FINISH_DRAW) {
                    if ((row.zones_count||0)==0) toggleBtn = '<button type="button" class="btn btn-info btn-sm disabled-zones-btn mr-1"><i class="fas fa-lock" style="opacity:0.5;"></i></button>';
                    else toggleBtn = '<form action="{{ route('element-bodies.toggle-state') }}" method="POST" style="display:inline-block;" class="js-confirm-zone mr-1">@csrf<input type="hidden" name="id" value="'+data+'"><input type="hidden" name="state" value="'+STATE_FINISH_ZONE+'"><button type="submit" class="btn btn-info btn-sm"><i class="fas fa-lock"></i></button></form>';
                } else if (row.state == STATE_FINISH_ZONE) {
                    toggleBtn = '<form action="{{ route('element-bodies.toggle-state') }}" method="POST" style="display:inline-block;" class="js-confirm-ancore mr-1">@csrf<input type="hidden" name="id" value="'+data+'"><input type="hidden" name="state" value="'+STATE_COMPLETED+'"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-check-double"></i></button></form>';
                }
                return '<div class="d-flex align-items-center">'+editBtn+toggleBtn+'</div>';
            }, targets: 6 },
        ],
    });

    $(document).on('click', '.disabled-state-btn', function(e){ e.preventDefault(); toastr ? toastr.warning('Disegna la grafica prima.') : alert('Disegna la grafica prima.'); });
    $(document).on('click', '.disabled-zones-btn', function(e){ e.preventDefault(); toastr ? toastr.warning('Crea almeno una zona.') : alert('Crea almeno una zona.'); });
    $(document).on('submit', '.js-confirm-complete', function(e){ if(!confirm('Terminare il disegno? Bloccherà la grafica.')) e.preventDefault(); });
    $(document).on('submit', '.js-confirm-zone', function(e){ if(!confirm('Terminare le zone? Bloccherà le modifiche.')) e.preventDefault(); });
    $(document).on('submit', '.js-confirm-ancore', function(e){ if(!confirm('Bloccare definitivamente le ancore?')) e.preventDefault(); });
});
</script>
@stop
