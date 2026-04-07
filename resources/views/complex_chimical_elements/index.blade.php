@extends('adminlte::page')

@section('title', 'Elementi Chimici Complessi')

@section('content_header')@stop

@section('content')
<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Elementi Chimici Complessi</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="row">
                    <div class="col-6"></div>
                    <div class="col-3">
                        <button type="button" class="btn btn-danger btn-block btn-sm js-delete" data-list="table_list"
                            data-url="{{ route('complex-chimical-elements.delete') }}">
                            <i class="fa fa-trash"></i> Cancella
                        </button>
                    </div>
                    <div class="col-3">
                        <a href="{{route('complex-chimical-elements.create')}}">
                            <button type="button" class="btn btn-primary btn-block btn-sm"><i class="fa fa-plus"></i>
                                Nuovo</button>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12">
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
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Simbolo</th>
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

<!-- Modal Tree -->
<div class="modal fade" id="modal_tree_chart" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ricetta: <span id="tree_element_name" class="font-weight-bold"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center" style="overflow-x: auto; background-color: #f8f9fa;">
                <div id="tree_container"></div>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
    $(document).ready(function () {

        $(document).on('click', '.btn_edit', function (e) {
            var url = "{{ route('complex-chimical-elements.edit', ['_id_']) }}";
            url = url.replace('_id_', $(this).data('id'));
            window.location.href = url;
        });

        var table = $("#table_list").DataTable({
            order: [1, 'asc'],
            pageLength: -1,
            ajax: {
                type: 'POST',
                url: '{{ route('complex-chimical-elements.datatable') }}',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {},
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
                { data: "id", name: "id" },
                { data: "name", name: "name" },
                { data: "symbol", name: "symbol" },
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
                        return '<button type="button" class="btn btn-warning btn-sm btn_tree" data-id="' + data + '" data-name="' + row.name + '" title="Albero Componenti"><i class="fa fa-sitemap"></i></button> ' +
                            '<a href="{{ url('complex-chimical-elements') }}/' + data + '" class="btn btn-info btn-sm btn_show" title="Dettagli"><i class="fa fa-eye"></i></a> ' +
                            '<button type="button" class="btn btn-primary btn-sm btn_edit" data-id="' + data + '"><i class="fa fa-edit"></i></button>';
                    },
                    targets: 4
                },
            ],
        });

        $(document).on('click', '.btn_tree', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            $('#tree_element_name').text(name);
            $('#tree_container').empty();
            $('#modal_tree_chart').modal('show');

            $.ajax({
                url: '{{ route('complex-chimical-elements.tree-data', '_id_') }}'.replace('_id_', id),
                type: 'GET',
                success: function (data) {
                    drawTree(data);
                },
                error: function () {
                    alert('Errore nel caricamento dei dati dell\'albero.');
                }
            });
        });

        function drawTree(data) {
            const width = 1000;
            const margin = { top: 20, right: 150, bottom: 20, left: 150 };

            // Helper to count nodes to determine height
            const root = d3.hierarchy(data);
            const dx = 40; // vertical spacing
            const dy = width / (root.height + 1); // horizontal spacing
            const height = (root.descendants().length * 20) + margin.top + margin.bottom;

            const tree = d3.tree().nodeSize([dx, dy]);
            const diag = d3.linkHorizontal().x(d => d.y).y(d => d.x);

            const svg = d3.select("#tree_container").append("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [-margin.left, -margin.top - (dx / 2), width, height])
                .attr("style", "max-width: 100%; height: auto; font: 12px sans-serif;");

            const gLink = svg.append("g")
                .attr("fill", "none")
                .attr("stroke", "#555")
                .attr("stroke-opacity", 0.4)
                .attr("stroke-width", 1.5);

            const gNode = svg.append("g")
                .attr("cursor", "pointer")
                .attr("pointer-events", "all");

            tree(root);

            gLink.selectAll("path")
                .data(root.links())
                .join("path")
                .attr("d", diag);

            const node = gNode.selectAll("g")
                .data(root.descendants())
                .join("g")
                .attr("transform", d => `translate(${d.y},${d.x})`);

            node.append("circle")
                .attr("fill", d => d.data.type === 'complex' ? "#ffc107" : "#17a2b8")
                .attr("r", 5)
                .attr("stroke", "#333")
                .attr("stroke-width", 1);

            node.append("text")
                .attr("dy", "0.31em")
                .attr("x", d => d.children ? -8 : 8)
                .attr("text-anchor", d => d.children ? "end" : "start")
                .text(d => d.data.name)
                .clone(true).lower()
                .attr("stroke", "white")
                .attr("stroke-width", 3);
        }

    });
</script>
@stop