kernel.renderer.registrar_pagelet('vincular', function (info) {

    var id = '#' + info.id;
    
    function click_matricular_alumnos()
    {
        // Si el checkbox "matricular alumnos" esta marcado
        if($('#js-matricular-alumnos').is(':checked')) {
            $("#js-matricular-alumnos-input").val(1);
        } else {
            $("#js-matricular-alumnos-input").val(0);
        }
    }

    function click_btn_volver()
    {
        kernel.ui.prompt({
            msg: info.mensajes.confirmacion_salir,
            msg_ok: info.mensajes.aceptar,
            msg_cancel: info.mensajes.cancelar,
            ok: function ()
            {
                window.location.replace(info.url_volver);
            }
        });
    }
    
    return {
        onload: function () 
        {
            
            $('#js-matricular-alumnos').on("click", click_matricular_alumnos);
            
            $('#js-btn-volver').on("click", click_btn_volver);
            
            $('#js-curso').trigger("focus");
            
            click_matricular_alumnos();
        }
    }

});

