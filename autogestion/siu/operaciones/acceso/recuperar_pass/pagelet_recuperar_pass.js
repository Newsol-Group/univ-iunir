var url_tipo_documentos;
kernel.renderer.registrar_pagelet('recuperar_pass', function (info)
{
    var id = '#' + info.id;

    return {
        onload: function ()
        {

            $('#js_nro_documento').html(info.mensajes.msj_tipo_documento_ayuda).hide();
            url_tipo_documentos = info.url_tipo_documentos;
            //Le doy foco al campo 'Número de documento'
            $('#guarani_form_recuperar_pass #documento').focus();

            //Evento que se ejecuta cada vez que se tipea en el campo 'Número de documento' o este pierde el foco
            $('#guarani_form_recuperar_pass #documento').on('change', evt_change_documento);
            $('#guarani_form_recuperar_pass #pais').on('change', evt_change_pais);
            
            function evt_change_pais()
            {
                buscar_tipo_documento();
            }
            function evt_change_documento()
            {

                var documento = $('#guarani_form_recuperar_pass #documento').val();
                var tipos = $('#guarani_form_recuperar_pass #tipo_doc').val();
                var pais = $('#guarani_form_recuperar_pass #pais').val();
                validar_documento(pais, tipos, documento);


                return true;

            }
            function buscar_tipo_documento()
            {
                var pais = $('#guarani_form_recuperar_pass #pais').val();
             $.ajax({
                    url: url_tipo_documentos,
                    dataType: 'json',
                    async: false,
                    data: {pais: pais},
                    type: 'post',
                    success: function (data)
                    {
                        $('#guarani_form_recuperar_pass #tipo_doc').children().remove();
                        $.each(data, function (key, value) {
                            $('#guarani_form_recuperar_pass #tipo_doc').append(
                                $('<option></option>').val(key).html(value)
                                );
             var pais = $('#guarani_form_recuperar_pass #pais').val();
            //paises miembros del mercosur
            var paises_mercosur =['54','55','595','591','593','57','598','592','51','597'];
            //Elimino los errores del documento
                if (paises_mercosur.includes(pais)===false){
                     $('#js_nro_documento').html(info.mensajes.msj_tipo_documento_ayuda).slideDown('slow');
                 }else{
                     $('#js_nro_documento').html(info.mensajes.msj_tipo_documento_ayuda).slideUp('slow');
                }
                        });
           }});


            
            }
            function validar_documento(_pais, _tipo_documento, nro_documento)
            {
                $.ajax({
                    url: url_tipo_documentos,
                    dataType: 'json',
                    async: false,
                    data: {pais: _pais, tipo_documento: _tipo_documento},
                    type: 'post',
                    success: function (data)
                    {
                        var exp_reg = new RegExp(data.exp_regular);
                        var documento = $('#guarani_form_recuperar_pass #documento');
                        $(documento).nextAll("div.cont_mensajes").remove();
                        documento.removeClass('js-error');
                        if (!exp_reg.test(nro_documento)) {
                            var notif = kernel.ui.armar_notificaciones('', [{
                                    mtipo: 'E',
                                    mcuerpo: data.mensaje
                                }]);
                            $(documento).nextAll("div.cont_mensajes").remove();
                            documento.after(notif);
                            documento.addClass('js-error');
                            documento.focus();
                        }
                    }
                });
            }

            //Evento que se ejecuta cada vez que cambia el valor del campo 'Tipo de documento'
            $('#guarani_form_recuperar_pass #tipo_doc').change(evt_change_tipo_documento);

            function evt_change_tipo_documento()
            {
                var documento = $('#guarani_form_recuperar_pass #documento');
                //Elimino los errores del documento
                $(documento).nextAll("div.cont_mensajes").remove();
                $(documento).removeClass('js-error');
                //Limpio el documento y le doy foco
                $(documento).val('').focus();
            }

            $('#guarani_form_recuperar_pass').submit(function ()
            {
                // se ejecuta keyup para que pase por la validación
                $(this).find('input[name=documento]').keyup();
                if ($(this).find('input[name=documento]').hasClass('js-error')) {
                    return false;
                }

            });

            $('#guarani_form_reset_pass').on("submit", function ()
            {
                $('#js-error_rpassword').hide();
                var rpassword = $('#guarani_form_reset_pass').find('#rpassword');
                var password = $('#guarani_form_reset_pass').find('#password');
                password.removeClass('error_val');
                rpassword.removeClass('error_val');

                if (password.val() == "") {
                    password.addClass('error_val');
                    return false;
                }

                if (rpassword.val() == "") {
                    rpassword.addClass('error_val');
                    return false;
                } else if (password.val() != rpassword.val()) {
                    $('#js-error_rpassword').show();
                    rpassword.addClass('error_val');
                    return false;
                }
            });
        }
    };
});
