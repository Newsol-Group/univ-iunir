kernel.renderer.registrar_pagelet('validador', function (info) {
    var id = '#' + info.id;

    return {

        onload: function () {
            $(id + ' #guarani_form_validar').on('submit', function () {

                var regexp_cod_validacion   = new RegExp(info.regexp_cod_validacion);
                var regexp_documento        = new RegExp(info.regexp_documento);
                var documento = $(id + ' #documento').val();
                var codigo_validacion = $(id + ' #codigo_valid').val();
                $(id + " br").remove();
                $(id + " #documento_error").remove();
                $(id + " #codigo_valid_error").remove();
                $(id + " #recaptcha_error").remove();
                var hubo_error = false;

                if (documento != "") {        
                    if (regexp_documento.test(documento)) {//Codigo de validacion valido            
                        $(id + " #control_group_documento").removeClass('error');

                    } else {//Codigo de validacion invalido
                        
                        $(id + " #documento").after('<br><span id="documento_error" class="help-inline">' + info.mensajes.error_documento_invalido + '</span>');
                        $(id + " #control_group_documento").addClass('error');
                        $(id + " #documento").prop("readonly", false);
                        $(id + " #documento").focus();
                        hubo_error = true;

                    }

                } else {
                    $(id + " #documento").after('<br><span id="documento_error" class="help-inline">' + info.mensajes.error_debe_ingresar_documento +'</span>');
                    $(id + " #control_group_documento").addClass('error');
                    $(id + " #documento").prop("readonly", false);
                    $(id + " #documento").focus();
                    hubo_error = true;
                }

                if (codigo_validacion != "") {

                    if (regexp_cod_validacion.test(codigo_validacion)) {//Codigo de validacion valido
                        
                        $(id + " #control_group_codigo_valid").removeClass('error');

                    } else {//Codigo de validacion invalido

                        $(id + " #codigo_valid").after('<br><span id="codigo_valid_error" class="help-inline">' + info.mensajes.error_codigo_validacion_invalido + '</span>');
                        $(id + " #control_group_codigo_valid").addClass('error');
                        $(id + " #codigo_valid").prop("readonly", false);
                        $(id + " #codigo_valid").focus();
                        hubo_error = true;

                    }

                } else {
                    $(id + " #codigo_valid").after('<br><span id="codigo_valid_error" class="help-inline">' + info.mensajes.error_debe_ingresar_codigo_validacion + '</span>');
                    $(id + " #control_group_codigo_valid").addClass('error');
                    $(id + " #codigo_valid").prop("readonly", false);
                    $(id + " #codigo_valid").focus();
                    hubo_error = true;
                }
                if (info.usa_recaptcha){
                    var response = grecaptcha.getResponse();
                    if(response.length == 0){
                        $(id + " .alert-info").after('<div id="recaptcha_error" class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>' + info.mensajes.error_recaptcha + '</div>');
                        hubo_error = true;
                    }
                }
                if (hubo_error) {
                    return false;
                }
            });
        }
    };
});
