function _get_selected_checkbox_number(table){
    return table.find('tbody input[type=checkbox]:checked').map(function(_, el) {
        if($(el).val() !== "on")
            return $(el).val();
        else return;
    }).get();
}

function jsgrid()
{
    var t = jQuery("table.js-grid"),
        id = jQuery(".js-delete");

    t.each(function() {
        /* check if events are just associated */
        var elem = jQuery(this);
        var table = jQuery(this),
            check = table.find(".check");

        check.off('change');    // prevent multiple binding
        check.on("change", function() {

            var btn = $('body').find("a[data-list='" + table.attr("id") + "']");
            if(!btn.length) return false;

            /*
            if(_get_selected_checkbox_number(table).length > 0)
                btn.show().removeClass('d-none');
            else
                btn.hide().addClass('d-none');
            */
        });

    });

    id.each(function() {
        //check if events are just associated
        var elem = jQuery(this);
        //elem.hide();

        var t = jQuery(this),
            table = jQuery("#" + t.attr("data-list"));
        if (table) {
            _grid_bind_delete(table, t);
        }
    });

}

function _grid_bind_delete(table, btn)
{

    btn.off('click');   // prevent multiple binding
    btn.on("click", function() {

        if ($(this).hasClass('disabled'))
            return false;

        var elements = _get_selected_checkbox_number(table);
        if(elements.length === 0) {
            _empty_elements();
            return false;
        }

        $(this).addClass('disabled');

        var url = $(this).data('url');

        _confirm_delete(function (e) {
            if(e.value !== null && e.value === true){
                //Send ajax request
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { ids: elements },
                    success: function(result) {
                        if(result.success) {
                            window.location.reload();
                        } else {
                            btn.removeClass('disabled');
                            var msg = 'Si è verificato un errore.';
                            if(result.msg != null) msg = result.msg;
                            $.notify({title: "Ops!", message: result.msg}, {type: "warning"})
                        }
                    },
                    error: function () {
                        $.notify({title: "Ops!", message: "Si è verificato un errore imprevisto."}, {type: "danger"});
                        btn.removeClass('disabled');
                    }
                });
            } else {
                btn.removeClass('disabled');
            }
        });

        return false;

    });
}

function _confirm_delete(callback) {
    swal({
        title: 'Attenzione',
        text: 'Vuoi davvero eliminare questi dati? L\'operazione è irreversibile.',
        type: 'warning',
        showCancelButton: true,
        buttonsStyling: false,
        confirmButtonClass: 'btn btn-danger',
        confirmButtonText: 'Conferma',
        cancelButtonClass: 'btn btn-default',
        cancelButtonText: 'Annulla'
    }).then(callback);
}


function _empty_elements(){
    swal({
        title: 'Attenzione',
        text: 'Selezionare almento un elemento da cancellare.',
        type: 'warning',
        showCancelButton: false,
        buttonsStyling: false,
        confirmButtonClass: 'btn btn-info',
        confirmButtonText: 'Ho Capito!',
    });
}