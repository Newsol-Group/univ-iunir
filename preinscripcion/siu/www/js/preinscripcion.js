var no_seleccion = 'null';

/*
 * Ordena las opciones de un combo. Necesario cuando se carga vía AJAX.
 */
function ordenar_combo(idSelect)
{
	var opcion_no_seleccion = $('#' + idSelect + " option[value='" + no_seleccion + "']");
	var ops = $('#' + idSelect + " option:not(option[value='null'])");

	// Se comparan todos los elementos del array
	ops.sort(function (a, b)
	{
		return ($(a).html().toUpperCase() < $(b).html().toUpperCase()) ? -1 : ($(a).html().toUpperCase() > $(b).html().toUpperCase()) ? 1 : 0;
	});

	// Primero se agrega la opción de no selección
	var html;
	if (opcion_no_seleccion.length > 0) {
		html = "<option value='" + $(opcion_no_seleccion[0]).val() + "'>" + $(opcion_no_seleccion[0]).html() + "</option>";
	} else {
		html = '';
	}

	// Se agregan las opciones ordenadas por descripción.
	for (var i = 0; i < ops.length; i++) {
		html += "<option value='" + $(ops[i]).val() + "'>" + $(ops[i]).html() + "</option>";
	}

	// Se insertan las opciones en el select
	$('#' + idSelect).html(html);
}

function cloneObject(source_)
{
	for (var item in source_) {
		if (typeof source_[item] === 'object') {
			this[item] = new cloneObject(source_[item]);
		} else {
			this[item] = source_[item];
		}
	}
}

function toggle(idElem)
{
	$('#' + idElem).slideToggle(200);
}

function ocultar(elemId, resetear)
{
	$('#' + elemId).hide();
	if (resetear) {
		resetDiv(elemId);
	}
}

function mostrar(elemId)
{
	$('#' + elemId).show();
}

function set_solo_lectura(elemId, solo_lectura)
{
	if (solo_lectura) {
		$('#' + elemId).attr('readonly', 'readonly');
	} else {
		$('#' + elemId).removeAttr("readonly");
	}
}

function resetDiv(divId)
{
	// Se recorren todos los campos del formulario contenidos en el div
	$(':input', '#' + divId).each(function ()
	{
		var type = this.type;
		var tag = this.tagName.toLowerCase();
		// Se limpian los valores de los campos...
		if (type === 'text' || type === 'password' || tag === 'textarea')
			this.value = '';
		else if (type === 'checkbox' || type === 'radio')
			// Para checkboxes y radios se le quita el checked
			this.checked = false;
		else if (tag === 'select')
			// Para los selects -> no_seleccion
			this.value = no_seleccion;
	});
}

//------------------------------------------------------------------------------
//---- Control de cambios en formularios ---------------------------------------
//------------------------------------------------------------------------------

function formulario_hubo_cambios()
{
	var hubo_cambio = false;
	$('.js-editable').each(function() {
		var valor_actual;

		if (this.tagName.toLowerCase() === 'select') {
			// Se examinan los selects
			valor_actual = $(this).val();
			if ((valor_actual === 'null') || (valor_actual === null)) {
				valor_actual = '';
			}
		} else {
			// Se examina el resto de los inputs
			switch ($(this).prop('type')) {
				case 'checkbox':
					valor_actual = ($(this).is(':checked')) ? 'S' : 'N';
					break;
				case 'radio':
                                        valor_actual = $('[name="'+$(this).prop('name')+'"]:checked').val();
					break;
				default:
					valor_actual = $(this).val();
			}
		}

		if ((!$(this).hasClass('select-pais')) && ($(this).data('prev-value') != valor_actual)) {
			hubo_cambio = true;
		}

	});

	return hubo_cambio;
}

function handle_combo_pais_localidad(campo_pais, campo_id)
{
	if (($('#' + campo_id).val() === 'null') || ($('#' + campo_id).val() === '')) {
		$('#' + campo_pais).show();
		$('#' + campo_pais).val('54');
	} else {
		$('#' + campo_pais).hide();
	}
}

//------------------------------------------------------------------------------

