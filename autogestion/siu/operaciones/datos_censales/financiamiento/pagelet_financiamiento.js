/**
 * Cambio del campo "Otra fuente"
 */
function cambio_costeo_estudios_otro() 
{
	if ($('#form_financiamiento-costeo_estudios_otro').is(':checked')) {
		mostrar('div_form_financiamiento');
	} else {
		ocultar('div_form_financiamiento', true);
	}
}

/**
 * Cambio del campo "Con beca"
 */
function cambio_costeo_estudios_beca() 
{
	if ($('#form_financiamiento-costeo_estudios_beca').is(':checked')) {
		mostrar('div_datos_becas');
		$('#form_financiamiento-beca').val('S');
	} else {
		ocultar('div_datos_becas', true);
		$('#form_financiamiento-beca').val('N');
	}
}

/**
 * Cambio del campo "De ayuda económica"
 */
function cambio_beca_tipo_economica()
{
	if ($('#form_financiamiento-beca_tipo_economica').is(':checked')) {
		mostrar('div_beca_tipo_econom');
	} else {
		ocultar('div_beca_tipo_econom', true);
	}
}

kernel.renderer.registrar_pagelet('financiamiento', function(info) {
    var id = '#' + info.id;
		
    return {
		
        onload: function() {
			//---- Inicialización de estado de visibilidad de secciones --------
			cambio_costeo_estudios_otro();
			cambio_costeo_estudios_beca();
			cambio_beca_tipo_economica();
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_financiamiento-costeo_estudios_otro').on('change', function() { cambio_costeo_estudios_otro(); });
			$('#form_financiamiento-costeo_estudios_beca').on('change', function() { cambio_costeo_estudios_beca() });
			$('#form_financiamiento-beca_tipo_economica').on('change', function() { cambio_beca_tipo_economica() });
			
			//------------------------------------------------------------------
			
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_financiamiento').submit(function() {
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
							kernel.renderer.pagelet('financiamiento').onload();
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
})