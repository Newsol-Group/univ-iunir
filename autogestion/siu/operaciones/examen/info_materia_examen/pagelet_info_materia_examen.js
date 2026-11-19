kernel.renderer.registrar_pagelet('info_materia_examen', function(info) {
	var id = '#' + info.id;
	var cupo = parseFloat($("#cupo").attr('cupo'));

	function registrar_listeners() {
        kernel.evts.escuchar('seleccion_materia', cargar_materia);
    }
	function cargar_materia(params) {        
		kernel.ajax.load(params.href, id, {
			success: function() {
                registrar_eventos();
				kernel.evts.disparar('info_materia_cargada', params);
				reload_pagelet();
			}
		});
    }
	
	function registrar_eventos() {
		registrar_eventos_seleccion_instancia();
		registrar_eventos_inscripcion();
		registrar_eventos_baja();
		registrar_eventos_baja_inicial();
		registrar_loader_comprobante();
	}
	
	function registrar_eventos_seleccion_instancia() {
		$(id).on('change', '.instancias', function() {
			if ($(this).val() != ""){
				$('#btn_'+$(this).prop('id')).prop('disabled', false);
			} else {
				$('#btn_'+$(this).prop('id')).prop('disabled', true);
			}
			$(this).siblings('[name=tipo_insc]').val($(this).val());
		});
		
	}
	
	function registrar_eventos_inscripcion() {
		$(id).on('click', '.js-regular', function() {
			$(this).siblings('[name=tipo_insc]').val('R');
		});
		
		$(id).on('click', '.js-libre', function() {
			$(this).siblings('[name=tipo_insc]').val('L');
		});
		
		$(id).on('submit', '.form_inscribir', function() {
			if(cupo != 100){
				var url = $(this).attr('action');
				kernel.ajax.load(url, id, {
					historia: false,
					type: 'POST',
					data: $(this).serializeArray(),
					success: function(response) {
						kernel.evts.disparar('info_examen_inscripto', []);
						reload_pagelet();
						kernel.ui.show_mensaje(response.mensaje_inscripcion, {
							until_interaction: true
						});
					},
					error: function(paquete) {
						$(id).find('[name=__csrf]').val(paquete.csrf_token);
					}
				});
			}
			return false;
		});
	}
	
	function registrar_eventos_baja() {
		$(id).on('submit', '.form_baja', function() {
			var form = $(this);
			kernel.ui.prompt({
				msg: info.msg_baja,
				msg_ok: info.msg_confirmar_baja,
				msg_cancel: 'Cancelar',
				ok: function() {
					var url = form.attr('action');
					kernel.ajax.load(url, id, {
						historia: false,
						type: 'POST',
						data: form.serializeArray(),
						success: function(response) {
							kernel.evts.disparar('info_examen_baja', {
								cant_inscripciones: $(id).find('.llamado_mesa.inscripto').length
							});
							kernel.ui.show_mensaje(response.mensaje_cert_baja, {
							until_interaction: true
							});
							reload_pagelet();
						},
						error: function(response) {
							$(id).find('[name=__csrf]').val(response.csrf_token);
						}
					});
					return false;				
				}
			});
			return false;
		});
	}
	
	function registrar_eventos_baja_inicial() {
		$(id).find('.js-baja-inicial').on("click", function() {
			var actividad = $(this).data('actividad');
			var inscripcion = $(this).data('inscripcion');
			kernel.ui.prompt({
				msg: info.msg_baja,
				msg_ok: info.msg_confirmar_baja,
				msg_cancel: 'Cancelar',
				ok: function() {
					kernel.ajax.load(info.url_baja_inicial, id, {
						historia: false,
						type: 'POST',
						data: {
							'__csrf': $(id).find('.js-csrf').data('csrf'),
							'actividad': actividad,
							'inscripcion': inscripcion
						},
						success: function(response) {
							var inscripciones = $(id).find('.js-mis-inscripciones button').filter(function() {
								return $(this).data("actividad") == actividad 
							}).length;
							// cuando se ejecuta el onload se registra el listener de nuevo
							kernel.evts.disparar('info_examen_baja', {
								cant_inscripciones: inscripciones,
								materia: actividad
							});
							
							kernel.ui.show_mensaje(response.mensaje_cert_baja, {
							until_interaction: true
							});
							reload_pagelet();
						},
						error: function(paquete) {
							$(id).find('.js-csrf').data('csrf', paquete.csrf_token);
						}
					});
				}
			});
		});
	}
	
	function reload_pagelet() {
		kernel.evts.dejar_de_escuchar('seleccion_materia', cargar_materia);
		$(id).off();
		kernel.renderer.pagelet('info_materia_examen').onload();		
	}
	
	function toggle_msg_comprobante(placeholder) {
		var original_msg = placeholder.find('a').html();
		var nuevo_msg = placeholder.data('msg-toggle');
		placeholder.find('a:first').html(nuevo_msg);
		placeholder.data('msg-toggle', original_msg);
	}
	
	function registrar_loader_comprobante() {
		$(id).on('click', '.comprobante-examen', function() {
			var placeholder_comprobante = $(this);
			var url_img = placeholder_comprobante.data('img');
			if (! placeholder_comprobante.data('loaded')) {
				kernel.ui.show_loading(placeholder_comprobante, 'small', {
					my: 'center',
					at: 'right center',
					offset: '10 0',
					of: placeholder_comprobante
				});
				$('<img />').attr('src', url_img).load(function() {
					$(this).appendTo(placeholder_comprobante.find('.img-placeholder'));
					kernel.ui.hide_loading();
					toggle_msg_comprobante(placeholder_comprobante);
					placeholder_comprobante.find('.img-placeholder').toggle();
					placeholder_comprobante .data('loaded', true);
				});
			} else {
				toggle_msg_comprobante(placeholder_comprobante);
				placeholder_comprobante.find('.img-placeholder').toggle();
			}

			return false;
		});

			//Ver: http://www.todoexpertos.com/categorias/tecnologia-e-internet/desarrollo-de-sitios-web/respuestas/2214856/boton-imprimir
			$(id).on('click', '.js-print-comprobante', function() {
				var ventana_impresion = window.open( $(this).data('img'), '_blank', 'menubar=0,scrollbars=0,location=0');
				ventana_impresion.document.title = 'Imprimir comprobante de examen';
				ventana_impresion.print();
				return false;
			});
	
		$(id).on('click', '.js-mail-comprobante', function() {
			var href = $(this).attr('href');
			var fn_ok = function() {
				kernel.ajax.call(href, {
					type: 'get',
					success: function() {
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
	
    return {
        onload: function() {
			registrar_listeners();
			registrar_eventos();
			if(cupo == 100){
				$("#noform").show();
				$("#form").hide();
			}else{
				$("#noform").hide();
				$("#form").show();
			}
			if(cupo >= 90){
				$('#mess').removeClass('yellow');
				$('#mess').removeClass('blue');
				$('#mess').addClass('red');
			}else if(cupo <90 && cupo >60 ){
				$('#mess').removeClass('blue');
				$('#mess').removeClass('red');
				$('#mess').addClass('yellow');
			}else{
				$('#mess').removeClass('yellow');
				$('#mess').removeClass('red');
				$('#mess').addClass('blue');
			}
			if (info.estado != 'inicial') {
				var params = {
					href: window.location.pathname + window.location.search
				}
				kernel.evts.disparar('info_materia_cargada', params);
			}
        }
		
	}

})