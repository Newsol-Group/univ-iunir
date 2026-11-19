kernel.renderer.registrar_pagelet('alumnos', function (info)
{
	var id = '#' + info.id;
	var _url_instancia_automatica = info.url_instancia_automatica;
	var _instancia_automatica;

	function mostrar_instancia(mostrar)
	{
		if (mostrar) {
			$('#form_alumnos-instancia_alumno').parents('.control-group').css('visibility', 'visible');
		} else {
			$('#form_alumnos-instancia_alumno').parents('.control-group').css('visibility', 'hidden');
		}
	}
	
	function seleccion_alumno(_alumno)
	{
		$.ajax({
			url: _url_instancia_automatica,
			dataType: 'json',
			async: false,
			data: {alumno: _alumno},
			type: 'post',
			success: function(resp) {
				_instancia_automatica = resp.instancia_automatica === 'S';
				if (_instancia_automatica) {
					mostrar_instancia(false);
				} else {
					mostrar_instancia(true);
				}
			}
		});
	}

	return {
		onload: function ()
		{
			$form = $('#form_alumnos');
			$form.formulario();
			
			mostrar_instancia(false);
			$('#form_alumnos-buscar_alumnos').on('autocompleteselect', function(event, ui) {
				var alumno = ui.item.id;
				seleccion_alumno(alumno);
			});
			$('#form_alumnos-buscar_alumnos').on('focus keyup', function() {
				if (!$('#form_alumnos-buscar_alumnos').val().length) {
					mostrar_instancia(false);
				}
			});

			$form.formulario('elemento', 'alumnos').form_ml('change_settings', {
				before_remove: function (fila)
				{
					if (confirm(info.msg_eliminar_alumno)) {
						guarani.set_mensaje_antes_de_navegar(info.msg_navegacion);
						return true;
					}
					return false;
				}
			});


			$(id).on('submit', '#form_alumnos', function () {
				var $form = $(this);
				kernel.ui.prompt({
					title: info.msg_confirmacion_crear_acta_tit,
					msg: info.msg_confirmacion_crear_acta,
					ok: function () {
						if ($('#alumnos tr.form-ml-row').length > 0) {
							guarani.set_mensaje_antes_de_navegar_activo(false);
							kernel.ajax.load($form.attr('action') + '?n=' + info.nuevo, id, {
								historia: false,
								type: 'POST',
								data: $form.serializeArray(),
								success: function (response)
								{
									if (response.info_estado.exitoso) {
										// nuevo request php desde el controlador
										if (response.no_tiene_derechos != 'undefined' && response.no_tiene_derechos) {
											//no tiene derechos. Le muestro un popup con el mensaje (desde el controlador),
											// y aca redirigo asi no queda en la pantalla.
											$('#crear').attr('disabled', true);
											guarani.set_mensaje_antes_de_navegar_activo(false);
											guarani.cambiar_op({href: response.url_no_drechos});
											return;
										}
									} else {
										guarani.set_mensaje_antes_de_navegar_activo(true);
										kernel.ui.show_mensaje(response.info_estado.mensaje, {
											tipo: 'alert-error'
										});
									}
									guarani.set_mensaje_antes_de_navegar_activo(false);
									kernel.renderer.pagelet('alumnos').onload();
								}
							});
						} else {
							kernel.ui.show_mensaje(info.msg_sin_alumnos, {
								tipo: 'alert-error'
							});
						}
					}
				});
				return false;
			});

			$(id).on('click', '#js-btn-agregar-alumno', function () {
				var alumno = $('#form_alumnos-buscar_alumnos-autocomplete').val();
				if (alumno !== '') {
					if (_instancia_automatica) {
						kernel.ajax.call(info.url_agregar_alumno, {
							type: 'post',
							data: {alumno: alumno},
							success: function (response)
							{
								var datos = response.cont;
								if ($("#alumnos tr.form-ml-row input.hidden-persona[value='" + datos.persona + "']").length <= 0) {
									$('#alumnos .form-ml').form_ml('add_row', {persona: datos.persona, alumno: datos.alumno, legajo: datos.legajo, apellido_nombre: datos.apellido_nombre, instancia: datos.instancia, instancia_nombre: datos.instancia_nombre, identificacion: datos.identificacion, propuesta: datos.propuesta, plan_version: datos.plan_version});
									guarani.set_mensaje_antes_de_navegar(info.msg_navegacion);
								} else {
									kernel.ui.show_mensaje(info.msg_persona_existe, {
										tipo: 'alert-error'
									});
								}
								$('#form_alumnos-buscar_alumnos').val('');
								$('#form_alumnos-buscar_alumnos-autocomplete').val('');
							},
							error: {}
						});
					} else {
						var instancia = $('#form_alumnos-instancia_alumno').val();
						if (instancia !== '') {
							kernel.ajax.call(info.url_agregar_alumno, {
								type: 'post',
								data: {alumno: alumno, instancia: instancia},
								success: function (response)
								{
									var datos = response.cont;
									if ($("#alumnos tr.form-ml-row input.hidden-persona[value='" + datos.persona + "']").length <= 0) {
										//$('#alumnos .form-ml').form_ml();
										$('#alumnos .form-ml').form_ml('add_row', {persona: datos.persona, alumno: datos.alumno, legajo: datos.legajo, apellido_nombre: datos.apellido_nombre, instancia: datos.instancia, instancia_nombre: datos.instancia_nombre, identificacion: datos.identificacion, propuesta: datos.propuesta, plan_version: datos.plan_version});
									} else {
										kernel.ui.show_mensaje(info.msg_persona_existe, {
											tipo: 'alert-error'
										});
									}
									$('#form_alumnos-buscar_alumnos').val('');
									$('#form_alumnos-buscar_alumnos-autocomplete').val('');
									$('#form_alumnos-instancia_alumno').prop('selectedIndex', 0);
								},
								complete: function ()
								{
									//controlar_tabla_vacia();
								}
							});
						}
					}
					mostrar_instancia(false);
				}
				return false;
			});
		}
	}
})