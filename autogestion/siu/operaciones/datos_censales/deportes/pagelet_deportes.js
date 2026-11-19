/*
 * Cambio del campo "¿Practicás deportes?"
 */
function cambio_deportes()
{
	if ($('#form_deportes-deportes').is(':checked')) {
		mostrar('div_detalles_deportes');
	} else {
		ocultar('div_detalles_deportes', true);
	}
}

kernel.renderer.registrar_pagelet('deportes', function(info) {
    var id = '#' + info.id;
		
    return {
		
        onload: function() {
			//---- Inicialización de estado de visibilidad de secciones --------
			cambio_deportes()
			
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_deportes-deportes').on('change', function() { cambio_deportes(); });
			
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_deportes').submit(function() {
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
							kernel.renderer.pagelet('deportes').onload();
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