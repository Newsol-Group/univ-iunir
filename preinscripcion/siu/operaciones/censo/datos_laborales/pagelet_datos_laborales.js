var _info;

/**
 * Cambio del campo "Condición de actividad durante la semana pasada"
 */
function cambio_existe_trab_alum()
{
	if ($('#form_datos_laborales-existe_trab_alum').val() == '1') {
		mostrar('div_datos_trabajo');
		mostrar('div_datos_adicionales');
	} else {
		ocultar('div_datos_trabajo', true);
		ocultar('div_datos_adicionales', true);
	}
}

kernel.renderer.registrar_pagelet('datos_laborales', function(info) {
	var id = '#' + info.id;
	var r = window.matchMedia("(max-width: 700px)")
	_info = info;
	
    return {
        onload: function() {
			
			//---- Inicialización de estado de visibilidad de secciones --------
			cambio_existe_trab_alum();
			r.addListener(mediaQuerymatch(r));
			//---- Manejo de visibilidad de secciones por eventos --------------
			$('#form_datos_laborales-existe_trab_alum').on('change', function() { cambio_existe_trab_alum(); });
			
			// Si corresponde, se activa el autosave
			if (info.autosave) {
				$(id).autosave();
			}
			
			$('#form_datos_laborales').submit(function() {
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
							kernel.renderer.pagelet('datos_laborales').onload();
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
			
			//------------------------------------------------------------------
			//---- Manejo de cascadas ------------------------------------------
			//------------------------------------------------------------------

			

			

			
			function mediaQuerymatch(r)
			{
				//media query matches
				if (r.matches) { 
					$('#form_datos_laborales-existe_trab_alum').removeClass('combo_largo');	
				} else {
					$('#form_datos_laborales-existe_trab_alum').addClass('combo_largo');
				}
			}
			
        }
    }
})