kernel.renderer.registrar_pagelet('listado', function (info) {
    
    var id = '#' + info.id;

    return {
        onload: function () {
            
            // Evento click en checkbox "No poseo"
            $(id).on("click", ".js-no-poseo", function () {
                var requisito = $(this).data("requisito");
                var valor_actual = $(this).is(":checked") ? 1 : 0;
                $("#js-input-no-posee-actual-"+requisito).val(valor_actual);
                var valor_original = $("#js-input-no-posee-original-"+requisito).val();
                
                // Si hubo cambios
                if (valor_actual != valor_original) {
                    $("#js-btn-guardar-"+requisito).prop('disabled', false);
                } else {
                    $("#js-btn-guardar-"+requisito).prop('disabled', true);
                }
            });
            
            
            // Evento submit del botón "Guardar" para "No poseo"
            $(id).on("click", ".js-btn-guardar", function () {
                
                var $form = $(this).parents("form.js-form-guardar");
                
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
