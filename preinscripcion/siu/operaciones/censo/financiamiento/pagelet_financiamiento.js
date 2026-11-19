/**
 * Cambio del campo "Otra fuente"
 */
function cambio_alu_cos_est_otra() 
{
	if ($('#form_financiamiento-alu_cos_est_otra').is(':checked')) {
		mostrar('div_form_financiamiento');
	} else {
		ocultar('div_form_financiamiento', true);
	}
}

/**
 * Cambio del campo "Con beca"
 */
function cambio_alu_cos_est_beca() 
{
	if ($('#form_financiamiento-alu_cos_est_beca').is(':checked')) {
		mostrar('div_datos_becas');
	} else {
		ocultar('div_datos_becas', true);
	}
}

/**
 * Cambio del campo "De ayuda económica"
 */
function cambio_alu_beca_tipo_eco()
{
	if ($('#form_financiamiento-alu_beca_tipo_eco').is(':checked')) {
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
			cambio_alu_cos_est_otra();
			cambio_alu_cos_est_beca();
			cambio_alu_beca_tipo_eco();
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_financiamiento-alu_cos_est_otra').on('change', function() { cambio_alu_cos_est_otra(); });
			$('#form_financiamiento-alu_cos_est_beca').on('change', function() { cambio_alu_cos_est_beca(); });
			$('#form_financiamiento-alu_beca_tipo_eco').on('change', function() { cambio_alu_beca_tipo_eco(); });
			
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