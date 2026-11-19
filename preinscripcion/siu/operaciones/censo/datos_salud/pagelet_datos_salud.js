kernel.renderer.registrar_pagelet('datos_salud', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {
            
            if (info.autosave) {
                $(id).autosave();
            }

            $('#form_datos_salud').submit(function () {
                var hubo_cambios = formulario_hubo_cambios();
                if (hubo_cambios) {
                    var $form = $(this);
                    kernel.ajax.load($form.attr('action'), id, {
                        historia: false,
                        type: 'POST',
                        data: $form.serializeArray(),
                        success: function (response) {
                            if (response.info_estado.exitoso) {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-success',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                });
                            } else {
                                kernel.ui.show_mensaje(response.info_estado.mensaje, {
                                    tipo: 'alert-error',
                                    until_interaction: false,
                                    timeout: 5000,
                                    fadeout: 2000
                                })
                            }
                            // Si corresponde, se desactiva el autosave
                            if (info.autosave) {
                                $(id).autosave('disable');
                            }
                            kernel.renderer.pagelet('datos_salud').onload();
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
        }
    }
});