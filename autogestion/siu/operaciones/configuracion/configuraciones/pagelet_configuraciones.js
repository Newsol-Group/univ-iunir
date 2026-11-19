kernel.renderer.registrar_pagelet('configuraciones', function(info) {
	var id = '#' + info.id;
    var cambiaron_checks_notificaciones = false;

	function guardar_checks() {
		var mail_notificacion = 0;

		if ($('#mail-notificacion').prop("checked")){
			mail_notificacion = 1;
		}

		var error = false;
		$('#mail').removeClass('error_val');
		var mail = $('#mail').val();
        //Si el mail termina con un punto se lo saco
        if(mail.charAt(mail.length-1) == "\.")
        {
            mail = mail.slice(0, -1);
        }
		var options_error = {
			tipo: 'alert-error'
		}

		if (mail != "")
		{
			if (!isValidEmailAddress(mail))
			{
				error = true;
				$('#mail').addClass('error_val');
				kernel.ui.show_mensaje('E-mail inválido', options_error);
			}
		} else {

            error = true;
            $('#mail').addClass('error_val');
            kernel.ui.show_mensaje('Dato obligatorio', options_error);

        }

		if (!error){
			kernel.ajax.call($('#js-form-checks').attr('action'), {
				type: 'post',
				data: {recibe_mail_mensajes: mail_notificacion, correo: mail},
				success: function(data) {

                    $('#js-btn-guardar-checks').hide();

                    $('#mail-notificacion').attr("checked", $('#mail-notificacion').prop( "checked" ));

                    cambiaron_checks_notificaciones = false;

					if (data.cod == '2') { // es cambio de mail
						var msg = $('<div></div>');
						msg.append(msg_cambio_mail(data.mail_new));
						$('#mail').val(data.mail_old);
						kernel.ui.show_mensaje(msg, {until_interaction: true});

                        if($('#alert_solicitud_confirmacion_email_enviada').length == 0){
                            var alert_solicitud_confirmacion_email_enviada = $('<div class="alert alert-warning" id="alert_solicitud_confirmacion_email_enviada"></div>');
                            $(alert_solicitud_confirmacion_email_enviada).html(data.alert_solicitud_confirmacion_email_enviada);
                            $( "#email-control-group" ).after( $(alert_solicitud_confirmacion_email_enviada) );
                        }
                        else{
                            $('#alert_solicitud_confirmacion_email_enviada').html(data.alert_solicitud_confirmacion_email_enviada);
                        }

					} else {
						kernel.ui.show_mensaje('Guardado con éxito');
					}
				}
			});
		}	
	}

	function guardar_password() {
		$('#mensaje-pass').hide();
		var error = false;
		$('#password_actual').removeClass('error_val');
		$('#password').removeClass('error_val');
		$('#password1').removeClass('error_val');
		var options_error = {
			tipo: 'alert-error'
		}
		if ($('#password_actual').val() == ""){
			error = true;
			$('#password_actual').addClass('error_val');			
			kernel.ui.show_mensaje(info.mensajes.password_actual_error, options_error);
		} else {
			if ($('#password').val() == ""){
				error = true;
				$('#password').addClass('error_val');
				kernel.ui.show_mensaje(info.mensajes.passworderror, options_error);
			} else {
				if ($('#password').val() != $('#password1').val()){
					error = true;
					$('#password1').addClass('error_val');
					kernel.ui.show_mensaje(info.mensajes.password1error, options_error);
				}
			}
		}

		if (!error){
			kernel.ajax.call($('#js-form-pass').attr('action'), {
				type: 'post',
				data: {password_actual: $('#password_actual').val(), password: $('#password').val()},
				error: function(data) {
					var options = {
						tipo: 'alert-error'
					}
					kernel.ui.show_mensaje(data.cont, options);
				}, success: function(data) {
					$('#password_actual').val("");
					$('#password').val("");
					$('#password1').val("");
					kernel.ui.show_mensaje(data.cont);					
				}
			});
		}
	}

	function msg_cambio_mail(mail) {
		var tmp = $('<div>'+kernel.util.trans(info.mensajes.mail_token_cambio, {mail:mail})+'</div>');

		$('<span class="cerrar-msg icon-remove"></span>').on("click", function(){
			$('#_msg_placeholder').fadeOut();
		}).appendTo(tmp);
		return tmp;
	}
	
	return {
		onload: function() {

			var checkbox_mail_notificacion_default = ($('#mail-notificacion').attr( "checked" ) == 'checked')? true : false;
            if(!info.tiene_algun_email)
            {
                $('#checks-notificaciones').hide();
            }

            if (info.mensajes.cambio_mail_exitoso) {
                kernel.ui.show_mensaje(info.mensajes.cambio_mail_exitoso, {tipo: 'alert-success'});
            }
            
            if (info.mensajes.token_incorrecto_expirado) {
                kernel.ui.show_mensaje(info.mensajes.token_incorrecto_expirado, {tipo: 'alert-error'});
            }
            
            if (info.mensajes.token_gestionado_otra_persona) {
                kernel.ui.show_mensaje(info.mensajes.token_gestionado_otra_persona, {tipo: 'alert-error'});
            }

			$('#mensaje-pass').hide();

			var btn_guardar_checks = $('#js-btn-guardar-checks');
			var btn_guardar_perfil = $('#js-btn-guardar-perfil');
			var btn_guardar_propuesta = $('#js-btn-guardar-propuesta');

            btn_guardar_checks.hide();
            btn_guardar_perfil.hide();
            btn_guardar_propuesta.hide();


            $('#mail').on('keyup', function() {


                if(isValidEmailAddress($(this).val()))//Si el email es valido
                {

                    if($(this).val() != info.email)//Si el email del input es distinto al que ya tiene
                    {
                        btn_guardar_checks.show();
                    }
                    else//Si el email del input es igual al que ya tiene...
                    {
                        if(cambiaron_checks_notificaciones)//...pero cambiaron los checkboxes
                        {
                            btn_guardar_checks.show();
                        }
                        else//...y los checkboxes no cambiaron
                        {
                            btn_guardar_checks.hide();
                        }

                    }

                }
                else//Si el email es invalido
                {
                    btn_guardar_checks.hide();
                }

				return true;
			});

            $('#mail-notificacion').on('change', function(){
                var cambio_checkbox_mail_notificacion = (checkbox_mail_notificacion_default != $('#mail-notificacion').prop( "checked" ));

                if(cambio_checkbox_mail_notificacion)//Si los checkboxes cambiaron...
                {
                    cambiaron_checks_notificaciones = true;
                    if (isValidEmailAddress($('#mail').val()))//...y el email es valido
                    {
                        btn_guardar_checks.show();
                    }

                }
                else//Si los checkboxes no cambiaron...
                {
                    cambiaron_checks_notificaciones = false;
                    if ((!isValidEmailAddress($('#mail').val())) || ($('#mail').val() == info.email))//...y el email del input es invalido o igual al establecido
                    {
                        btn_guardar_checks.hide();
                    }

                }
            });

			btn_guardar_checks.on("click", function (){
				checkbox_mail_notificacion_default = $('#mail-notificacion').prop( "checked" );
				$('#js-form-checks').submit();
			});

			$('#js-form-checks').submit(function (){
				guardar_checks();
				return false;
			});

			var btn_guardar_password = $('#js-btn-guardar-password');
			btn_guardar_password.hide();
			$('#password_actual').on('keyup', function(){
				if ($(this).val() != '' && $('#password').val() != '' && $('#password1').val() != '')
					btn_guardar_password.show();
				else
					btn_guardar_password.hide();
			});
			$('#password').on('keyup', function(){
				if ($('#password_actual').val() != '' && $(this).val() != '' && $('#password1').val() != '')
					btn_guardar_password.show();
				else
					btn_guardar_password.hide();
			});
			$('#password1').on('keyup', function(){
				if ($('#password_actual').val() != '' && $('#password').val() != '' && $(this).val() != '')
					btn_guardar_password.show();
				else
					btn_guardar_password.hide();
			});
			btn_guardar_password.on("click", function (){
				$('#js-form-pass').submit();
			});

			$('#js-form-pass').submit(function (){
				guardar_password();
				return false;
			});					
			$('#js-form-perfil').submit(function (){
				guardar_perfil_default();
				return false;
			});		
			
			$('#js-btn-guardar-perfil').on("click", function (){
				$('#js-form-perfil').submit();
			});
			$('#js-select-perfil-def').change(function (){
				if($('#js-select-perfil-def').val() != info.tipo_usuario_inicial)
				{
					btn_guardar_perfil.show();
				}
				else
				{
					btn_guardar_perfil.hide();
				}	
			});
			
			$("#js-form-propuesta").on("submit", function() {
				guardar_propuesta_inicial();
				return false;
			});
			
			$("#js-btn-guardar-propuesta").on("click", function() {
			    $("#js-form-propuesta").submit();
			});
			
			$("#js-select-propuesta-def").on("change", function() {
			    if($(this).val() != info.propuesta_inicial)
			    {
				btn_guardar_propuesta.show();
			    }
			    else
			    {
				btn_guardar_propuesta.hide();
			    }	
			});
		}
	}

	function isValidEmailAddress(emailAddress) {
		var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
		//Validacion que desempeña HTML5 para los <input type="email">
		//var pattern = new RegExp(/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/i);
		return pattern.test(emailAddress);
	}
	
	function guardar_perfil_default() {
		
		kernel.ajax.call($('#js-form-perfil').attr('action'), {
			type: 'post',
			data: {perfil_defecto: $('#js-select-perfil-def').val()},
			error: function(data) {
				var options = {
					tipo: 'alert-error'
				}
				kernel.ui.show_mensaje('Error actualizando el perfil defecto a: '+ $('#js-select-perfil-def').val(), options);
			}, success: function(data) {
				info.tipo_usuario_inicial = $('#js-select-perfil-def').val();
				$('#js-btn-guardar-perfil').hide();
				kernel.ui.show_mensaje('Se ha actualizado el perfil defecto por: '+ $('#js-select-perfil-def').val());					
			}
		});
	}
	
	function guardar_propuesta_inicial() {
		
		kernel.ajax.call($('#js-form-propuesta').attr('action'), {
			type: 'post',
			data: {propuesta_inicial: $('#js-select-propuesta-def').val()},
			error: function(data) {
				var options = {
					tipo: 'alert-error'
				}
				kernel.ui.show_mensaje('Error actualizando la propuesta de ingreso por defecto.', options);
			}, success: function(data) {
				info.propuesta_inicial = $('#js-select-propuesta-def').val();
				$('#js-btn-guardar-propuesta').hide();
				$('#js-propuesta-option-seleccione').remove();
				kernel.ui.show_mensaje('Se ha actualizado la propuesta de ingreso por defecto.');					
			}
		});
	}
	
	
})