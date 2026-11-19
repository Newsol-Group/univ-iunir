/**
 */

(function ( $ ) {
	var settings = {
	};
	
	var pagelet, formulario;
	
	var fn_guardar = function() {
		/**
		 * formulario_hubo_cambios() -> en preinscripcion.js
		 */
		var hubo_cambio = formulario_hubo_cambios();
		if (hubo_cambio) {
			// Si se realizaron cambios, se ejecuta el mecanismo de autosave
			kernel.ajax.call(formulario.attr('action'), {
				type: 'POST',
				async: false,
				data: formulario.serializeArray(),
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
						});
						preinscripcion.set_enable_cambiar_op(false);
						pagelet.replaceWith(response.cont);
						kernel.evts.dejar_de_escuchar('beforeCambiarOp', fn_guardar);
						kernel.renderer.pagelet(pagelet.attr('id')).onload();
					}
				}
			})
		}
	}
	
	var methods = {
		init: function ( options ) {
			if ( options ) {
				var aux = {};
				$.extend(true, aux, settings, options);
				settings = aux;
			}
			// this elemento sobre el que se aplica
			pagelet = this;
//			formulario = this.find('js-autosave-form');
			formulario = this.find('form');
			
			kernel.evts.escuchar('beforeCambiarOp', fn_guardar);
		},
		disable: function () {
			formulario.remove();
			kernel.evts.dejar_de_escuchar('beforeCambiarOp', fn_guardar);
		}
	}
	
	$.fn.autosave = function ( method ) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return  methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' doesnt exist on jQuery.upload');
		}
	}
}) (jQuery);