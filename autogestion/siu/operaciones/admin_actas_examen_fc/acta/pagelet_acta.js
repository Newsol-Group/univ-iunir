kernel.renderer.registrar_pagelet('acta', function (info)
{
	var id = '#' + info.id;
	var _url_mostrar_nro_acta = info.url_mostrar_nro_acta;
	var _modo_edicion = info.modo_edicion;
	var _mostrar_nro_acta = false;
        
        //---- Manejo de selección de Docentes -------------------

	function docente_existe_en_lista(docente)
	{
		var existe = false;
		$("#docentes tr.form-ml-row input[type='hidden']").each(function ()
		{
			if ($(this).val() == docente) {
				existe = true;
			}
		});
		return existe;
	}
        
        function inicializar_docentes()
        {
            $(id).on('change', '#docentes select.select-rol', function () {
                
                // Si el 'Rol' es vacío
                if ($(this).val().length === 0) {
                    $(this).parent('td.form-ml-data').addClass('form-ml-data-error').attr('title', info.mensajes.msg_campo_obligatorio);
                } else {
                    $(this).parent('td.form-ml-data').removeClass('form-ml-data-error').attr('title', '');
                }
                
            });
        }
        
        function control_docentes()
	{
            var valido = true;
            
            // Controlar que haya al menos un docente con rol asignado
            if ($('#docentes select.select-rol').length === 0) {
                kernel.ui.show_mensaje(info.mensajes.msg_docentes_sin_rol, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
                return false;
            }
            
            // Controlar que cada docente tenga rol asignado
            $('#docentes select.select-rol').each(function( index ) {
            
            
                // Si tiene dicho ID lo desestimo 
                if ($(this).attr('id') === 'form_acta-docentes-__NUM_FILA_ML__-rol') {
                    return; //this is equivalent of 'continue' for jQuery loop
                }
                
                // Si el 'Rol' es vacío
                if ($(this).val().length === 0) {
                    $(this).parent('td.form-ml-data').addClass('form-ml-data-error').attr('title', info.mensajes.msg_campo_obligatorio);
                    valido = false;
                }
            });
            
            // Si hay uno o más docentes sin rol asignado
            if (!valido) {
                kernel.ui.show_mensaje(info.mensajes.msg_docentes_sin_rol_asignado, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
            
            return valido;
        }

        //---- Manejo de selección de Instancias -------------------
        
        function control_instancias()
	{
		$("#instancias tr.form-ml-row td.ef-fijo").removeClass('error');

		// Controlar que haya al menos una instancia activa
		if ($("#instancias td.form-ml-data:visible input[type='checkbox']").length == 0) {
			return true;
		}
		if ($("#instancias td.form-ml-data:visible input[type='checkbox']:checked").length > 0) {
			// Controlar que cada instancia activa tenga su escala
			var falta_rol = false;
			$("#instancias td.form-ml-data:visible input[type='checkbox']:checked").each(function ()
			{
				var row = $(this).parent().parent();
				if (row.find('select').val() == "") {
					kernel.ui.show_mensaje(info.msg_escala_vacia, {
						tipo: 'alert-error',
						until_interaction: false,
						timeout: 5000,
						fadeout: 2000
					});
					row.find("td.ef-fijo").addClass('error');

					if (!falta_rol) {
						// Para dejar en foco el primero y no el último
						row.find("select").focus();
					}
					falta_rol = true;
				}
			});
			return !falta_rol;
		} else {
			$("#instancias tr.form-ml-row td.ef-fijo").addClass('error');
			kernel.ui.show_mensaje(info.msg_sin_instancia_activa, {
				tipo: 'alert-error',
				until_interaction: false,
				timeout: 5000,
				fadeout: 2000
			});
			$("#instancias tr.form-ml-row input[type='checkbox']:first").focus();
			return false;
		}
	}

        //---- Manejo de selección de Año Académico -------------------
        
        function inicializar_anio_academico()
        {
            $(id).on('change', '#form_acta-datos_acta-anio_academico', function () {
                
                // Si el 'Año Académico' es vacío
                if ($(this).val().length === 0) {
                    $(this).parents(".control-group").addClass("error");
                    $(this).parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                } else {
                    $(this).parents(".control-group").removeClass("error");
                    $(this).parents(".control-group").find(".text-error").text("");
                }
                
            });
        }
        
	function control_anio_academico()
	{
            var valido = true;
            var anio_academico = $('#form_acta-datos_acta-anio_academico');
            
            // Si el 'Año Académico' es vacío
            if (anio_academico.val().length === 0) {
                valido = false;
                anio_academico.parents(".control-group").addClass("error");
                anio_academico.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
            }
            
            // Si hubo un error muestro mensaje
            if (!valido) {
                kernel.ui.show_mensaje(info.mensajes.msg_campo_anio_academico_obligatorio, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
            
            return valido;
        }

        //---- Manejo de selección de Ubicación -------------------
        
        function inicializar_ubicacion()
        {
            $(id).on('change', '#form_acta-datos_acta-ubicacion', function () {
                
                // Si la 'Ubicación' es vacía
                if ($(this).val().length === 0) {
                    $(this).parents(".control-group").addClass("error");
                    $(this).parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                } else {
                    $(this).parents(".control-group").removeClass("error");
                    $(this).parents(".control-group").find(".text-error").text("");
                }
                
            });
        }
        
	function control_ubicacion()
	{
            var valido = true;
            var ubicacion = $('#form_acta-datos_acta-ubicacion');
            
            // Si la 'Ubicación' es vacía
            if (ubicacion.val().length === 0) {
                valido = false;
                ubicacion.parents(".control-group").addClass("error");
                ubicacion.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
            }
            
            // Si hubo un error muestro mensaje
            if (!valido) {
                kernel.ui.show_mensaje(info.mensajes.msg_campo_ubicacion_obligatorio, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
            
            return valido;
        }
        
	function control_roles()
	{
		// desahilita roles seleccionados para evitar que se repitan
		// habilita todas las opciones
		$('tr.form-ml-row select.select-rol option').prop('disabled', false);

		$('tr.form-ml-row select.select-rol option:selected').each(function ()
		{
			if ($(this).val() != '') {
				// deshabilita las opciones seleccionadas
				$("tr.form-ml-row select.select-rol option:not(:selected)[value='" + $(this).val() + "']").prop('disabled', true);
			}
		});
	}

	function control_acciones()
	{
		var cant_docentes = $('#docentes tr.form-ml-row').length;

		if (cant_docentes > 1) {
			$('#docentes').find('.form-ml-row .form-ml-remove-row').show();
		} else {
			$('#docentes').find('.form-ml-row .form-ml-remove-row').hide();
		}
	}

	function control_hora_inicio_fin()
	{
		var hora_inicio = $('#form_acta-datos_acta-hora_inicio');
		var hora_fin = $('#form_acta-datos_acta-hora_finalizacion');

		var fecha_acta_valor = new Date($('#form_acta-datos_acta-fecha').val().replace(/(\d{2})\/(\d{2})\/(\d{4})/, "$2/$1/$3"));
		var fecha_acta = $('#form_acta-datos_acta-fecha');

		var valido = true;
		hora_inicio.removeClass('error');
		hora_fin.removeClass('error');
		fecha_acta.removeClass('error');
                
                // Si la 'Fecha del examen' es vacía
                if (fecha_acta.val().length === 0) {
                    valido = false;
                    fecha_acta.parents(".control-group").addClass("error");
                    fecha_acta.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                }
                
                // Si la 'Hora Inicio' es vacía
                if (hora_inicio.val().length === 0) {
                    valido = false;
                    hora_inicio.parents(".control-group").addClass("error");
                    hora_inicio.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                }
            
		if (!($('#form_acta-datos_acta-fecha').val() !== "" && fecha_acta_valor.getTime() < $.now())) {
			fecha_acta.addClass('error');
			valido = false;
		}

		if (hora_fin.val() != "" && hora_fin.val() <= hora_inicio.val()) {
			hora_inicio.addClass('error');
			hora_fin.addClass('error');
			valido = false;
		}

		if (!valido) {
			kernel.ui.show_mensaje(info.msg_hora_invalida, {
				tipo: 'alert-error',
				until_interaction: false,
				timeout: 5000,
				fadeout: 2000
			});
		}
		return valido;
	}
	
	//---- Manejo de selección de actividad y número de acta -------------------
	
	function inicializar_nro_acta()
	{
		if (!_modo_edicion) {
			if (!$('#form_acta-datos_acta-actividad').val()) {
				_mostrar_nro_acta = false;
			}
			mostrar_nro_acta(_mostrar_nro_acta);
		}
                
            $(id).on('change', '#form_acta-datos_acta-nro_acta', function () {
                
                // Si el 'Nro. Acta' es vacío
                if ($(this).val().length === 0) {
                    $(this).parents(".control-group").addClass("error");
                    $(this).parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                } else {
                    $(this).parents(".control-group").removeClass("error");
                    $(this).parents(".control-group").find(".text-error").text("");
                }
                
            });
	}
        
        function control_nro_acta()
	{
            
            // Si se trata de una edición salgo
            if (_modo_edicion) {
                return true;
            }
            
            var valido = true;
            var nro_acta = $('#form_acta-datos_acta-nro_acta');
            
            // Si muestra 'Nro. Acta' y el mismo es vacío
            if (_mostrar_nro_acta && nro_acta.val().length === 0) {
                valido = false;
                nro_acta.parents(".control-group").addClass("error");
                nro_acta.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
            }
            
            // Si hubo un error muestro mensaje
            if (!valido) {
                kernel.ui.show_mensaje(info.mensajes.msg_campo_nro_acta_obligatorio, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
            
            return valido;
        }

	function control_actividad()
	{
            // Si se trata de una edición salgo
            if (_modo_edicion) {
                return true;
            }
            
            var valido = true;
            var actividad = $("#form_acta-datos_acta-actividad");
            var actividad_descr = $('#form_acta-datos_acta-actividad_descr');
            
            // Si la 'Actividad' es vacía
            if (actividad.val().length === 0) {
                valido = false;
                actividad_descr.parents(".control-group").addClass("error");
                actividad_descr.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
            }
            
            // Si hubo un error muestro mensaje
            if (!valido) {
                kernel.ui.show_mensaje(info.mensajes.msg_campo_actividad_obligatorio, {
                    tipo: 'alert-error',
                    until_interaction: false,
                    timeout: 5000,
                    fadeout: 2000
                });
            }
                        
            return valido;
        }
        
        function inicializar_actividad()
        {
            // Si se trata de una edición salgo
            if (_modo_edicion) {
                return true;
            }
            
            // Si hay una actividad seleccionada la pongo como "solo lectura"
            if($("#form_acta-datos_acta-actividad").val()) {
                set_solo_lectura("form_acta-datos_acta-actividad_descr", true);
            }
            
            /* Autocomplete de actividad */
            $('#form_acta-datos_acta-actividad_descr').autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: info.url_buscar_actividades,
                        dataType: 'json',
                        data: {
                            term: request.term
                        },
                        success: function (data) {
                            response(data);
                        },
                        // A value of 0 means there will be no timeout
                        timeout: 0
                    });
                },
                minLength: $("#js-ui-autocomplete-min-length").val(),
                select: actividad_select,
                focus: actividad_focus,
                search: function (event, ui) {
                    kernel.ui.show_loading();
                },
                response: function (event, ui) {
                    kernel.ui.hide_loading();
                }

            });
            
            // Botón para limpiar la actividad 
            $( id ).on("click", '#limpiar_actividad', function() {
                limpiar_actividad();
            });
        }
	
        function actividad_select(event, ui)
        {
            
            $("#form_acta-datos_acta-actividad_descr").parents(".control-group").removeClass("error");
            $("#form_acta-datos_acta-actividad_descr").parents(".control-group").find(".text-error").text('');
                
            var id = ui.item.id;
            var label = ui.item.label;
            
            $("#form_acta-datos_acta-actividad").val(id);
            $("#form_acta-datos_acta-actividad_descr").val(label).data('escala_defecto', ui.item.escala_nota);
            set_solo_lectura("form_acta-datos_acta-actividad_descr", true);
            
            cambio_actividad(id);
            
            event.preventDefault();
        }

        function actividad_focus(event)
        {
            event.preventDefault();
        }

        function limpiar_actividad(){
            $("#form_acta-datos_acta-actividad").val('');
            $("#form_acta-datos_acta-actividad_descr").val('').data('escala_defecto', '').focus();
            set_solo_lectura("form_acta-datos_acta-actividad_descr", false);
            reset_actividad();
        }
            
	function cambio_actividad(_elemento)
	{	
		$.ajax({
			url: _url_mostrar_nro_acta,
			dataType: 'json',
			async: false,
			data: {elemento: _elemento},
			type: 'post',
			success: function(data) {
				_mostrar_nro_acta = (data.mostrar_nro_acta === 'S');
				mostrar_nro_acta(_mostrar_nro_acta);
			}
		});
	}

	function reset_actividad()
	{
		_mostrar_nro_acta = false;
		mostrar_nro_acta(_mostrar_nro_acta);
	}
	
	function mostrar_nro_acta(mostrar)
	{
		if (!_modo_edicion) {
			if (mostrar) {
				$('#form_acta-datos_acta-nro_acta').attr('disabled', false);
                                // Pongo 'Nro. Acta' como obligatorio
				$('#form_acta-datos_acta-nro_acta').parents('.control-group').find('.control-label').removeClass('label-no-obligatorio').addClass('label-obligatorio');
                                $('#form_acta-datos_acta-nro_acta').parents('.control-group').find('.control-label label').text(info.mensajes.msg_nro_acta + ' (*)');
			} else {
				$('#form_acta-datos_acta-nro_acta').val('');
				$('#form_acta-datos_acta-nro_acta').attr('disabled', true);
                                // Pongo 'Nro. Acta' como no obligatorio
				$('#form_acta-datos_acta-nro_acta').parents('.control-group').find('.control-label').removeClass('label-obligatorio').addClass('label-no-obligatorio');
                                $('#form_acta-datos_acta-nro_acta').parents('.control-group').find('.control-label label').text(info.mensajes.msg_nro_acta);
			}
		}
	}
	
	//---- Fin de Manejo de selección de actividad y número de acta -------------------
	
	return {
		onload: function ()
		{
			$form = $('#form_acta');
			$form.formulario();
			if ($('#form_acta').formulario('elemento', 'datos_acta').length) {
				$form.formulario('elemento', 'datos_acta').plugin_autocompletar('callback_select', function (id, value, original) {
					$form.formulario('elemento', 'datos_acta').data('escala_defecto', original.escala_nota);
				});
			}
			control_roles();
			$form.formulario('elemento', 'docentes').form_ml('change_settings', {
				after_remove: function ()
				{
					control_roles();
					control_acciones();
				},
				before_remove: function (fila)
				{
					if (confirm(info.msg_eliminar_docente)) {
						return true;
					}
					return false;
				}
			}
			);
	
			//---- Selección de una actividad ----------------------------------
			inicializar_nro_acta();
                        
                        inicializar_actividad();
			//------------------------------------------------------------------

                        inicializar_anio_academico();
                        inicializar_ubicacion();
                        inicializar_docentes();
                        
			$(id).on('submit', '#form_acta', function (event)
			{
				var $form = $(this);
				var controles_ok = true;

				// Si es un acta de examen fuera de calendario controlo hora e instancias
				if(info.es_acta_examen_fc){
                                    var control_docentes_ok = control_docentes();
                                    var control_instancias_ok = control_instancias();
                                    var control_hora_inicio_fin_ok = control_hora_inicio_fin();
                                    var control_ubicacion_ok = control_ubicacion();
                                    var control_anio_academico_ok = control_anio_academico();
                                    var control_nro_ok = control_nro_acta();
                                    var control_actividad_ok = control_actividad();
                                    controles_ok = control_actividad_ok && control_nro_ok && control_anio_academico_ok && control_ubicacion_ok && control_hora_inicio_fin_ok && control_instancias_ok && control_docentes_ok;
				}

				if (controles_ok) {
					kernel.ajax.load($form.attr('action'), id, {
						historia: false,
						type: 'POST',
						data: $form.serializeArray(),
						success: function (response)
						{
							if (response.info_estado.exitoso) {

								// Si es un acta de examen fuera de calendario
								if (info.es_acta_examen_fc) {

									var url = response.url_siguiente;
									if (response.nueva) {
										var url_actual = info.url_actual + '/editar/' + response.hash;
										guarani.cambiar_op({href: url, url_actual: url_actual});
									} else {
										guarani.cambiar_op({href: url});
									}
								// Si es un acta de examen en turno
								} else {
									kernel.ui.show_mensaje(response.guardado_datos_exitoso);
								}

							} else {
								inicializar_nro_acta();
                                                                inicializar_actividad();
								kernel.ui.show_mensaje(response.info_estado.mensaje, {
									tipo: 'alert-error',
									until_interaction: false,
									timeout: 5000,
									fadeout: 2000
								});
							}
						},
						complete: function ()
						{
							kernel.renderer.pagelet('acta').onload();
						}
					});
				}
				event.preventDefault();
				return false;
			});

			$(id).on('change', '#form_acta-datos_acta-fecha', function ()
			{
				// Se toma la fecha del examen
				var fecha_acta_valor = new Date($('#form_acta-datos_acta-fecha').val().replace(/(\d{2})\/(\d{2})\/(\d{4})/, "$2/$1/$3"));
				fecha_acta_valor.setHours(0, 0, 0, 0);
				var fecha_acta = $('#form_acta-datos_acta-fecha');

                                // Si la 'Fecha del examen' es vacía
                                if (fecha_acta.val().length === 0) {
                                    fecha_acta.parents(".control-group").addClass("error");
                                    fecha_acta.parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                                } else {
                                    fecha_acta.parents(".control-group").removeClass("error");
                                    fecha_acta.parents(".control-group").find(".text-error").text('');
                                }
                
				// Fecha inicio para validar
				var fecha_inicio = new Date();
				fecha_inicio.setHours(0, 0, 0, 0);
				fecha_inicio.setMonth(fecha_inicio.getMonth() - info.cant_meses_sugeridos_antes_fecha_examen);

				fecha_acta.popover('destroy');
				fecha_acta.removeClass('nota');

				if (fecha_acta_valor.getTime() < fecha_inicio.getTime()) {
					fecha_acta.addClass('nota');

					fecha_acta.data('container', id);
					fecha_acta.data('toggle', 'popover');
					fecha_acta.data('placement', 'bottom');
					fecha_acta.data('html', true);
					fecha_acta.data('content', '<p>' + info.msg_fecha_sugerencia + '</p> <a id="js-popoverFecha">Cerrar</a>');

					fecha_acta.popover();
					fecha_acta.popover('show');

				}
			});

			$('body').on('click', '#js-popoverFecha', function ()
			{
				$('#form_acta-datos_acta-fecha').popover('destroy');
			});

                        $(id).on('change', '#form_acta-datos_acta-hora_inicio', function ()
			{
                                // Si la 'Hora Inicio' es vacía
                                if ($(this).val().length === 0) {
                                    $(this).parents(".control-group").addClass("error");
                                    $(this).parents(".control-group").find(".text-error").text(info.mensajes.msg_campo_obligatorio);
                                } else {
                                    $(this).parents(".control-group").removeClass("error");
                                    $(this).parents(".control-group").find(".text-error").text('');
                                }
				
			});
                        
			//todo: pasar lo que se pueda de esto a la api del ml y autocompletar
			$(id).on('click', '#js-btn-agregar-docente', function ()
			{
				var cant_docentes = $('#docentes tr.form-ml-row').length;
				var cant_roles = $('#docentes tr.form-template-row select option').length - 1;

				// Controlo que no haya mas docentes que roles
				if (cant_docentes < cant_roles) {
					var docente = $('#form_acta-buscar_docentes-autocomplete').val();
					if (!docente_existe_en_lista(docente)) {
						if (docente !== '') {
							$.ajax({
								url: info.url_agregar_docente,
								data: {docente: docente},
								type: 'post',
								success: function (response)
								{
									var datos = JSON.parse(response);
									$('#docentes .form-ml').form_ml('add_row', {docente: datos[0].docente, legajo: datos[0].legajo, apellido_nombre: datos[0].apellido_nombre})
									$('#form_acta-buscar_docentes').val('');
									$('#form_acta-buscar_docentes-autocomplete').val('');
									control_roles();
									control_acciones();
								},
								error: {}
							});
						}
					} else {
						kernel.ui.show_mensaje(info.msg_docente_existe, {
							tipo: 'alert-error',
							until_interaction: false,
							timeout: 5000,
							fadeout: 2000
						});
						$('#form_acta-buscar_docentes').val('');
						$('#form_acta-buscar_docentes-autocomplete').val('');
					}
				} else {
					kernel.ui.show_mensaje(info.msg_docentes_maximo, {
						tipo: 'alert-error',
						until_interaction: false,
						timeout: 5000,
						fadeout: 2000
					})
				}
				return false;
			});

			$(id).on('click', 'button.js-detalle_escala', function ()
			{
				var id_escala = $(this).parents('tr').find('select').val();
				var escala_notas_cargada = false;
				var tabla_notas = $("<table style='margin-top: 16px'><thead><th>" + info.msg_valor + "</th><th>" + info.msg_descripcion + "</th><th>" + info.msg_resultado + "</th></thead><tbody></tbody></table>");
				tabla_notas.addClass('table').addClass('table-condensed');
				if (id_escala !== "") {
					if (!escala_notas_cargada) {
						kernel.ui.show_loading();
						$.ajax({
							url: info.detalle_escala_url,
							data: {id_escala: id_escala},
							success: function (res)
							{
								$(JSON.parse(res)).each(function ()
								{
									var escala = $(this)[0];
									var fila = $('<tr>');
									fila.append($('<td>' + escala.nota + '</td>'));
									fila.append($('<td>' + escala.descripcion + '</td>'));
									fila.append($('<td>' + escala.resultado + '</td>'));
									tabla_notas.find('tbody').append(fila);
								});
								escala_notas_cargada = true;
								kernel.ui.hide_loading();
								$.facebox(tabla_notas);
							}
						});
					} else {
						$.facebox(tabla_notas);
					}
				}
			});

			$(id).on('click', 'tr.form-ml-row select.select-rol', function ()
			{
				control_roles();
			});
			control_acciones();

			// Si la escala esta vacía se deschequea
			$(id).on('change', '.select-escala_nota', function ()
			{
				var elem_escala = $(this);
				if (elem_escala.val() == '') {
					elem_escala.parents('tr').find('.instancias_activas').prop('checked', false);
				} else {
					elem_escala.parents('tr').find('.instancias_activas').prop('checked', true);
				}
			});

			// Si se deschequea el estado se vacia la escala
			$(id).on('click', '.instancias_activas', function ()
			{
				var elem_estado = $(this);
				if (elem_estado.prop('checked') == false) {
					elem_estado.parents('tr').find('.select-escala_nota').val('');
				} else {
                                        // Toma la escala de nota de la actividad
					if ($('#form_acta').formulario('elemento', 'actividad_descr', 'datos_acta').length) {
						elemento = 'actividad_descr';
					} else {
						elemento = 'actividad_nombre';
					}
					var valor = $('#form_acta').formulario('elemento', elemento, 'datos_acta').data('escala_defecto');
					elem_estado.parents('tr').find('.select-escala_nota').val(valor);
				}
			});

			// Si se deschequean todos los estados se vacian todas las escalas
			$(id).on('click', 'input[type="checkbox"].todo_nada', function ()
			{
				var elem_tn = $(this);
				if (elem_tn.prop('checked') == false) {
					$(id).find('input[type="checkbox"].instancias_activas:enabled').parents('tr').find('.select-escala_nota').val('');

				}
			});
		}
	};
});
