(function( $ ) {
    "use strict";

    $(function () {
        $(".bookings li").each(
            function () {
                var dataTip = $(this).attr("data-tip");
                $(this).find("a").each(
                    function () {
                        var str = dataTip.replace($(this).text() + " - ", "").replace(/<(?:.|\n)*?>/gm, " ");
                        $(this).attr("data-tip", str);
                    });
            });
    });

})( jQuery );
