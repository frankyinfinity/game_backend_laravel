@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Tile</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list"
                            data-url="{{ route('tiles.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <button type="button" id="tiles-create-btn" class="btn btn-primary btn-block btn-sm"><i class="fa fa-plus"></i>
                            Nuovo</button>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <ul class="nav nav-tabs mb-3" id="tileFilterTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-family-tile-id="all">Tutte</a>
                    </li>
                </ul>
                <div class="material-datatables">
                    <table id="table_list" class="js-grid table table-no-bordered table-hover" cellspacing="0"
                        width="100%" style="width:100%">
                        <thead>
                            <tr>
                                <th class="column_with_checkbox">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            onClick="toggle(this, 'selected[]')">
                                    </div>
                                </th>
                            <th>Nome</th>
                            <th>Grafica</th>
                            <th>Famiglia Tile</th>
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
    var typeLabels = @json(\App\Models\FamilyTile::getTypeLabels());
    var familyTiles = @json(\App\Models\FamilyTile::all()->pluck('name', 'id')->toArray());
    var currentFilter = 'all';
    var createUrl = '{{ route("tiles.create") }}';

    $(document).ready(function () {
        // Build filter tabs
        $.each(familyTiles, function (id, name) {
            $('#tileFilterTabs').append('<li class="nav-item"><a class="nav-link" href="#" data-family-tile-id="' + id + '">' + name + '</a></li>');
        });

        // Handle tab clicks
        $(document).on('click', '#tileFilterTabs .nav-link', function (e) {
            e.preventDefault();
            $('.nav-link', '#tileFilterTabs').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('family-tile-id');
            table.ajax.url('{{ route('tiles.datatable') }}').load();
        });

        $(document).on('click', '.btn_edit', function (e) {
            var url = "{{ route('tiles.show', ['_id_']) }}";
            url = url.replace('_id_', $(this).data('id'));
            window.location.href = url;
        });

        // Handle new button - generate URL with current filter
        $('#tiles-create-btn').on('click', function (e) {
            e.preventDefault();
            var url = createUrl;
            if (currentFilter !== 'all') {
                url += '?family_tile_id=' + currentFilter;
            }
            window.location.href = url;
        });

        var table = $("#table_list").DataTable({
            order: [1, 'asc'],
            pageLength: -1,
            ajax: {
                type: 'POST',
                url: '{{ route('tiles.datatable') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')
                },
                data: function (d) {
                    if (currentFilter !== 'all') {
                        d.family_tile_id = currentFilter;
                    }
                },
            },
            columns: [
                {
                    searchable: false,
                    orderable: false,
                    data: null,
                    name: "checkbox",
                    defaultContent: "",
                    class: "disableEdit",
                },
                { data: "name", name: "name" },
                { data: "color", name: "color" },
                { data: "family_tile_name", name: "family_tile_name" },
                { data: "id", name: "id" },
            ],
            sDom: '<"dataTables_top"lfBr>t<"dataTables_bottom"ip><"clear">',
            initComplete: function (a, b) {
                jsgrid();
            },
            "drawCallback": function () {
                jsgrid();
                $('#selAll').prop('checked', false);
            },
            columnDefs: [
                {
                    render: function (data, type, row) {
                        return '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" id="sel-' + data.id + '" name="selected[]" value="' + data.id + '">' +
                            '</div>';
                    },
                    targets: 0
                },
                {
                    render: function (data, type, row) {
                        return '<img src="/storage/tiles/' + row.id + '.png" style="width: 20px; height: 20px; image-rendering: pixelated; border: 1px solid #000;" alt="Grafica" onerror="this.style.display=\'none\'">';
                    },
                    targets: 2
                },
                {
                    render: function (data, type, row) {
                        return '<button type="button" class="btn btn-primary btn-block btn-sm btn_edit" data-id="' + data + '"><i class="fa fa-edit"></i></button>';
                    },
                    targets: 4
                },
            ],
        });

    });
</script>
@stop