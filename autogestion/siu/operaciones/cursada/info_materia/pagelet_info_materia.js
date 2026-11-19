kernel.renderer.registrar_pagelet('info_materia', function (info)
{
	var id = '#' + info.id;
	var filtro;
	var mensajes = info.mensajes;
	var clase_form_subcomisiones = 'form_inscribir_subcomisiones';
	
	function submit_comisiones(e)
	{
		var $form = $(e.target);
		var url = $form.attr('action');

		submit_inscripcion(url, $form);
	}

	/**
	 * Devuelve true si hay una selección válida en todas las comisiones con subcomisiones.
	 * Si no hay selección válida se agrega un mensaje y devuelve false
	 * @param forms
	 * @returns {Boolean}
	 */
	function hay_seleccion_valida_subcomisiones(forms)
	{
		var error = false;
		$(forms).each(function ()
		{
			$(this).children('fieldset').each(function ()
			{
				var $this = $(this);

				$this.next('.alert-error').remove();
				var val = $this.find('input').serialize();
				if (val == '') { // no seleccionó ninguna subcomisión
					var mensaje = mensajes.error_subcomision;
					$this.after("<div class='alert alert-error locura'>" + mensaje + "</div>");
					error = true;
				}
			});
		});

		return !error;
	}

	function submit_subcomisiones(e)
	{
		var $form = $(e.target);
		var url = $form.attr('action');

		if (hay_seleccion_valida_subcomisiones($form)) {
			submit_inscripcion(url, $form);
		}
	}

	function submit_inscripcion(url, $form)
	{
		kernel.ajax.load(url, id, {
			historia: false,
			type: 'POST',
			data: $form.serializeArray(),
			success: function (paquete)
			{
				kernel.evts.disparar('info_materia_inscripto', []);
				reload_pagelet();
				kernel.ui.show_mensaje(paquete.mensaje_inscripcion, {
					until_interaction: true
				});
			},
			error: function (paquete)
			{
				$(id).find('[name=__csrf]').val(paquete.csrf_token);
			}
		});
	}

	function submit_inscripcion_prioridad(url, data)
	{
		kernel.ui.prompt({
			msg: info.msg_confirma_desea_inscribirse_a_cursar,
			msg_ok: info.aceptar,
			msg_cancel: info.cancelar,
			ok: function ()
			{
				kernel.ajax.load(url, id, {
					historia: false,
					type: 'POST',
					data: data,
					success: function (paquete)
					{
						kernel.evts.disparar('info_materia_inscripto', []);
						reload_pagelet();
						kernel.ui.show_mensaje(paquete.mensaje_inscripcion, {
							until_interaction: true
						});
					},
					error: function (paquete)
					{
						$(id).find('[name=__csrf]').val(paquete.csrf_token);
					}
				});
			}
		});
	}

	function registrar_eventos_comisiones()
	{
	
	    $(id).on("keyup focusout", ".codigo_inscripcion_cerrada", function (e) {
		var codigo_inscripcion = $(this);
		var boton = null;
		
		if (codigo_inscripcion.hasClass("insc_prioridad")) {
		    boton = codigo_inscripcion.parents(".acciones").find("."+clase_btn_agregar_a_insc_prioridad);
		} else {
		    boton = codigo_inscripcion.parents(".acciones").find(".js-btn-inscribirse");
		}

		//Si el codigo de inscripcion es vacio deshabilito la inscripcion
		if (codigo_inscripcion.val().trim() == "") {
		    boton.attr("disabled", true).prop("disabled", true);
		} else {
		    boton.attr("disabled", false).prop("disabled", false);
		}

		e.preventDefault();
		return false;
	    });

	    $(id + ' .' + clase_form_subcomisiones).submit(function (e)
	    {
		submit_subcomisiones(e);
		return false;
	    });
	    
	    $(id + ' .form_inscribir').not('.' + clase_form_subcomisiones).submit(function (e)
	    {
		submit_comisiones(e);
		return false;
	    });

	    $(id).on("click", ".js-cic-icon", function (e) {

		var id_input = $(this).data('idInput');

		// Si se quiere mostrar el "Código inscripción"
		if ($(this).hasClass("icon-eye-close")) {
		    $(this).removeClass("icon-eye-close");
		    $(this).addClass("icon-eye-open");
		    $("#" + id_input).attr("type", "text");
		} else {// Si se quiere ocultar el "Código inscripción"
		    $(this).removeClass("icon-eye-open");
		    $(this).addClass("icon-eye-close");
		    $("#" + id_input).attr("type", "password");
		}
	    });
	}

	function registrar_eventos_info_catedras()
	{
		$(".toggle_info_catedra").hide();

		$('span.catedra_ver_info').on("click", function ()
		{
			$(this).toggleClass("activo").parent().next().slideToggle("fast");
			return false;
		});
	}
        
        function registrar_eventos_filtros()
	{
            $( id ).on( "vclick", ".js-dia", function(e) {
                filtrar();
            });
            
            $( id ).on( "vclick", ".js-turno", function(e) {
                filtrar();
            });
	    
	    $( id ).on( "vclick", ".js-horario", function(e) {
                filtrar();
            });
            
            $( id ).on( "change", "#js-ubicaciones", function(e) {
                filtrar();
            });
	}
        
        function filtrar()
        {
            var dias = [];
            var turnos = [];
            var ubicacion = "";
	    var horario = [];
            
            if ($( "#js-ubicaciones" ).val() != 0) {
                ubicacion = 'js-ubicacion-'+$( "#js-ubicaciones" ).val();
            }
            
            $( ".js-dia.active" ).each(function( index ) {
                dias.push(".js-dia-"+$( this ).data('dia'));
            });
            
            $( ".js-turno.active" ).each(function( index ) {
                turnos.push('.js-turno-'+$( this ).data('turno'));
            });
	    
	    $( ".js-horario.active" ).each(function( index ) {
                horario.push('.js-horario-'+$( this ).data('horario'));
            });
            
            $( "li.comision" ).each(function( index ) {
                
                var cumple_dias = dias.length == 0 || $( this ).is(dias.join(','));
                var cumple_turnos = turnos.length == 0 || $( this ).is(turnos.join(','));
		var cumple_horario = horario.length == 0 || $( this ).is(horario.join(','));
                var cumple_ubicacion = ubicacion == "" || $( this ).hasClass(ubicacion);
                
                if (cumple_dias && cumple_turnos && cumple_ubicacion && cumple_horario)
                {
                    $( this ).slideDown("slow");
                } else {
                    $( this ).slideUp("slow");
                }
                
            });
            
        }

	/*********************************************************************************************
	 * INSCRIPCIÓN POR PRIORIDAD
	 *********************************************************************************************/
	var clase_btn_agregar_a_insc_prioridad = 'js-toggle-activacion-insc-prioridad';
	var clase_comision_activa_prioridad = 'insc-prioridad-activa';
	var clase_comision_inactiva_prioridad = 'insc-prioridad-no-activa';
	var clase_btn_ordenar_prioridad = 'js-btn-ordenar-prioridades';
	var clase_btn_back_ordenar_prioridad = 'js-btn-back-to-ordenar-prioridades';
	var clase_btn_inscribir_por_prioridad = 'js-btn-inscribir-prioridad';
	var clase_toolbar_orden_prioridad = 'js-toolbar-ordenar-prioridad';
	var clase_ordenando_prioridades = 'js-ordenando-prioridades';
	var clase_mensaje_ordenar_prioridad = 'js-mensaje-ordenar-prioridad';
	var clase_mensaje_ordenar_prioridad_max = 'js-mensaje-ordenar-prioridad-max';
	var clase_subir_prioridad = 'js-subir-prioridad';
	var clase_bajar_prioridad = 'js-bajar-prioridad';
	var comisiones_ordenadas_count = -1;

	function refresh_flechas_prioridad()
	{
		var comisiones = $(id).find('.comision:visible');
		var count = comisiones.length;
		var first = true;
		comisiones.each(function (i)
		{
			var btn_up = $(this).find('.' + clase_subir_prioridad);
			var btn_down = $(this).find('.' + clase_bajar_prioridad);
			var clase_disabled = 'disabled';
			btn_up.removeClass(clase_disabled);
			btn_down.removeClass(clase_disabled);

			if (count == 1) {
				btn_up.addClass(clase_disabled);
				btn_down.addClass(clase_disabled);
			}

			if (first) {
				btn_up.addClass(clase_disabled);
			}

			if (i == count - 1) {
				btn_down.addClass(clase_disabled);
			}
			first = false;
		});
	}

	function registrar_eventos_flechas_prioridad()
	{
		$(id + ' .' + clase_subir_prioridad).on("click", function ()
		{
			var comision_actual = $(this).parents('.comision');
			var prev_comision = comision_actual.prevAll('.comision:visible:eq(0)').clone(true);
			comision_actual.prevAll('.comision:visible:eq(0)').remove();
			comision_actual.after(prev_comision);

			var data_order = comision_actual.data('ordererIndex');
			data_order--;
			comision_actual.attr('data-orderer-index', data_order);
			comision_actual.data('ordererIndex', data_order);

			data_order = prev_comision.data('ordererIndex');
			data_order++;
			prev_comision.attr('data-orderer-index', data_order);
			prev_comision.data('ordererIndex', data_order);

			refresh_flechas_prioridad();
			return false;
		});

		$(id + ' .' + clase_bajar_prioridad).on("click", function ()
		{
			var comision_actual = $(this).parents('.comision');
			var next_comision = comision_actual.nextAll('.comision:visible:eq(0)').clone(true);
			comision_actual.nextAll('.comision:visible:eq(0)').remove();
			comision_actual.before(next_comision);

			var data_order = comision_actual.data('ordererIndex');
			data_order++;
			comision_actual.attr('data-orderer-index', data_order);
			comision_actual.data('ordererIndex', data_order);

			data_order = next_comision.data('ordererIndex');
			data_order--;
			next_comision.attr('data-orderer-index', data_order);
			next_comision.data('ordererIndex', data_order);

			refresh_flechas_prioridad();
			return false;
		});
	}

	function get_count_agregados_insc_prioridad()
	{
		return get_seleccion_insc_prioridad().length;
	}

	function get_seleccion_insc_prioridad()
	{
		return $(id + ' .' + clase_btn_agregar_a_insc_prioridad).filter(function ()
		{
			return $(this).data('toggle-estado-activo');
		});
	}


	function validar_seleccion_prioridades(btn_presionado, class_li_comision)
	{
		var seleccion = get_seleccion_insc_prioridad();
		var cant_actual = seleccion.length;

		if (cant_actual >= info.prioridad_cantidad_maxima) {
			kernel.ui.show_mensaje(info.msg_max_inscripcion_prioridad_excedida, {
				tipo: 'alert alert-warning'
			});
			return false;
		}
		var periodo_nuevo = btn_presionado.parents(class_li_comision).data('periodo');
		for (var i = 0; i < cant_actual; i++) {
			var per = $(seleccion[i]).parents(class_li_comision).data('periodo');
			if (periodo_nuevo != per) {
				kernel.ui.show_mensaje(info.msg_comisiones_igual_periodo_lectivo, {
					tipo: 'alert alert-warning'
				});
				return false;
			}
		}
		return true;
	}

	function registrar_evento_ordenar_inscripciones()
	{
		$(id + " ." + clase_btn_ordenar_prioridad).on("click", function ()
		{
			if ($(this).hasClass('disabled')) {
				kernel.ui.show_mensaje(info.mensaje_error_sin_seleccion_de_comision, {
					tipo: 'alert alert-warning'
				});
				return false;
			}

			toggle_modo_ordenar_prioridad();
		});
	}

	function registrar_evento_inscripcion_prioridad()
	{
		$(id + ' .' + clase_btn_inscribir_por_prioridad).on("click", function ()
		{
			var forms = [];
			$(id + " .comision:visible").each(function ()
			{
				forms.push($(this).find('.form_inscribir'));
			});

			if (hay_seleccion_valida_subcomisiones(forms)) {
				var materia = $(forms[0]).find('input[name=materia]').val();
				var data = [];
				var csrf;
				$(forms).each(function (i)
				{
					var serialized = $(this).serializeArray();

					$(serialized).each(function ()
					{
						if (this.name == '__csrf') {
							csrf = this.value;
							return true;
						}
						var es_subcomision = this.name.indexOf('subcomisiones') != -1;
						if (es_subcomision) {
							var regexp = /.*\[(.*)\]/;
							var subcom_index = regexp.exec(this.name)[1];
							this.name = 'comisiones[' + i + '][subcomisiones][' + subcom_index + ']';
						} else {
							this.name = 'comisiones[' + i + '][' + this.name + ']';
						}

						data.push(this);
					});
				});

				data.push({name: '__csrf', value: csrf});
				data.push({name: 'materia', value: materia});
				submit_inscripcion_prioridad(info.url_inscripcion_prioridad, data);
			}
		});

	}

	function registrar_evento_volver_a_ordenar()
	{
		$(id + " ." + clase_btn_back_ordenar_prioridad).on("click", function ()
		{
			toggle_modo_ordenar_prioridad();
		});
	}

	function sortAlpha(a, b)
	{
		return $(a).data('ordererIndex') > $(b).data('ordererIndex') ? 1 : -1;
	}

	function toggle_modo_ordenar_prioridad()
	{
		var boton_prioridad = $(id + " ." + clase_btn_ordenar_prioridad);
		var activar = !boton_prioridad.hasClass(clase_ordenando_prioridades);
		if (activar) {

			//ordenar comisiones por el orden de seleccion
			$(id + " ul#comisiones li.comision").sort(sortAlpha).appendTo('ul#comisiones');

			// esconder comisiones sin selección
			$(id + " li.comision").not('.' + clase_comision_activa_prioridad).hide();
			// esconder botones de agregar
			$(id).find('.' + clase_btn_agregar_a_insc_prioridad).hide();
			// mostrar flechas de ordenamiento
			$(id).find('.' + clase_toolbar_orden_prioridad).show();
			// cambiar texto boton azul a 'Inscribir'
			$(id).find('.' + clase_mensaje_ordenar_prioridad).show();
			$(id).find('.' + clase_mensaje_ordenar_prioridad_max).hide();
			$(id + " ." + clase_btn_ordenar_prioridad).addClass(clase_ordenando_prioridades).hide();
			$(id + " ." + clase_btn_inscribir_por_prioridad).show();
			$(id + " ." + clase_btn_back_ordenar_prioridad).show();
			refresh_flechas_prioridad();
		} else {
			// mostrar todas las comisiones
			$(id + " li.comision").show();
			// mostrar botones de agregar
			$(id).find('.' + clase_btn_agregar_a_insc_prioridad).show();
			// esconder flechas de ordenamiento
			$(id).find('.' + clase_toolbar_orden_prioridad).hide();
			// cambiar texto boton azul a 'Ordenar Prioridades >'
			$(id).find('.' + clase_mensaje_ordenar_prioridad).hide();
			$(id).find('.' + clase_mensaje_ordenar_prioridad_max).show();
			$(id + " ." + clase_btn_ordenar_prioridad).removeClass(clase_ordenando_prioridades).show();
			$(id + " ." + clase_btn_inscribir_por_prioridad).hide();
			$(id + " ." + clase_btn_back_ordenar_prioridad).hide();
		}

	}

	function registrar_eventos_inscripcion_prioridad()
	{
		if ($(id).find('.' + clase_btn_ordenar_prioridad).length > 0) {
			registrar_evento_ordenar_inscripciones();
			registrar_eventos_flechas_prioridad();
			registrar_evento_inscripcion_prioridad();
			registrar_evento_volver_a_ordenar();
			$(id + ' .' + clase_btn_agregar_a_insc_prioridad).on("click", function ()
			{
				var btn = $(this);
				var key_estado = 'toggle-estado-activo';
				var class_li_comision = '.comision';
				if (btn.data(key_estado)) {
					btn.parents(class_li_comision).addClass(clase_comision_inactiva_prioridad);
					btn.parents(class_li_comision).removeClass(clase_comision_activa_prioridad);
					btn.removeClass('btn-success');
					btn.text(btn.data('toggle-texto-desactivado'));
					btn.data(key_estado, false);
					// Muestro el "Código inscripción"
					btn.parents('.acciones').find(".control-group").show();

					if (btn.parents(class_li_comision).data('ordererIndex') != comisiones_ordenadas_count) {

						$('.comision.insc-prioridad-activa').filter(
								function ()
								{
									return $(this).data('ordererIndex') > btn.parents(class_li_comision).data('ordererIndex')
								}
						).each(function ()
						{
							var orderer_index = $(this).data('ordererIndex');
							orderer_index--;
							$(this).attr('data-orderer-index', orderer_index);
							$(this).data('ordererIndex', orderer_index);
						});

					}
					comisiones_ordenadas_count--;
					btn.parents(class_li_comision).attr('data-orderer-index', info.prioridad_cantidad_maxima);
					btn.parents(class_li_comision).data('ordererIndex', info.prioridad_cantidad_maxima);

				} else {
					if (!validar_seleccion_prioridades(btn, class_li_comision)) {
						return;
					}
					btn.parents(class_li_comision).addClass(clase_comision_activa_prioridad);
					btn.parents(class_li_comision).removeClass(clase_comision_inactiva_prioridad);
					btn.addClass('btn-success');
					btn.text(btn.data('toggle-texto-activado'));
					btn.data(key_estado, true);
					// Oculto el "Código inscripción"
					btn.parents('.acciones').find(".control-group").hide();

					comisiones_ordenadas_count++;
					btn.parents(class_li_comision).attr('data-orderer-index', comisiones_ordenadas_count);
					btn.parents(class_li_comision).data('ordererIndex', comisiones_ordenadas_count);

				}

				if (get_count_agregados_insc_prioridad() == 0) {
					$(id + " ." + clase_btn_ordenar_prioridad).addClass('disabled');
				} else {
					$(id + " ." + clase_btn_ordenar_prioridad).removeClass('disabled');
				}
			});
		}
	}

	function registrar_eventos()
	{
		registrar_eventos_inscripcion_prioridad();
		registrar_eventos_comisiones();
		registrar_eventos_info_catedras();
		registrar_loader_comprobante();
		registrar_eventos_inscripto();
		registrar_eventos_baja_inicial();
                registrar_eventos_filtros();
	}

	function cargar_materia(params)
	{
		kernel.ajax.load(params.href, id, {
			success: function (paquete)
			{
				$('.titulo_operacion .notificaciones').remove();
				kernel.evts.disparar('info_materia_cargada', params);
				reload_pagelet();
				info.msg_confirma_desea_inscribirse_a_cursar = paquete.msg_confirma_desea_inscribirse_a_cursar;
				comisiones_ordenadas_count = -1;
			}
		});
	}

	function registrar_listeners()
	{
		var id_evento = 'seleccion_materia';
		kernel.evts.escuchar(id_evento, cargar_materia);
	}

	function registrar_eventos_inscripto()
	{
		$(id + ' .form_baja').submit(function ()
		{
			var $form = $(this);
			var url = $form.attr('action');

			submit_baja(url, $form);
			return false;
		});
	}

	function toggle_msg_comprobante(placeholder)
	{
		var original_msg = placeholder.find('a').html();
		var nuevo_msg = placeholder.data('msg-toggle');
		placeholder.find('a:first').html(nuevo_msg);
		placeholder.data('msg-toggle', original_msg);
	}

	function registrar_loader_comprobante()
	{
		$(id).on('click', '.comprobante-cursada', function ()
		{
			var placeholder_comprobante = $(this);
			var url_img = placeholder_comprobante.data('img');
			if (!placeholder_comprobante.data('loaded')) {
				kernel.ui.show_loading(placeholder_comprobante, 'small', {
					my: 'center',
					at: 'right center',
					offset: '10 0',
					of: placeholder_comprobante
				});
				$('<img />').attr('src', url_img).load(function ()
				{
					$(this).appendTo(placeholder_comprobante.find('.img-placeholder'));
					kernel.ui.hide_loading();
					toggle_msg_comprobante(placeholder_comprobante);
					placeholder_comprobante.find('.img-placeholder').toggle();
					placeholder_comprobante.data('loaded', true);
				});
			} else {
				toggle_msg_comprobante(placeholder_comprobante);
				placeholder_comprobante.find('.img-placeholder').toggle();
			}

			return false;
		});

		//Ver: http://www.todoexpertos.com/categorias/tecnologia-e-internet/desarrollo-de-sitios-web/respuestas/2214856/boton-imprimir
		$(id).on('click', '.js-print-comprobante', function ()
		{
			var ventana_impresion = window.open($(this).data('img'), '_blank', 'menubar=0,scrollbars=0,location=0');
			ventana_impresion.document.title = 'Imprimir comprobante de cursada';
			ventana_impresion.print();
			return false;
		});

		$(id).on('click', '.js-mail-comprobante', function ()
		{
			var href = $(this).attr('href');
			var fn_ok = function ()
			{
				kernel.ajax.call(href, {
					type: 'get',
					success: function ()
					{
						kernel.ui.show_mensaje(info.envio_mail_exitoso);
					}
				});
			};

			kernel.ui.prompt({
				title: info.msg_titulo_enviar,
				msg: (info.falta_mail) ? info.msg_falta_mail : info.msg_enviar,
				msg_ok: info.msg_enviar_boton,
				ok: (info.falta_mail) ? false : fn_ok
			});

			return false;
		});
	}

	function submit_baja(url, $form)
	{
		kernel.ui.prompt({
			msg: info.msg_baja,
			msg_ok: info.msg_confirmar_baja,
			msg_cancel: info.cancelar,
			ok: function ()
			{
				kernel.ajax.load(url, id, {
					historia: false,
					type: 'POST',
					data: $form.serializeArray(),
					success: function (response)
					{
						kernel.evts.disparar('info_materia_baja', {
							cant_inscripciones: $(id).find('.comision.inscripto').length
						});
						kernel.ui.show_mensaje(response.mensaje_cert_baja, {
							until_interaction: true
						});
						reload_pagelet();
					},
					error: function (paquete)
					{
						$(id).find('[name=__csrf]').val(paquete.csrf_token);
					}
				});
			}
		});
	}

	function registrar_eventos_baja_inicial()
	{
		$(id).find('.js-baja-inicial').on("click", function ()
		{
			var materia = $(this).data('materia');
			var inscripcion = $(this).data('inscripcion');
			kernel.ui.prompt({
				msg: info.msg_baja,
				msg_ok: info.msg_confirmar_baja,
				msg_cancel: info.cancelar,
				ok: function ()
				{
					kernel.ajax.load(info.url_baja_inicial, id, {
						historia: false,
						type: 'POST',
						data: {
							'__csrf': $(id).find('.js-csrf').data('csrf'),
							'materia': materia,
							'inscripcion': inscripcion
						},
						success: function (response)
						{
							var inscripciones = $(id).find('.js-mis-inscripciones button').filter(function ()
							{
								return $(this).data("materia") == materia
							}).length;
							// cuando se ejecuta el onload se registra el listener de nuevo
							kernel.evts.disparar('info_materia_baja', {
								cant_inscripciones: inscripciones,
								materia: materia
							});
							kernel.ui.show_mensaje(response.mensaje_cert_baja, {
								until_interaction: true
							});
							reload_pagelet();
						},
						error: function (paquete)
						{
							$(id).find('.js-csrf').data('csrf', paquete.csrf_token);
						}
					});
				}
			});
		});
	}

	function reload_pagelet()
	{
		kernel.evts.dejar_de_escuchar('seleccion_materia', cargar_materia);
		$(id).off();
		kernel.renderer.pagelet('info_materia').onload();
	}

	return {
		onload: function ()
		{
		
			registrar_listeners();
			filtro = new util.filtro();
			registrar_eventos();
			if (info.estado != 'inicial') {
				var params = {
					href: window.location.pathname + window.location.search
				}
				kernel.evts.disparar('info_materia_cargada', params);
			}
		}

	}
})