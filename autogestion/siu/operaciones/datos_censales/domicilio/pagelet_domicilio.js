var _info;

function copiar_domicilio()
{
	$('#form_domicilio-procedencia_calle').val($('#form_domicilio-periodo_lectivo_calle').val());
	$('#form_domicilio-procedencia_numero').val($('#form_domicilio-periodo_lectivo_numero').val());
	$('#form_domicilio-procedencia_piso').val($('#form_domicilio-periodo_lectivo_piso').val());
	$('#form_domicilio-procedencia_departamento').val($('#form_domicilio-periodo_lectivo_departamento').val());
	$('#form_domicilio-procedencia_unidad').val($('#form_domicilio-periodo_lectivo_unidad').val());
	$('#form_domicilio-procedencia_localidad').val($('#form_domicilio-periodo_lectivo_localidad').val());
	$('#form_domicilio-procedencia_localidad_descr').val($('#form_domicilio-periodo_lectivo_localidad_descr').val());
	$('#form_domicilio-procedencia_codigo_postal').val($('#form_domicilio-periodo_lectivo_codigo_postal').val());
	$('#form_domicilio-procedencia_barrio').val($('#form_domicilio-periodo_lectivo_barrio').val());
	
	if ($('#form_domicilio-procedencia_localidad_descr').val() !== '') {
		set_solo_lectura('form_domicilio-procedencia_localidad_descr', true);
		$('#procedencia_localidad_help').hide();
	} else {
		set_solo_lectura('form_domicilio-procedencia_localidad_descr', false);
		$('#procedencia_localidad_help').show();
	}
	
	handle_combo_pais_localidad('form_domicilio-procedencia_pais', 'form_domicilio-procedencia_localidad');
}

//------------------------------------------------------------------------------

