'use strict';

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
        source: function source(request, response) {
            var form = $("#search-product").closest("form");
            $.ajax({
                url: '/Mtz/Search?handler=AutoComplete',
                data: form.serialize(),
                type: 'GET',
                success: function success(data) {
                    // předpokládám, že data je pole stringů nebo objektů s vlastností 'value'
                    response(data);
                },
                error: function error(xhr) {
                    alert(xhr.statusText);
                }
            });
        },
        select: function select(event, ui) {
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

    function syncRowsAndHeight(el) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
        var lines = el.value.split(/\r\n|\r|\n/).length || 1;
        el.rows = Math.max(lines, 1);
    }

    function submitForm(form) {
        // preferované tlačítko (zachová "submitter" a případné name/value)
        var preferred = form.querySelector('input[type="submit"].cart-odeslat-objednavku, button[type="submit"].cart-odeslat-objednavku');

        if (typeof form.requestSubmit === 'function') {
            if (preferred) form.requestSubmit(preferred);else form.requestSubmit();
            return;
        }
        if (preferred) {
            preferred.click();return;
        }

        var any = form.querySelector('button[type="submit"], input[type="submit"]');
        if (any) {
            any.click();return;
        }

        var tmp = document.createElement('button');
        tmp.type = 'submit';
        tmp.hidden = true;
        form.appendChild(tmp);
        tmp.click();
        tmp.remove();
    }

    // inicializace existujících textů
    document.querySelectorAll('textarea.js-autogrow').forEach(syncRowsAndHeight);

    // autogrow při změně
    document.addEventListener('input', function (e) {
        if (e.target && e.target.matches('textarea.js-autogrow')) {
            syncRowsAndHeight(e.target);
        }
    });

    document.addEventListener('compositionend', function (e) {
        if (e.target && e.target.matches('textarea.js-autogrow')) {
            syncRowsAndHeight(e.target);
        }
    });

    // Enter → submit, Shift+Enter → nový řádek
    document.addEventListener('keydown', function (e) {
        var ta = e.target;
        if (!ta || ta.tagName !== 'TEXTAREA' || !ta.classList.contains('js-autogrow')) return;

        if (e.key === 'Enter') {
            if (e.shiftKey) {
                // necháme vložit \n (autogrow se chytí na input)
                return;
            } else {
                // Enter bez Shift → odešli formulář
                e.preventDefault(); // žádné \n do textarea
                var form = ta.closest('form');
                if (form) submitForm(form);
            }
        }
    });
});

