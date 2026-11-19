var _info;

function cambio_tipo_contacto()
{
	var tipo = $('#form_datos_contacto-contacto_tipo').val();
	if (tipo === no_seleccion) {
		ocultar('otros_contactos', true);
		ocultar('email', true);
		ocultar('telefono', true);
	} else if ((tipo === _info.tipo_MP) || (tipo === _info.tipo_MI) || (tipo === _info.tipo_MS)) {
		ocultar('otros_contactos', true);
		mostrar('email');
		ocultar('telefono', true);
	} else if ((tipo === _info.tipo_OTRO) || (tipo === _info.tipo_IN) || (tipo === _info.tipo_FB) || (tipo === _info.tipo_TW)||(tipo === _info.tipo_IG)){
		mostrar('otros_contactos');
                if ( (tipo === _info.tipo_IN) || (tipo === _info.tipo_FB) || (tipo === _info.tipo_TW)||(tipo === _info.tipo_IG)){
                    $( "#form_datos_contacto-otros_contactos" ).attr("placeholder",  $( "#form_datos_contacto-contacto_tipo option:selected" ).text().toLowerCase()+".com/username");
                }else{
                    $( "#form_datos_contacto-otros_contactos" ).attr("placeholder","");
                }
		ocultar('email', true);
		ocultar('telefono', true);
	} else {
		ocultar('otros_contactos', true);
		ocultar('email', true);
		mostrar('telefono');
	}
	
}

kernel.renderer.registrar_pagelet('datos_contacto', function(info) {
    var id = '#' + info.id;
	_info = info;

	return {
        onload: function() {
			inicializar();
			
			$('.js-btnEliminar').on('click', function() {
				return accion_eliminar();
			});
				
        }
    }
	
	function inicializar()
	{
		cambio_tipo_contacto();
		$('#form_datos_contacto-contacto_tipo').on('change', function() { cambio_tipo_contacto(); });
		
		$('#form_datos_contacto').submit(function() {
			if (validar()) {
				return true;
			} else {
				return false;
			}
		});
	}
	
	function validar()
	{
		var valido;
		var tipo = $('#form_datos_contacto-contacto_tipo').val();
		if (tipo === no_seleccion) {
			$('#msg-contacto_tipo').html(info.error_vacio);
			$('#cg-contacto_tipo').addClass('error');
			valido = false;
		} else if ((tipo === _info.tipo_MP) || (tipo === _info.tipo_MI) || (tipo === _info.tipo_MS)) {
			valido = validar_campo('email');
                } else if ( (tipo === _info.tipo_OTRO) ||  (tipo === _info.tipo_IN) || (tipo === _info.tipo_FB) || (tipo === _info.tipo_TW)||(tipo === _info.tipo_IG)) {
			valido = validar_campo('otros_contactos');
		} else {
			valido = validar_campo('telefono_codigo_area');
			valido = valido & validar_campo('telefono_numero');
		}
		return valido;
	}
		
	function validar_campo(campo)
	{
		var valor = $('#form_datos_contacto-'+campo).val();
		if (valor === '') {
			$('#msg-'+campo).html(info.error_vacio);
			$('#cg-'+campo).addClass('error');
			return false;
		} else {
			$('#msg-'+campo).html('');
			$('#cg-'+campo).removeClass('error');
			return true;
		}
	}
		
	function accion_eliminar()
	{
		if (confirm(info.msj_confirmar_eliminar)) {
			submit();
			return true;
		}
		return false;
	}
	
});