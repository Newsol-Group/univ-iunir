kernel.renderer.registrar_pagelet('listado', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {

            if (info.guardado_ok) {

                kernel.ui.show_mensaje(info.mensajes.guardado_ok_msj, {
                    tipo: 'alert-success',
                    timeout: 10000
                });

            }
            
            // Evento submit del botón "Reabrir Solicitud"
            $(id).on("click", ".js-btn-reabrir", function () {

                var $form = $(this).parents("form");

                // Muestro prompt para confirmar el cambio
                kernel.ui.prompt({
                    msg: info.mensajes.msg_prompt_reabrir,
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