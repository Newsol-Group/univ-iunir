kernel.renderer.registrar_pagelet('actas', function(info) {
	var id = '#' + info.id;

	function mostrar_notificaciones(response) {
		var div = $('<div class="cont_mensajes">');
		div.append($('<h3>').html('Notificaciones'));
		$.each(response.notificaciones, function (i, elem) {
			var mensaje = $('<div>').addClass('alert').addClass('alert-info');
			mensaje.html(elem);
			div.append(mensaje);
		});
		var wrapper = $('<div class="clearfix">');
		var botonera = $('<div style="text-align:center;">');
		var b_ok = $('<button type="button" class="btn btn-small">').html('Aceptar').on("click", function () {
			$.facebox.close();
			$(id).replaceWith(response.cont);
			kernel.renderer.pagelet('actas').onload();
			kernel.ui.show_mensaje(response.mensaje, {until_interaction: true});
		});
		botonera.append(b_ok);
		wrapper.append(botonera);
		div.append(wrapper);
		$.facebox(div, 'prompt');
	}

	function success_response(response) {
		if (response.notificaciones.length > 0) {
			mostrar_notificaciones(response);
		} else {
			$(id).replaceWith(response.cont);
			kernel.renderer.pagelet('actas').onload();
			kernel.ui.show_mensaje(response.mensaje, {until_interaction: true});
		}
	}

	return {
		onload: function () {

			$('.js-btn-cerrar').on('click', function (e) {

				var btn_cerrar_acta = $(this);

				kernel.ui.prompt({
					msg: info.msj_confirm_cierre,
					msg_ok: info.mensaje_aceptar,
					msg_cancel: info.mensajes.cancelar,
					ok: function ()
					{
						cerrar_acta(btn_cerrar_acta);
					}
				});

				e.preventDefault();
				return false;

			});

			function cerrar_acta(btn_cerrar_acta) {

				var acta_url = btn_cerrar_acta.attr('href');

				kernel.ajax.call(acta_url, {
					type: 'POST',
					success: function (response) {
						if (response.result == 'popup') {
							kernel.ui.prompt({
								title: info.titulo_elija_libro,
								msg: response.popup,
								msg_ok: info.boton_cerrar_acta,
								ok: function () {
									var acta_libro_url = btn_cerrar_acta.attr('data-url-cerrar-acta-libro');
									var data = $('#form-cerrar').serializeArray();
									kernel.ajax.call(acta_libro_url, {
										type: 'POST',
										data: data,
										success: function (paquete) {
											if (paquete.result == 'failure') {
												kernel.ui.modal({mensaje: paquete.mensaje, tipo: 'error'});
											} else {
												success_response(paquete);
											}
										}
									});
									return false;
								}
							});
						} else if (response.result == 'failure') {
							kernel.ui.modal({mensaje: response.mensaje, tipo: 'error' });
						} else {
							success_response(response);
						}
					}
				});

			}
		}
	}

});