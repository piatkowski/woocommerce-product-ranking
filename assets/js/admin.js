(function ($) {
    "use strict";

    $(document).ready(function ($) {
        $('td.editable').dblclick(function (e) {
            e.preventDefault();
            $(this).data('default', $(this).text().trim());
            $(this).html('<textarea name="' + $(this).data('name') + '">' + $(this).text().trim() + '</textarea><a href="#" class="close"><span class="dashicons dashicons-no"></span></a>');
        });
        $('body').on('click', 'td.editable .close', function(e){
            e.preventDefault();
            if(confirm('Czy na pewno anulować edycję pola?')) {
                $(this).parent().text($(this).parent().data('default'));
            }
        });
    });

})(jQuery);