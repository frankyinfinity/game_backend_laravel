<div class="card">
    <div class="card-header pb-0">
        <h4 class="mb-0">Mappa</h5>
    </div>
    <div class="card-body" style="overflow: auto;">
        <div class="row">
            <div class="col-12">
                <div class="row">
                    @foreach ($tiles as $tile)
                        <div class="col-4">
                            <button type="button" id="button_tile_{{$tile->id}}" class="btn {{$region->climate->defaultTile->id == $tile->id ? 'btn-primary' : 'btn-outline-primary' }} btn-block button_tile mb-2" data-color="{{$tile->color}}">
                                <i id="icon_tile_{{$tile->id}}" class="fa fa-square" style="{{$region->climate->defaultTile->id == $tile->id ? 'color: white' : $tile->text_color }}"></i> {{$tile->name}}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-12" style="overflow: auto">
                <div style="display: grid; grid-template-columns: repeat({{ $region->width }}, 35px); gap: 1px;">
                    @for ($i = 0; $i < $region->height; $i++)
                        @for ($j = 0; $j < $region->width; $j++)
                            <div id="tile_{{$i}}_{{$j}}" class="tile" style="width: 35px; height: 35px; background-color: {{$region->climate->defaultTile->color}}; cursor: pointer"></div>
                        @endfor
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
    <script>
        $(document).ready(function () {

            let tile_selected_id = '{{$region->climate->defaultTile->id}}';
            let tile_selected_color = '{{$region->climate->defaultTile->color}}';

            //Init Map
            let map = {!! json_encode($map) !!};
            map.forEach(item => {
                let tile = item.tile;
                let i = item.i;
                let j = item.j;
                $("#tile_"+i+'_'+j).css("background-color", tile.color);
            });

            $(document).on('click', '.button_tile', function () {

                let el = $(this);
                let tile_id = el.attr('id').split('_')[2];
                let tile_color = el.data('color');

                //Reset all
                tile_selected_id = null;
                tile_selected_color = null;

                let elements = $('.button_tile').toArray();
                elements.forEach(element => {
                    let item = $('#'+element.id);
                    if(item.hasClass('btn-primary')) {
                        item.removeClass('btn-primary');
                        item.addClass('btn-outline-primary');
                        $("#icon_tile_"+element.id.split('_')[2]).css("color", item.data('color'));
                    }
                });

                //Click
                tile_selected_id = tile_id;
                tile_selected_color = tile_color;

                el.removeClass('btn-outline-primary');
                el.addClass('btn-primary');
                $("#icon_tile_"+tile_selected_id).css("color", tile_selected_color);

            });

            $(document).on('click', '.tile', function () {

                let tile = $(this);
                let tile_i = tile.attr('id').split('_')[1];
                let tile_j = tile.attr('id').split('_')[2];

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: "{{ route('regions.tile') }}",
                    type: 'POST',
                    data: {
                        region_id: '{{$region->id}}',
                        tile_id: tile_selected_id,
                        tile_i: tile_i,
                        tile_j: tile_j
                     },
                    success: function(result) {
                        if(result.success) {
                            $("#tile_"+tile_i+'_'+tile_j).css("background-color", tile_selected_color);
                        } else {
                            var msg = 'Si è verificato un errore.';
                            if(result.msg != null) msg = result.msg;
                            $.notify({title: "Ops!", message: result.msg}, {type: "warning"})
                        }
                    },
                    error: function () {
                        Swal.fire({
                            title: 'Ops!',
                            text: 'Si è verificato un errore imprevisto.',
                            type: 'danger',
                            showCancelButton: false,
                            buttonsStyling: false,
                            confirmButtonClass: 'btn btn-info',
                            confirmButtonText: 'Ho Capito!',
                        })
                    }
                });

            });

        });
    </script>
@stop
