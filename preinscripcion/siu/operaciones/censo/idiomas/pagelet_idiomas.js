kernel.renderer.registrar_pagelet('idiomas', function(info) {
    var id = '#' + info.id;
	
    return {
        onload: function() {
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_idiomas-alu_idioma_otro').on('keyup', function() { cambio__alu_idioma_otro(); });
			cambio__alu_idioma_otro();
			
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
    };
	
	/**
	 * Cambio del campo "Otro"
	 */
	function cambio__alu_idioma_otro()
	{
	   if ($('#form_idiomas-alu_idioma_otro').val().trim()) {
		   mostrar('div_alu_idioma_otro');
	   } else {
		   ocultar('div_alu_idioma_otro', true);
	   }
	}
	
});