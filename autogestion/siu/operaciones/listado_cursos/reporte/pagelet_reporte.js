kernel.renderer.registrar_pagelet('reporte', function(info) {
    var id = '#' + info.id;
    
	function registrar_disparadores() {
    }
    
    function registrar_listeners() {
    }

    return {
        onload: function() {

            $(document).on("click","div.titulo", function (event) {
                    var $ver_detalle = $(this).find('i.icon-white');

                    if ($ver_detalle.hasClass('icon-chevron-up'))
                        $ver_detalle.removeClass('icon-chevron-up').addClass('icon-chevron-down');
                    else if ($ver_detalle.hasClass('icon-chevron-down'))
                    {
                        $ver_detalle.removeClass('icon-chevron-down').addClass('icon-chevron-up');
                    }
            });
        }
    }
})