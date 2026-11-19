/**
 * Requiere que los renglones tengan id #renglon_[renglon_nro]
 * los campos de fecha deben ser input[type=date]
 * el formulario debe tener clase form-renglones
 * cuando hay un error en un renglón se agrega la clase error
 */

(function ( $ ) {
	var settings = {
		nav_class: '.nav',
		validadores: {},
		highlight_renglon: false,
		mensaje_submit_con_error: 'No se puede guardar ya que hay errores',
		datepicker_min_date: null,
		datepicker_max_date: null,
                mostrar_prompt_perdera_cambios: false,
                msg_prompt_perdera_cambios: 'Al realizar esta acción perderá los cambios que ha realizado, ¿desea continuar?',
                msg_ok_prompt_perdera_cambios: 'Aceptar',
                msg_cancel_prompt_perdera_cambios: 'Cancelar',
                guardar_cambio_pagina: true,
		success: function() {
			
		},
		error: function() {
			
		},
		autocomplete: {
			url: '',
			mensaje_vacio: '',
			min_letras_busqueda: $("#js-ui-autocomplete-min-length").val()
		}
	};
	
	var clase_form = 'form-renglones';
	var clase_error = 'error';
	var clase_alerta = 'alerta';
	var pagelet;

	var error_desc = $('<div>').addClass('info-msg').hide().on("click", function() {
		$(this).hide();
	}).appendTo($('body'));
	
	function get_tipo_campo(elem) {
		return elem.data('tipo');
	}
	
	/**
	 * clase_input es la clase del elemento que disparó el evento
	 */
	function move_row(renglon_actual, direccion, tipo_campo) {
		var renglon_nuevo;
		if (direccion == 'next') {
			renglon_nuevo = renglon_actual.next();
		} else {
			renglon_nuevo = renglon_actual.prev();
		}
		
		if (renglon_nuevo.length > 0) {
			renglon_nuevo.find("[data-tipo='" + tipo_campo + "']").select().focus();
		}
	}
	
	function bind_mov_listeners() {
		pagelet.on('keydown', settings.nav_class, function(e) {
			var input = $(this);
			var parent = input.parents('tr');
			var tipo  = get_tipo_campo(input);
			
			if (e.keyCode == 13) { // enter
				move_row(parent, 'next', tipo);
				e.preventDefault();
			}

			if (e.keyCode == 38) { // up
				move_row(parent, 'prev', tipo);
			}

			if (e.keyCode == 40) { // down
				move_row(parent, 'next', tipo);
			}
		});
		
		pagelet.on('focus', 'tr', function() {
			$(this).addClass('selected');
		});

		pagelet.on('blur', 'tr', function(e) {
			$(this).removeClass('selected');
		});
	}
	
	/**
	 * puede recibir un elemento tr que es el renglón o un nro
	 */
	function highlight_renglon(renglon) {
		var row = (isNaN(renglon)) ? renglon : $("#renglon_" + renglon);
		
		// se agrega este flag xq el animate hay que agregarselo a dos elementos
		// por compatibilidad crossbrowser
		var completed = false;
		$('html, body').animate({
			scrollTop: row.offset().top
		}, {
			duration: 200,
			complete: function() {
				if (completed) {
					return;
				}
				row.find('td:eq(0)').addClass('resultado-busqueda');
				row.find('[data-tipo=nota]').focus();
				completed = true;
			}
		});
	}
	
	function init_fechas() {
		pagelet.find("[data-tipo='fecha']").datepicker({
			onSelect: function() {
				validar($(this));
			},
                        // Agrego fecha máxima y mínima al datepicker (https://api.jqueryui.com/datepicker/#option-maxDate)
                        minDate: settings.datepicker_min_date,
                        maxDate: settings.datepicker_max_date
		});
	}
	
	function hay_error_validacion() {
		var row = pagelet.find('.' + clase_error);
		var hay_error = row.length > 0;
		if (hay_error) {
			kernel.ui.show_mensaje(settings.mensaje_submit_con_error, {tipo: 'alert-error'});
			highlight_renglon(row);
		}
		
		return hay_error;
	}
	
	function guardar($form, data) {
		return kernel.ajax.call($form.attr('action'), {
			type: 'post',
			data: data,
			historia: false,
			success: function(paquete) {
				// en paquete.cont viene el pagelet entero, hay que agregar
				// sólo el contenido
				pagelet.empty().html($(paquete.cont).children());
				inicializar();
				validar_renglones();
				var e = pagelet.find('.error-renglon:first');
				if (e.length == 0) {
					settings.success();
				} else {
					settings.error();
				}
			},
			error: function(paquete) {
				kernel.ui.modal({
					mensaje: paquete.cont,
					tipo: 'alert-error'
				});
			}
		});
	}
        
        function guardar_y_cambiar_pagina($form, data, url) {
            
            // Si se debe guardar antes de cambiar de página
            if (settings.guardar_cambio_pagina) {
                if (hay_error_validacion()) {
                    return false;
                }
                var promise = guardar($form, data);
                promise.done(function (param) {
                    if (pagelet.find('.error-renglon').length == 0) {
                        guarani.cambiar_op({
                            href: url
                        });
                    }
                });
            } else {// Si no se debe guardar antes de cambiar de página
                guarani.cambiar_op({
                    href: url
                });
            }
        }
	
	function setup_autocomplete() {
            
		if (settings.autocomplete.url == '') {
			return;
		}
                
		$('#notas_cursada_query').autocomplete({
			source: settings.autocomplete.url,
			minLength: settings.autocomplete.min_letras_busqueda,
			search: function() {
				kernel.ui.show_loading();
			},
			open: function() {
			},
			response: function(event, ui) {
				kernel.ui.hide_loading();
				if (ui.content.length === 0) {
					kernel.ui.show_mensaje(settings.autocomplete.mensaje_vacio, {tipo: 'alert-error'});
				}
			},
			select: function(event, ui) {
				var $form = pagelet.find('.form-renglones');
				var data = $form.serializeArray();
                                
                                // Si hay que avisar que perderá los cambios
                                if (settings.mostrar_prompt_perdera_cambios) {
                                    
                                    // Si hay cambios muestro el prompt
                                    if (sessionStorage.hay_cambios == 'true') {
                                        kernel.ui.prompt({
                                            msg: settings.msg_prompt_perdera_cambios,
                                            msg_ok: settings.msg_ok_prompt_perdera_cambios,
                                            msg_cancel: settings.msg_cancel_prompt_perdera_cambios,
                                            ok: function ()
                                            {
                                                guardar_y_cambiar_pagina($form, data, ui.item.id);
                                            }
                                        });
                                    } else {// Si no hay cambios no muestro el prompt
                                        guardar_y_cambiar_pagina($form, data, ui.item.id);
                                    }
                                    
                                } else {// Si no hay que avisar que perderá los cambios
                                    guardar_y_cambiar_pagina($form, data, ui.item.id);
                                }
                                
			}
		});

		$('#notas_cursada_query').keypress(function(event){
			if (event.keyCode == 13) {
				event.preventDefault();
			}
		});
	}
	
	function inicializar() {
		init_paginador();
		init_ficha();
		init_fechas();
		pagelet.find('.error-renglon').tooltip({container: 'body'});
		setup_autocomplete();
	}
	
	
	function registrar_evento_form() {
		pagelet.on('submit', '.' + clase_form, function() {
			var $form = $(this);
			var data  = $form.serializeArray();
			
			if (hay_error_validacion()) {
				return false;
			}
			
			guardar($form, data);
			return false;
		});
	}
	
	function validar_renglones() {
		pagelet.find('tbody tr').each(function() {
			var row = $(this);
			$(settings.validadores).each(function(i, validador) {
				if (validador.tipo == 'inicial') {
					validador.validar(row);
					return false;
				}
				return true;
			});
		});
	}
	
	function validar(elem, event) {
		var row = elem.parents('tr');
		var tipo = elem.data('tipo');

		$(settings.validadores).each(function(i, validador) {
			if ($.inArray(tipo, validador.tipo) > -1) {
				if (event) {
					validador.validar(row, tipo, event);
				}
				
				validador.validar(row, tipo);
				return false; // break the loop
			}

			return true;
		}); 
	}

	function bind_error_listeners() {
		pagelet.on('keyup blur', 'input', function(event) {
			validar($(this), event);
		});
		pagelet.on('change', 'select', function() {
			validar($(this));
		});
	}
	
	function init_paginador() {
		pagelet.on('click', '.pager_link', function() {
			var $form = pagelet.find('.form-renglones');
			var data = $form.serializeArray();
                        var link = $(this).prop('href');
                        
                        // Si hay que avisar que perderá los cambios
                        if (settings.mostrar_prompt_perdera_cambios) {

                            // Si hay cambios muestro el prompt
                            if (sessionStorage.hay_cambios == 'true') {
                                kernel.ui.prompt({
                                    msg: settings.msg_prompt_perdera_cambios,
                                    msg_ok: settings.msg_ok_prompt_perdera_cambios,
                                    msg_cancel: settings.msg_cancel_prompt_perdera_cambios,
                                    ok: function ()
                                    {
                                        guardar_y_cambiar_pagina($form, data, link);
                                    }
                                });
                            } else {// Si no hay cambios no muestro el prompt
                                guardar_y_cambiar_pagina($form, data, link);
                            }

                        } else {// Si no hay que avisar que perderá los cambios
                            guardar_y_cambiar_pagina($form, data, link);
                        }
			return false;
		});
	}
	
	function init_ficha() {
		pagelet.find('.ficha-alumno').on("click", function() {
			var url = $(this).data('url-ficha');
			kernel.ui.pagelet_modal(url);			
		});
	}
	
	var methods = {
		init: function ( options ) {
			if ( options ) {
				var aux = {};
				$.extend(true, aux, settings, options);
				settings = aux;
			}

			pagelet = this;
			pagelet.find('[data-tipo=nota]:first').focus();
			registrar_evento_form();
			inicializar();
			bind_mov_listeners();
			bind_error_listeners();
			if (settings.highlight_renglon && settings.highlight_renglon !== false) {
				highlight_renglon(settings.highlight_renglon);
			}
			validar_renglones();
		},
		
		set_error: function (element, mensaje) {
			if (element.hasClass(clase_error)) {
				return;
			}
			element.attr('data-original-title', mensaje);
			element.tooltip({container: 'body'});
			element.tooltip('enable');
			element.tooltip('show');
			element.addClass(clase_error);
		},
		
		remove_error: function (element) {
			if (! element.hasClass(clase_error)) {
				return;
			}

			element.tooltip('hide');
			element.tooltip('disable');
			element.removeClass(clase_error);
		},

                set_alerta: function (element, mensaje) {
                    if (element.hasClass(clase_alerta)) {
                        return;
                    }
                    element.attr('data-original-title', mensaje);
                    element.tooltip({container: 'body'});
                    element.tooltip('enable');
                    element.tooltip('show');
                    element.addClass(clase_alerta);
                },

                remove_alerta: function (element) {
                    if (! element.hasClass(clase_alerta)) {
                        return;
                    }

                    element.tooltip('hide');
                    element.tooltip('disable');
                    element.removeClass(clase_alerta);
                }
	};
	
	$.fn.plugin_renglones = function ( method ) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return  methods.init.apply(this, arguments);
		} else {
			$.on("error", 'Method ' + method + ' doesnt exist on jQuery.upload');
		}
	};
}) (jQuery);