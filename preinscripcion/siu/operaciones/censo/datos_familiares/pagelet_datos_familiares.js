/**
 * Cambio del campo "Padre - ¿Vive?"
 */
function cambio_padre_vive()
{
	if ($('#form_datos_familiares-padre_vive').val() === 'S') {
		mostrar('div_sit_laboral_padre');
	} else {
		ocultar('div_sit_laboral_padre', true);
		ocultar('div_trabajo_padre', true);
		ocultar('div_padre_no_trabaja', true);
	}
}

/**
 * Cambio del campo "Padre - Condición de actividad durante la semana pasada"
 */
function cambio_sit_laboral_padre()
{
	if ($('#form_datos_familiares-sit_laboral_padre').val() === '1') {
		mostrar('div_trabajo_padre');
		ocultar('div_padre_no_trabaja', true);
	} else {
		if ($('#form_datos_familiares-sit_laboral_padre').val() === '3') {
			mostrar('div_padre_no_trabaja');
			ocultar('div_trabajo_padre', true);
		} else {
			ocultar('div_trabajo_padre', true);
			ocultar('div_padre_no_trabaja', true);
		}
	}
}

/**
 * Cambio del campo "Madre - ¿Vive?"
 */
function cambio_madre_vive()
{
	if ($('#form_datos_familiares-madre_vive').val() === 'S') {
		mostrar('div_sit_laboral_madre');
	} else {
		ocultar('div_sit_laboral_madre', true);
		ocultar('div_trabajo_madre', true);
		ocultar('div_madre_no_trabaja', true);
	}
}

/**
 * Cambio del campo "Madre - Condición de actividad durante la semana pasada"
 */
function cambio_sit_laboral_madre()
{
	if ($('#form_datos_familiares-sit_laboral_madre').val() === '1') {
		mostrar('div_trabajo_madre');
		ocultar('div_madre_no_trabaja', true);
	} else {
		if ($('#form_datos_familiares-sit_laboral_madre').val() === '3') {
			mostrar('div_madre_no_trabaja');
			ocultar('div_trabajo_madre', true);
		} else {
			ocultar('div_trabajo_madre', true);
			ocultar('div_madre_no_trabaja', true);
		}
	}
}

kernel.renderer.registrar_pagelet('datos_familiares', function(info) {
    var id = '#' + info.id;
	
    return {
        onload: function() {
			
			//---- Inicialización de estado de visibilidad de secciones --------
			cambio_padre_vive();
			cambio_sit_laboral_padre();
			cambio_madre_vive();
			cambio_sit_laboral_madre();
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_datos_familiares-padre_vive').on('change', function() { cambio_padre_vive(); });			
			$('#form_datos_familiares-sit_laboral_padre').on('change', function() { cambio_sit_laboral_padre(); });
			$('#form_datos_familiares-madre_vive').on('change', function() { cambio_madre_vive(); });
			$('#form_datos_familiares-sit_laboral_madre').on('change', function() { cambio_sit_laboral_madre(); });
			
			//------------------------------------------------------------------
			
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_datos_familiares').submit(function() {
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
							kernel.renderer.pagelet('datos_familiares').onload();
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
});