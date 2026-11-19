/**
 * Cambio del campo "¿Te preinscribís simultáneamente a una segunda Propuesta en otra Institución?"
 */
function cambio__insc_otra_institucion()
{
	if ($('#form_preferencias-insc_otra_institucion').is(':checked')) {
		mostrar('div_insc_otra_inst_descr');
	} else {
		ocultar('div_insc_otra_inst_descr', true);
	}
}

kernel.renderer.registrar_pagelet('preferencias', function(info) {
    var id = '#' + info.id;
		
    return {
		
        onload: function() {
			cambio__insc_otra_institucion();
			$('#form_preferencias-insc_otra_institucion').on('change', function() { cambio__insc_otra_institucion(); });
			
			//------------------------------------------------------------------
			
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_preferencias').submit(function() {
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
							kernel.renderer.pagelet('preferencias').onload();
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