kernel.renderer.registrar_pagelet('copia', function(info) {
	var id = '#' + info.id;

	return {
		onload: function() {
			$('#form_copia').submit(function() {
				if (validar()) {
					return true;
				} else {
					return false;
				}
			})
		}
	}
	
	function validar()
	{
		var valido;
		valido = validar_campo('tipo_propuesta_origen');
		valido = valido & validar_campo('tipo_propuesta_destino');
		if (valido) {
			valido = validar_selecciones();
		}
		return valido;
	}

	function validar_campo(campo)
	{
		var valor = $('#form_copia-'+campo).val();
		if ((valor == no_seleccion) || (valor == null)) {
			$('#msg-'+campo).html(info.error_vacio);
			$('#cg-'+campo).addClass('error');
			return false;
		} else {
			$('#msg-'+campo).html('');
			$('#cg-'+campo).removeClass('error');
			return true;
		}
	}
	
	function validar_selecciones()
	{
		var valor_origen = $('#form_copia-tipo_propuesta_origen').val();
		var valor_destino = $('#form_copia-tipo_propuesta_destino').val();
		
		if (valor_origen == valor_destino) {
			$('#msg-tipo_propuesta_destino').html(info.error_tipos_iguales);
			$('#cg-tipo_propuesta_destino').addClass('error');
			return false;
		}  else {
			$('#msg-tipo_propuesta_destino').html('');
			$('#cg-tipo_propuesta_destino').removeClass('error');
			return true;
		}
	}
	
})
