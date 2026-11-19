/**
 * Cambio del campo "Condición de actividad durante la semana pasada"
 */
function cambio_trabajo_existe()
{
	if ($('#form_allegados-trabajo_existe').val() === '1') {
		mostrar('div_datos_trabajo');
	} else {
		ocultar('div_datos_trabajo', true);
	}
}

kernel.renderer.registrar_pagelet('allegados', function(info) {
    var id = '#' + info.id;

	return {
		
        onload: function() {
			//---- Inicialización de estado de visibilidad de secciones --------
			cambio_trabajo_existe();
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_allegados-trabajo_existe').on('change', function() { cambio_trabajo_existe(); });
			
			//------------------------------------------------------------------
			//---- Selector de localidad (período de clases) -------------------
			//------------------------------------------------------------------
			
			//---- Combo de países para el selector de localidades -------------
			handle_combo_pais_localidad('form_allegados-pais', 'form_allegados-localidad');
			
			//---- Lista de localidades (autocompletado) -----------------------
			if ($("#form_allegados-localidad_descr").val() !== '') {
				set_solo_lectura('form_allegados-localidad_descr', true);
				$("#localidad_help").hide();
			} else {
				$("#localidad_help").show();
			}
			
			$("#form_allegados-localidad_descr").attr('campo_id', 'form_allegados-localidad');
			$("#form_allegados-localidad_descr").attr('ayuda', 'localidad_help');
			$("#form_allegados-localidad_descr").attr('campo_pais', 'form_allegados-pais');
			$("#form_allegados-localidad_descr").attr('campo_cp', 'form_allegados-codigo_postal');
			$("#form_allegados-localidad_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_localidades,
					  dataType: "json",
					  data: {
						term : request.term,
						pais : $('#form_allegados-pais').val()
					  },
					  success: function(data) {
						response(data);
					  }, 
                                          // A value of 0 means there will be no timeout
                                          timeout: 0 
					});
				},
				minLength: $("#js-ui-autocomplete-min-length").val(),	
				select: localidad_seleccionada_cp,
				focus: localidad_foco_cp
				
			});
			
			$('#btnSelectorLoc').on('click', function() {
				$.facebox(info.selector_localidad);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_allegados-localidad';
				_info['retorno_descr'] = 'form_allegados-localidad_descr';
				_info['retorno_cp'] = 'form_allegados-codigo_postal';
				_info['ayuda'] = 'localidad_help';
				_info['campo_pais'] = 'form_allegados-pais';
				inicializar_popup_localidades(_info);
			});
			
			$('#btnLimpiarLoc').on('click', function() {
				$('#form_allegados-localidad').val('null');
				$('#form_allegados-localidad_descr').val('');
				$('#form_allegados-codigo_postal').val('');
				set_solo_lectura('form_allegados-localidad_descr', false);
				$('#form_allegados-codigo_postal').children().remove();		
				set_solo_lectura('form_allegados-codigo_postal', true);
				handle_combo_pais_localidad('form_allegados-pais', 'form_allegados-localidad');
				$("#localidad_help").show();
			});
			
			$('#btnSelectorCP').on('click', function() {
				var localidad = $('#form_allegados-localidad').val();
				if ((localidad === 'null') || (localidad === '')) {
					return false;
				} else {
					$.facebox(info.selector_cp);
					var _info = new cloneObject(info);
					_info['retorno_cp'] = 'form_allegados-codigo_postal';
					_info['localidad'] = localidad;
					inicializar_popup_cps(_info);
				}
			});
			
			$('.js-btnEliminar').on('click', function() {
				return accion_eliminar();
			});
				
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
	
	//--------------------------------------------------------------------------
	//---- Función autocompletar (localidades) ---------------------------------
	//--------------------------------------------------------------------------
			
	function localidad_seleccionada_cp(event, ui)
	{
		var campo_id = event.target.attributes.campo_id.nodeValue;
		var campo_descr = event.target.attributes.id.nodeValue;
		var ayuda = event.target.attributes.ayuda.nodeValue;
		var campo_pais = event.target.attributes.campo_pais.nodeValue;
		var campo_cp = event.target.attributes.campo_cp.nodeValue;

		var localidad = ui.item.value.localidad;
		var localidad_descr = ui.item.value.localidad_descr;

		chequear_cp_unico(localidad, campo_cp);

		$("#"+campo_id).val(localidad);
		$("#"+campo_descr).val(localidad_descr);
		$("#"+ayuda).hide();
		$("#"+campo_pais).hide();
		event.preventDefault();
		
		set_solo_lectura(campo_descr, true);
	}
	
	function localidad_foco_cp(event)
	{
		event.preventDefault();
	}
	
	/**
	 * Si la localidad tiene un único código postal, lo completa automáticamente
	 */
	function chequear_cp_unico(_localidad, _campo_cp)
	{
		$.ajax({
			url: info.url_cp_unico,
			dataType: 'json',
			data: {localidad: _localidad},
			type: 'post',
			success: function(data) {
				$('#'+_campo_cp).val(data.cp);
			}
		});	
	}
	
});