kernel.renderer.registrar_pagelet('edicion', function (info) {
    
    var id = '#' + info.id;

    return {
        onload: function () {
            
            // Evento submit del botón "Eliminar"
            $(id).on("click", ".js-btn-eliminar", function () {
                
                var $form = $(this).parents("form.js-form-eliminar");
                
                // Muestro prompt para confirmar el cambio
                kernel.ui.prompt({
                    msg: info.mensajes.msg_prompt_no_poseo_guardar,
                    msg_ok: info.mensajes.aceptar,
                    msg_cancel: info.mensajes.cancelar,
                    ok: function ()
                    {
                        $form.submit();
                    }
                });
            });
            
        }
    };
});

