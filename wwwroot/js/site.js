$(function () {
    // Odeslání formuláře, pokud má class="auto-submit"
    $('form.auto-submit').each(function () {
        $(this).find('input, select, textarea').on('change', function () {
            $(this).closest('form').submit();
        });
    });

    // Odeslání nadřazeného formuláře, pokud má input class="auto-submit"
    $('input.auto-submit, select.auto-submit, textarea.auto-submit').on('change', function () {
        $(this).closest('form').submit();
    });

    // Klikání mimo dropdown a toggle menu
    $(document).on("click", function (e) {
        var $dropdown = $("#top-panel-dropdown");
        var $menu = $("#top-panel-dropdown-menu");

        if ($dropdown.is(e.target) || $dropdown.has(e.target).length) {
            $menu.stop(true, true).slideToggle("fast");
        } else {
            $menu.stop(true, true).slideUp("fast");
        }
    });

    // Zavírání flash zprávy klikem na tlačítko
    $(document).on("click", ".flash-close-btn", function () {
        $(this).parent().slideUp();
    });

    $("#search-product").autocomplete({
        source: function (request, response) {
            var form = $("#search-product").closest("form");
            $.ajax({
                url: '/Mtz/Search?handler=AutoComplete',
                data: form.serialize(),
                type: 'GET',
                success: function (data) {
                    // předpokládám, že data je pole stringů nebo objektů s vlastností 'value'
                    response(data);
                },
                error: function (xhr) {
                    alert(xhr.statusText);
                }
            });
        },
        select: function (event, ui) {
            // správně nastav hodnotu inputu
            $("#search-product").val(ui.item.value);
        },
        minLength: 3
    });

    $("input[type='number']").each(function () {
        var numberInput = $(this);
        numberInput.inputSpinner({
            groupClass: "product-size"
        });
        numberInput.removeAttr("style");
        numberInput.attr("style", "width: 0px; visibility: hidden; float: left; height: 0px;");
    });

});