kernel.renderer.registrar_pagelet('domicilio', function(info) {
    var id = '#' + info.id;
	_info = info;
		
    return {
        onload: function() {
			
			//---- Copia de domicilio ------------------------------------------
			$('#btn_copiar_domicilio').on('click', function() {
				copiar_domicilio();
			});
			
			//------------------------------------------------------------------
			//---- Selector de localidad (período de clases) -------------------
			//------------------------------------------------------------------
			
			//---- Combo de países para el selector de localidades -------------
			handle_combo_pais_localidad('form_domicilio-periodo_lectivo_pais', 'form_domicilio-periodo_lectivo_localidad');
			
			//---- Lista de localidades (autocompletado) -----------------------
			if ($("#form_domicilio-periodo_lectivo_localidad_descr").val() !== '') {
				set_solo_lectura('form_domicilio-periodo_lectivo_localidad_descr', true);
				$("#periodo_lectivo_localidad_help").hide();
			} else {
				$("#periodo_lectivo_localidad_help").show();
			}
			
			$("#form_domicilio-periodo_lectivo_localidad_descr").attr('campo_id', 'form_domicilio-periodo_lectivo_localidad');
			$("#form_domicilio-periodo_lectivo_localidad_descr").attr('ayuda', 'periodo_lectivo_localidad_help');
			$("#form_domicilio-periodo_lectivo_localidad_descr").attr('campo_pais', 'form_domicilio-periodo_lectivo_pais');
			$("#form_domicilio-periodo_lectivo_localidad_descr").attr('campo_cp', 'form_domicilio-periodo_lectivo_codigo_postal');
			$("#form_domicilio-periodo_lectivo_localidad_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_localidades,
					  dataType: "json",
					  data: {
						term : request.term,
						pais : $('#form_domicilio-periodo_lectivo_pais').val()
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
			
			$('#btnSelectorLocPerLect').on('click', function() {
				$.facebox(info.selector_localidad);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_domicilio-periodo_lectivo_localidad';
				_info['retorno_descr'] = 'form_domicilio-periodo_lectivo_localidad_descr';
				_info['retorno_cp'] = 'form_domicilio-periodo_lectivo_codigo_postal';
				_info['ayuda'] = 'periodo_lectivo_localidad_help';
				_info['campo_pais'] = 'form_domicilio-periodo_lectivo_pais';
				inicializar_popup_localidades(_info);
			});
			
			$('#btnLimpiarLocPerLect').on('click', function() {
				$('#form_domicilio-periodo_lectivo_localidad').val('null');
				$('#form_domicilio-periodo_lectivo_localidad_descr').val('');
				$('#form_domicilio-periodo_lectivo_codigo_postal').val('');
				set_solo_lectura('form_domicilio-periodo_lectivo_localidad_descr', false);
				$('#form_domicilio-periodo_lectivo_codigo_postal').children().remove();		
				set_solo_lectura('form_domicilio-periodo_lectivo_codigo_postal', true);
				handle_combo_pais_localidad('form_domicilio-periodo_lectivo_pais', 'form_domicilio-periodo_lectivo_localidad');
				$("#periodo_lectivo_localidad_help").show();
			});
			
			$('#btnSelectorCP_PerLect').on('click', function() {
				var localidad = $('#form_domicilio-periodo_lectivo_localidad').val();
				if ((localidad === 'null') || (localidad === '')) {
					return false;
				} else {
					$.facebox(info.selector_cp);
					var _info = new cloneObject(info);
					_info['retorno_cp'] = 'form_domicilio-periodo_lectivo_codigo_postal';
					_info['localidad'] = localidad;
					inicializar_popup_cps(_info);
				}
			});
			
			//------------------------------------------------------------------
			//---- Selector de localidad (procedencia) -------------------------
			//------------------------------------------------------------------
			
			//---- Combo de países para el selector de localidades -------------
			handle_combo_pais_localidad('form_domicilio-procedencia_pais', 'form_domicilio-procedencia_localidad');
			
			//---- Lista de localidades (autocompletado) -----------------------
			if ($("#form_domicilio-procedencia_localidad_descr").val() !== '') {
				set_solo_lectura('form_domicilio-procedencia_localidad_descr', true);
				$("#procedencia_localidad_help").hide();
			} else {
				$("#procedencia_localidad_help").show();
			}
			
			$("#form_domicilio-procedencia_localidad_descr").attr('campo_id', 'form_domicilio-procedencia_localidad');
			$("#form_domicilio-procedencia_localidad_descr").attr('ayuda', 'procedencia_localidad_help');
			$("#form_domicilio-procedencia_localidad_descr").attr('campo_pais', 'form_domicilio-procedencia_pais');
			$("#form_domicilio-procedencia_localidad_descr").attr('campo_cp', 'form_domicilio-procedencia_codigo_postal');
			$("#form_domicilio-procedencia_localidad_descr").autocomplete({
				source: function(request, response) {
					$.ajax({
					  url: info.url_lista_localidades,
					  dataType: "json",
					  data: {
						term : request.term,
						pais : $('#form_domicilio-procedencia_pais').val()
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
			
			$('#btnSelectorLocProc').on('click', function() {
				$.facebox(info.selector_localidad);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_domicilio-procedencia_localidad';
				_info['retorno_descr'] = 'form_domicilio-procedencia_localidad_descr';
				_info['retorno_cp'] = 'form_domicilio-procedencia_codigo_postal';
				_info['ayuda'] = 'procedencia_localidad_help';
				_info['campo_pais'] = 'form_domicilio-procedencia_pais';
				inicializar_popup_localidades(_info);
			});
			
			$('#btnLimpiarLocProc').on('click', function() {
				$('#form_domicilio-procedencia_localidad').val('null');
				$('#form_domicilio-procedencia_localidad_descr').val('');
				$('#form_domicilio-procedencia_codigo_postal').val('');
				set_solo_lectura('form_domicilio-procedencia_localidad_descr', false);
				$('#form_domicilio-procedencia_codigo_postal').children().remove();		
				set_solo_lectura('form_domicilio-procedencia_codigo_postal', true);
				handle_combo_pais_localidad('form_domicilio-procedencia_pais', 'form_domicilio-procedencia_localidad');
				$("#procedencia_localidad_help").show();
			});
			
			$('#btnSelectorCP_Proc').on('click', function() {
				var localidad = $('#form_domicilio-procedencia_localidad').val();
				if ((localidad === 'null') || (localidad === '')) {
					return false;
				} else {
					$.facebox(info.selector_cp);
					var _info = new cloneObject(info);
					_info['retorno_cp'] = 'form_domicilio-procedencia_codigo_postal';
					_info['localidad'] = localidad;
					inicializar_popup_cps(_info);
				}
			});
			
			//---- Si corresponde, se activa el autosave -----------------------
			if (info.autosave) {
				$(id).autosave();
			}
			
			//---- Submit ------------------------------------------------------
			$('#form_domicilio').submit(function() {
				var hubo_cambios = formulario_hubo_cambios();
				if (hubo_cambios) {
					var $form = $(this);
					kernel.ajax.load($form.attr('action'), id, {
						historia: false,
						type: 'POST',
						data: $form.serializeArray(),
						success: function(response) {
							if (response.info_estado.exitoso) {
								kernel.ui.show_mensaje(response.info_estado.mensaje, {
									tipo: 'alert-success',
									until_interaction: false,
									timeout: 5000,
									fadeout: 2000
								});
							} else {
								kernel.ui.show_mensaje(response.info_estado.mensaje, {
									tipo: 'alert-error',
									until_interaction: false,
									timeout: 5000,
									fadeout: 2000
								})	
							}
							// Si corresponde, se desactiva el autosave
							if (info.autosave) {
								$(id).autosave('disable');
							}
							kernel.renderer.pagelet('domicilio').onload();
						}
					});
				} else {
					kernel.ui.show_mensaje(info.msj_sin_cambios, {
						tipo: 'alert-info',
						until_interaction: false,
						timeout: 5000,
						fadeout: 2000
					});
				}
				return false;
			});
        }
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
	
	function localidad_foco_cp()
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
	
})