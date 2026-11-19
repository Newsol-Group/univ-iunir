kernel.renderer.registrar_pagelet('contacto', function (info) {

    var id = '#' + info.id;
    
    return {
        onload: function () 
        {
            //cargo los span ocultos para luego mostrarlos al validar
            $('#form_id_nombres').append("<span class='help-inline' id='js-error-nombre'></span>");
            $('#form_id_nro_documento').append('<span class="help-inline" id="js-error-nro_documento"></span>');
            $('#form_id_email').append('<span class="help-inline" id="js-error-email"></span>');
            $('#form_id_mensaje').append('<span class="help-inline" id="js-error-mensaje"></span>');
            
            //VALIDAR NOMBRE Y APELLIDO
            $(id).on('blur', '#form_contacto-nombres', function ()
            {
                validarNombre();
            });
            
            function validarNombre()
            {
                var valido = true;
                var nombre = $('#form_contacto-nombres').val();
                
                if(nombre == ""){
                    $('#form_id_nombres').addClass('error');
                    $('#js-error-nombre').text(info.mensajes.error_vacio).show();
                    valido = false;
                }                
                if(valido){                    
                    $('#js-error-nombre').hide();
                    $('#form_id_nombres').removeClass('error');
                }
                return valido;
            }
            
            //VALIDAR DNI
            $(id).on('blur', '#form_contacto-nro_documento', function ()
            {
                validarDni();
            });            
            function checkDNI(dni){
                var ex_regular_dni = RegExp(info.regexp_doc);; 
                if(ex_regular_dni.test (dni) == false){
                     return true;
                }else{
                   return false;
                 }
            }
            function validarDni()
            {
                var valido = true;
                var nombre = $('#form_contacto-nro_documento').val();
                
                if(nombre == ""){
                    $('#form_id_nro_documento').addClass('error');
                    $('#js-error-nro_documento').text(info.mensajes.error_vacio).show();
                    valido = false;
                }else{
                    if(checkDNI(nombre)){
                        $('#form_id_nro_documento').addClass('error');
                        $('#js-error-nro_documento').text(info.mensajes.error_dni).show();
                        valido = false;
                    }
                }

                if(valido){
                    $('#js-error-nro_documento').hide();
                    $('#form_id_nro_documento').removeClass('error');
                }
                return valido;
            }
            
            //VALIDAR EMAIL
            $(id).on('blur', '#form_contacto-email', function ()
            {
                validarEmail();
            });  
            function checkEmail(email){
                var ex_regular_email = new RegExp(info.regexp_email,'i'); 
                if(ex_regular_email.test(email) == false){
                     return true;
                }else{
                   return false;
                 }
            }
            function validarEmail()
            {
                var valido = true;
                var nombre = $('#form_contacto-email').val();
                
                if(nombre == ""){
                    $('#form_id_email').addClass('error');
                    $('#js-error-email').text(info.mensajes.error_vacio).show();
                    valido = false;
                }else{
                    if(checkEmail(nombre)){
                        $('#form_id_email').addClass('error');
                        $('#js-error-email').text(info.mensajes.error_email).show();
                        valido = false;
                    }
                }                

                if(valido){
                    $('#js-error-email').hide();                    
                    $('#form_id_email').removeClass('error');
                }
                return valido;
            }

            //VALIDAR MENSAJE TEXTAREA
            $(id).on('blur', '#form_contacto-mensaje', function ()
            {
                validarMensaje();
            });            
            
            function validarMensaje()
            {
                var valido = true;
                var nombre = $('#form_contacto-mensaje').val();
                
                if(nombre == ""){
                    $('#form_id_mensaje').addClass('error');
                    $('#js-error-mensaje').text(info.mensajes.error_vacio).show();
                    valido = false;
                }                
                if(valido){                    
                    $('#js-error-mensaje').hide();
                    $('#form_id_mensaje').removeClass('error');
                }
                return valido;
            }
            
            //SUBMIT
            $(id).on('submit', '#form_contacto', function (event)
            {
                if(validarNombre() && validarDni() && validarEmail() && validarMensaje()){
                    return true;
                }else{
                    event.preventDefault();
                    return false;
                }
            });
        }
    }

});
