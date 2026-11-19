kernel.renderer.registrar_pagelet('con_curso', function (info) {

    var id = '#' + info.id;
    
    function click_btn_desvincular()
    {
        kernel.ui.prompt({
            msg: info.mensajes.confirmacion_desvincular,
            msg_ok: info.mensajes.aceptar,
            msg_cancel: info.mensajes.cancelar,
            ok: function ()
            {
                window.location.replace(info.url_desvincular);
            }
        });
    }
    
    function click_btn_actualizar_docentes()
    {
        kernel.ui.prompt({
            msg: info.mensajes.confirmacion_actualizar_docentes,
            msg_ok: info.mensajes.aceptar,
            msg_cancel: info.mensajes.cancelar,
            ok: function ()
            {
                window.location.replace(info.url_actualizar_docentes);
            }
        });
    }
    
    function click_btn_actualizar_alumnos()
    {
        kernel.ui.prompt({
            msg: info.mensajes.confirmacion_actualizar_alumnos,
            msg_ok: info.mensajes.aceptar,
            msg_cancel: info.mensajes.cancelar,
            ok: function ()
            {
                window.location.replace(info.url_actualizar_alumnos);
            }
        });
    }
    
    return {
        onload: function () 
        {

            $('#js-btn-desvincular').on("click", click_btn_desvincular);
            $('#js-btn-actualizar-docentes').on("click", click_btn_actualizar_docentes);
            $('#js-btn-actualizar-alumnos').on("click", click_btn_actualizar_alumnos);
            
        }
    }

});
