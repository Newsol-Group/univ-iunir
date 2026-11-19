kernel.renderer.registrar_pagelet('login', function (info)
{
    var id = '#' + info.id;

    return {
        onload: function ()
        {
            $('#usuario').focus();

            // Si la contraseña se recupero con éxito muestro mensaje
            if (typeof info.mensajes.msg_contrasenia_recuparada_exito !== 'undefined') {
                kernel.ui.show_mensaje(info.mensajes.msg_contrasenia_recuparada_exito, {
                    tipo: 'alert-success',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
                                                                
            $('#guarani_form_login').submit(function (event)
            {
                var usuario = $("#usuario").val();
                var password = $("#password").val();
                var hubo_error = false;
                var msj_error = "";

                if (usuario.trim() == "") {
                    hubo_error = true;
                    msj_error = info.mensajes.id_requerido;
                    $("#usuario").val("");
                    $("#usuario").focus();
                } else if (password.trim() == "") {
                    hubo_error = true;
                    msj_error = info.mensajes.clave_requerida;
                    $("#password").val("");
                    $("#password").focus();
                }

                if (hubo_error) {
                    event.preventDefault();
                    //Si ya existe el mensaje de error le cambio el texto
                    if ($("#error_login").length > 0) {
                        $("#error_login").html(msj_error);
                    } else {//sino lo agrego
                        $(this).after('<div id="error_login" class="alert alert-error">' + msj_error + '</div>');
                    }
                    return false;
                }

            });
            
        }
    };
});
