function cambio_institucion()
{
	if ($('#form_actuacion_docente-institucion').val() === no_seleccion) {
		mostrar('div_institucion_otra');
	} else {
		ocultar('div_institucion_otra', true);
	}
}

function cambio_institucion_otra()
{
	if ($('#form_actuacion_docente-institucion_otra').val() !== '') {
		$('#form_actuacion_docente-institucion').val(no_seleccion);
		set_solo_lectura('form_actuacion_docente-institucion', true);
	} else {
		set_solo_lectura('form_actuacion_docente-institucion', false);
	}
}

kernel.renderer.registrar_pagelet('actuacion_docente', function(info) {
    var id = '#' + info.id;

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
		cambio_institucion();
		$('#form_actuacion_docente-institucion').on('change', function() { cambio_institucion(); });
		
		cambio_institucion_otra();
		$('#form_actuacion_docente-institucion_otra').on('keyup', function() { cambio_institucion_otra(); });
		
		//---- Date Picker -------------------------------------------------
		$.datepicker.setDefaults( $.datepicker.regional["es"] );
		$(id).find('input.date').datepicker({
			changeMonth: true,
			changeYear: true,
			firstDay:0,
			yearRange: "-70:+0"
		});
		
		$('#form_actuacion_docente').submit(function() {
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
	
		valido = validar_institucion();
		valido = valido & validar_campo('cargo');
		valido = valido & validar_campo('materia');
		valido = valido & validar_campo('fecha_desde');
		valido = valido & validar_fechas();
		
		return valido;
	}
	
	function validar_institucion()
	{
		if ($('#form_actuacion_docente-institucion').val() === no_seleccion) {
			if ($('#form_actuacion_docente-institucion_otra').val() === '') {
				$('#msg-institucion').html(info.error_vacio);
				$('#cg-institucion').addClass('error');
				return false;
			}
		}
		return true;
	}
	
	function validar_campo(campo)
	{
		var valor = $('#form_actuacion_docente-'+campo).val();
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
	
	function validar_fechas()
	{
		var salida = true;
		var fecha_desde = $('#form_actuacion_docente-fecha_desde').val();
		if (fecha_desde !== '') {
			// Se valida que la fecha no sea anterior a 1900
			var fecha_limite = '1/1/1901';
			if (Date.parse(fecha_desde) < Date.parse(fecha_limite)) {
					$('#msg-fecha_desde').html(info.error_fecha_antigua);
					$('#cg-fecha_desde').addClass('error');
					salida = false;
				} else {
					$('#msg-fecha_desde').html('');
					$('#cg-fecha_desde').removeClass('error');
					salida = true;
				}
			
			// Se valida que la fecha desde sea anterior a la fecha actual
			if (salida === true) { 
				var d = new Date();
				var hoy = d.getDate() + '/' + (d.getMonth() + 1) + '/' + d.getFullYear();

				if (Date.parse(hoy) < Date.parse(fecha_desde)) {
					$('#msg-fecha_desde').html(info.error_fecha_posterior);
					$('#cg-fecha_desde').addClass('error');
					salida = false;
				} else {
					$('#msg-fecha_desde').html('');
					$('#cg-fecha_desde').removeClass('error');
					salida = true;
				}
			}
			
			// Se valida que la fecha hasta sea posterior a la fecha desde
			if (salida === true) { 
				var fecha_hasta = $('#form_actuacion_docente-fecha_hasta').val();
				if (fecha_hasta !== '') {
					if (Date.parse(fecha_hasta) < Date.parse(fecha_desde)) {	
						$('#msg-fecha_hasta').html(info.error_fechas);
						$('#cg-fecha_hasta').addClass('error');
						salida = false;
					} else {
						$('#msg-fecha_hasta').html('');
						$('#cg-fecha_hasta').removeClass('error');
						salida = true;
					}
				}
			}
		} 
		return salida;
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