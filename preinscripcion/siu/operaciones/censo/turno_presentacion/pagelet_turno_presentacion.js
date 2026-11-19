var _info;

kernel.renderer.registrar_pagelet('turno_presentacion', function(info) {
	_info = info;
	var id = '#' + info.id;
	return {
		onload: function() {
			
			//---- Date Picker -------------------------------------------------
			$.datepicker.setDefaults($.datepicker.regional["es"]);
			$(id).find('input.date').datepicker({
				changeMonth: true,
				changeYear: true,
				firstDay: 0,
				minDate: info.fecha_desde,
				maxDate: info.fecha_hasta,
				beforeShowDay: function(fecha) {
					fecha = $.datepicker.formatDate('d/m/yy', fecha);
					var habilitada = jQuery.inArray(fecha, info.fechas_disponibles) !== -1;
					return [habilitada, '', ''];
				},
				onSelect: seleccion_fecha
			});
			
			$('.js-btnEliminar').on('click', function() {
				var ok = accion_eliminar($(this).data('ra_nombre'));
				if (!ok) {					
					return false;
				}
			});
		}
	};
	
	function accion_eliminar(ra_nombre)
	{
		var mensaje;
		mensaje = info.msj_confirmar_baja + ra_nombre + '?';
		if (confirm(mensaje)) {
			return true;
		}
		return false;
	}
	
});

//------------------------------------------------------------------------------

function seleccion_fecha()
{
	var _fecha = $('#form_turno-fecha').val();
	
	$.ajax({
		url: _info.url_franjas,
		dataType: 'json',
		data: {
			fecha: _fecha,
			ra: _info.ra
		},
		type: 'post',
		success: function(data) {
			$('#form_turno-franja').children().remove();					
			$.each(data, function(key, value) {
				$('#form_turno-franja').append(
					$('<option></option>').val(key).html(value)
				);
			});
			ordenar_combo('form_turno-franja');
			set_solo_lectura('form_turno-franja', false);
		}
	});	
}