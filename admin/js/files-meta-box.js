(function( $ ) {
    "use strict";

    $(function () {
        $('#booking_files .remove-file').on(
            'click',
            function( event ) {
                if (confirm("Are you sure you want to delete this file?")) {
                    var id = $(this).data().id;
                    $.ajax({
                        type: 'POST',
                        url: filesOptions.ajaxUrl,
                        data: {
                            action: filesOptions.deleteAction,
                            id: id,
                            _wpnonce: filesOptions.nonce,
                        },
                        success: $.proxy(function (data) {
                            $(this).parent().remove();
                        }, this),
                        error: function (jqXHR, textStatus, errorThrown) {

                        },
                        complete: function () {
                        }
                    });
                    event.preventDefault();
                }
            }
        );

        $('#booking_add_file').on(
            "click",
            function( event ) {
                var params = {
                    _wpnonce: filesOptions.nonce,
                };
                //tb_show( filesOptions.title, filesOptions.url + "&" + $.param(params) );
                //tb_show( filesOptions.title, 'media-upload.php?referer=media_page&type=image&TB_iframe=true&post_id=0', false);
                tb_show( filesOptions.title, filesOptions.url );
                event.preventDefault();
            }
        );

        $('body').on( 'thickbox:iframe:loaded', function() {
            debugger;
        } );
        var tb_unload_count = 1;
        $('body').on('thickbox:removed', function () {
            debugger;
            if (tb_unload_count > 1) {
                tb_unload_count = 1;
            } else {
                debugger;
                // do something here
                console.log( 'Thickbox closed' );
                tb_unload_count = tb_unload_count + 1;
            }
        });
    });

})( jQuery );