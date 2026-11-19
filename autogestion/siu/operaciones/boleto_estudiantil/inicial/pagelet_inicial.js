kernel.renderer.registrar_pagelet('inicial', function(info) {
    var id = '#' + info.id;

    return {
        onload: function() {

            // Si viene un mensaje lo muestro
            if (info.mensaje) {
                kernel.ui.show_mensaje(info.mensaje, {tipo: info.tipo_mensaje, until_interaction: true});
            }

            $( id ).on( "change", "#acepto", function() {

                //Si el check esta marcado habilito el botón
                if ($(this).prop("checked")) {
                    $('#btn_solicitar_boleto').prop("disabled", false);
                    $('#btn_solicitar_boleto').attr("disabled", false);
                }
                else {//sino lo deshabilito
                    $('#btn_solicitar_boleto').prop("disabled", true);
                    $('#btn_solicitar_boleto').attr("disabled", true);
                }
                
            });

            $( id ).on( "submit", "#js_form_tramite", function(event) {

                var csrf = $(this).find("input[name='__csrf']").val();

                kernel.ajax.call($(this).attr('action'), {
                    type: 'POST',
                    data: {
                        '__csrf': csrf
                    },
                    success: function(response) {

                        $("#kernel_contenido").html(response.cont);

                    }
                });

                event.preventDefault();
                return false;
            });

        }
    }

});