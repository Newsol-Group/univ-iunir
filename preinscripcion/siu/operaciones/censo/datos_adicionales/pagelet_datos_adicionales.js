kernel.renderer.registrar_pagelet('datos_adicionales', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {
            
            if (info.autosave) {
                $(id).autosave();
            }

            $('#form_datos_adicionales').submit(function () {
                var hubo_cambios = formulario_hubo_cambios();
                if (hubo_cambios) {
                    var $form = $(this);
                    kernel.ajax.load($form.attr('action'), id, {
                        historia: false,
                        type: 'POST',
                        data: $form.serializeArray(),
                        success: function (response) {
                            if (response.info_estado.exitoso) {
                                if (response.info_estado.cambios_desde_impresion) {
                                    document.location.reload(true);
                                } else {
                                    kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                        tipo: 'alert-success',
                                        until_interaction: false,
                                        timeout: 5000,
                                        fadeout: 2000
                                    });
                                }
                            } else {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-error',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                });
                            }

                            // Si corresponde, se desactiva el autosave
                            if (info.autosave) {
                                $(id).autosave('disable');
                            }
                            kernel.renderer.pagelet('datos_adicionales').onload();
                        }
                    });
                } else {
                    kernel.ui.show_mensaje(info.msj_sin_cambios, {
                        tipo: 'alert-info',
                        until_interaction: false,
                        timeout: 5000,
                        fadeout: 2000
                    });
                }
                return false;
            });

            $('#form_datos_adicionales-insc_otra_inst').on('change', function () {
                cambio__insc_otra_inst();
            });
            cambio__insc_otra_inst();
        }
    }

    /**
     * Cambio del campo "¿Estás inscripto en otra institución?"
     */
    function cambio__insc_otra_inst()
    {
        if ($('#form_datos_adicionales-insc_otra_inst').is(':checked')) {
            mostrar('div_insc_otra_inst_descr');
        } else {
            ocultar('div_insc_otra_inst_descr', true);
        }
    }

    
});