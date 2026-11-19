/**
 * Cambio del campo "Otra fuente"
 */
function cambio_idioma_otro() 
{
	if ($('#form_idiomas-idioma_otro').val() !== '') {
		mostrar('js-nivel_idioma_otro');
	} else {
		ocultar('js-nivel_idioma_otro', true);
	}
}

kernel.renderer.registrar_pagelet('idiomas', function(info) {
    var id = '#' + info.id;
	
    return {
        onload: function() {
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			cambio_idioma_otro();
			$('#form_idiomas-idioma_otro').on('change', function() { cambio_idioma_otro(); });
			
			$('#form_idiomas').submit(function() {
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
							kernel.renderer.pagelet('idiomas').onload();
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
});