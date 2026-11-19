kernel.renderer.registrar_pagelet('lista_materias', function (info) {
	var id = '#' + info.id;

	return {
		onload: function () {

			$('.js-borrar').on("click", function () {
				if($(this).hasClass('link-no-acceso')){
					return false;
				}
				var url = $(this).attr('href');
				var nodo_eval = $(this).parents('.evaluacion-fila');
				var tabla = $(this).parents('table');
				kernel.ui.prompt({
					msg: info.mensajes.seguro_desea_eliminar_evaluacion,
					msg_ok: 'Aceptar',
					ok: function () {
						kernel.ajax.call(url, {
							type: 'get',
							//data: {evaluacion: evaluacion_id},
							success: function (paquete) {
								if (paquete.result == 0) {
									kernel.ui.show_mensaje(paquete.cont, {tipo: 'alert-success'});
									if($('.evaluacion-fila:visible').length == 1) { //si quedo sola
										tabla.slideUp();
									}else {
										nodo_eval.slideUp();
									}
								}
								else if (paquete.result < 0) {
									kernel.ui.show_mensaje(paquete.cont, {tipo: 'alert-error', until_interaction: true});
								}
								else
									kernel.ui.show_mensaje(paquete.cont, {tipo: 'alert-error'});
							}
						});
					}
				});
				return false;

			});
		}
	}
});