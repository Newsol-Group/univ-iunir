kernel.renderer.registrar_pagelet('documentacion', function (info) {
    var id = '#' + info.id;

    return {
        onload: function () {
            
            // Evento submit del botón "Finalizar Solicitud"
            $(id).on("click", ".js-btn-finalizar", function () {

                var $form = $(this).parents("form");

                // Muestro prompt para confirmar el cambio
                kernel.ui.prompt({
                    msg: info.mensajes.msg_prompt_finalizar,
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
