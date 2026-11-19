var url_cascada_nac;

function set_solo_lectura(elemId, solo_lectura) 
{
	elemId = '#' + elemId;
	if (solo_lectura) {
		$(elemId).attr('disabled', true);
	} else {
		$(elemId).attr('disabled', false);
	}
}

function inicializar_nacionalidad()
{
	var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
	var pais_documento = $('#form_crear_usuario-pais_documento').val();
	
	if (nacionalidad === no_seleccion) {
		set_solo_lectura('form_crear_usuario-pais_documento', true);
		$('#form_crear_usuario-pais_documento').children().remove();
	} else {
		buscar_paises(nacionalidad);
		set_solo_lectura('form_crear_usuario-pais_documento', false);
		if (nacionalidad !== '2') {
			set_solo_lectura('form_crear_usuario-tipo_documento', false);
		} else {
			set_solo_lectura('form_crear_usuario-tipo_documento', true);
		}
	}
	
	$('#form_crear_usuario-pais_documento').val(pais_documento);
}

function cambio_nacionalidad() 
{
	var nacionalidad = $('#form_crear_usuario-nacionalidad').val();
	
	if (nacionalidad === no_seleccion) {
		set_solo_lectura('form_crear_usuario-pais_documento', true);
		$('#form_crear_usuario-pais_documento').children().remove();
	} else {
		buscar_paises(nacionalidad);
		set_solo_lectura('form_crear_usuario-pais_documento', false);
		if (nacionalidad !== '2') {
			set_solo_lectura('form_crear_usuario-tipo_documento', false);
		} else {
			set_solo_lectura('form_crear_usuario-tipo_documento', true);
		}
	}
	cambio_pais_documento();
}

function buscar_paises(_nacionalidad)
{
	$.ajax({
		url: url_cascada_nac,
		dataType: 'json',
		async: false,
		data: {nacionalidad: _nacionalidad},
		type: 'post',
		success: function(data) {
			$('#form_crear_usuario-pais_documento').children().remove();
			$.each(data, function(key, value) {
				$('#form_crear_usuario-pais_documento').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_crear_usuario-pais_documento');
		}
	});	
}

function cambio_pais_documento() 
{
	var valor = $('#form_crear_usuario-pais_documento').val();
	if ((valor === no_seleccion) || (valor === null)) {
		set_solo_lectura('form_crear_usuario-tipo_documento', true);
		$('#form_crear_usuario-tipo_documento').val(no_seleccion);
	} else {
		set_solo_lectura('form_crear_usuario-tipo_documento', false);
	}
	cambio_tipo_documento();
}

function cambio_tipo_documento() 
{
	var valor = $('#form_crear_usuario-tipo_documento').val();
	if (valor === no_seleccion) {
		set_solo_lectura('form_crear_usuario-nro_documento', true);;
		$('#form_crear_usuario-nro_documento').val('');
	} else {
		set_solo_lectura('form_crear_usuario-nro_documento', false);
	}
}

kernel.renderer.registrar_pagelet('crear_usuario', function(info) {
	var id = '#' + info.id;
	url_cascada_nac = info.url_paises;

	return {

		onload: function() {
			
			$('#form_crear_usuario').submit(function() {
				var $currentIFrame = $('#frameRC');
				$("#recaptcha_response_field").val($currentIFrame.contents().find("body #recaptcha_response_field").val());
				$("#recaptcha_challenge_field").val($currentIFrame.contents().find("body #recaptcha_challenge_field").val());
			});
			
			inicializar_nacionalidad();
			cambio_pais_documento();
			cambio_tipo_documento();
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_crear_usuario-nacionalidad').on('change', function() {cambio_nacionalidad();});
			$('#form_crear_usuario-pais_documento').on('change', function() {cambio_pais_documento();});
			$('#form_crear_usuario-tipo_documento').on('change', function() {cambio_tipo_documento();});
			
			//------------------------------------------------------------------
			
			$('#frameRC').contents().on('keypress', function(e) {
				 if(e.which === 13) {
					$('#form_crear_usuario').submit();
				}
			});
		}
	}
})
