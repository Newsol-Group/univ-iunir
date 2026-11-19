kernel.renderer.registrar_pagelet('renglones', function (info) {

	var id = '#' + info.id;
	var fecha_inicio, fecha_fin;

	function create_date(date_string) {
		var fecha_split = date_string.split('/');
		if (fecha_split.length != 3) {
			return false;
		}
		return new Date(fecha_split[2], fecha_split[1] - 1, fecha_split[0]);
	}

	//--------------------------------------------------------------------------
	//---- UTIL ----------------------------------------------------------------
	//--------------------------------------------------------------------------

	function get_escala(row) {
		var escala = $.trim(row.find('.escala_nota').val());
		return guarani.escalas().recuperar(escala);
	}

	function get_nota(row) {
		return $.trim(row.find('.nota').val());
	}

	function get_nota_anterior(row) {
		return $.trim(row.find('.nota').data('valor-anterior'));
	}

	function set_nota_anterior(row, nota) {
		return row.find('.nota').data('valor-anterior', nota);
	}

	function get_res(row) {
		return $.trim(row.find('.resultado').val());
	}

	function get_res_nota(row) {
		var nota = get_nota(row);
		if (nota === '') {
			return '';
		}
		return get_escala(row).get_resultado(nota);
	}

	function formatear_nota(row, event) {
		var escala = get_escala(row);
		if (escala.get_tipo() === 'continua') {
			var nota = get_nota(row);
			if (!event) {
				// si no viene el evento no se dispar� desde una entrada de input, no hay que formatear
				return;
			}

			var key = event.keyCode || event.which;
			key = String.fromCharCode(key);

			if (!/[0-9]|\.|,/.test(key) && event.type !== 'focusout') {
				// se formatea s�lo si se presion� alg�n nro, un pto o una coma
				return;
			}

			var nota_formateada = escala.formatear(nota);
			row.find('.nota').val(nota_formateada);
			set_nota_anterior(row, nota_formateada);
		}
	}

	/*
	 * Setea el resultado v�lido para la nota actual.
	 */
	function update_resultado_nota(row) {
		var res = get_res_nota(row);
		row.find('.resultado').val(res);
	}


	//--------------------------------------------------------------------------
	//---- CHEQUEO DE ERRORES --------------------------------------------------
	//--------------------------------------------------------------------------

	/*
	 * Una nota es v�lida si es vac�a o si existe en el mapeo de la escala.
	 */
	function chequeo_nota_valida(row) {
		var nota = get_nota(row);

		if (nota === '') {
			return true;
		} else {
			return get_escala(row).validar(nota);
		}
	}

	//--------------------------------------------------------------------------
	//---- VALIDACIONES --------------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_fecha(row) {
		var fecha = create_date(row.find('.fecha').val());
		if (row.find('.fecha').val() !== '' && (fecha === false || fecha.getTime() < fecha_inicio.getTime() || fecha_fin.getTime() < fecha.getTime())) {
			$(id).plugin_renglones('set_error', row.find('.fecha'), info.mensajes.fecha_invalida);
		} else {
			$(id).plugin_renglones('remove_error', row.find('.fecha'));
		}
		// Si luego de autocompletar se modifica alg�n campo, entonces se deshabilita el bot�n "Deshacer"
		$('#herramientas #preset_undo').prop('disabled', true);
	}

	function validar_nota(row, tipo, event) {
		if (!chequeo_nota_valida(row)) {
			$(id).plugin_renglones('set_error', row.find('.nota'), info.mensajes.nota_invalida);
		} else { // si es v�lido hay que setear el resultado 
			if (get_nota(row) !== '') { // s�lo formateo la nota si la misma no es vac�a
				formatear_nota(row, event);
			}
			update_resultado_nota(row);
			$(id).plugin_renglones('remove_error', row.find('.nota'));
		}
		// Si luego de autocompletar se modifica alg�n campo, entonces se deshabilita el bot�n "Deshacer"
		$('#herramientas #preset_undo').prop('disabled', true);
	}

	function validar_resultado(row, tipo, event) {

		const escala = $.trim(row.find('.escala_nota').val());
		const vNota = $.trim(row.find('.nota').val());

		if (escala == 105 && vNota == '' && row.find('.resultado').val() == 'A') {
			row.find('.nota').val('Aprobado');
		}

		if (row.find('.nota').val() !== '') {
			var resultado = get_escala(row).get_resultado(row.find('.nota').val());
			if (resultado !== row.find('.resultado').val()) {
				row.find('.nota').val('');
			}
		}
		// Si luego de autocompletar se modifica alg�n campo, entonces se deshabilita el bot�n "Deshacer"
		$('#herramientas #preset_undo').prop('disabled', true);
	}

	return {
		onload: function () {
			$(id).plugin_renglones({
				validadores: [{
					tipo: ['fecha'],
					validar: validar_fecha
				}
					, {
					tipo: ['nota'],
					validar: validar_nota
				}
					, {
					tipo: ['resultado'],
					validar: validar_resultado
				}
				],

				highlight_renglon: info.highlight_renglon,

				success: function () {
					kernel.ui.show_mensaje(info.mensajes.guardado_exitoso, { until_interaction: true });
				},
				error: function () {
					kernel.ui.show_mensaje(info.mensajes.guardado_error, { tipo: 'alert-error' });
				},

				autocomplete: {
					url: info.url_autocomplete,
					mensaje_vacio: info.mensajes.no_se_encontraron_alumnos
				}
			});

			fecha_inicio = create_date(info.fecha_inicio);
			fecha_fin = create_date(info.fecha_fin);

			$('.form-renglones').keypress(function (e) {
				if (e == 13) {
					return false;
				}
			});

			$('input').keypress(function (e) {
				if (e.which == 13) {
					return false;
				}
			});

			// $sel_resultado = $('.resultado');
			// $sel_resultado.on('change', function (e) {
			// });
		}
	}
})