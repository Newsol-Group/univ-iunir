kernel.renderer.registrar_pagelet('datos_estudios', function (info) {
	var id = '#' + info.id;
	var r = window.matchMedia("(max-width: 700px)")

	return {
		onload: function () {
			r.addListener(mediaQuerymatch(r));
			$('#form_datos_estudios-alu_otestsup_esta').on('change', function() { cambio__alu_otestsup_esta(); });
			cambio__alu_otestsup_esta();
			
			$('#form_datos_estudios-alu_otestsup_razon_abandono').on('change', function() { cambio__alu_otestsup_razon_abandono(); });
			cambio__alu_otestsup_razon_abandono();
			
                        //checked mayor de 25 sin secundario desactivo campos de datos secundario y limpio valores
                        checked_mayor_25();
                        $('#form_datos_estudios-es_mayor_25_sin_secundario').on('click', function() {
                            validar_edad_mayor_25();
                        });
			//---- Selector de colegio -----------------------------------------
			$('#btnSelectorColegio').on('click', function () {
				$.facebox(info.selector_colegio);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_datos_estudios-colegio_secundario';
				_info['retorno_descr'] = 'form_datos_estudios-colegio_secundario_descr';
				inicializar_popup_colegios(_info);
				popupMovil(r);
			});

			$('#btnLimpiarColegio').on('click', function () {
				$('#form_datos_estudios-colegio_secundario').val('null');
				$('#form_datos_estudios-colegio_secundario_descr').val('');
			});

			//---- Selector de título secundario -------------------------------
			$('#btnSelectorTitulo').on('click', function () {
				$.facebox(info.selector_titulo_sec);
				var _info = new cloneObject(info);
				_info['retorno_valor'] = 'form_datos_estudios-titulo_secundario';
				_info['retorno_descr'] = 'form_datos_estudios-titulo_secundario_descr';
				inicializar_popup_titulos(_info);
				popupMovil(r);
			});

			$('#btnLimpiarTitulo').on('click', function () {
				$('#form_datos_estudios-titulo_secundario').val('null');
				$('#form_datos_estudios-titulo_secundario_descr').val('');
			});

			//---- Si corresponde, se activa el autosave -----------------------
			if (info.autosave) {
				$(id).autosave();
			}

			//---- Submit ------------------------------------------------------
			$('#form_datos_estudios').submit(function () {
				var hubo_cambios = formulario_hubo_cambios();
				if (hubo_cambios) {
					var $form = $(this);
					kernel.ajax.load($form.attr('action'), id, {
						historia: false,
						type: 'POST',
						data: $form.serializeArray(),
						success: function (response) {
							if (response.info_estado.exitoso) {
								if (response.info_estado.cambios_desde_impresion) {
									document.location.reload(true);
								} else {
									kernel.ui.show_mensaje(response.info_estado.mensaje, {
										tipo: 'alert-success',
										until_interaction: false,
										timeout: 5000,
										fadeout: 2000
									});
								}
							} else {
								kernel.ui.show_mensaje(response.info_estado.mensaje, {
									tipo: 'alert-error',
									until_interaction: false,
									timeout: 5000,
									fadeout: 2000
								});
							}

							// Si corresponde, se desactiva el autosave
							if (info.autosave) {
								$(id).autosave('disable');
							}
							kernel.renderer.pagelet('datos_estudios').onload();
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
	};
		
	/**
	 * Cambio del campo estado
	 */
	function cambio__alu_otestsup_esta()
	{
	   if ($('#form_datos_estudios-alu_otestsup_esta').val() == info.estado_estudio_ABANDONADO) {
		   mostrar('motivos-abandono');
	   } else {
		   ocultar('motivos-abandono', true);
	   }
	   
	   if ($('#form_datos_estudios-alu_otestsup_esta').val() == info.estado_estudio_FINALIZADO) {
		   mostrar('div-alu_otestsup_egre');
	   } else {
		   ocultar('div-alu_otestsup_egre', true);
	   }
	}
	
	/**
	 * Cambio del campo "Razón de abandono"
	 */
	function cambio__alu_otestsup_razon_abandono()
	{
	   if ($('#form_datos_estudios-alu_otestsup_razon_abandono').val() == info.razon_abandono_OTRO) {
		   mostrar('motivo-abandono-otro');
	   } else {
		   ocultar('motivo-abandono-otro', true);
	   }
	}
	function mediaQuerymatch(r)
	{
		//media query matches
		if (r.matches) { 
			$('#form_datos_estudios-colegio_secundario_descr').removeClass('input_largo');
			$('#form_datos_estudios-titulo_secundario_descr').removeClass('input_largo');
		} else {
			$('#form_datos_estudios-colegio_secundario_descr').addClass('input_largo');
			$('#form_datos_estudios-titulo_secundario_descr').addClass('input_largo');
			}
	}
	function popupMovil(r){
		if(r.matches){
			val=$('#facebox').attr('style');
			top = val.split(';');
			$('#facebox').css({
				"left": "0px",
				"top": top,
			 });
			 $("#selectorColegio").css({'width': '100%'});
			 $("#selectorTituloSec").css({'width': '100%'});
			 $('#facebox .content').css({'width':'0px'});
		}
	}
	
    /*
     * consulto el chequeo
     */
    function checked_mayor_25() {

	if ($('#form_datos_estudios-es_mayor_25_sin_secundario').is(':checked')) {

	    $('#datos_estudios_sec').hide();
	    $('#form_datos_estudios-anio_egreso_sec').val('');
	    $('#form_datos_estudios-anio_egreso_sec_descr').val('');
	    $('#form_datos_estudios-colegio_secundario').val('');
	    $('#form_datos_estudios-colegio_secundario_descr').val('');
	    $('#form_datos_estudios-titulo_secundario').val('');
	    $('#form_datos_estudios-titulo_secundario_descr').val('');
	    $('#form_datos_estudios-adeuda_materias').prop("checked", false);
	} else {
	    $('#datos_estudios_sec').show();
	}
    }
	
    function validar_edad_mayor_25() {

	if (Boolean(info.fecha_nacimiento)) {

	    if (Boolean(info.es_menor_25)) {

		kernel.ui.show_mensaje(info.mensajes.error_es_mayor_25_sin_secundario, {
		    tipo: 'alert-error',
		    until_interaction: true
		});

		$('#form_datos_estudios-es_mayor_25_sin_secundario').prop("checked", false);
		checked_mayor_25();
	    } else {
		checked_mayor_25();
	    }

	} else {
	    
	    kernel.ui.show_mensaje(info.mensajes.error_fecha_nacimiento_vacia, {
		tipo: 'alert-error',
		until_interaction: true
	    });
	    $('#form_datos_estudios-es_mayor_25_sin_secundario').prop("checked", false);

	}
    }

});