var preinscripcion = function ()
{
	var ajax_nav_enabled = true;

	/*function init_perfiles()
	{
		$('#select_perfil li').on('click', function ()
		{
			var perfil = $(this).data('perfil');
			window.location.replace(url_cambio_perfil + "?id=" + perfil);
		});
	}*/

	/*function init_carreras_phone()
	{
		$('#select_carreras li').on('click', function ()
		{
			var carrera = $(this).data('carrera-id');
			window.location.replace(url_cambio_carrera + "?id=" + carrera);
		});
	}*/

	/*function init_carreras()
	{
		if ($('#select_carreras').length == 0) {
			return;
		}

		$('#select_carreras').change(function ()
		{
			var carrera = $(this).val();
			window.location.replace(url_cambio_carrera + "?id=" + carrera + "&op=" + kernel_config.op_actual);
		});
	}*/

	function init_links()
	{
		$('body').on('click', 'a', function ()
		{
			var cambiar = !$(this).hasClass('no-ajax') && this.href !== '' && !this.href.match(/#$/);
			if (cambiar) {
				var params = {href: this.href, cambia_op: true};
				preinscripcion.cambiar_op(params);
				return false;
			}
		});

		$('body').on('click', '.link-in-data', function (e)
		{
			if ($(e.target).is('a')) { // si apretÃ³ en un link lo dejamos pasar
				return true;
			}

			var url = $(this).data('link');
			var params = {href: url, cambia_op: true};
			preinscripcion.cambiar_op(params);
			return false;
		});
	}

	function listener_global(paquete)
	{
		if (paquete.hay_datos_personales) {
			hay_datos_personales = true;
		}

		if (paquete.hay_datos_beca) {
			hay_datos_beca = true;
		}
	}

	function controlar_operacion(url)
	{
		var split = url.split('/');
		var op = split[split.length - 1];
		if (op === 'becas' && !hay_datos_personales) {
			kernel.ui.modal({
				mensaje: mensaje_error_beca,
				tipo: 'error'
			})
			return false;
		}

		if (op === 'condiciones' && !hay_datos_beca) {
			kernel.ui.modal({
				mensaje: mensaje_error_condiciones,
				tipo: 'error'
			})
			return false;
		}

		return true;
	}

	var enable_cambio_op = true;

	return {
		set_ajax_nav: function (value)
		{
			ajax_nav_enabled = value;
		},
		set_enable_cambiar_op: function (value)
		{
			enable_cambio_op = value;
		},
		cambiar_op: function (params)
		{
			preinscripcion.set_enable_cambiar_op(true);
			if (!ajax_nav_enabled) {
				window.location.href = params.href;
				return false;
			}
			if (!kernel_config.historia_activa) {
				return false;
			}

			if (!controlar_operacion(params.href)) {
				return false;
			}

			kernel.evts.disparar('beforeCambiarOp', {});

			if (!enable_cambio_op) {
				return;
			}

			var id = '#kernel_contenido';
			var href = params.href;
			var historia = (params.historia || params.historia === false) ? params.historia : true;
			var show_loading = (params.show_loading || params.show_loading === false) ? params.show_loading : true;

			// hack para esconder el menu
			$('.dropdown').removeClass('open');
			$('.nav-collapse').removeClass('in').css('height', 0);


			kernel.ajax.load(href, id, {
				success: function (paquete)
				{
					if (paquete.operacion) {
						var menu_item = $('#menu_' + paquete.operacion);
						if (!menu_item.hasClass('js-menuitem-root')) {
							menu_item = menu_item.parents('.js-menuitem-root');
						}
						$('#js-nav').find('.js-menuitem-root').removeClass('menu-item-seleccionado');
						menu_item.addClass('menu-item-seleccionado');
					}
					kernel.evts.disparar('operacion_cambiada', {href: params.href});
				},
				historia: historia,
				forzar_cambio_op: true,
				show_loading: show_loading
			});
		},
		init: function ()
		{
			//init_perfiles();
			//init_carreras_phone();
			//init_carreras();
			init_links();
			$('body').on('click', function ()
			{
				$('.dropdown').removeClass('open');
			});
			$('body').on('click', '.js-btn-back', function ()
			{
				window.history.back();
			});

			kernel.ajax.set_listener_response(listener_global);
                        
                        //http://api.jquery.com/ajaxStop/
                        //Se dispara cuando se terminan todos los AJAXs
                        $(document).ajaxStop(function () {
                            setTimeout(function () {
                                $('[data-toggle="tooltip"]').tooltip();
                                $('[data-toggle="popover"]').popover();
                            }, 1000);
                        });
		}
	};
}();

$(document).ready(function ()
{
	preinscripcion.init();
});